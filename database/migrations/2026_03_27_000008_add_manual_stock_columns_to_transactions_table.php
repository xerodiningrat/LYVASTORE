<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('manual_fulfillment_status', 30)->nullable()->after('vipayment_status');
            $table->timestamp('admin_manual_order_notified_at')->nullable()->after('lyvaflow_failed_notified_at');
            $table->timestamp('fulfilled_at')->nullable()->after('admin_manual_order_notified_at');
            $table->foreignId('fulfilled_by_user_id')->nullable()->after('fulfilled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fulfilled_by_user_id');
            $table->dropColumn([
                'manual_fulfillment_status',
                'admin_manual_order_notified_at',
                'fulfilled_at',
            ]);
        });
    }
};
