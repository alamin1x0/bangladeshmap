<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('created_at', 'users_created_at_idx');
            $table->index('email_verified_at', 'users_verified_at_idx');
        });

        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->index('created_at', 'password_reset_created_at_idx');
        });

        Schema::table('job_batches', function (Blueprint $table): void {
            $table->index('finished_at', 'job_batches_finished_at_idx');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->index(['queue', 'reserved_at', 'available_at'], 'jobs_claim_idx');
            $table->index('created_at', 'jobs_created_at_idx');
        });

        Schema::table('divisions', function (Blueprint $table): void {
            $table->index('created_at', 'divisions_created_at_idx');
        });

        Schema::table('districts', function (Blueprint $table): void {
            $table->index('created_at', 'districts_created_at_idx');
        });

        Schema::table('upazilas', function (Blueprint $table): void {
            $table->index('created_at', 'upazilas_created_at_idx');
        });

        Schema::table('app_user_logs', function (Blueprint $table): void {
            $table->index('ip_address', 'app_user_logs_ip_idx');
        });

        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->index('audience_detail', 'push_notifications_audience_detail_idx');
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->dropIndex('push_notifications_audience_detail_idx');
        });
        Schema::table('app_user_logs', function (Blueprint $table): void {
            $table->dropIndex('app_user_logs_ip_idx');
        });
        Schema::table('upazilas', function (Blueprint $table): void {
            $table->dropIndex('upazilas_created_at_idx');
        });
        Schema::table('districts', function (Blueprint $table): void {
            $table->dropIndex('districts_created_at_idx');
        });
        Schema::table('divisions', function (Blueprint $table): void {
            $table->dropIndex('divisions_created_at_idx');
        });
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropIndex('jobs_claim_idx');
            $table->dropIndex('jobs_created_at_idx');
        });
        Schema::table('job_batches', function (Blueprint $table): void {
            $table->dropIndex('job_batches_finished_at_idx');
        });
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->dropIndex('password_reset_created_at_idx');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_created_at_idx');
            $table->dropIndex('users_verified_at_idx');
        });
    }
};
