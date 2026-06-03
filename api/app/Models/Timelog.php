<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'user_id', 'start_time', 'end_time', 'continuous_work_notified_at', 'created_at', 'updated_at'])]
class Timelog extends Model
{
    protected $table = 'timelog';

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'continuous_work_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(TimelogBreak::class, 'timelog_id');
    }
}
