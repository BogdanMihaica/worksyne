<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'user_id', 'workstream_id', 'unique_code', 'units', 'logged_on', 'reference_code', 'note', 'created_at', 'updated_at'])]
class UserWorkstream extends Model
{
    protected $table = 'user_workstream';

    protected function casts(): array
    {
        return [
            'units' => 'integer',
            'logged_on' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workstream(): BelongsTo
    {
        return $this->belongsTo(Workstream::class);
    }
}
