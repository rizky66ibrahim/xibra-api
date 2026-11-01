<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('telegram_pairing_codes', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('code_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_pairing_codes');
    }
};
