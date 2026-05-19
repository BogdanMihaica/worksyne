<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['id', 'subscription_plan_id', 'name', 'owner_id', 'created_at', 'updated_at'])]
class Company extends Model
{
    protected $table = 'company';

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function workstreams(): HasMany
    {
        return $this->hasMany(Workstream::class);
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            CompanyUser::class,
            'company_id',
            'company_user_id',
            'id',
            'id',
        );
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function seniorities(): HasMany
    {
        return $this->hasMany(CompanyUserSeniority::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
