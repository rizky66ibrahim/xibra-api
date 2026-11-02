<?php

namespace Database\Seeders;

use App\Models\ShiftBlock;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShiftBlockSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('username', config('owner.username'))->first();

        if (! $owner) {
            $this->command?->error('Owner belum ada. Jalankan UserSeeder dulu.');

            return;
        }

        // Blok yang sudah berjalan, sesuai konfirmasi Anda
        $blocks = [
            ['member' => 'rayhand', 'starts_on' => '2026-11-02'],
            ['member' => 'azhar', 'starts_on' => '2026-11-07'],
            ['member' => 'rizky', 'starts_on' => '2026-11-12'],
        ];

        foreach ($blocks as $block) {
            ShiftBlock::updateOrCreate(
                ['user_id' => $owner->id, 'starts_on' => $block['starts_on']],
                ['member' => $block['member']]
            );
        }

        $this->command?->info('Data blok malam awal sudah diisi.');
    }
}
