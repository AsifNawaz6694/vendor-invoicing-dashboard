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
        Schema::table('users', function (Blueprint $table) {
            // Google OAuth columns
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');

            // Email 2FA columns
            $table->string('email_two_factor_code', 6)->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('email_two_factor_expires_at')->nullable()->after('email_two_factor_code');

            // 2FA method preference: 'totp' or 'email'
            $table->string('two_factor_method', 10)->nullable()->after('email_two_factor_expires_at');

            // Admin and user management columns
            $table->boolean('is_admin')->default(false)->after('two_factor_method');
            $table->boolean('is_active')->default(true)->after('is_admin');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'google_id',
                'avatar',
                'email_two_factor_code',
                'email_two_factor_expires_at',
                'two_factor_method',
                'is_admin',
                'is_active',
                'created_by',
            ]);
        });
    }
};
