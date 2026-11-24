<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_sets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('division_id')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('upazila_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->text('remarks')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index(['division_id', 'district_id', 'upazila_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_sets');
    }
};
