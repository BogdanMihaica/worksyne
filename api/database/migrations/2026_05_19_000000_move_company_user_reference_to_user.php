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
        if (! Schema::hasColumn('user', 'company_user_id')) {
            Schema::table('user', function (Blueprint $table) {
                $table->foreignId('company_user_id')->nullable()->unique()->after('id');
            });
        }

        if (Schema::hasColumn('company_user', 'user_id')) {
            DB::table('company_user')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $companyUser) {
                    DB::table('user')
                        ->where('id', $companyUser->user_id)
                        ->update(['company_user_id' => $companyUser->id]);
                });

            Schema::table('company_user', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('company_user', 'user_id')) {
            Schema::table('company_user', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('company_id');
            });
        }

        if (Schema::hasColumn('user', 'company_user_id')) {
            DB::table('user')
                ->whereNotNull('company_user_id')
                ->orderBy('id')
                ->get()
                ->each(function (object $user) {
                    DB::table('company_user')
                        ->where('id', $user->company_user_id)
                        ->update(['user_id' => $user->id]);
                });

            Schema::table('user', function (Blueprint $table) {
                $table->dropColumn('company_user_id');
            });
        }
    }
};
