<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            $table->string('product_name')->nullable();
            $table->string('package_label');
            $table->string('stock_label')->nullable();
            $table->text('stock_value');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('available');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reserved_for_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'package_label', 'status'], 'manual_stock_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_stock_items');
    }
};
