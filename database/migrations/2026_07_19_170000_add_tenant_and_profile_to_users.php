<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Multi-tenancy
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');

            // Locale & timezone
            $table->string('locale', 10)->default('pt_BR')->after('password');
            $table->string('timezone', 50)->default('America/Sao_Paulo')->after('locale');

            // Avatar
            $table->string('avatar_url')->nullable()->after('timezone');

            // 2FA
            $table->boolean('two_factor_enabled')->default(false)->after('avatar_url');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');

            // Status
            $table->boolean('is_active')->default(true)->after('two_factor_secret');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            // Soft deletes for audit
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'tenant_id', 'locale', 'timezone', 'avatar_url',
                'two_factor_enabled', 'two_factor_secret',
                'is_active', 'last_login_at', 'last_login_ip',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
