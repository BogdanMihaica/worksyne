<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timeoff_request') && ! Schema::hasColumn('timeoff_request', 'timezone')) {
            Schema::table('timeoff_request', function (Blueprint $table) {
                $table->string('timezone')->nullable()->after('end_date');
            });
        }

        if (Schema::hasTable('workstream')) {
            Schema::table('workstream', function (Blueprint $table) {
                $table->unique(['company_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workstream')) {
            Schema::table('workstream', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'name']);
            });
        }

        if (Schema::hasTable('timeoff_request') && Schema::hasColumn('timeoff_request', 'timezone')) {
            Schema::table('timeoff_request', function (Blueprint $table) {
                $table->dropColumn('timezone');
            });
        }
    }
};
