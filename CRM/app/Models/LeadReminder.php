<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'created_by',
    'completed_by',
    'scheduled_at',
    'description',
    'status',
    'completed_at',
])]
class LeadReminder extends Model
{
    public const STATUS_PENDING = 'Pendiente';
    public const STATUS_COMPLETED = 'Completado';

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('lead_reminders.status', self::STATUS_PENDING);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('lead', fn (Builder $leadQuery): Builder => $leadQuery->visibleTo($user));
    }

    public function scopeOperationallyVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->pending()
            ->whereHas('lead', fn (Builder $leadQuery): Builder => $leadQuery
                ->visibleTo($user)
                ->whereNotNull('assigned_user_id'));
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
