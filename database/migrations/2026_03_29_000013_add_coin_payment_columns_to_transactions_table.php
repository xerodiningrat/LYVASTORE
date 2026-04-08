<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('coin_spent_amount')->default(0)->after('promo_snapshot');
            $table->unsignedInteger('coin_spent_value')->default(0)->after('coin_spent_amount');
            $table->timestamp('coin_refunded_at')->nullable()->after('coin_spent_value');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['coin_spent_amount', 'coin_spent_value', 'coin_refunded_at']);
        });
    }
};
