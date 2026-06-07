<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_workstream') || Schema::hasColumn('user_workstream', 'logged_on')) {
            return;
        }

        Schema::table('user_workstream', function (Blueprint $table) {
            $table->date('logged_on')->nullable()->after('units');
        });

        DB::table('user_workstream')->update([
            'logged_on' => DB::raw('DATE(created_at)'),
        ]);

        Schema::table('user_workstream', function (Blueprint $table) {
            $table->date('logged_on')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_workstream') && Schema::hasColumn('user_workstream', 'logged_on')) {
            Schema::table('user_workstream', function (Blueprint $table) {
                $table->dropColumn('logged_on');
            });
        }
    }
};
