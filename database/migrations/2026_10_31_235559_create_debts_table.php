<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('type', 20); // debt (utang saya) or credit (piutang dari orang lain)
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending, paid, overdue
            $table->string('source_of_funds', 100)->nullable(); // rekening mana
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
