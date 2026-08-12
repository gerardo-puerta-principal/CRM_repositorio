<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectToInstaller;
use App\Models\Lead;
use App\Models\LeadReminder;
use App\Models\User;
use App\Services\LeadReminder\LeadReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(RedirectToInstaller::class);
    }

    public function test_a_user_can_create_multiple_reminders_and_the_legacy_field_tracks_the_next_pending_one(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead de prueba',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $later = now()->addDays(2)->setSecond(0);
        $earlier = now()->addDay()->setSecond(0);

        $this->actingAs($user)
            ->post(route('leads.reminders.store', $lead), [
                'scheduled_at' => $later->format('Y-m-d H:i:s'),
                'description' => 'Llamar mas adelante.',
            ])
            ->assertRedirect(route('leads.show', $lead));

        $this->actingAs($user)
            ->post(route('leads.reminders.store', $lead), [
                'scheduled_at' => $earlier->format('Y-m-d H:i:s'),
                'description' => 'Confirmar cita.',
            ])
            ->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseCount('lead_reminders', 2);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'reminder_at' => $earlier->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_completing_a_pending_reminder_updates_the_legacy_field_to_the_next_pending_one(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead de prueba',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $user->id,
            'created_by' => $user->id,
        ]);

        $firstReminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $user->id,
            'scheduled_at' => now()->addHour()->setSecond(0),
            'description' => 'Primer seguimiento.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $secondReminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $user->id,
            'scheduled_at' => now()->addDay()->setSecond(0),
            'description' => 'Segundo seguimiento.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $lead->update([
            'reminder_at' => $firstReminder->scheduled_at,
        ]);

        $this->actingAs($user)
            ->post(route('leads.reminders.complete', [$lead, $firstReminder]))
            ->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('lead_reminders', [
            'id' => $firstReminder->id,
            'status' => LeadReminder::STATUS_COMPLETED,
            'completed_by' => $user->id,
        ]);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'reminder_at' => $secondReminder->scheduled_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_a_reminder_can_be_created_for_an_unassigned_visible_lead(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead sin asignar',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => null,
            'created_by' => $superAdmin->id,
        ]);

        $scheduledAt = now()->addHours(3)->setSecond(0);

        $this->actingAs($superAdmin)
            ->post(route('leads.reminders.store', $lead), [
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'description' => 'Preparar seguimiento inicial.',
            ])
            ->assertRedirect(route('leads.show', $lead));

        $this->assertDatabaseHas('lead_reminders', [
            'lead_id' => $lead->id,
            'description' => 'Preparar seguimiento inicial.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'reminder_at' => $scheduledAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_a_user_cannot_create_a_reminder_for_a_non_visible_lead(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        /** @var User $intruder */
        $intruder = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead privado',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $owner->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($intruder)
            ->post(route('leads.reminders.store', $lead), [
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'description' => 'No deberia poder crearlo.',
            ])
            ->assertForbidden();
    }

    public function test_unassigned_pending_reminders_are_excluded_from_operational_visibility(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead sin asignacion operativa',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => null,
            'created_by' => $superAdmin->id,
        ]);

        $reminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $superAdmin->id,
            'scheduled_at' => now()->addHour()->setSecond(0),
            'description' => 'Pendiente sin asignacion.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $this->assertReminderIds([$reminder->id], LeadReminder::query()->visibleTo($superAdmin)->pluck('id')->all(), 'El recordatorio debe ser visible en el alcance general del lead.');
        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($superAdmin)->pluck('id')->all(), 'Los recordatorios de leads sin asignar no deben entrar en la visibilidad operativa.');
    }

    public function test_assigning_an_unassigned_lead_makes_its_pending_reminders_operationally_visible(): void
    {
        /** @var User $superAdmin */
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

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

        $lead = Lead::query()->create([
            'name' => 'Lead a asignar',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => null,
            'created_by' => $supervisor->id,
        ]);

        $reminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->addHours(2)->setSecond(0),
            'description' => 'Aparece al asignar.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($agent)->pluck('id')->all(), 'El agente no debe ver pendientes operativos antes de la asignacion.');
        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($supervisor)->pluck('id')->all(), 'El supervisor no debe ver pendientes operativos de leads aun sin asignar.');

        $lead->update([
            'assigned_user_id' => $agent->id,
        ]);

        $expected = [$reminder->id];

        $this->assertReminderIds($expected, LeadReminder::query()->operationallyVisibleTo($agent)->pluck('id')->all(), 'El agente asignado debe heredar visibilidad operativa del recordatorio.');
        $this->assertReminderIds($expected, LeadReminder::query()->operationallyVisibleTo($supervisor)->pluck('id')->all(), 'El supervisor debe ver los pendientes operativos visibles de su equipo.');
        $this->assertReminderIds($expected, LeadReminder::query()->operationallyVisibleTo($superAdmin)->pluck('id')->all(), 'El super admin debe ver los pendientes operativos visibles del sistema.');
        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($outsider)->pluck('id')->all(), 'Un agente ajeno no debe heredar visibilidad operativa.');
    }

    public function test_reassigning_a_lead_moves_pending_reminders_to_the_new_effective_agent_visibility(): void
    {
        /** @var User $supervisor */
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'is_active' => true,
        ]);

        /** @var User $firstAgent */
        $firstAgent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'supervisor_id' => $supervisor->id,
            'is_active' => true,
        ]);

        /** @var User $secondAgent */
        $secondAgent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'supervisor_id' => $supervisor->id,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead reasignable',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $firstAgent->id,
            'created_by' => $supervisor->id,
        ]);

        $reminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->addHours(5)->setSecond(0),
            'description' => 'Seguimiento que cambia de agente.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $this->assertReminderIds([$reminder->id], LeadReminder::query()->operationallyVisibleTo($firstAgent)->pluck('id')->all(), 'El primer agente debe ver el pendiente mientras el lead siga asignado a el.');
        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($secondAgent)->pluck('id')->all(), 'El segundo agente no debe ver el pendiente antes de la reasignacion.');

        $lead->update([
            'assigned_user_id' => $secondAgent->id,
        ]);

        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($firstAgent)->pluck('id')->all(), 'El agente anterior debe dejar de ver el pendiente tras la reasignacion.');
        $this->assertReminderIds([$reminder->id], LeadReminder::query()->operationallyVisibleTo($secondAgent)->pluck('id')->all(), 'El nuevo agente debe ver automaticamente el pendiente.');
        $this->assertReminderIds([$reminder->id], LeadReminder::query()->operationallyVisibleTo($supervisor)->pluck('id')->all(), 'El supervisor debe mantener visibilidad sobre el pendiente del equipo.');
    }

    public function test_completed_reminders_are_excluded_from_operational_visibility_even_when_the_lead_is_assigned(): void
    {
        /** @var User $agent */
        $agent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Lead con completado',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $agent->id,
            'created_by' => $agent->id,
        ]);

        $reminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $agent->id,
            'scheduled_at' => now()->subHour()->setSecond(0),
            'description' => 'Ya no debe salir en bandeja.',
            'status' => LeadReminder::STATUS_COMPLETED,
            'completed_at' => now()->setSecond(0),
            'completed_by' => $agent->id,
        ]);

        $this->assertReminderIds([$reminder->id], LeadReminder::query()->visibleTo($agent)->pluck('id')->all(), 'El recordatorio completado sigue siendo visible en el detalle del lead.');
        $this->assertReminderIds([], LeadReminder::query()->operationallyVisibleTo($agent)->pluck('id')->all(), 'Los recordatorios completados no deben entrar a la visibilidad operativa.');
    }

    public function test_operational_inbox_service_returns_count_and_items_ordered_by_overdue_first(): void
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

        $lead = Lead::query()->create([
            'name' => 'Lead con bandeja',
            'status' => Lead::STATUS_CONTACTED,
            'assigned_user_id' => $agent->id,
            'created_by' => $supervisor->id,
        ]);

        $overdueReminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->subHours(2)->setSecond(0),
            'description' => 'Llamada vencida.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $nextReminder = LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->addHour()->setSecond(0),
            'description' => 'Seguimiento proximo.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $lead->id,
            'created_by' => $supervisor->id,
            'scheduled_at' => now()->addHours(3)->setSecond(0),
            'description' => 'No debe contar porque ya esta completado.',
            'status' => LeadReminder::STATUS_COMPLETED,
            'completed_at' => now()->setSecond(0),
            'completed_by' => $supervisor->id,
        ]);

        $service = app(LeadReminderService::class);

        $this->assertValueSame(2, $service->operationalInboxCountFor($supervisor), 'La bandeja debe contar solo pendientes operativos visibles.');
        $this->assertReminderIds(
            [$overdueReminder->id, $nextReminder->id],
            $service->operationalInboxItemsFor($supervisor)->pluck('id')->all(),
            'La bandeja debe devolver vencidos y proximos visibles.'
        );
        $this->assertOrderedReminderIds(
            [$overdueReminder->id, $nextReminder->id],
            $service->operationalInboxItemsFor($supervisor)->pluck('id')->all(),
            'La bandeja debe ordenar primero vencidos y luego proximos.'
        );
    }

    public function test_operational_inbox_service_respects_visibility_and_limit(): void
    {
        /** @var User $supervisor */
        $supervisor = User::factory()->create([
            'role' => User::ROLE_SUPERVISOR,
            'is_active' => true,
        ]);

        /** @var User $teamAgent */
        $teamAgent = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'supervisor_id' => $supervisor->id,
            'is_active' => true,
        ]);

        /** @var User $outsider */
        $outsider = User::factory()->create([
            'role' => User::ROLE_AGENT,
            'is_active' => true,
        ]);

        $service = app(LeadReminderService::class);

        $expectedIds = [];

        for ($index = 1; $index <= 7; $index++) {
            $lead = Lead::query()->create([
                'name' => 'Lead equipo '.$index,
                'status' => Lead::STATUS_NEW,
                'assigned_user_id' => $teamAgent->id,
                'created_by' => $supervisor->id,
            ]);

            $expectedIds[] = LeadReminder::query()->create([
                'lead_id' => $lead->id,
                'created_by' => $supervisor->id,
                'scheduled_at' => now()->addMinutes($index)->setSecond(0),
                'description' => 'Pendiente equipo '.$index,
                'status' => LeadReminder::STATUS_PENDING,
            ])->id;
        }

        $outsiderLead = Lead::query()->create([
            'name' => 'Lead externo',
            'status' => Lead::STATUS_NEW,
            'assigned_user_id' => $outsider->id,
            'created_by' => $outsider->id,
        ]);

        LeadReminder::query()->create([
            'lead_id' => $outsiderLead->id,
            'created_by' => $outsider->id,
            'scheduled_at' => now()->addMinutes(1)->setSecond(0),
            'description' => 'No visible para supervisor.',
            'status' => LeadReminder::STATUS_PENDING,
        ]);

        $this->assertValueSame(7, $service->operationalInboxCountFor($supervisor), 'La bandeja debe contar todos los pendientes visibles aunque el dropdown solo muestre una parte.');
        $this->assertCollectionCount(
            LeadReminderService::DEFAULT_INBOX_LIMIT,
            $service->operationalInboxItemsFor($supervisor)->count(),
            'La bandeja debe respetar el limite configurado de elementos.'
        );
        $this->assertOrderedReminderIds(
            array_slice($expectedIds, 0, LeadReminderService::DEFAULT_INBOX_LIMIT),
            $service->operationalInboxItemsFor($supervisor)->pluck('id')->all(),
            'La bandeja debe respetar el limite y excluir pendientes fuera de visibilidad.'
        );
    }

    private function assertReminderIds(array $expected, array $actual, string $message): void
    {
        sort($expected);
        sort($actual);

        if ($expected !== $actual) {
            throw new \RuntimeException($message.' Esperado: ['.implode(', ', $expected).'] Actual: ['.implode(', ', $actual).']');
        }
    }

    private function assertOrderedReminderIds(array $expected, array $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message.' Esperado: ['.implode(', ', $expected).'] Actual: ['.implode(', ', $actual).']');
        }
    }

    private function assertValueSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message.' Esperado: '.var_export($expected, true).' Actual: '.var_export($actual, true));
        }
    }

    private function assertCollectionCount(int $expected, int $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message.' Esperado: '.$expected.' Actual: '.$actual);
        }
    }
}
