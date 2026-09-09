<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['promo_id', 'org_id'])]
class PromoRedemption extends Model
{
    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
