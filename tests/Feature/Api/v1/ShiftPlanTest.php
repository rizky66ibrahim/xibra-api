<?php

namespace Tests\Feature\Api\v1;

use App\Models\DailyStandby;
use App\Models\ShiftBlock;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftPlanTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';
    private const TG_ID = 555003;

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
            'telegram_username' => 'rizky_tg',
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

    private function block(User $user, string $member, string $startsOn): void
    {
        ShiftBlock::create(['user_id' => $user->id, 'member' => $member, 'starts_on' => $startsOn]);
    }

    private function shiftsOn(string $date)
    {
        return $this->withHeaders($this->bot())
            ->getJson("/api/v1/integrations/telegram/shifts?date={$date}");
    }

    public function test_preview_suggests_next_member_after_last_block(): void
    {
        $user = $this->owner();
        $this->block($user, 'rizky', '2026-11-12');

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/shift-blocks/preview?starts_on=2026-11-17')
            ->assertOk()
            ->assertJsonPath('data.member', 'rayhand')
            ->assertJsonPath('data.h1_date', '2026-11-16')
            ->assertJsonPath('data.ends_on', '2026-11-21')
            ->assertJsonPath('data.gap_warning', false);
    }

    public function test_preview_warns_when_start_is_not_five_days_after_last_block(): void
    {
        $user = $this->owner();
        $this->block($user, 'rizky', '2026-11-12');

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/shift-blocks/preview?starts_on=2026-11-20')
            ->assertOk()
            ->assertJsonPath('data.gap_warning', true)
            ->assertJsonPath('data.expected_start', '2026-11-17');
    }

    public function test_store_block_saves_next_member_computed_by_server(): void
    {
        $user = $this->owner();
        $this->block($user, 'rizky', '2026-11-12');

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/shift-blocks', ['starts_on' => '2026-11-17'])
            ->assertCreated();

        $this->assertDatabaseHas('shift_blocks', [
            'user_id' => $user->id,
            'member' => 'rayhand',
            'starts_on' => '2026-11-17 00:00:00',
        ]);
    }

    public function test_block_must_start_after_last_block(): void
    {
        $user = $this->owner();
        $this->block($user, 'rizky', '2026-11-12');

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/shift-blocks', ['starts_on' => '2026-11-10'])
            ->assertStatus(422);
    }

    public function test_owner_sees_night_shift_during_block(): void
    {
        $user = $this->owner();
        $this->block($user, 'rizky', '2026-11-12');

        $this->shiftsOn('2026-11-12')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '3')
            ->assertJsonPath('data.0.end', '05:00')
            ->assertJsonPath('data.0.ends_on', '2026-11-13');
    }

    public function test_owner_sees_h1_libur_the_day_before_block(): void
    {
        $user = $this->owner();
        $this->block($user, 'rizky', '2026-11-12');

        $this->shiftsOn('2026-11-11')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'libur');
    }

    public function test_other_members_block_is_not_shown_for_owner(): void
    {
        $user = $this->owner();
        $this->block($user, 'rayhand', '2026-11-12');

        $this->shiftsOn('2026-11-12')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_friday_shows_nothing_until_standby_is_answered(): void
    {
        $this->owner();

        $this->shiftsOn('2026-11-06')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_friday_one_standby_gives_shift_four_with_two_segments(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/standby', ['date' => '2026-11-06', 'count' => 1])
            ->assertOk();

        $this->shiftsOn('2026-11-06')
            ->assertOk()
            ->assertJsonPath('data.0.code', '4')
            ->assertJsonCount(2, 'data.0.segments');
    }

    public function test_friday_two_standby_gives_shift_five(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/standby', ['date' => '2026-11-06', 'count' => 2])
            ->assertOk();

        $this->shiftsOn('2026-11-06')
            ->assertOk()
            ->assertJsonPath('data.0.code', '5')
            ->assertJsonPath('data.0.end', '02:00');
    }

    public function test_standby_rejects_non_friday(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/standby', ['date' => '2026-11-12', 'count' => 1])
            ->assertStatus(422);
    }

    public function test_standby_show_returns_saved_answer(): void
    {
        $user = $this->owner();
        DailyStandby::create(['user_id' => $user->id, 'work_date' => '2026-11-06', 'standby_count' => 2]);

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/standby?date=2026-11-06')
            ->assertOk()
            ->assertJsonPath('data.standby_count', 2)
            ->assertJsonPath('data.is_friday', true);
    }

    public function test_manual_shift_one_is_recorded_and_listed(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/shifts', ['date' => '2026-11-09', 'shift_code' => '1'])
            ->assertCreated();

        $this->shiftsOn('2026-11-09')
            ->assertOk()
            ->assertJsonPath('data.0.code', '1')
            ->assertJsonPath('data.0.shift_name', 'Shift 1');
    }

    public function test_manual_shift_rejects_shift_four(): void
    {
        $this->owner();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/shifts', ['date' => '2026-11-06', 'shift_code' => '4'])
            ->assertStatus(422);
    }

    public function test_manual_shift_rejects_duplicate_for_same_day(): void
    {
        $user = $this->owner();
        WorkShift::create([
            'user_id' => $user->id,
            'shift_name' => 'Shift 1',
            'shift_code' => '1',
            'start_time' => '06:00:00',
            'end_time' => '15:00:00',
            'break_minutes' => 0,
            'work_date' => '2026-11-09',
            'is_active' => true,
        ]);

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/shifts', ['date' => '2026-11-09', 'shift_code' => '1'])
            ->assertStatus(422);
    }
}
