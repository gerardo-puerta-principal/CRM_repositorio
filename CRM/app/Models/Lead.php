<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'phone',
    'email',
    'city',
    'type',
    'source',
    'status',
    'assigned_user_id',
    'created_by',
    'reminder_at',
    'last_contact_at',
    'import_file_name',
    'imported_at',
])]
class Lead extends Model
{
    use SoftDeletes;

    public const STATUS_NEW = 'Nuevo';
    public const STATUS_PENDING_CALL = 'Por llamar';
    public const STATUS_NO_ANSWER = 'No contesta';
    public const STATUS_CONTACTED = 'Contactado';
    public const STATUS_INTERESTED = 'Interesado';
    public const STATUS_APPOINTMENT = 'Cita agendada';
    public const STATUS_CLOSED = 'Cerrado';
    public const STATUS_LOST = 'Perdido';

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeadLog::class)->latest('created_at');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(LeadReminder::class)->orderBy('scheduled_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isSupervisor()) {
            $teamAgentIds = $user->agents()
                ->where('is_active', true)
                ->pluck('id');
            $teamCreatorIds = $teamAgentIds
                ->push($user->id)
                ->unique()
                ->values();

            return $query->where(function (Builder $nestedQuery) use ($teamAgentIds, $teamCreatorIds): void {
                $nestedQuery
                    ->whereIn('assigned_user_id', $teamAgentIds)
                    ->orWhere(function (Builder $unassignedQuery) use ($teamCreatorIds): void {
                        $unassignedQuery
                            ->whereNull('assigned_user_id')
                            ->whereIn('created_by', $teamCreatorIds);
                    });
            });
        }

        return $query->where('assigned_user_id', $user->id);
    }

    protected function casts(): array
    {
        return [
            'reminder_at' => 'datetime',
            'last_contact_at' => 'datetime',
            'imported_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
