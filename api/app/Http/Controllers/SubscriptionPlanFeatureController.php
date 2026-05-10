<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlanFeatureController extends ApiResourceController
{
    protected string $modelClass = SubscriptionPlanFeature::class;

    protected function storeRules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plan,id'],
            'feature_id' => ['required', 'integer', 'exists:feature,id'],
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'subscription_plan_id' => ['sometimes', 'required', 'integer', 'exists:subscription_plan,id'],
            'feature_id' => ['sometimes', 'required', 'integer', 'exists:feature,id'],
        ];
    }
}
