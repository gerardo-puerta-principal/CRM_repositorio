<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadReminder;
use App\Models\User;
use App\Services\LeadReminder\LeadReminderService;
use Illuminate\Http\Request;

class LeadReminderController extends Controller
{
    public function store(Request $request, Lead $lead, LeadReminderService $leadReminderService)
    {
        $this->abortIfCannotViewLead($request->user(), $lead);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $leadReminderService->create($lead, $validated, $request->user()->id);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Recordatorio creado correctamente.');
    }

    public function complete(Request $request, LeadReminder $reminder, LeadReminderService $leadReminderService)
    {
        $lead = $reminder->lead;

        $this->abortIfCannotViewLead($request->user(), $lead);

        $leadReminderService->complete($lead, $reminder, $request->user()->id);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'Recordatorio completado correctamente.');
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
}
