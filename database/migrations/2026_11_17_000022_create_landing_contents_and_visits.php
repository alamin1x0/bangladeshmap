<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only texts the admin changed are stored; everything else falls back to the defaults in code.
        Schema::create('landing_contents', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->text('value');
            $table->timestamps();
        });

        // One row per page view. visitor_hash = daily, salted hash of IP + browser (no raw IP stored).
        Schema::create('landing_visits', function (Blueprint $table): void {
            $table->id();
            $table->char('visitor_hash', 64);
            $table->string('device', 10);
            $table->string('source', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
            $table->index(['visitor_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_visits');
        Schema::dropIfExists('landing_contents');
    }
};
