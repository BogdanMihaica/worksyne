<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['id', 'company_id', 'role', 'status', 'created_at', 'updated_at'])]
class CompanyUser extends Model
{
    protected $table = 'company_user';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
