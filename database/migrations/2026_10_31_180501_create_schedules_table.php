<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('location', 255)->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->boolean('recurring')->default(false);
            $table->string('recurring_pattern', 50)->nullable(); // daily, weekly, monthly
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->boolean('is_overnight')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
