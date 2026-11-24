<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('screen_lock_enabled')->default(false);
            $table->boolean('screenshot_enabled')->default(true);
            $table->boolean('screen_recording_enabled')->default(true);
            $table->boolean('short_video_enabled')->default(true);
            $table->boolean('server_down')->default(false);
            $table->boolean('force_update')->default(false);
            $table->string('minimum_app_version', 30)->nullable();
            $table->string('maintenance_message', 500)->nullable();
            $table->string('support_phone', 30)->nullable();
            $table->string('support_email', 190)->nullable();
            $table->timestamp('force_logout_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
