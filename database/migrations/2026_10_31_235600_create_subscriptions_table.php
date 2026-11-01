<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('provider', 100);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly
            $table->date('next_due_date');
            $table->string('status', 20)->default('active'); // active, cancelled, paused
            $table->string('category', 50)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
