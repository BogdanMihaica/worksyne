<?php

namespace Database\Seeders;

use App\Models\CapacityModel;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\CompanyUserSeniority;
use App\Models\Timelog;
use App\Models\TimelogBreak;
use App\Models\TimeoffRequest;
use App\Models\User;
use App\Models\UserWorkstream;
use App\Models\Workstream;
use Carbon\CarbonPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedTestCompany extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = $this->company();

            $this->deleteUserTimeData($company);
            $this->deleteWorkstreamData($company);

            $workstreams = $this->createWorkstreams($company);
            $this->createCapacityModels($company, $workstreams);

            $workers = $this->workers($company);
            $assignments = $this->createWorkerSeniorities($company, $workers, $workstreams);
            $this->createWorkLogs($assignments);
            $this->createTimeoffRequests($company);
            $this->createTimelogs($company);
        });
    }

    private function company(): Company
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'alex.manager@worksyne.local.test'],
            [
                'name' => 'Alex Manager',
                'password' => Hash::make('test'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_email_verified' => true,
                'is_blocked' => false,
            ],
        );

        $company = Company::query()->updateOrCreate(
            ['name' => 'Acme Operations'],
            [
                'owner_id' => $owner->id,
            ],
        );

        $this->assignCompanyUser($owner, $company, 'company_admin', 'approved', 'ACME-'.$owner->id);

        return $company;
    }

    private function deleteWorkstreamData(Company $company): void
    {
        $workstreamIds = Workstream::query()
            ->where('company_id', $company->id)
            ->pluck('id');

        if ($workstreamIds->isEmpty()) {
            return;
        }

        UserWorkstream::query()
            ->whereIn('workstream_id', $workstreamIds)
            ->delete();

        CapacityModel::query()
            ->where('company_id', $company->id)
            ->whereIn('workstream_id', $workstreamIds)
            ->delete();

        CompanyUserSeniority::query()
            ->where('company_id', $company->id)
            ->whereIn('workstream_id', $workstreamIds)
            ->delete();

        Workstream::query()
            ->where('company_id', $company->id)
            ->delete();
    }

    private function deleteUserTimeData(Company $company): void
    {
        $userIds = $this->companyUsers($company)->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $timelogIds = Timelog::query()
            ->whereIn('user_id', $userIds)
            ->pluck('id');

        if ($timelogIds->isNotEmpty()) {
            TimelogBreak::query()
                ->whereIn('timelog_id', $timelogIds)
                ->delete();
        }

        Timelog::query()
            ->whereIn('user_id', $userIds)
            ->delete();

        TimeoffRequest::query()
            ->whereIn('user_id', $userIds)
            ->delete();
    }

    private function createWorkstreams(Company $company): array
    {
        $workstreams = [];

        foreach (['calls', 'sales', 'chats'] as $name) {
            $workstreams[$name] = Workstream::query()->create([
                'company_id' => $company->id,
                'name' => $name,
            ]);
        }

        return $workstreams;
    }

    private function createCapacityModels(Company $company, array $workstreams): void
    {
        $rates = [
            'intern' => 10,
            'junior' => 18,
            'mid' => 28,
            'senior' => 40,
        ];

        foreach ($workstreams as $workstream) {
            foreach (CapacityModel::SENIORITIES as $seniority) {
                CapacityModel::query()->create([
                    'company_id' => $company->id,
                    'workstream_id' => $workstream->id,
                    'seniority' => $seniority,
                    'units_per_hour' => $rates[$seniority],
                ]);
            }
        }
    }

    private function workers(Company $company)
    {
        $workers = User::query()
            ->whereHas('companyUser', function ($query) use ($company) {
                $query->where('company_id', $company->id)
                    ->where('role', 'worker');
            })
            ->get();

        if ($workers->isNotEmpty()) {
            return $workers;
        }

        return collect(range(1, 8))->map(function ($number) use ($company) {
            $user = User::query()->updateOrCreate(
                ['email' => 'acme.worker'.$number.'@worksyne.local.test'],
                [
                    'name' => 'Acme Worker '.$number,
                    'password' => Hash::make('test'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                    'is_email_verified' => true,
                    'is_blocked' => false,
                ],
            );

            $this->assignCompanyUser($user, $company, 'worker', 'approved', 'ACME-WRK-'.$number);

            return $user;
        });
    }

    private function createWorkerSeniorities(Company $company, $workers, array $workstreams)
    {
        $seniorities = CapacityModel::SENIORITIES;
        $workstreamValues = collect($workstreams)->values();
        $assignments = collect();

        $workers->values()->each(function (User $worker, $workerIndex) use ($company, $seniorities, $workstreamValues, $assignments) {
            $workstreamsForWorker = $workstreamValues->filter(function ($workstream, $workstreamIndex) use ($workerIndex) {
                return $workstreamIndex === $workerIndex % 3 || ($workerIndex + $workstreamIndex) % 2 === 0;
            });

            if ($workstreamsForWorker->isEmpty()) {
                $workstreamsForWorker = collect([$workstreamValues[$workerIndex % $workstreamValues->count()]]);
            }

            $workstreamsForWorker->values()->each(function (Workstream $workstream, $assignmentIndex) use ($company, $seniorities, $worker, $assignments) {
                $seniority = $seniorities[($worker->id + $assignmentIndex) % count($seniorities)];

                CompanyUserSeniority::query()->create([
                    'company_id' => $company->id,
                    'user_id' => $worker->id,
                    'workstream_id' => $workstream->id,
                    'seniority' => $seniority,
                ]);

                $assignments->push([
                    'worker' => $worker,
                    'workstream' => $workstream,
                ]);
            });
        });

        return $assignments;
    }

    private function createWorkLogs($assignments): void
    {
        $period = CarbonPeriod::create(now()->subMonths(3)->startOfDay(), now()->startOfDay());
        $rows = [];

        foreach ($period as $day) {
            $entries = mt_rand(40, 50);

            foreach (range(1, $entries) as $entryNumber) {
                $createdAt = $day->copy()
                    ->addHours(mt_rand(8, 17))
                    ->addMinutes(mt_rand(0, 59))
                    ->addSeconds(mt_rand(0, 59));
                $assignment = $assignments->random();

                $rows[] = [
                    'user_id' => $assignment['worker']->id,
                    'workstream_id' => $assignment['workstream']->id,
                    'unique_code' => 'acme-'.$day->format('Ymd').'-'.$entryNumber,
                    'units' => mt_rand(1, 12),
                    'logged_on' => $day->toDateString(),
                    'reference_code' => 'ACME-'.$day->format('Ymd').'-'.$entryNumber,
                    'note' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                if (count($rows) >= 500) {
                    UserWorkstream::query()->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            UserWorkstream::query()->insert($rows);
        }
    }

    private function createTimeoffRequests(Company $company): void
    {
        $statuses = ['approved', 'approved', 'approved', 'pending', 'rejected'];
        $reasons = [
            'Family appointment',
            'Personal day',
            'Medical appointment',
            'Vacation',
            'Administrative leave',
        ];
        $rows = [];

        foreach ($this->companyUsers($company) as $user) {
            foreach (range(1, mt_rand(1, 4)) as $index) {
                $startDate = now()
                    ->subDays(mt_rand(1, 88))
                    ->startOfDay();
                $endDate = $startDate->copy()->addDays(mt_rand(0, 3));
                $createdAt = $startDate->copy()->subDays(mt_rand(3, 18));

                $rows[] = [
                    'user_id' => $user->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'timezone' => 'Europe/Bucharest',
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => $statuses[array_rand($statuses)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        if ($rows !== []) {
            TimeoffRequest::query()->insert($rows);
        }
    }

    private function createTimelogs(Company $company): void
    {
        $period = CarbonPeriod::create(now()->subMonths(3)->startOfDay(), now()->startOfDay());
        $users = $this->companyUsers($company);
        $minuteOptions = [0, 15, 30, 45];
        $rows = [];

        foreach ($period as $day) {
            if ($day->isWeekend()) {
                continue;
            }

            foreach ($users as $user) {
                $startTime = $day->copy()
                    ->addHours(mt_rand(7, 10))
                    ->addMinutes($minuteOptions[array_rand($minuteOptions)]);
                $endTime = $startTime->copy()
                    ->addHours(mt_rand(7, 9))
                    ->addMinutes(mt_rand(0, 45));

                $rows[] = [
                    'user_id' => $user->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'continuous_work_notified_at' => null,
                    'created_at' => $startTime,
                    'updated_at' => $endTime,
                ];

                if (count($rows) >= 500) {
                    Timelog::query()->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            Timelog::query()->insert($rows);
        }
    }

    private function companyUsers(Company $company)
    {
        return User::query()
            ->whereHas('companyUser', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->get();
    }

    private function assignCompanyUser(User $user, Company $company, string $role, string $status, string $externalId): CompanyUser
    {
        $attributes = [
            'company_id' => $company->id,
            'external_id' => $externalId,
            'role' => $role,
            'status' => $status,
        ];

        $companyUser = $user->company_user_id
            ? tap(CompanyUser::query()->findOrFail($user->company_user_id))->update($attributes)
            : CompanyUser::query()->create($attributes);

        $user->forceFill(['company_user_id' => $companyUser->id])->save();

        return $companyUser;
    }
}
