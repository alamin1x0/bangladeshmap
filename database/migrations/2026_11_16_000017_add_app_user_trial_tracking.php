<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->date('trial_started_at')->nullable()->after('package_auto_renew');
            $table->date('trial_ends_at')->nullable()->after('trial_started_at');
            $table->index(['trial_started_at', 'trial_ends_at'], 'app_users_trial_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table): void {
            $table->dropIndex('app_users_trial_dates_idx');
            $table->dropColumn(['trial_started_at', 'trial_ends_at']);
        });
    }
};
