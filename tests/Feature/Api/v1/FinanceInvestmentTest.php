<?php

namespace Tests\Feature\Api\v1;

use App\Models\Account;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceInvestmentTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';
    private const TG_ID = 555401;

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

    private function base(string $path): string
    {
        return '/api/v1/integrations/telegram/finance'.$path;
    }

    private function investment(User $user, float $units = 2, float $price = 1500000): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => 'Emas Antam',
            'type' => 'investment',
            'asset_class' => 'emas',
            'units' => $units,
            'unit_price' => $price,
            'price_updated_at' => now(),
            'balance' => $units * $price,
            'currency' => 'IDR',
            'is_active' => true,
        ]);
    }

    public function test_investment_account_value_is_units_times_price(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson($this->base('/accounts'), [
                'name' => 'BBCA',
                'type' => 'investment',
                'asset_class' => 'saham',
                'units' => 100,
                'unit_price' => 9500,
            ])
            ->assertCreated()
            ->assertJsonPath('data.balance', 950000)
            ->assertJsonPath('data.asset_class', 'saham');
    }

    public function test_investment_account_requires_units_and_price(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson($this->base('/accounts'), [
                'name' => 'Emas',
                'type' => 'investment',
                'asset_class' => 'emas',
            ])
            ->assertStatus(422);
    }

    public function test_updating_price_recalculates_value(): void
    {
        $user = $this->owner();
        $account = $this->investment($user, 2, 1500000);

        $this->withHeaders($this->bot())
            ->postJson($this->base("/accounts/{$account->id}/price"), ['unit_price' => 1600000])
            ->assertOk()
            ->assertJsonPath('data.balance', 3200000)
            ->assertJsonPath('data.unit_price', 1600000);

        $this->assertNotNull($account->fresh()->price_updated_at);
    }

    public function test_price_update_is_rejected_for_a_normal_account(): void
    {
        $user = $this->owner();
        $bank = Account::create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'type' => 'bank',
            'balance' => 100000,
            'currency' => 'IDR',
            'is_active' => true,
        ]);

        $this->withHeaders($this->bot())
            ->postJson($this->base("/accounts/{$bank->id}/price"), ['unit_price' => 5000])
            ->assertStatus(422);
    }

    public function test_price_update_on_another_users_account_is_not_found(): void
    {
        $this->owner();
        $other = User::factory()->createOne();
        $foreign = $this->investment($other);

        $this->withHeaders($this->bot())
            ->postJson($this->base("/accounts/{$foreign->id}/price"), ['unit_price' => 1])
            ->assertNotFound();

        $this->assertSame(3000000.0, (float) $foreign->fresh()->balance);
    }

    public function test_transactions_cannot_be_booked_to_an_investment_account(): void
    {
        $user = $this->owner();
        $account = $this->investment($user);

        $this->withHeaders($this->bot())
            ->postJson($this->base('/transactions'), [
                'type' => 'expense',
                'amount' => 10000,
                'account_id' => $account->id,
            ])
            ->assertStatus(422);

        $this->assertSame(3000000.0, (float) $account->fresh()->balance);
    }

    public function test_summary_separates_cash_from_investment(): void
    {
        $user = $this->owner();
        Account::create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'type' => 'bank',
            'balance' => 400000,
            'currency' => 'IDR',
            'is_active' => true,
        ]);
        $this->investment($user, 2, 1500000);

        $this->withHeaders($this->bot())
            ->getJson($this->base('/summary'))
            ->assertOk()
            ->assertJsonPath('data.cash_balance', 400000)
            ->assertJsonPath('data.investment_value', 3000000)
            ->assertJsonPath('data.total_balance', 3400000);
    }
}
