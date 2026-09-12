<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'organization_id',
    'reason_category',
    'reason',
    'description',
    'start_date',
    'end_date',
    'status',
    'actioned_by_id',
    'admin_remarks',
    'actioned_at',
])]
class PermissionRequest extends Model
{
    public const REASON_CATEGORIES = [
        'sick_leave' => 'Sick / Medical Leave',
        'personal' => 'Personal / Family',
        'emergency' => 'Emergency',
        'vacation' => 'Vacation / Annual Leave',
        'official_duty' => 'Official Duty / Field Work',
        'half_day' => 'Half-Day Permission',
        'other' => 'Other',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'actioned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by_id');
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_name' => $this->user ? $this->user->name : null,
            'employee_id' => $this->user ? $this->user->employee_id : null,
            'employee_email' => $this->user ? $this->user->email : null,
            'employee_avatar' => $this->user && $this->user->avatar_path ? asset('storage/'.$this->user->avatar_path) : null,
            'reason_category' => $this->reason_category,
            'reason_category_label' => self::REASON_CATEGORIES[$this->reason_category] ?? ucfirst(str_replace('_', ' ', $this->reason_category)),
            'reason' => $this->reason,
            'description' => $this->description,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'status' => $this->status,
            'admin_remarks' => $this->admin_remarks,
            'actioned_by' => $this->actionedBy ? $this->actionedBy->name : null,
            'actioned_at' => $this->actioned_at ? $this->actioned_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}

