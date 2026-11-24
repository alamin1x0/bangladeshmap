<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('landing_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 20)->index();
            $table->string('district', 120)->nullable();
            $table->string('address', 1000)->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->string('status', 20)->default('new');
            $table->string('admin_note', 1000)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::table('app_settings', function (Blueprint $table): void {
            $table->unsignedInteger('landing_price')->nullable()->after('bkash_merchant_number');
            $table->string('landing_phone', 30)->nullable()->after('landing_price');
            $table->boolean('landing_orders_open')->default(true)->after('landing_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_orders');
        Schema::table('app_settings', function (Blueprint $table): void {
            $table->dropColumn(['landing_price', 'landing_phone', 'landing_orders_open']);
        });
    }
};
