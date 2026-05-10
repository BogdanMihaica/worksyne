<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyUserSeniority;
use App\Models\User;
use App\Models\UserWorkstream;
use App\Models\Workstream;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            'admin@worksyne.local.test' => User::query()->updateOrCreate(
                ['email' => 'admin@worksyne.local.test'],
                [
                    'name' => 'Admin',
                    'password' => Hash::make('test'),
                    'email_verified_at' => now(),
                    'is_admin' => true,
                    'is_email_verified' => true,
                    'is_blocked' => false,
                ],
            ),
            'alex.manager@worksyne.local.test' => User::query()->updateOrCreate(
                ['email' => 'alex.manager@worksyne.local.test'],
                [
                    'name' => 'Alex Manager',
                    'password' => Hash::make('test'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                    'is_email_verified' => true,
                    'is_blocked' => false,
                ],
            ),
            'maria.lead@worksyne.local.test' => User::query()->updateOrCreate(
                ['email' => 'maria.lead@worksyne.local.test'],
                [
                    'name' => 'Maria Lead',
                    'password' => Hash::make('test'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                    'is_email_verified' => true,
                    'is_blocked' => false,
                ],
            ),
            'sam.worker@worksyne.local.test' => User::query()->updateOrCreate(
                ['email' => 'sam.worker@worksyne.local.test'],
                [
                    'name' => 'Sam Worker',
                    'password' => Hash::make('test'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                    'is_email_verified' => true,
                    'is_blocked' => false,
                ],
            ),
            'nina.worker@worksyne.local.test' => User::query()->updateOrCreate(
                ['email' => 'nina.worker@worksyne.local.test'],
                [
                    'name' => 'Nina Worker',
                    'password' => Hash::make('test'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                    'is_email_verified' => true,
                    'is_blocked' => false,
                ],
            ),
        ];

        $company = Company::query()->updateOrCreate(
            ['name' => 'Acme Operations'],
            ['owner_id' => $users['admin@worksyne.local.test']->id],
        );

        $workstreams = [
            'tasks' => Workstream::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'tasks',
                ],
                [],
            ),
            'calls' => Workstream::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'calls',
                ],
                [],
            ),
            'vendors' => Workstream::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => 'vendors',
                ],
                [],
            ),
        ];

        $memberships = [
            ['email' => 'admin@worksyne.local.test', 'role' => 'company_admin', 'status' => 'approved'],
            ['email' => 'alex.manager@worksyne.local.test', 'role' => 'company_admin', 'status' => 'approved'],
            ['email' => 'maria.lead@worksyne.local.test', 'role' => 'team_lead', 'status' => 'approved'],
            ['email' => 'sam.worker@worksyne.local.test', 'role' => 'worker', 'status' => 'approved'],
            ['email' => 'nina.worker@worksyne.local.test', 'role' => 'worker', 'status' => 'pending'],
        ];

        foreach ($memberships as $membership) {
            CompanyUser::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'user_id' => $users[$membership['email']]->id,
                ],
                [
                    'role' => $membership['role'],
                    'status' => $membership['status'],
                ],
            );
        }

        $seniorities = [
            ['email' => 'maria.lead@worksyne.local.test', 'workstream' => 'tasks', 'seniority' => 'senior'],
            ['email' => 'maria.lead@worksyne.local.test', 'workstream' => 'calls', 'seniority' => 'mid'],
            ['email' => 'sam.worker@worksyne.local.test', 'workstream' => 'calls', 'seniority' => 'junior'],
            ['email' => 'nina.worker@worksyne.local.test', 'workstream' => 'vendors', 'seniority' => 'intern'],
        ];

        foreach ($seniorities as $seniority) {
            CompanyUserSeniority::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'user_id' => $users[$seniority['email']]->id,
                    'workstream_id' => $workstreams[$seniority['workstream']]->id,
                ],
                ['seniority' => $seniority['seniority']],
            );
        }

        $userWorkstreams = [
            ['email' => 'maria.lead@worksyne.local.test', 'workstream' => 'tasks', 'units' => 1, 'unique_code' => 'maria-tasks-1'],
            ['email' => 'maria.lead@worksyne.local.test', 'workstream' => 'calls', 'units' => 1, 'unique_code' => 'maria-calls-1'],
            ['email' => 'sam.worker@worksyne.local.test', 'workstream' => 'calls', 'units' => 1, 'unique_code' => 'sam-calls-1'],
            ['email' => 'nina.worker@worksyne.local.test', 'workstream' => 'vendors', 'units' => 2, 'unique_code' => 'nina-vendors-1'],
        ];

        foreach ($userWorkstreams as $userWorkstream) {
            UserWorkstream::query()->updateOrCreate([
                'user_id' => $users[$userWorkstream['email']]->id,
                'workstream_id' => $workstreams[$userWorkstream['workstream']]->id,
                'units' => $userWorkstream['units'],
                'unique_code' => $userWorkstream['unique_code'],
            ]);
        }
    }
}
