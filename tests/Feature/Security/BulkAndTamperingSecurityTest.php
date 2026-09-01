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

class BulkAndTamperingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $agentBranchA;
    private User $agentBranchB;
    private Lead $leadBranchA;
    private Lead $leadBranchB;
    private LeadStatus $status;

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
            'name' => 'فرع الشرقية',
            'code' => 'branch-east',
            'is_system' => false,
        ]);
        $groupA->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_CREATE->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::CALENDAR_MANAGE->value,
        ])->pluck('id'));

        $this->agentBranchA = User::factory()->create([
            'name' => 'Agent East',
            'username' => 'agent_east',
            'is_active' => true,
        ]);
        $this->agentBranchA->groups()->attach($groupA);

        $groupB = Group::query()->create([
            'name' => 'فرع الغربية',
            'code' => 'branch-west',
            'is_system' => false,
        ]);
        $groupB->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_CREATE->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::CALENDAR_MANAGE->value,
        ])->pluck('id'));

        $this->agentBranchB = User::factory()->create([
            'name' => 'Agent West',
            'username' => 'agent_west',
            'is_active' => true,
        ]);
        $this->agentBranchB->groups()->attach($groupB);

        $stage = PipelineStage::query()->create([
            'code' => 'start',
            'name_ar' => 'البداية',
            'position' => 1,
            'color' => '#3478f6',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->status = $stage->statuses()->first();
        $this->status->update(['code' => 'new', 'name_ar' => 'جديد']);

        $this->leadBranchA = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'Lead Branch East',
            'phone' => '0501117777',
            'assigned_user_id' => $this->agentBranchA->id,
            'source' => 'web',
        ]);

        $this->leadBranchB = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'Lead Branch West Secret',
            'phone' => '0502227777',
            'assigned_user_id' => $this->agentBranchB->id,
            'source' => 'web',
        ]);
    }

    public function test_mixed_id_array_in_export_is_strictly_rejected(): void
    {
        $response = $this->actingAs($this->agentBranchA)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$this->leadBranchA->id, $this->leadBranchB->id],
        ]);

        $this->assertEquals(422, $response->getStatusCode(), 'Mixed authorized and foreign ID array must be rejected with 422');
    }

    public function test_forged_cross_branch_employee_assignment_is_rejected_on_lead_create(): void
    {
        // Agent A attempts to assign new lead to Agent B (who is in Branch B)
        $response = $this->actingAs($this->agentBranchA)->post(route('v2.leads.store'), [
            'first_name' => 'Tampered',
            'phone' => '0503337777',
            'source' => 'web',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->agentBranchB->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('leads', ['phone' => '0503337777']);
    }

    public function test_unauthorized_lead_deletion_leaves_database_unmodified(): void
    {
        $initialCount = Lead::count();

        $response = $this->actingAs($this->agentBranchA)->delete(route('v2.leads.destroy', $this->leadBranchB));
        $response->assertForbidden();

        $this->assertEquals($initialCount, Lead::count());
        $this->assertDatabaseHas('leads', ['id' => $this->leadBranchB->id]);
    }

    public function test_unauthorized_calendar_event_deletion_leaves_database_unmodified(): void
    {
        $eventB = CalendarEvent::query()->create([
            'user_id' => $this->agentBranchB->id,
            'title' => 'West Private Event',
            'start_time' => now()->addHours(3),
            'end_time' => now()->addHours(4),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        $initialCount = CalendarEvent::count();

        $response = $this->actingAs($this->agentBranchA)->delete(route('v2.calendar.destroy', $eventB));
        $response->assertForbidden();

        $this->assertEquals($initialCount, CalendarEvent::count());
        $this->assertDatabaseHas('calendar_events', ['id' => $eventB->id, 'deleted_at' => null]);
    }

    public function test_unauthorized_campaign_deletion_leaves_database_unmodified(): void
    {
        $campaignB = Campaign::query()->create([
            'name' => 'West Confidential Campaign',
            'created_by_user_id' => $this->agentBranchB->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 10000,
        ]);
        $campaignB->users()->attach($this->agentBranchB->id);

        $initialCount = Campaign::count();

        $response = $this->actingAs($this->agentBranchA)->delete(route('v2.campaigns.destroy', $campaignB));
        $response->assertForbidden();

        $this->assertEquals($initialCount, Campaign::count());
        $this->assertDatabaseHas('campaigns', ['id' => $campaignB->id]);
    }
}
