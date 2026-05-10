<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'user_id',
    'name',
    'token_hash',
    'last_used_at',
    'expires_at',
    'revoked_at',
    'ip_address',
    'user_agent',
    'created_at',
    'updated_at',
])]
class AuthToken extends Model
{
    protected $table = 'auth_token';

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
