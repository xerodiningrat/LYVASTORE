<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_id', 32)->unique();
            $table->string('guest_token')->nullable()->index();

            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->string('vipayment_status', 20)->nullable()->index();

            $table->string('product_source', 20)->default('manual');
            $table->string('product_id', 160);
            $table->string('product_name');
            $table->string('product_image', 2048)->nullable();
            $table->string('package_code', 160)->nullable();
            $table->string('package_label');
            $table->unsignedInteger('quantity')->default(1);

            $table->string('payment_method_code', 80)->nullable();
            $table->string('payment_method_label');
            $table->string('payment_method_type', 40)->nullable();
            $table->string('payment_method_image', 2048)->nullable();
            $table->string('payment_badge', 24)->nullable();
            $table->string('payment_caption')->nullable();
            $table->string('payment_display_type', 32)->nullable();
            $table->string('payment_reference_label')->nullable();
            $table->text('payment_reference_value')->nullable();

            $table->string('duitku_reference', 120)->nullable()->index();
            $table->string('duitku_payment_url', 2048)->nullable();
            $table->string('duitku_app_url', 2048)->nullable();
            $table->longText('duitku_qr_string')->nullable();

            $table->string('vipayment_endpoint', 40)->nullable();
            $table->json('vipayment_trx_ids')->nullable();

            $table->unsignedBigInteger('total')->default(0);
            $table->text('checkout_notice')->nullable();
            $table->string('guarantee_text')->nullable();
            $table->json('notes')->nullable();
            $table->json('summary_rows')->nullable();
            $table->json('account_fields')->nullable();
            $table->json('contact_fields')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable()->index();
            $table->string('customer_whatsapp', 40)->nullable()->index();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
