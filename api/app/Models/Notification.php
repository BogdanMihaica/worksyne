<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'from_id', 'to_id', 'message', 'is_read', 'created_at', 'updated_at'])]
class Notification extends Model
{
    protected $table = 'notification';

    public static function notify($toId, $message, $fromId = null)
    {
        return self::query()->create([
            'from_id' => $fromId,
            'to_id' => $toId,
            'message' => $message,
        ]);
    }

    protected function casts()
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_id');
    }
}
