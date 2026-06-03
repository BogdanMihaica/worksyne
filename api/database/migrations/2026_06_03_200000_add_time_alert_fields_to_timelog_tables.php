<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('timelog') && ! Schema::hasColumn('timelog', 'continuous_work_notified_at')) {
            Schema::table('timelog', function (Blueprint $table) {
                $table->timestamp('continuous_work_notified_at')->nullable()->after('end_time');
            });
        }

        if (Schema::hasTable('timelog_break') && ! Schema::hasColumn('timelog_break', 'long_break_notified_at')) {
            Schema::table('timelog_break', function (Blueprint $table) {
                $table->timestamp('long_break_notified_at')->nullable()->after('end_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('timelog') && Schema::hasColumn('timelog', 'continuous_work_notified_at')) {
            Schema::table('timelog', function (Blueprint $table) {
                $table->dropColumn('continuous_work_notified_at');
            });
        }

        if (Schema::hasTable('timelog_break') && Schema::hasColumn('timelog_break', 'long_break_notified_at')) {
            Schema::table('timelog_break', function (Blueprint $table) {
                $table->dropColumn('long_break_notified_at');
            });
        }
    }
};
