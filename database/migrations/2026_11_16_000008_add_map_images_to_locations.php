<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['divisions', 'districts', 'upazilas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('image_path')->nullable()->after('code');
            });
        }
    }

    public function down(): void
    {
        foreach (['divisions', 'districts', 'upazilas'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('image_path');
            });
        }
    }
};
