<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->date('alert_date');
            $table->string('fingerprint', 64);
            $table->string('level', 32);
            $table->string('title');
            $table->text('body');
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_detected_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('seen_count')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['alert_date', 'fingerprint']);
            $table->index(['alert_date', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_alert_logs');
    }
};
