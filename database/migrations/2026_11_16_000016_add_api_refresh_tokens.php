<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->string('api_refresh_token_hash', 64)->nullable()->unique()->after('api_token_hash');
            $table->timestamp('api_refresh_token_expires_at')->nullable()->after('api_token_expires_at');
            $table->index(['api_refresh_token_expires_at', 'status'], 'app_users_refresh_expiry_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropIndex('app_users_refresh_expiry_status_idx');
            $table->dropUnique(['api_refresh_token_hash']);
            $table->dropColumn(['api_refresh_token_hash', 'api_refresh_token_expires_at']);
        });
    }
};
