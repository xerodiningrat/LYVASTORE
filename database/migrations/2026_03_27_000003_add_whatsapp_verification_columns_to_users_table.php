<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('whatsapp_verified_at')->nullable()->after('email_verified_at');
            $table->string('whatsapp_verification_code')->nullable()->after('whatsapp_verified_at');
            $table->timestamp('whatsapp_verification_expires_at')->nullable()->after('whatsapp_verification_code');
            $table->timestamp('whatsapp_verification_sent_at')->nullable()->after('whatsapp_verification_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_verified_at',
                'whatsapp_verification_code',
                'whatsapp_verification_expires_at',
                'whatsapp_verification_sent_at',
            ]);
        });
    }
};
