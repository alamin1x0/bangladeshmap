<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->string('body', 1000);
            $table->string('image_url', 500)->nullable();
            $table->string('link', 500)->nullable();
            $table->string('audience', 30); // all | platform | users | token
            $table->string('audience_detail', 190)->nullable();
            $table->unsignedInteger('target_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->string('status', 20)->default('sending')->index(); // sending | sent | partial | failed
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('push_notification_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('push_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('token_hint', 24)->nullable(); // last characters only, never the full token
            $table->string('status', 20); // sent | failed
            $table->string('error', 255)->nullable();
            $table->string('message_id', 190)->nullable();
            $table->timestamps();
            $table->index(['push_notification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_recipients');
        Schema::dropIfExists('push_notifications');
    }
};
