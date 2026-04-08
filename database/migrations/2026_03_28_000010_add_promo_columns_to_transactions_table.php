<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal')->default(0)->after('vipayment_trx_ids');
            $table->string('promo_code', 40)->nullable()->index()->after('subtotal');
            $table->string('promo_label')->nullable()->after('promo_code');
            $table->unsignedBigInteger('promo_discount')->default(0)->after('promo_label');
            $table->json('promo_snapshot')->nullable()->after('promo_discount');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'promo_code',
                'promo_label',
                'promo_discount',
                'promo_snapshot',
            ]);
        });
    }
};
