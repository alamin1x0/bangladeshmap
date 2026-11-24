<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deletion_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('admin_name', 100)->nullable();      // snapshot, survives admin removal
            $table->string('entity_type', 40);                  // district, upazila, app_user, location_set, landing_order
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_name', 190)->nullable();
            $table->string('entity_code', 60)->nullable();
            $table->string('parent_name', 190)->nullable();     // e.g. "Dhaka / Dhaka"
            $table->unsignedInteger('children_count')->default(0);
            $table->json('snapshot')->nullable();               // full record (+ children) as it was
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_logs');
    }
};
