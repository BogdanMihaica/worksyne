<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'company_id', 'workstream_id', 'seniority', 'units_per_hour', 'created_at', 'updated_at'])]
class CapacityModel extends Model
{
    public const SENIORITIES = ['intern', 'junior', 'mid', 'senior'];

    protected $table = 'capacity_model';

    protected $casts = [
        'units_per_hour' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workstream(): BelongsTo
    {
        return $this->belongsTo(Workstream::class);
    }
}
