<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'contact_email',
    'contact_phone',
    'address',
    'website',
    'tin',
    'employee_id_prefix',
    'plan',
    'status',
])]
class Organization extends Model
{
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'org_id');
    }

    public function activeBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'org_id')->where('active', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }
}
