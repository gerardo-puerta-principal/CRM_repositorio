<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $statuses = config('crm.lead_statuses', []);

        $leads = Lead::query()
            ->visibleTo($user)
            ->with(['assignedUser', 'creator'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'search' => $search,
            'status' => $status,
            'statuses' => $statuses,
            'assignableAgents' => $this->assignableAgentsFor($user),
            'canManageAssignments' => $this->canManageAssignments($user),
        ]);
    }

    public function create()
    {
        return view('leads.create', [
            'statuses' => config('crm.lead_statuses', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:name'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(config('crm.lead_statuses', []))],
        ]);

        $lead = Lead::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $this->createLeadLog($lead, $request->user()->id, 'Creado', [
            'note' => 'Lead creado manualmente.',
            'to_status' => $lead->status,
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Lead creado correctamente.');
    }

    public function show(Lead $lead)
    {
        $this->abortIfCannotViewLead(request()->user(), $lead);
        $lead->load(['assignedUser', 'creator', 'logs.user', 'reminders.creator', 'reminders.completer']);
        $user = request()->user();
        $pendingReminders = $lead->reminders
            ->where('status', 'Pendiente')
            ->sortBy('scheduled_at')
            ->values();
        $completedReminders = $lead->reminders
            ->where('status', 'Completado')
            ->sortByDesc('completed_at')
            ->values();

        return view('leads.show', [
            'lead' => $lead,
            'statuses' => config('crm.lead_statuses', []),
            'interactionResults' => config('crm.interaction_results', []),
            'assignableAgents' => $this->assignableAgentsFor($user),
            'canManageAssignments' => $this->canManageAssignments($user),
            'pendingReminders' => $pendingReminders,
            'completedReminders' => $completedReminders,
            'nextPendingReminder' => $pendingReminders->first(),
        ]);
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->abortIfCannotViewLead($request->user(), $lead);
        $validated = $request->validate([
            'status' => ['required', Rule::in(config('crm.lead_statuses', []))],
            'note' => ['nullable', 'string'],
        ]);

        if (
            $validated['status'] === Lead::STATUS_CONTACTED
            && trim((string) ($validated['note'] ?? '')) === ''
        ) {
            return back()
                ->withErrors(['status_note' => 'La nota es obligatoria cuando el estado cambia a Contactado.'])
                ->withInput();
        }

        $fromStatus = $lead->status;

        $lead->update([
            'status' => $validated['status'],
        ]);

        $this->createLeadLog($lead, $request->user()->id, 'Estado actualizado', [
            'note' => $validated['note'] ?? null,
            'from_status' => $fromStatus,
            'to_status' => $validated['status'],
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Estado actualizado correctamente.');
    }

    public function storeInteraction(Request $request, Lead $lead)
    {
        $this->abortIfCannotViewLead($request->user(), $lead);
        $validated = $request->validate([
            'result' => ['required', Rule::in(config('crm.interaction_results', []))],
            'note' => ['nullable', 'string'],
        ]);

        $lead->update([
            'last_contact_at' => now(),
        ]);

        $this->createLeadLog($lead, $request->user()->id, 'Llamada registrada', [
            'result' => $validated['result'],
            'note' => $validated['note'] ?? null,
            'to_status' => $lead->status,
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Interaccion registrada correctamente.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->abortIfCannotViewLead($request->user(), $lead);
        $this->abortIfCannotManageAssignments($request->user());

        $assignableAgents = $this->assignableAgentsFor($request->user());

        $validated = $request->validate([
            'assigned_user_id' => ['nullable', 'integer', Rule::in($assignableAgents->pluck('id')->all())],
        ]);

        $fromUser = $lead->assignedUser;
        $toUser = $assignableAgents->firstWhere('id', (int) ($validated['assigned_user_id'] ?? 0));

        $lead->update([
            'assigned_user_id' => $toUser?->id,
        ]);

        $this->createLeadLog($lead, $request->user()->id, 'Asignacion actualizada', [
            'note' => $toUser !== null
                ? 'Lead asignado a '.$toUser->name.'.'
                : 'Lead marcado como sin asignar.',
            'to_status' => $lead->status,
            'meta_json' => [
                'from_user' => $fromUser?->name,
                'to_user' => $toUser?->name,
            ],
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Asignacion actualizada correctamente.');
    }

    public function roundRobin(Request $request)
    {
        $user = $request->user();

        $this->abortIfCannotManageAssignments($user);

        $assignableAgents = $this->assignableAgentsFor($user);
        $validated = $request->validate([
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['required', 'integer', Rule::in($assignableAgents->pluck('id')->all())],
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $selectedAgents = $assignableAgents
            ->whereIn('id', $validated['agent_ids'])
            ->values();

        if ($selectedAgents->isEmpty()) {
            return back()->withErrors(['agent_ids' => 'Selecciona al menos un agente valido para asignar leads.']);
        }

        $search = trim((string) ($validated['search'] ?? ''));
        $status = trim((string) ($validated['status'] ?? ''));

        $assignedCount = DB::transaction(function () use ($user, $search, $status, $selectedAgents): int {
            $leads = $this->baseVisibleLeadQuery($user, $search, $status)
                ->whereNull('assigned_user_id')
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($leads->isEmpty()) {
                return 0;
            }

            $index = 0;

            foreach ($leads as $lead) {
                $agent = $selectedAgents[$index % $selectedAgents->count()];

                $lead->update([
                    'assigned_user_id' => $agent->id,
                ]);

                $this->createLeadLog($lead, $user->id, 'Asignado por round robin', [
                    'note' => 'Lead asignado automaticamente a '.$agent->name.'.',
                    'to_status' => $lead->status,
                    'meta_json' => [
                        'assigned_user' => $agent->name,
                    ],
                ]);

                $index++;
            }

            return $leads->count();
        });

        if ($assignedCount === 0) {
            return back()->withErrors(['agent_ids' => 'No hay leads sin asignar para el filtro actual.']);
        }

        return redirect()
            ->route('leads.index', array_filter([
                'search' => $search,
                'status' => $status,
            ]))
            ->with('status', 'Round robin completado. Leads asignados: '.$assignedCount.'.');
    }

    public function destroy(Request $request, Lead $lead)
    {
        $user = $request->user();

        $this->abortIfCannotDeleteLead($user, $lead);

        DB::transaction(function () use ($lead, $user): void {
            $this->createLeadLog($lead, $user->id, 'Eliminado', [
                'note' => 'Lead eliminado logicamente por Super Admin.',
                'to_status' => $lead->status,
            ]);

            $lead->delete();
        });

        return redirect()
            ->route('leads.index')
            ->with('status', 'Lead eliminado correctamente.');
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

    private function baseVisibleLeadQuery(User $user, string $search = '', string $status = ''): Builder
    {
        return Lead::query()
            ->visibleTo($user)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            });
    }

    private function assignableAgentsFor(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return User::query()
                ->where('role', User::ROLE_AGENT)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        if ($user->isSupervisor()) {
            return User::query()
                ->where('role', User::ROLE_AGENT)
                ->where('supervisor_id', $user->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return new Collection();
    }

    private function canManageAssignments(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isSupervisor();
    }

    private function abortIfCannotManageAssignments(User $user): void
    {
        if (! $this->canManageAssignments($user)) {
            abort(403);
        }
    }

    private function abortIfCannotViewLead(User $user, Lead $lead): void
    {
        $canView = Lead::query()
            ->visibleTo($user)
            ->whereKey($lead->id)
            ->exists();

        if (! $canView) {
            abort(403);
        }
    }

    private function abortIfCannotDeleteLead(User $user, Lead $lead): void
    {
        if (! $user->isSuperAdmin()) {
            abort(403);
        }

        $this->abortIfCannotViewLead($user, $lead);
    }
}
