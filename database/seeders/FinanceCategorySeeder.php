<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

// Default categories for the owner. Names only, no amounts and no balances.
class FinanceCategorySeeder extends Seeder
{
    private const EXPENSE = ['Makan', 'Transport', 'Tagihan', 'Belanja', 'Hiburan', 'Kesehatan', 'Lainnya'];
    private const INCOME = ['Gaji', 'Lainnya'];

    public function run(): void
    {
        $owner = User::where('username', config('owner.username'))->first();

        if (! $owner) {
            $this->command?->error('Owner belum ada. Jalankan UserSeeder dulu.');

            return;
        }

        foreach (self::EXPENSE as $name) {
            FinanceCategory::firstOrCreate(
                ['user_id' => $owner->id, 'name' => $name, 'type' => 'expense'],
                ['is_system' => true],
            );
        }

        foreach (self::INCOME as $name) {
            FinanceCategory::firstOrCreate(
                ['user_id' => $owner->id, 'name' => $name, 'type' => 'income'],
                ['is_system' => true],
            );
        }

        $this->command?->info('Kategori default untuk owner sudah diisi.');
    }
}
