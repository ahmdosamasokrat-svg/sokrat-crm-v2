<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CalendarEvent;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignAndCalendarSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $agentA;
    private User $agentB;
    private Campaign $campaignA;
    private Campaign $campaignB;
    private CalendarEvent $eventA;
    private CalendarEvent $eventB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ]
            );
        }

        $groupA = Group::query()->create([
            'name' => 'فريق أ',
            'code' => 'team-a',
            'is_system' => false,
        ]);
        $groupA->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_CREATE->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::CALENDAR_MANAGE->value,
            CrmPermission::LEADS_VIEW->value,
        ])->pluck('id'));

        $this->agentA = User::factory()->create([
            'name' => 'Agent A',
            'username' => 'agent_a_sec',
            'is_active' => true,
        ]);
        $this->agentA->groups()->attach($groupA);

        $groupB = Group::query()->create([
            'name' => 'فريق ب',
            'code' => 'team-b',
            'is_system' => false,
        ]);
        $groupB->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_CREATE->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::CALENDAR_MANAGE->value,
            CrmPermission::LEADS_VIEW->value,
        ])->pluck('id'));

        $this->agentB = User::factory()->create([
            'name' => 'Agent B',
            'username' => 'agent_b_sec',
            'is_active' => true,
        ]);
        $this->agentB->groups()->attach($groupB);

        // Campaign created by Agent A
        $this->campaignA = Campaign::query()->create([
            'name' => 'حملة الرياض الخاصة بأحمد',
            'created_by_user_id' => $this->agentA->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 5000,
        ]);
        $this->campaignA->users()->attach($this->agentA->id);

        // Campaign created by Agent B
        $this->campaignB = Campaign::query()->create([
            'name' => 'حملة جدة السرية لخالد',
            'created_by_user_id' => $this->agentB->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 8000,
        ]);
        $this->campaignB->users()->attach($this->agentB->id);
        // Calendar Events
        $this->eventA = CalendarEvent::query()->create([
            'user_id' => $this->agentA->id,
            'title' => 'اجتماع عمل لفريق أ',
            'start_time' => now()->addHours(2),
            'end_time' => now()->addHours(3),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        $this->eventB = CalendarEvent::query()->create([
            'user_id' => $this->agentB->id,
            'title' => 'اجتماع سري لفريق ب',
            'start_time' => now()->addHours(4),
            'end_time' => now()->addHours(5),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);
    }

    public function test_campaign_index_is_isolated_per_user_and_scope(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.campaigns.index'));

        $response->assertOk();
        $response->assertSee('حملة الرياض الخاصة بأحمد');
        $response->assertDontSee('حملة جدة السرية لخالد');
    }

    public function test_campaign_show_is_blocked_for_unauthorized_user(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.campaigns.show', $this->campaignB));
        $response->assertForbidden();
    }

    public function test_campaign_edit_and_update_are_blocked_for_unauthorized_user(): void
    {
        $editResponse = $this->actingAs($this->agentA)->get(route('v2.campaigns.edit', $this->campaignB));
        $editResponse->assertForbidden();

        $patchResponse = $this->actingAs($this->agentA)->patch(route('v2.campaigns.update', $this->campaignB), [
            'name' => 'Hacked Campaign Name',
            'cost' => 5000,
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
            'user_ids' => [$this->agentA->id],
        ]);
        $patchResponse->assertForbidden();
    }

    public function test_campaign_destroy_is_blocked_for_unauthorized_user(): void
    {
        $response = $this->actingAs($this->agentA)->delete(route('v2.campaigns.destroy', $this->campaignB));
        $response->assertForbidden();
        $this->assertDatabaseHas('campaigns', ['id' => $this->campaignB->id]);
    }

    public function test_campaign_report_for_unauthorized_campaign_is_rejected(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.campaigns.reports', [
            'campaign_id' => $this->campaignB->id,
        ]));

        $this->assertTrue($response->isNotFound() || $response->isForbidden());
    }

    public function test_calendar_events_index_only_returns_accessible_events(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.calendar.events', [
            'start' => now()->startOfDay()->toIso8601String(),
            'end' => now()->endOfDay()->toIso8601String(),
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'اجتماع عمل لفريق أ']);
        $response->assertJsonMissing(['title' => 'اجتماع سري لفريق ب']);
    }

    public function test_calendar_event_show_is_blocked_for_unauthorized_event(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.calendar.show', $this->eventB));
        $response->assertForbidden();
    }

    public function test_calendar_event_update_and_destroy_are_blocked_for_unauthorized_event(): void
    {
        $updateResponse = $this->actingAs($this->agentA)->patch(route('v2.calendar.update', $this->eventB), [
            'title' => 'Tampered Event Title',
            'start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'type' => 'call',
        ]);
        $updateResponse->assertForbidden();

        $deleteResponse = $this->actingAs($this->agentA)->delete(route('v2.calendar.destroy', $this->eventB));
        $deleteResponse->assertForbidden();
        $this->assertDatabaseHas('calendar_events', ['id' => $this->eventB->id]);
    }
}
