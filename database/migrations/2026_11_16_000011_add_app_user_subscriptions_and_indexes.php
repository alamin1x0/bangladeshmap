<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->enum('account_type', ['free', 'paid', 'trial'])->default('free')->after('status');
            $table->string('subscription_id', 190)->nullable()->after('account_type');
            $table->string('subscription_package_name', 120)->nullable()->after('subscription_id');
            $table->string('subscription_package_id', 100)->nullable()->after('subscription_package_name');
            $table->string('subscription_package_type', 50)->nullable()->after('subscription_package_id');
            $table->date('package_start_date')->nullable()->after('subscription_package_type');
            $table->date('package_end_date')->nullable()->after('package_start_date');
            $table->enum('package_status', ['pending', 'active', 'expired', 'cancelled'])->nullable()->after('package_end_date');
            $table->boolean('package_auto_renew')->default(false)->after('package_status');
        });

        Schema::table('app_users', function (Blueprint $table): void {
            $table->index(['account_type', 'status'], 'app_users_account_status_idx');
            $table->index(['package_status', 'package_end_date'], 'app_users_package_expiry_idx');
            $table->index('subscription_id', 'app_users_subscription_idx');
        });

        Schema::table('divisions', function (Blueprint $table): void {
            $table->index('name', 'divisions_name_idx');
            $table->index('name_bn', 'divisions_name_bn_idx');
            $table->index('image_path', 'divisions_image_idx');
        });

        Schema::table('districts', function (Blueprint $table): void {
            $table->index(['division_id', 'name'], 'districts_division_name_idx');
            $table->index('name_bn', 'districts_name_bn_idx');
            $table->index('image_path', 'districts_image_idx');
        });

        Schema::table('upazilas', function (Blueprint $table): void {
            $table->index(['district_id', 'name'], 'upazilas_district_name_idx');
            $table->index('name_bn', 'upazilas_name_bn_idx');
            $table->index(['type', 'image_path'], 'upazilas_type_image_idx');
        });

        Schema::table('access_logs', function (Blueprint $table): void {
            $table->index('admin_user_id', 'access_logs_admin_idx');
            $table->index(['method', 'created_at'], 'access_logs_method_date_idx');
        });

        Schema::table('app_user_logs', function (Blueprint $table): void {
            $table->index(['event', 'created_at'], 'app_user_logs_event_date_idx');
        });

        Schema::table('location_sets', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'location_sets_status_date_idx');
        });

        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'push_notifications_status_date_idx');
            $table->index('admin_user_id', 'push_notifications_admin_idx');
        });

        Schema::table('push_notification_recipients', function (Blueprint $table): void {
            $table->index('app_user_id', 'push_recipients_user_idx');
            $table->index(['status', 'created_at'], 'push_recipients_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_recipients', function (Blueprint $table): void {
            $table->dropIndex('push_recipients_user_idx');
            $table->dropIndex('push_recipients_status_date_idx');
        });
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->dropIndex('push_notifications_status_date_idx');
            $table->dropIndex('push_notifications_admin_idx');
        });
        Schema::table('location_sets', function (Blueprint $table): void {
            $table->dropIndex('location_sets_status_date_idx');
        });
        Schema::table('app_user_logs', function (Blueprint $table): void {
            $table->dropIndex('app_user_logs_event_date_idx');
        });
        Schema::table('access_logs', function (Blueprint $table): void {
            $table->dropIndex('access_logs_admin_idx');
            $table->dropIndex('access_logs_method_date_idx');
        });
        Schema::table('upazilas', function (Blueprint $table): void {
            $table->dropIndex('upazilas_district_name_idx');
            $table->dropIndex('upazilas_name_bn_idx');
            $table->dropIndex('upazilas_type_image_idx');
        });
        Schema::table('districts', function (Blueprint $table): void {
            $table->dropIndex('districts_division_name_idx');
            $table->dropIndex('districts_name_bn_idx');
            $table->dropIndex('districts_image_idx');
        });
        Schema::table('divisions', function (Blueprint $table): void {
            $table->dropIndex('divisions_name_idx');
            $table->dropIndex('divisions_name_bn_idx');
            $table->dropIndex('divisions_image_idx');
        });
        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropIndex('app_users_account_status_idx');
            $table->dropIndex('app_users_package_expiry_idx');
            $table->dropIndex('app_users_subscription_idx');
            $table->dropColumn([
                'account_type',
                'subscription_id',
                'subscription_package_name',
                'subscription_package_id',
                'subscription_package_type',
                'package_start_date',
                'package_end_date',
                'package_status',
                'package_auto_renew',
            ]);
        });
    }
};
