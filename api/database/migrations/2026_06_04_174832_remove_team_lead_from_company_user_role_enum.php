<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('company_user')
            ->where('role', 'team_lead')
            ->update(['role' => 'worker']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE company_user MODIFY role ENUM('company_admin', 'worker') NOT NULL DEFAULT 'worker'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE company_user MODIFY role ENUM('company_admin', 'team_lead', 'worker') NOT NULL DEFAULT 'worker'");
        }
    }
};
