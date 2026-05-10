<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'user_id', 'workstream_id', 'created_at', 'updated_at'])]
class UserWorkstream extends Model
{
    protected $table = 'user_workstream';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workstream(): BelongsTo
    {
        return $this->belongsTo(Workstream::class);
    }
}
