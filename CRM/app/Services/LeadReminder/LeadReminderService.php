<?php

namespace App\Services\LeadReminder;

use App\Models\Lead;
use App\Models\LeadLog;
use App\Models\LeadReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadReminderService
{
    public const DEFAULT_INBOX_LIMIT = 6;

    public function create(Lead $lead, array $data, int $userId): LeadReminder
    {
        return DB::transaction(function () use ($lead, $data, $userId): LeadReminder {
            $reminder = LeadReminder::query()->create([
                'lead_id' => $lead->id,
                'created_by' => $userId,
                'scheduled_at' => $data['scheduled_at'],
                'description' => trim((string) $data['description']),
                'status' => LeadReminder::STATUS_PENDING,
            ]);

            $this->syncLegacyReminderAt($lead);
            $this->createLeadLog($lead, $userId, 'Recordatorio creado', [
                'note' => $reminder->description,
                'to_status' => $lead->status,
                'meta_json' => [
                    'reminder_id' => $reminder->id,
                    'scheduled_at' => $reminder->scheduled_at?->toDateTimeString(),
                ],
            ]);

            return $reminder;
        });
    }

    public function complete(Lead $lead, LeadReminder $reminder, int $userId): void
    {
        if (! $reminder->isPending()) {
            throw ValidationException::withMessages([
                'reminder' => 'Solo se pueden completar recordatorios pendientes.',
            ]);
        }

        DB::transaction(function () use ($lead, $reminder, $userId): void {
            $reminder->update([
                'status' => LeadReminder::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => $userId,
            ]);

            $this->syncLegacyReminderAt($lead);
            $this->createLeadLog($lead, $userId, 'Recordatorio completado', [
                'note' => $reminder->description,
                'to_status' => $lead->status,
                'meta_json' => [
                    'reminder_id' => $reminder->id,
                    'scheduled_at' => $reminder->scheduled_at?->toDateTimeString(),
                    'completed_at' => $reminder->completed_at?->toDateTimeString(),
                ],
            ]);
        });
    }

    public function syncLegacyReminderAt(Lead $lead): void
    {
        $nextReminderAt = LeadReminder::query()
            ->where('lead_id', $lead->id)
            ->where('status', LeadReminder::STATUS_PENDING)
            ->orderBy('scheduled_at')
            ->value('scheduled_at');

        $lead->update([
            'reminder_at' => $nextReminderAt,
        ]);
    }

    public function operationalInboxCountFor(User $user): int
    {
        return $this->baseOperationalInboxQuery($user)->count();
    }

    public function operationalInboxItemsFor(User $user, int $limit = self::DEFAULT_INBOX_LIMIT): Collection
    {
        return $this->baseOperationalInboxQuery($user)
            ->with([
                'lead' => fn ($query) => $query->select('id', 'name', 'status', 'assigned_user_id'),
            ])
            ->orderByRaw('CASE WHEN lead_reminders.scheduled_at <= ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('lead_reminders.scheduled_at')
            ->limit($limit)
            ->get();
    }

    public function overdueOperationalCountFor(User $user): int
    {
        return $this->baseOverdueOperationalQuery($user)->count();
    }

    public function overdueOperationalCountsByAssignedAgent(User $user, array $agentIds): array
    {
        if ($agentIds === []) {
            return [];
        }

        return $this->baseOverdueOperationalQuery($user)
            ->join('leads', 'leads.id', '=', 'lead_reminders.lead_id')
            ->whereIn('leads.assigned_user_id', $agentIds)
            ->select('leads.assigned_user_id', DB::raw('COUNT(lead_reminders.id) as total'))
            ->groupBy('leads.assigned_user_id')
            ->pluck('total', 'leads.assigned_user_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function baseOperationalInboxQuery(User $user)
    {
        return LeadReminder::query()
            ->operationallyVisibleTo($user);
    }

    private function baseOverdueOperationalQuery(User $user)
    {
        return $this->baseOperationalInboxQuery($user)
            ->where('lead_reminders.scheduled_at', '<=', now());
    }

    private function createLeadLog(Lead $lead, ?int $userId, string $action, array $data = []): void
    {
        LeadLog::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'action' => $action,
            'result' => $data['result'] ?? null,
            'note' => $data['note'] ?? null,
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'] ?? null,
            'meta_json' => $data['meta_json'] ?? null,
            'created_at' => now(),
        ]);
    }
}
