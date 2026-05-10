<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'name', 'key', 'description', 'created_at', 'updated_at'])]
class Feature extends Model
{
    protected $table = 'feature';

    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'subscription_plan_feature')
            ->withTimestamps();
    }

    public function planAssignments(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }
}
