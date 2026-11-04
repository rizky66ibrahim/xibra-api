<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\Goal;
use App\Services\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Finance endpoints used by the Telegram bot. Every call is scoped to the user
// resolved from X-Telegram-User-Id by the telegram.bot middleware.
class FinanceBridgeController extends Controller
{
    public function __construct(private FinanceLedger $ledger) {}

    // GET /finance/accounts
    public function accounts(Request $request)
    {
        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return ApiResponse::success(
            $accounts->map(fn(Account $a) => $this->presentAccount($a))->values(),
            'Accounts retrieved'
        );
    }

    // POST /finance/accounts
    // cash, bank, and ewallet hold money. investment holds units x unit_price, updated by hand.
    public function storeAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:cash,bank,ewallet,investment'],
            'provider' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Negative balances are allowed, for example credit cards and overdrafts
            'balance' => ['sometimes', 'numeric'],
            'asset_class' => ['required_if:type,investment', 'nullable', 'string', 'in:saham,emas,kripto,reksadana,lainnya'],
            'units' => ['required_if:type,investment', 'nullable', 'numeric', 'gt:0'],
            'unit_price' => ['required_if:type,investment', 'nullable', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $isInvestment = $data['type'] === Account::TYPE_INVESTMENT;

        $account = Account::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'provider' => $data['provider'] ?? null,
            'asset_class' => $isInvestment ? $data['asset_class'] : null,
            'units' => $isInvestment ? $data['units'] : null,
            'unit_price' => $isInvestment ? $data['unit_price'] : null,
            'price_updated_at' => $isInvestment ? now() : null,
            'balance' => $isInvestment
                ? round((float) $data['units'] * (float) $data['unit_price'], 2)
                : (float) ($data['balance'] ?? 0),
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        return ApiResponse::created($this->presentAccount($account), 'Account created');
    }

