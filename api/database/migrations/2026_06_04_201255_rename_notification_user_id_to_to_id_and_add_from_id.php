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
        if (Schema::hasTable('notification') && ! Schema::hasColumn('notification', 'from_id')) {
            Schema::table('notification', function (Blueprint $table) {
                $table->unsignedBigInteger('from_id')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('notification') && Schema::hasColumn('notification', 'user_id') && ! Schema::hasColumn('notification', 'to_id')) {
            Schema::table('notification', function (Blueprint $table) {
                $table->renameColumn('user_id', 'to_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notification') && Schema::hasColumn('notification', 'to_id') && ! Schema::hasColumn('notification', 'user_id')) {
            Schema::table('notification', function (Blueprint $table) {
                $table->renameColumn('to_id', 'user_id');
            });
        }

        if (Schema::hasTable('notification') && Schema::hasColumn('notification', 'from_id')) {
            Schema::table('notification', function (Blueprint $table) {
                $table->dropColumn('from_id');
            });
        }
    }
};
