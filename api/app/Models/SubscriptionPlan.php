<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'name', 'price', 'created_at', 'updated_at'])]
class SubscriptionPlan extends Model
{
    protected $table = 'subscription_plan';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'subscription_plan_feature')
            ->withTimestamps();
    }

    public function featureAssignments(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }
}
