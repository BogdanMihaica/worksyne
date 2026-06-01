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
        if (Schema::hasTable('user_workstream')) {
            Schema::table('user_workstream', function (Blueprint $table) {
                if (! Schema::hasColumn('user_workstream', 'reference_code')) {
                    $table->string('reference_code')->nullable()->after('units');
                }

                if (! Schema::hasColumn('user_workstream', 'note')) {
                    $table->text('note')->nullable()->after('reference_code');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_workstream')) {
            Schema::table('user_workstream', function (Blueprint $table) {
                if (Schema::hasColumn('user_workstream', 'note')) {
                    $table->dropColumn('note');
                }

                if (Schema::hasColumn('user_workstream', 'reference_code')) {
                    $table->dropColumn('reference_code');
                }
            });
        }
    }
};
