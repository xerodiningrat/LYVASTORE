<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('lyvaflow_processing_notified_at')->nullable()->after('error_message');
            $table->timestamp('lyvaflow_completed_notified_at')->nullable()->after('lyvaflow_processing_notified_at');
            $table->timestamp('lyvaflow_failed_notified_at')->nullable()->after('lyvaflow_completed_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'lyvaflow_processing_notified_at',
                'lyvaflow_completed_notified_at',
                'lyvaflow_failed_notified_at',
            ]);
        });
    }
};
