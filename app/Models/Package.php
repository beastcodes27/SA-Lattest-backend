<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'tagline',
    'price_label',
    'features',
    'employee_limit',
    'branch_limit',
    'active',
    'position',
])]
class Package extends Model
{
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'active' => 'boolean',
        ];
    }

    public function isUnlimitedEmployee(): bool
    {
        return $this->employee_limit === null;
    }

    public function isUnlimitedBranch(): bool
    {
        return $this->branch_limit === null;
    }
}
