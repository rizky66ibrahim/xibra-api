<?php

namespace Tests\Feature\Api\v1;

use App\Models\Account;
use App\Models\Budget;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\Goal;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceBridgeTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';
    private const TG_ID = 555201;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_secret' => self::SECRET]);
    }

    private function owner(): User
    {
        $user = User::factory()->createOne();
        $user->forceFill(['is_active' => true])->save();

        TelegramAccount::create([
            'user_id' => $user->id,
            'telegram_user_id' => self::TG_ID,
            'telegram_username' => 'owner_tg',
            'linked_at' => now(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function bot(): array
    {
        return [
            'X-Telegram-Bot-Secret' => self::SECRET,
            'X-Telegram-User-Id' => (string) self::TG_ID,
        ];
    }

    private function account(User $user, string $name, float $balance = 0): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => 'bank',
            'balance' => $balance,
            'currency' => 'IDR',
            'is_active' => true,
        ]);
    }

    private function postTransaction(array $body)
    {
        return $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/finance/transactions', $body);
    }

    public function test_expense_decreases_account_balance(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'BCA', 100000);

        $this->postTransaction([
            'type' => 'expense',
            'amount' => 25000,
            'account_id' => $account->id,
        ])->assertCreated();

        $this->assertSame(75000.0, (float) $account->fresh()->balance);
    }

    public function test_income_increases_account_balance(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'Cash', 0);

        $this->postTransaction([
            'type' => 'income',
            'amount' => 500000,
            'account_id' => $account->id,
        ])->assertCreated()
            ->assertJsonPath('data.balance_after', 500000);
    }

    public function test_negative_balance_is_allowed(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'Kartu', 0);

        $this->postTransaction([
            'type' => 'expense',
            'amount' => 300000,
            'account_id' => $account->id,
        ])->assertCreated();

        $this->assertSame(-300000.0, (float) $account->fresh()->balance);
    }

    public function test_updating_amount_adjusts_balance_by_the_difference(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'BCA', 100000);
        Sanctum::actingAs($user);

        $transaction = FinanceTransaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 10000,
            'currency' => 'IDR',
            'transaction_date' => now()->toDateString(),
        ]);
        $account->forceFill(['balance' => 90000])->save();

        $this->putJson("/api/v1/finance/transactions/{$transaction->id}", ['amount' => 25000])
            ->assertOk();

        $this->assertSame(75000.0, (float) $account->fresh()->balance);
    }

    public function test_moving_transaction_to_another_account_moves_the_effect(): void
    {
        $user = $this->owner();
        $from = $this->account($user, 'BCA', 90000);
        $to = $this->account($user, 'Cash', 0);
        Sanctum::actingAs($user);

        $transaction = FinanceTransaction::create([
            'user_id' => $user->id,
            'account_id' => $from->id,
            'type' => 'expense',
            'amount' => 10000,
            'currency' => 'IDR',
            'transaction_date' => now()->toDateString(),
        ]);

        $this->putJson("/api/v1/finance/transactions/{$transaction->id}", ['account_id' => $to->id])
            ->assertOk();

        $this->assertSame(100000.0, (float) $from->fresh()->balance);
        $this->assertSame(-10000.0, (float) $to->fresh()->balance);
    }

    public function test_deleting_transaction_restores_balance(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'BCA', 100000);
        Sanctum::actingAs($user);

        $transaction = FinanceTransaction::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 40000,
            'currency' => 'IDR',
            'transaction_date' => now()->toDateString(),
        ]);
        $account->forceFill(['balance' => 60000])->save();

        $this->deleteJson("/api/v1/finance/transactions/{$transaction->id}")->assertOk();

        $this->assertSame(100000.0, (float) $account->fresh()->balance);
    }

    public function test_cannot_use_another_users_account(): void
    {
        $this->owner();
        $other = User::factory()->createOne();
        $foreign = $this->account($other, 'Milik orang lain', 500000);

        $this->postTransaction([
            'type' => 'expense',
            'amount' => 10000,
            'account_id' => $foreign->id,
        ])->assertStatus(422);

        $this->assertSame(500000.0, (float) $foreign->fresh()->balance);
    }

    public function test_summary_totals_the_month(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'BCA', 0);

        $this->postTransaction(['type' => 'income', 'amount' => 1000000, 'account_id' => $account->id, 'transaction_date' => '2026-11-05']);
        $this->postTransaction(['type' => 'expense', 'amount' => 250000, 'account_id' => $account->id, 'transaction_date' => '2026-11-06']);
        $this->postTransaction(['type' => 'expense', 'amount' => 999, 'account_id' => $account->id, 'transaction_date' => '2026-10-31']);

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/finance/summary?month=2026-11')
            ->assertOk()
            ->assertJsonPath('data.income', 1000000)
            ->assertJsonPath('data.expense', 250000)
            ->assertJsonPath('data.net', 750000)
            ->assertJsonPath('data.total_balance', 749001);
    }

    public function test_budget_spent_is_calculated_from_category_expenses(): void
    {
        $user = $this->owner();
        $account = $this->account($user, 'BCA', 0);
        $food = FinanceCategory::create(['user_id' => $user->id, 'name' => 'Makan', 'type' => 'expense']);

        Budget::create([
            'user_id' => $user->id,
            'name' => 'Makan November',
            'category' => 'Makan',
            'finance_category_id' => $food->id,
            'amount' => 1000000,
            'period' => 'monthly',
            'start_date' => '2026-11-01',
            'spent_amount' => 0,
            'is_active' => true,
        ]);

        $this->postTransaction(['type' => 'expense', 'amount' => 150000, 'account_id' => $account->id, 'finance_category_id' => $food->id, 'transaction_date' => '2026-11-03']);
        $this->postTransaction(['type' => 'expense', 'amount' => 50000, 'account_id' => $account->id, 'finance_category_id' => $food->id, 'transaction_date' => '2026-12-01']);

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/finance/budgets')
            ->assertOk()
            ->assertJsonPath('data.0.spent', 150000)
            ->assertJsonPath('data.0.remaining', 850000);
    }

    public function test_goal_contribution_increments_progress(): void
    {
        $user = $this->owner();
        $goal = Goal::create(['user_id' => $user->id, 'title' => 'Liburan', 'target_amount' => 5000000, 'current_amount' => 200000]);

        $this->withHeaders($this->bot())
            ->postJson("/api/v1/integrations/telegram/finance/goals/{$goal->id}/contributions", ['amount' => 100000])
            ->assertOk()
            ->assertJsonPath('data.current_amount', 300000);
    }

    public function test_goal_contribution_on_another_users_goal_is_not_found(): void
    {
        $this->owner();
        $other = User::factory()->createOne();
        $goal = Goal::create(['user_id' => $other->id, 'title' => 'Milik orang lain', 'target_amount' => 1000000, 'current_amount' => 0]);

        $this->withHeaders($this->bot())
            ->postJson("/api/v1/integrations/telegram/finance/goals/{$goal->id}/contributions", ['amount' => 1000])
            ->assertNotFound();

        $this->assertSame(0.0, (float) $goal->fresh()->current_amount);
    }
}
