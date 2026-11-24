<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->index('last_login_at', 'admin_users_last_login_idx');
        });

        Schema::table('app_users', function (Blueprint $table): void {
            $table->index(['account_type', 'package_status', 'package_start_date', 'package_end_date'], 'app_users_entitlement_idx');
        });

        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->index(['audience', 'status', 'created_at'], 'push_notifications_audience_idx');
        });

        Schema::table('push_notification_recipients', function (Blueprint $table): void {
            $table->index('message_id', 'push_recipients_message_idx');
        });

        Schema::table('location_sets', function (Blueprint $table): void {
            $table->index('image_path', 'location_sets_image_idx');
        });
    }

    public function down(): void
    {
        Schema::table('location_sets', function (Blueprint $table): void {
            $table->dropIndex('location_sets_image_idx');
        });
        Schema::table('push_notification_recipients', function (Blueprint $table): void {
            $table->dropIndex('push_recipients_message_idx');
        });
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->dropIndex('push_notifications_audience_idx');
        });
        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropIndex('app_users_entitlement_idx');
        });
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropIndex('admin_users_last_login_idx');
        });
    }
};
