<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Only used by accounts of type "investment"
            $table->string('asset_class', 30)->nullable()->after('provider');
            $table->decimal('units', 20, 8)->nullable()->after('balance');
            $table->decimal('unit_price', 20, 2)->nullable()->after('units');
            $table->timestamp('price_updated_at')->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['asset_class', 'units', 'unit_price', 'price_updated_at']);
        });
    }
};
