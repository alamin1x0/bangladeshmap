<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_payment_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('gateway', 30)->default('bkash');
            $table->string('customer_bkash_number', 20);
            $table->string('merchant_bkash_number', 20);
            $table->string('transaction_id', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('package_name', 120);
            $table->string('package_id', 100)->nullable();
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['app_user_id', 'status'], 'payment_requests_user_status_idx');
            $table->index(['status', 'created_at'], 'payment_requests_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payment_requests');
    }
};
