<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'user_id', 'message', 'is_read', 'created_at', 'updated_at'])]
class Notification extends Model
{
    protected $table = 'notification';

    public static function notify($userId, $message)
    {
        return self::query()->create([
            'user_id' => $userId,
            'message' => $message,
        ]);
    }

    protected function casts()
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
