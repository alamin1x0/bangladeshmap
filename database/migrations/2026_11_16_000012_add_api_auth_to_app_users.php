<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->string('api_token_hash', 64)->nullable()->unique()->after('remember_token');
            $table->timestamp('api_token_issued_at')->nullable()->after('api_token_hash');
            $table->timestamp('api_token_expires_at')->nullable()->after('api_token_issued_at');
            $table->string('api_device_id', 190)->nullable()->after('api_token_expires_at');
            $table->index(['api_token_expires_at', 'status'], 'app_users_api_expiry_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropIndex('app_users_api_expiry_status_idx');
            $table->dropUnique(['api_token_hash']);
            $table->dropColumn(['api_token_hash', 'api_token_issued_at', 'api_token_expires_at', 'api_device_id']);
        });
    }
};
