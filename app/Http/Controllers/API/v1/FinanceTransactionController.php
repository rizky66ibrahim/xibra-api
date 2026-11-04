<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use App\Services\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FinanceTransactionController extends Controller
{
    public function __construct(private FinanceLedger $ledger) {}

    public function index(Request $request)
    {
        $transactions = FinanceTransaction::where('user_id', $request->user()->id)
            ->with('category')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('finance_category_id'), fn ($q) => $q->where('finance_category_id', $request->finance_category_id))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($transactions, 'Finance transactions retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules($request, creating: true));

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $transaction = DB::transaction(function () use ($request, $validator) {
            $transaction = FinanceTransaction::create(array_merge(
                $validator->validated(),
                ['user_id' => $request->user()->id]
            ));

            $this->ledger->change(null, FinanceLedger::snapshot($transaction));

            return $transaction;
        });

        return ApiResponse::created(
            new FinanceTransactionResource($transaction->load('category')),
            'Finance transaction created'
        );
    }

    public function show(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        return ApiResponse::success(
            new FinanceTransactionResource($transaction->load('category')),
            'Finance transaction retrieved'
        );
    }

    public function update(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), $this->rules($request, creating: false));

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        DB::transaction(function () use ($transaction, $validator) {
            $before = FinanceLedger::snapshot($transaction);

            $transaction->update($validator->validated());

            $this->ledger->change($before, FinanceLedger::snapshot($transaction->fresh()));
        });

        return ApiResponse::success(
            new FinanceTransactionResource($transaction->load('category')),
            'Finance transaction updated'
        );
    }

    public function destroy(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        DB::transaction(function () use ($transaction) {
            // Undo the balance effect first, then soft-delete the row
            $this->ledger->change(FinanceLedger::snapshot($transaction), null);
            $transaction->delete();
        });

        return ApiResponse::success(null, 'Finance transaction deleted');
    }

    private function rules(Request $request, bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';
        $userId = $request->user()->id;

        return [
            'type' => [$required, 'string', 'in:income,expense'],
            'amount' => [$required, 'numeric', 'gt:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => [$required, 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            // Transactions cannot be booked to investment accounts
            'account_id' => [
                'sometimes',
                'nullable',
                Rule::exists('accounts', 'id')
                    ->where('user_id', $userId)
                    ->where('type', '!=', 'investment'),
            ],
            'finance_category_id' => [
                'sometimes',
                'nullable',
                Rule::exists('finance_categories', 'id')->where('user_id', $userId),
            ],
        ];
    }
}
