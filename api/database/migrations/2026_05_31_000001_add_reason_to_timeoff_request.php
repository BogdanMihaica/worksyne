<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('timeoff_request') && ! Schema::hasColumn('timeoff_request', 'reason')) {
            Schema::table('timeoff_request', function (Blueprint $table) {
                $table->text('reason')->nullable()->after('timezone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('timeoff_request') && Schema::hasColumn('timeoff_request', 'reason')) {
            Schema::table('timeoff_request', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
