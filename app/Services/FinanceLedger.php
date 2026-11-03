<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

// Keeps account balances in step with finance transactions.
// Income adds to the account, expense subtracts. Negative balances are allowed.
class FinanceLedger
{
    // Fields of a transaction that affect balances. Null means "no transaction".
    public static function snapshot(?object $transaction): ?array
    {
        if ($transaction === null) {
            return null;
        }

        return [
            'account_id' => $transaction->account_id,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
        ];
    }

    // Moves balances from the old state of a transaction to its new state.
    // create: change(null, new). delete: change(old, null). update: change(old, new).
    public function change(?array $old, ?array $new): void
    {
        $deltas = [];

        if ($old && $old['account_id']) {
            $deltas[$old['account_id']] = ($deltas[$old['account_id']] ?? 0.0) - $this->signed($old);
        }

        if ($new && $new['account_id']) {
            $deltas[$new['account_id']] = ($deltas[$new['account_id']] ?? 0.0) + $this->signed($new);
        }

        if ($deltas === []) {
            return;
        }

        // Lock accounts in a fixed order to avoid deadlocks between concurrent requests
        ksort($deltas);

        DB::transaction(function () use ($deltas) {
            foreach ($deltas as $accountId => $delta) {
                if (abs($delta) < 0.005) {
                    continue;
                }

                $account = Account::whereKey($accountId)->lockForUpdate()->first();
                if (! $account) {
                    continue;
                }

                $account->forceFill([
                    'balance' => round((float) $account->balance + $delta, 2),
                ])->save();
            }
        });
    }

    private function signed(array $side): float
    {
        return ($side['type'] === 'income' ? 1.0 : -1.0) * $side['amount'];
    }
}
