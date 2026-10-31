<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('finance_category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type', 10); // income, expense
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference_number', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
