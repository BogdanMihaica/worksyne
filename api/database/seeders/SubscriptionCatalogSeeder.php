<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Company;
use Illuminate\Database\Seeder;

class SubscriptionCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            'company-timeoff' => Feature::query()->updateOrCreate(
                ['key' => 'company-timeoff'],
                [
                    'name' => 'Company timesheet and timeoffs',
                    'description' => 'Review company timesheets and manage timeoff requests.',
                ],
            ),
            'time-logging' => Feature::query()->updateOrCreate(
                ['key' => 'time-logging'],
                [
                    'name' => 'Time logging',
                    'description' => 'Track work sessions and log completed units.',
                ],
            ),
            'notifications' => Feature::query()->updateOrCreate(
                ['key' => 'notifications'],
                [
                    'name' => 'Notification system',
                    'description' => 'Send, receive, and manage operational notifications.',
                ],
            ),
            'capacity-models' => Feature::query()->updateOrCreate(
                ['key' => 'capacity-models'],
                [
                    'name' => 'Capacity models',
                    'description' => 'Configure capacity by workstream and seniority.',
                ],
            ),
            'forecast' => Feature::query()->updateOrCreate(
                ['key' => 'forecast'],
                [
                    'name' => 'Workload forecast',
                    'description' => 'Forecast workload, available capacity, and gaps.',
                ],
            ),
            'dashboard-flashcards' => Feature::query()->updateOrCreate(
                ['key' => 'dashboard-flashcards'],
                [
                    'name' => 'Dashboard flash cards',
                    'description' => 'Surface smart forecast signals on the dashboard.',
                ],
            ),
        ];

        $free = SubscriptionPlan::query()->updateOrCreate(
            ['name' => 'Free'],
            ['price' => 0],
        );

        $pro = SubscriptionPlan::query()->updateOrCreate(
            ['name' => 'Pro'],
            ['price' => 9.99],
        );

        $enterprise = SubscriptionPlan::query()->updateOrCreate(
            ['name' => 'Enterprise'],
            ['price' => 17.99],
        );

        $free->features()->sync([]);

        $pro->features()->sync([
            $features['company-timeoff']->id,
            $features['time-logging']->id,
            $features['notifications']->id,
        ]);

        $enterprise->features()->sync([
            $features['company-timeoff']->id,
            $features['time-logging']->id,
            $features['notifications']->id,
            $features['capacity-models']->id,
            $features['forecast']->id,
            $features['dashboard-flashcards']->id,
        ]);

        $this->replaceLegacyPlan('Starter', $free);
        $this->replaceLegacyPlan('Growth', $pro);
    }

    private function replaceLegacyPlan(string $legacyName, SubscriptionPlan $replacement): void
    {
        $legacy = SubscriptionPlan::query()->where('name', $legacyName)->first();

        if (! $legacy) {
            return;
        }

        Company::query()
            ->where('subscription_plan_id', $legacy->id)
            ->update(['subscription_plan_id' => $replacement->id]);

        Subscription::query()
            ->where('subscription_plan_id', $legacy->id)
            ->update(['subscription_plan_id' => $replacement->id]);

        Order::query()
            ->where('subscription_plan_id', $legacy->id)
            ->update(['subscription_plan_id' => $replacement->id]);

        $legacy->features()->detach();
        $legacy->delete();
    }
}
