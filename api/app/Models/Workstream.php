<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'company_id', 'name', 'created_at', 'updated_at'])]
class Workstream extends Model
{
    protected $table = 'workstream';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function seniorities(): HasMany
    {
        return $this->hasMany(CompanyUserSeniority::class);
    }

    public function capacityModels(): HasMany
    {
        return $this->hasMany(CapacityModel::class);
    }

    public function userWorkstreams(): HasMany
    {
        return $this->hasMany(UserWorkstream::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_workstream')
            ->withPivot(['unique_code', 'units'])
            ->withTimestamps();
    }
}
