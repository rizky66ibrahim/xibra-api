<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('member', 20);
            $table->date('starts_on');
            $table->timestamps();

            $table->unique(['user_id', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_blocks');
    }
};
