<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes that keep the dashboard's date-range aggregates fast as the log tables grow.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('app_users', function (Blueprint $table): void {
            $table->index('created_at');
        });

        Schema::table('app_user_logs', function (Blueprint $table): void {
            $table->index(['created_at', 'app_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('app_user_logs', function (Blueprint $table): void {
            $table->dropIndex(['created_at', 'app_user_id']);
        });

        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });

        Schema::table('access_logs', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });
    }
};
