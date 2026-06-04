<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Timelog;
use App\Models\TimelogBreak;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('worksyne:check-time-alerts')]
#[Description('Create notifications for long continuous work times and long breaks.')]
class CheckTimeAlerts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $continuousWorkNotifications = $this->notifyLongContinuousWork();
        $longBreakNotifications = $this->notifyLongBreaks();

        $this->info(sprintf(
            'Created %d continuous work notifications and %d long break notifications.',
            $continuousWorkNotifications,
            $longBreakNotifications,
        ));

        return self::SUCCESS;
    }

    private function notifyLongContinuousWork()
    {
        $notifications = 0;

        Timelog::query()
            ->with(['user.companyUser', 'breaks'])
            ->whereNull('end_time')
            ->whereNull('continuous_work_notified_at')
            ->chunkById(100, function ($timelogs) use (&$notifications) {
                foreach ($timelogs as $timelog) {
                    $breakSeconds = $timelog->breaks->sum(function ($break) {
                        $breakEnd = $break->end_time ?? now();

                        return $break->start_time->diffInSeconds($breakEnd);
                    });

                    $activeWorkSeconds = $timelog->start_time->diffInSeconds(now()) - $breakSeconds;

                    if ($activeWorkSeconds < 8 * 60 * 60) {
                        continue;
                    }

                    Notification::notify(
                        $timelog->user_id,
                        'You have been working for over 8 hours in one session.',
                        null,
                    );
                    $this->notifyCompanyAdmins(
                        $timelog->user,
                        sprintf('%s has been working for over 8 hours in one session.', $timelog->user->name),
                    );

                    $timelog->update([
                        'continuous_work_notified_at' => now(),
                    ]);

                    $notifications++;
                }
            });

        return $notifications;
    }

    private function notifyLongBreaks()
    {
        $notifications = 0;

        TimelogBreak::query()
            ->with('timelog.user.companyUser')
            ->whereNull('end_time')
            ->whereNull('long_break_notified_at')
            ->where('start_time', '<=', now()->subHours(2))
            ->chunkById(100, function ($breaks) use (&$notifications) {
                foreach ($breaks as $break) {
                    if (! $break->timelog?->user) {
                        continue;
                    }

                    Notification::notify(
                        $break->timelog->user_id,
                        'Your break has been running for over 2 hours.',
                        null,
                    );
                    $this->notifyCompanyAdmins(
                        $break->timelog->user,
                        sprintf('%s has been on break for over 2 hours.', $break->timelog->user->name),
                    );

                    $break->update([
                        'long_break_notified_at' => now(),
                    ]);

                    $notifications++;
                }
            });

        return $notifications;
    }

    private function notifyCompanyAdmins($user, $message)
    {
        $companyId = $user->companyUser?->company_id;

        if (! $companyId) {
            return;
        }

        User::query()
            ->whereKeyNot($user->getKey())
            ->whereHas('companyUser', function ($query) use ($companyId) {
                $query
                    ->where('company_id', $companyId)
                    ->where('role', 'company_admin')
                    ->where('status', 'approved');
            })
            ->pluck('id')
            ->each(function ($userId) use ($message) {
                Notification::notify($userId, $message, null);
            });
    }
}
