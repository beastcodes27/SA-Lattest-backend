<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'org_id',
    'name',
    'lat',
    'lng',
    'radius_meters',
    'check_in_time',
    'grace_period_minutes',
    'check_out_time',
    'active',
])]
class Branch extends Model
{
    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'radius_meters' => 'integer',
            'grace_period_minutes' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function lateThresholdMinutes(): int
    {
        $time = $this->check_in_time ?: '09:00';
        $parts = explode(':', $time);
        $hours = isset($parts[0]) ? (int) $parts[0] : 9;
        $minutes = isset($parts[1]) ? (int) $parts[1] : 0;
        $grace = (int) ($this->grace_period_minutes ?? 15);

        return ($hours * 60 + $minutes) + $grace;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branch_id');
    }
}
