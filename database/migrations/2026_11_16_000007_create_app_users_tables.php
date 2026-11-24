<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->index();
            $table->text('fcm_token')->nullable();
            $table->string('app_version', 30)->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->ipAddress('last_ip_address')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('app_user_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('event', 50)->index();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('app_version', 30)->nullable();
            $table->text('fcm_token')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['app_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_logs');
        Schema::dropIfExists('app_users');
    }
};
