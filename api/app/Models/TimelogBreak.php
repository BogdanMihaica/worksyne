<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'user_id', 'start_time', 'end_time', 'created_at', 'updated_at'])]
class TimelogBreak extends Model
{
    protected $table = 'timelog_break';

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function timelog(): BelongsTo
    {
        return $this->belongsTo(Timelog::class, 'timelog_id');
    }
}
