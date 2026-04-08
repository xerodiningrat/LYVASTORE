<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('affiliate_status')->default('none')->after('avatar_path');
            $table->string('affiliate_code')->nullable()->unique()->after('affiliate_status');
            $table->foreignId('referred_by_user_id')->nullable()->after('affiliate_code')->constrained('users')->nullOnDelete();
            $table->timestamp('affiliate_applied_at')->nullable()->after('referred_by_user_id');
            $table->timestamp('affiliate_approved_at')->nullable()->after('affiliate_applied_at');
            $table->timestamp('referred_at')->nullable()->after('affiliate_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropColumn([
                'affiliate_status',
                'affiliate_code',
                'affiliate_applied_at',
                'affiliate_approved_at',
                'referred_at',
            ]);
        });
    }
};
