<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanAndDashboardSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $agentA;
    private User $agentB;
    private Lead $leadA1;
    private Lead $leadA2;
    private Lead $leadB1;
    private LeadStatus $statusStart;
    private LeadStatus $statusInterest;

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
            'name' => 'فرع أ',
            'code' => 'branch-a',
            'is_system' => false,
        ]);
        $groupA->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
        ])->pluck('id'));

        $this->agentA = User::factory()->create([
            'name' => 'Agent A',
            'username' => 'agent_a_kdb',
            'is_active' => true,
        ]);
        $this->agentA->groups()->attach($groupA);

        $groupB = Group::query()->create([
            'name' => 'فرع ب',
            'code' => 'branch-b',
            'is_system' => false,
        ]);
        $groupB->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
        ])->pluck('id'));

        $this->agentB = User::factory()->create([
            'name' => 'Agent B',
            'username' => 'agent_b_kdb',
            'is_active' => true,
        ]);
        $this->agentB->groups()->attach($groupB);

        $stageStart = PipelineStage::query()->create([
            'code' => 'new',
            'name_ar' => 'البداية',
            'position' => 1,
            'color' => '#3478f6',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->statusStart = $stageStart->statuses()->first();
        $this->statusStart->update(['code' => 'new', 'name_ar' => 'جديد']);

        $stageInterest = PipelineStage::query()->create([
            'code' => 'interest',
            'name_ar' => 'الاهتمام',
            'position' => 2,
            'color' => '#10b981',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->statusInterest = $stageInterest->statuses()->first();
        $this->statusInterest->update(['code' => 'interested', 'name_ar' => 'مهتم']);

        // Leads for Branch A
        $this->leadA1 = Lead::query()->create([
            'lead_status_id' => $this->statusStart->id,
            'name' => 'عميل فرع أ الأول',
            'phone' => '0501110001',
            'assigned_user_id' => $this->agentA->id,
            'next_follow_up_at' => now(),
            'source' => 'web',
        ]);
        $this->leadA2 = Lead::query()->create([
            'lead_status_id' => $this->statusInterest->id,
            'name' => 'عميل فرع أ الثاني',
            'phone' => '0501110002',
            'assigned_user_id' => $this->agentA->id,
            'next_follow_up_at' => now(),
            'source' => 'web',
        ]);
        $this->leadB1 = Lead::query()->create([
            'lead_status_id' => $this->statusStart->id,
            'name' => 'عميل فرع ب المنفصل',
            'phone' => '0502220001',
            'assigned_user_id' => $this->agentB->id,
            'source' => 'web',
        ]);

        // Follow-ups
        LeadFollowup::query()->create([
            'lead_id' => $this->leadA1->id,
            'user_id' => $this->agentA->id,
            'employee_name' => $this->agentA->name,
            'to_status_id' => $this->statusStart->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة فرع أ',
            'followed_up_at' => now(),
        ]);

        LeadFollowup::query()->create([
            'lead_id' => $this->leadB1->id,
            'user_id' => $this->agentB->id,
            'employee_name' => $this->agentB->name,
            'to_status_id' => $this->statusStart->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة فرع ب السرية',
            'followed_up_at' => now(),
        ]);
    }

    public function test_kanban_view_only_renders_accessible_leads(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.leads.kanban', ['scope' => 'all']));

        $response->assertOk();
        $response->assertSee('عميل فرع أ الأول');
        $response->assertSee('عميل فرع أ الثاني');
        $response->assertDontSee('عميل فرع ب المنفصل');
    }

    public function test_kanban_employee_only_sees_leads_assigned_to_them(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusStart->id,
            'name' => 'عميل أنشأه الموظف ثم أعيد إسناده',
            'phone' => '0502220002',
            'assigned_user_id' => $this->agentB->id,
            'created_by_user_id' => $this->agentA->id,
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->agentA)->get(route('v2.leads.kanban', [
            'employee_id' => $this->agentB->id,
        ]));

        $response->assertOk();
        $response->assertSee('عميل فرع أ الأول');
        $response->assertDontSee('عميل فرع ب المنفصل');
        $response->assertDontSee('عميل أنشأه الموظف ثم أعيد إسناده');
        $response->assertDontSee('kanbanEmployee');
    }

    public function test_kanban_manager_can_view_all_or_filter_by_employee(): void
    {
        $managerGroup = Group::query()->create([
            'name' => 'إدارة المبيعات',
            'code' => 'sales-management',
            'is_system' => false,
        ]);
        $managerGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_ALL->value,
        ])->pluck('id'));

        $manager = User::factory()->create([
            'name' => 'Sales Manager',
            'username' => 'sales_manager_kdb',
            'is_active' => true,
        ]);
        $manager->groups()->attach($managerGroup);

        $allResponse = $this->actingAs($manager)->get(route('v2.leads.kanban'));
        $allResponse->assertOk();
        $allResponse->assertSee('عميل فرع أ الأول');
        $allResponse->assertSee('عميل فرع ب المنفصل');
        $allResponse->assertSee('kanbanEmployee');

        $filteredResponse = $this->actingAs($manager)->get(route('v2.leads.kanban', [
            'employee_id' => $this->agentB->id,
        ]));
        $filteredResponse->assertOk();
        $filteredResponse->assertDontSee('عميل فرع أ الأول');
        $filteredResponse->assertDontSee('عميل فرع أ الثاني');
        $filteredResponse->assertSee('عميل فرع ب المنفصل');
    }

    public function test_dashboard_total_leads_excludes_unauthorized_branch_leads(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalLeads', 2); // only 2 leads for Agent A, excluding Lead B1
    }

    public function test_dashboard_latest_followups_strictly_excludes_unauthorized_leads(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('عميل فرع أ الأول');
        $response->assertDontSee('عميل فرع ب المنفصل');
    }
}
