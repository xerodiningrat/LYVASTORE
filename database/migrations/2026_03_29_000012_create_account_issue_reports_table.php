<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_issue_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('public_id', 32)->unique();
            $table->string('status', 24)->default('pending');
            $table->string('product_id', 120);
            $table->string('product_name', 160);
            $table->string('issue_type', 64);
            $table->string('transaction_reference', 120)->nullable();
            $table->string('account_email', 255);
            $table->string('contact_whatsapp', 30);
            $table->text('issue_message');
            $table->string('proof_path')->nullable();
            $table->string('proof_url')->nullable();
            $table->timestamp('telegram_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_issue_reports');
    }
};