    // POST /finance/accounts/{account}/price: the investment value is units x the price you enter
    public function updatePrice(Request $request, Account $account)
    {
        if ($account->user_id !== $request->user()->id) {
            return ApiResponse::error('Account not found', null, 404);
        }

        if (! $account->isInvestment()) {
            return ApiResponse::error('Harga hanya bisa diperbarui untuk akun investasi.', null, 422);
        }

        $validator = Validator::make($request->all(), [
            'unit_price' => ['required', 'numeric', 'gt:0'],
            'units' => ['sometimes', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $units = (float) ($data['units'] ?? $account->units);
        $price = (float) $data['unit_price'];

        $account->forceFill([
            'units' => $units,
            'unit_price' => $price,
            'balance' => round($units * $price, 2),
            'price_updated_at' => now(),
        ])->save();

        return ApiResponse::success($this->presentAccount($account->fresh()), 'Price updated');
    }

    // GET /finance/categories?type=expense|income
    public function categories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['sometimes', 'string', 'in:income,expense'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $categories = FinanceCategory::where('user_id', $request->user()->id)
            ->where('type', $request->input('type', 'expense'))
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return ApiResponse::success($categories, 'Categories retrieved');
    }

    // GET /finance/summary?month=2026-11
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => ['sometimes', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $userId = $request->user()->id;
        $start = Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $totals = FinanceTransaction::where('user_id', $userId)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $income = (float) ($totals['income'] ?? 0);
        $expense = (float) ($totals['expense'] ?? 0);

        $cash = (float) Account::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', '!=', Account::TYPE_INVESTMENT)
            ->sum('balance');

        $investment = (float) Account::where('user_id', $userId)
            ->where('is_active', true)
            ->where('type', Account::TYPE_INVESTMENT)
            ->sum('balance');

        return ApiResponse::success([
            'month' => $start->format('Y-m'),
            'income' => round($income, 2),
            'expense' => round($expense, 2),
            'net' => round($income - $expense, 2),
            'cash_balance' => round($cash, 2),
            'investment_value' => round($investment, 2),
            // Everything together (cash and bank plus investments)
            'total_balance' => round($cash + $investment, 2),
        ], 'Finance summary retrieved');
    }

    // POST /finance/transactions
    public function storeTransaction(Request $request)
    {
        $userId = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['sometimes', 'date'],
            'account_id' => [
                'sometimes',
                'nullable',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $userId)
                    ->where(fn($query) => $query->where('type', '<>', 'investment')),
            ],
            'finance_category_id' => ['sometimes', 'nullable', Rule::exists('finance_categories', 'id')->where('user_id', $userId)],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();

        $transaction = DB::transaction(function () use ($userId, $data) {
            $transaction = FinanceTransaction::create(array_merge($data, [
                'user_id' => $userId,
                'currency' => 'IDR',
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            ]));

            $this->ledger->change(null, FinanceLedger::snapshot($transaction));

            return $transaction;
        });

        $account = $transaction->account_id ? Account::find($transaction->account_id) : null;

        return ApiResponse::created([
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'description' => $transaction->description,
            'account_name' => $account?->name,
            'balance_after' => $account ? (float) $account->fresh()->balance : null,
        ], 'Transaction recorded');
    }

    // GET /finance/budgets: spent is calculated from transactions, not stored
    public function budgets(Request $request)
    {
        $budgets = Budget::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $rows = $budgets->map(function (Budget $budget) use ($request) {
            [$from, $to] = $this->budgetRange($budget);

            $spent = $budget->finance_category_id
                ? (float) FinanceTransaction::where('user_id', $request->user()->id)
                    ->where('finance_category_id', $budget->finance_category_id)
                    ->where('type', 'expense')
                    ->whereBetween('transaction_date', [$from, $to])
                    ->sum('amount')
                : 0.0;

            $amount = (float) $budget->amount;

            return [
                'id' => $budget->id,
                'name' => $budget->name,
                'amount' => $amount,
                'spent' => round($spent, 2),
                'remaining' => round($amount - $spent, 2),
                'from' => $from,
                'to' => $to,
            ];
        });

        return ApiResponse::success($rows->values(), 'Budgets retrieved');
    }

    // GET /finance/goals
    public function goals(Request $request)
    {
        $goals = Goal::where('user_id', $request->user()->id)->orderBy('id')->get();

        return ApiResponse::success(
            $goals->map(fn(Goal $g) => $this->presentGoal($g))->values(),
            'Goals retrieved'
        );
    }

    // POST /finance/goals/{goal}/contributions: adds savings to a goal.
    // This does not change account balances; it only records progress on the goal.
    public function contribute(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return ApiResponse::error('Goal not found', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        // Atomic increment, so two contributions at the same time both count
        Goal::whereKey($goal->id)->increment('current_amount', (float) $validator->validated()['amount']);

        return ApiResponse::success($this->presentGoal($goal->fresh()), 'Contribution recorded');
    }

    // Period of a budget: explicit end date, or one month / one year from the start
    private function budgetRange(Budget $budget): array
    {
        $start = $budget->start_date->copy();

        $end = $budget->end_date
            ? $budget->end_date->copy()
            : ($budget->period === 'yearly'
                ? $start->copy()->addYear()->subDay()
                : $start->copy()->addMonth()->subDay());

        return [$start->toDateString(), $end->toDateString()];
    }

    private function presentAccount(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type,
            'provider' => $account->provider,
            'asset_class' => $account->asset_class,
            'balance' => (float) $account->balance,
            'units' => $account->units !== null ? (float) $account->units : null,
            'unit_price' => $account->unit_price !== null ? (float) $account->unit_price : null,
            'price_updated_at' => $account->price_updated_at?->toIso8601String(),
            'currency' => $account->currency,
        ];
    }

    private function presentGoal(Goal $goal): array
    {
        return [
            'id' => $goal->id,
            'title' => $goal->title,
            'emoji' => $goal->emoji,
            'current_amount' => (float) $goal->current_amount,
            'target_amount' => (float) $goal->target_amount,
        ];
    }
}
