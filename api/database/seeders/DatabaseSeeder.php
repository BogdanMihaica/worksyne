<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyUserSeniority;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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

        $fakerUsers = User::factory()
            ->count(50)
            ->create([
                'is_admin' => false,
                'is_email_verified' => true,
                'is_blocked' => false,
            ]);

        $subscriptionPlans = [
            'starter' => SubscriptionPlan::query()->updateOrCreate(
                ['name' => 'Starter'],
                ['price' => 29.00],
            ),
            'growth' => SubscriptionPlan::query()->updateOrCreate(
                ['name' => 'Growth'],
                ['price' => 79.00],
            ),
            'enterprise' => SubscriptionPlan::query()->updateOrCreate(
                ['name' => 'Enterprise'],
                ['price' => 199.00],
            ),
        ];

        $company = Company::query()->updateOrCreate(
            ['name' => 'Acme Operations'],
            [
                'owner_id' => $users['admin@worksyne.local.test']->id,
                'subscription_plan_id' => $subscriptionPlans['growth']->id,
            ],
        );

        $additionalCompanies = collect([
            ['name' => 'Northstar Logistics', 'plan' => 'starter'],
            ['name' => 'Blue Harbor Support', 'plan' => 'growth'],
            ['name' => 'Summit Field Services', 'plan' => 'enterprise'],
            ['name' => 'Cedarline Operations', 'plan' => 'starter'],
            ['name' => 'Orbit Response Group', 'plan' => 'growth'],
        ])->map(function (array $companyData, int $index) use ($fakerUsers, $subscriptionPlans) {
            return Company::query()->updateOrCreate(
                ['name' => $companyData['name']],
                [
                    'owner_id' => $fakerUsers[$index]->id,
                    'subscription_plan_id' => $subscriptionPlans[$companyData['plan']]->id,
                ],
            );
        });

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

        $assignCompanyUser = function (User $user, Company $company, string $role, string $status): CompanyUser {
            $attributes = [
                'company_id' => $company->id,
                'role' => $role,
                'status' => $status,
            ];

            $companyUser = $user->company_user_id
                ? tap(CompanyUser::query()->findOrFail($user->company_user_id))->update($attributes)
                : CompanyUser::query()->create($attributes);

            $user->forceFill(['company_user_id' => $companyUser->id])->save();

            return $companyUser;
        };

        foreach ($memberships as $membership) {
            $assignCompanyUser(
                $users[$membership['email']],
                $company,
                $membership['role'],
                $membership['status'],
            );
        }

        foreach ($additionalCompanies as $index => $additionalCompany) {
            $assignCompanyUser($fakerUsers[$index], $additionalCompany, 'company_admin', 'approved');

            foreach ($fakerUsers->slice(($index + 1) * 5, 5) as $fakerUser) {
                $assignCompanyUser($fakerUser, $additionalCompany, 'worker', 'approved');
            }
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
            UserWorkstream::query()->updateOrCreate(
                [
                    'unique_code' => $userWorkstream['unique_code'],
                ],
                [
                    'user_id' => $users[$userWorkstream['email']]->id,
                    'workstream_id' => $workstreams[$userWorkstream['workstream']]->id,
                    'units' => $userWorkstream['units'],
                ],
            );
        }

        $companies = collect([$company])->merge($additionalCompanies);

        foreach ($companies as $index => $seededCompany) {
            $plan = $seededCompany->subscriptionPlan;
            $startsAt = now()->subMonths($index + 1)->toDateString();

            Subscription::query()->updateOrCreate(
                [
                    'company_id' => $seededCompany->id,
                    'subscription_plan_id' => $plan->id,
                ],
                [
                    'starts_at' => $startsAt,
                    'ends_at' => null,
                    'status' => 'active',
                ],
            );

            Order::query()->updateOrCreate(
                [
                    'external_id' => 'seed-order-'.$seededCompany->id,
                ],
                [
                    'user_id' => $seededCompany->owner_id,
                    'company_id' => $seededCompany->id,
                    'subscription_plan_id' => $plan->id,
                    'amount' => $plan->price,
                    'currency' => 'USD',
                    'status' => 'paid',
                ],
            );
        }
    }
}
