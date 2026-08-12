<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadReminder\LeadReminderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    public function index(Request $request, LeadReminderService $leadReminderService)
    {
        $user = $request->user();
        $period = $this->resolvePeriod((string) $request->query('period', 'today'));
        $agents = $this->agentsForMetrics($user);
        $statusRows = $this->statusRows($user);
        $metricRows = $this->metricRows($user, $agents, $period, $leadReminderService);

        return view('metrics.index', [
            'summary' => [
                'total_leads' => Lead::query()->visibleTo($user)->count(),
                'unassigned_leads' => Lead::query()->visibleTo($user)->whereNull('assigned_user_id')->count(),
                'overdue_pending_reminders' => $leadReminderService->overdueOperationalCountFor($user),
            ],
            'statusRows' => $statusRows,
            'metricRows' => $metricRows,
            'selectedPeriod' => $period['key'],
            'periodOptions' => $this->periodOptions(),
            'resultColumns' => config('crm.interaction_results', []),
            'statusColumns' => config('crm.lead_statuses', []),
        ]);
    }

    private function metricRows(User $user, Collection $agents, array $period, LeadReminderService $leadReminderService): array
    {
        $agentIds = $agents->pluck('id')->all();

        if ($agentIds === []) {
            return [];
        }

        $callsByAgent = $this->callsByAgent($user, $agentIds, $period);
        $workedByAgent = $this->workedLeadsByAgent($user, $agentIds, $period);
        $resultsByAgent = $this->callResultsByAgent($user, $agentIds, $period);
        $statusesByAgent = $this->pipelineStatusesByAgent($user, $agentIds);
        $staleByAgent = $this->staleLeadsByAgent($user, $agentIds, $period);
        $overdueByAgent = $this->overdueRemindersByAgent($user, $agentIds, $leadReminderService);
        $resultColumns = config('crm.interaction_results', []);
        $statusColumns = config('crm.lead_statuses', []);

        return $agents->map(function (User $agent) use (
            $callsByAgent,
            $workedByAgent,
            $resultsByAgent,
            $statusesByAgent,
            $staleByAgent,
            $overdueByAgent,
            $resultColumns,
            $statusColumns
        ): array {
            $agentId = (int) $agent->id;

            return [
                'agent_id' => $agentId,
                'agent_name' => $agent->name,
                'calls_made' => (int) ($callsByAgent[$agentId] ?? 0),
                'leads_worked' => (int) ($workedByAgent[$agentId] ?? 0),
                'call_results' => collect($resultColumns)->mapWithKeys(
                    fn (string $result): array => [$result => (int) ($resultsByAgent[$agentId][$result] ?? 0)]
                )->all(),
                'pipeline_statuses' => collect($statusColumns)->mapWithKeys(
                    fn (string $status): array => [$status => (int) ($statusesByAgent[$agentId][$status] ?? 0)]
                )->all(),
                'stale_leads' => (int) ($staleByAgent[$agentId] ?? 0),
                'overdue_reminders' => (int) ($overdueByAgent[$agentId] ?? 0),
            ];
        })->all();
    }

    private function agentsForMetrics(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return User::query()
                ->where('role', User::ROLE_AGENT)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        if ($user->isSupervisor()) {
            return $user->agents()
                ->where('role', User::ROLE_AGENT)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return collect();
    }

    private function statusRows(User $user): array
    {
        $counts = Lead::query()
            ->visibleTo($user)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(config('crm.lead_statuses', []))
            ->map(fn (string $status): array => [
                'status' => $status,
                'total' => (int) ($counts[$status] ?? 0),
            ])
            ->all();
    }

    private function callsByAgent(User $user, array $agentIds, array $period): array
    {
        return DB::table('lead_logs')
            ->whereIn('user_id', $agentIds)
            ->where('action', 'Llamada registrada')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->whereIn('lead_id', $this->visibleLeadIdsQuery($user))
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function workedLeadsByAgent(User $user, array $agentIds, array $period): array
    {
        return DB::table('lead_logs')
            ->whereIn('user_id', $agentIds)
            ->whereIn('action', [
                'Llamada registrada',
                'Estado actualizado',
                'Recordatorio actualizado',
                'Recordatorio creado',
                'Recordatorio completado',
            ])
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->whereIn('lead_id', $this->visibleLeadIdsQuery($user))
            ->select('user_id', DB::raw('COUNT(DISTINCT lead_id) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function callResultsByAgent(User $user, array $agentIds, array $period): array
    {
        $rows = DB::table('lead_logs')
            ->whereIn('user_id', $agentIds)
            ->where('action', 'Llamada registrada')
            ->whereBetween('created_at', [$period['start'], $period['end']])
            ->whereIn('lead_id', $this->visibleLeadIdsQuery($user))
            ->whereNotNull('result')
            ->select('user_id', 'result', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id', 'result')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row->user_id][$row->result] = (int) $row->total;
        }

        return $grouped;
    }

    private function pipelineStatusesByAgent(User $user, array $agentIds): array
    {
        $rows = Lead::query()
            ->visibleTo($user)
            ->whereIn('assigned_user_id', $agentIds)
            ->select('assigned_user_id', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('assigned_user_id', 'status')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row->assigned_user_id][$row->status] = (int) $row->total;
        }

        return $grouped;
    }

    private function staleLeadsByAgent(User $user, array $agentIds, array $period): array
    {
        return Lead::query()
            ->visibleTo($user)
            ->whereIn('assigned_user_id', $agentIds)
            ->where(function ($query) use ($period): void {
                $query
                    ->whereNull('last_contact_at')
                    ->orWhere('last_contact_at', '<', $period['start']);
            })
            ->select('assigned_user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('assigned_user_id')
            ->pluck('total', 'assigned_user_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function overdueRemindersByAgent(User $user, array $agentIds, LeadReminderService $leadReminderService): array
    {
        return $leadReminderService->overdueOperationalCountsByAssignedAgent($user, $agentIds);
    }

    private function visibleLeadIdsQuery(User $user)
    {
        return Lead::query()
            ->visibleTo($user)
            ->select('id')
            ->toBase();
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            '7d' => [
                'key' => '7d',
                'label' => 'Ultimos 7 dias',
                'start' => now()->copy()->startOfDay()->subDays(6),
                'end' => now(),
            ],
            '30d' => [
                'key' => '30d',
                'label' => 'Ultimos 30 dias',
                'start' => now()->copy()->startOfDay()->subDays(29),
                'end' => now(),
            ],
            default => [
                'key' => 'today',
                'label' => 'Hoy',
                'start' => now()->copy()->startOfDay(),
                'end' => now(),
            ],
        };
    }

    private function periodOptions(): array
    {
        return [
            ['key' => 'today', 'label' => 'Hoy'],
            ['key' => '7d', 'label' => 'Ultimos 7 dias'],
            ['key' => '30d', 'label' => 'Ultimos 30 dias'],
        ];
    }
}
