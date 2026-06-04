<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'id',
    'company_user_id',
    'name',
    'email',
    'email_verified_at',
    'password',
    'remember_token',
    'created_at',
    'updated_at',
    'is_admin',
    'is_email_verified',
    'is_blocked',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_email_verified' => 'boolean',
            'is_blocked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function authTokens(): HasMany
    {
        return $this->hasMany(AuthToken::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class);
    }

    public function seniorities(): HasMany
    {
        return $this->hasMany(CompanyUserSeniority::class);
    }

    public function timeoffRequests(): HasMany
    {
        return $this->hasMany(TimeoffRequest::class);
    }

    public function userWorkstreams(): HasMany
    {
        return $this->hasMany(UserWorkstream::class);
    }

    public function workstreams(): BelongsToMany
    {
        return $this->belongsToMany(Workstream::class, 'user_workstream')
            ->withPivot(['unique_code', 'units'])
            ->withTimestamps();
    }

    public function timelogs()
    {
        return $this->hasMany(Timelog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'to_id');
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'from_id');
    }
}
