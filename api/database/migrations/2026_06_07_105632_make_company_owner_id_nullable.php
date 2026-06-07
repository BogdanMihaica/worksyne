<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('company') && Schema::hasColumn('company', 'owner_id')) {
            Schema::table('company', function (Blueprint $table) {
                $table->foreignId('owner_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('company') && Schema::hasColumn('company', 'owner_id')) {
            Schema::table('company', function (Blueprint $table) {
                $table->foreignId('owner_id')->nullable(false)->change();
            });
        }
    }
};
