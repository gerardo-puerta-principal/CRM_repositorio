<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectToInstaller;
use App\Models\Lead;
use App\Models\LeadReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RedirectToInstaller::class);
    }

    public function test_metrics_summary_uses_overdue_pending_reminders_instead_of_legacy_reminder_at(): void
    {
        /** @var User $supervisor */
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'is_active' => true,
        ]);

        /** @var User $agent */
        $agent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'supervisor_id' => $supervisor->id,
            'is_active' => true,
        ]);

        /** @var User $outsider */
        $outsider = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        $visibleLead = Lead::query()->create([
            'name' => 'Lead visible',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $agent->id,
            'created_by' => $supervisor->id,
            'reminder_at' => now()->subDay()->setSecond(0),
        ]);

        LeadReminder::query()->create([
            'lead_id' => $visibleLead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHours(2)->setSecond(0),
            'description' => 'Recordatorio vencido visible.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $visibleLead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->addHours(4)->setSecond(0),
            'description' => 'No debe contar porque no esta vencido.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $visibleLead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHour()->setSecond(0),
            'description' => 'No debe contar porque ya esta completado.',
            'status' => LeadReminder::STATUS_COMPLETED,
            'completed_at' => now()->setSecond(0),
            'completed_by' => $supervisor->id,
        ]);

        $unassignedLead = Lead::query()->create([
            'name' => 'Lead sin asignar',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => null,
            'created_by' => $supervisor->id,
            'reminder_at' => now()->subDay()->setSecond(0),
        ]);

        LeadReminder::query()->create([
            'lead_id' => $unassignedLead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHours(3)->setSecond(0),
            'description' => 'No debe contar por no tener agente asignado.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $outsiderLead = Lead::query()->create([
            'name' => 'Lead no visible',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $outsider->id,
            'created_by' => $outsider->id,
            'reminder_at' => now()->subDay()->setSecond(0),
        ]);

        LeadReminder::query()->create([
            'lead_id' => $outsiderLead->id,
            'created_by' => $outsider->id,
            'scheduled_at' => now()->subHours(4)->setSecond(0),
            'description' => 'No debe contar por visibilidad.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        Lead::query()->create([
            'name' => 'Solo legacy',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $agent->id,
            'created_by' => $supervisor->id,
            'reminder_at' => now()->subHours(6)->setSecond(0),
        ]);

        $response = $this->actingAs($supervisor)->get(route('metrics.index'));

        $response->assertOk();
        $response->assertViewHas('summary', function (array $summary): bool {
            return $summary['total_leads'] === 3
                && $summary['unassigned_leads'] === 1
                && $summary['overdue_pending_reminders'] === 1;
        });
    }

    public function test_metrics_rows_group_overdue_pending_reminders_by_current_assigned_agent(): void
    {
        /** @var User $supervisor */
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'is_active' => true,
        ]);

        /** @var User $agentA */
        $agentA = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'supervisor_id' => $supervisor->id,
            'is_active' => true,
        ]);

        /** @var User $agentB */
        $agentB = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'supervisor_id' => $supervisor->id,
            'is_active' => true,
        ]);

        $leadA = Lead::query()->create([
            'name' => 'Lead A',
            'status' => Lead::STATUS_CONTACTED,
            'assigned_user_id' => $agentA->id,
            'created_by' => $supervisor->id,
        ]);

        $leadB = Lead::query()->create([
            'name' => 'Lead B',
            'status' => Lead::STATUS_INTERESTED,
            'assigned_user_id' => $agentB->id,
            'created_by' => $supervisor->id,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $leadA->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHours(2)->setSecond(0),
            'description' => 'Vencido A1',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $leadA->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHour()->setSecond(0),
            'description' => 'Vencido A2',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $leadB->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHours(3)->setSecond(0),
            'description' => 'Vencido B1',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $leadB->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->addHours(2)->setSecond(0),
            'description' => 'No vencido B2',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $response = $this->actingAs($supervisor)->get(route('metrics.index'));

        $response->assertOk();
        $response->assertViewHas('metricRows', function (array $rows) use ($agentA, $agentB): bool {
            $rowsByAgent = collect($rows)->keyBy('agent_id');

            return ($rowsByAgent[$agentA->id]['overdue_reminders'] ?? null) === 2
                && ($rowsByAgent[$agentB->id]['overdue_reminders'] ?? null) === 1;
        });
    }
}
