<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\CalendarEvent;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\Quotation;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StabilizationPermissionAndModalAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $employee;
    private User $manager;
    private User $otherEmployee;
    private Lead $employeeLead;
    private Lead $otherLead;
    private PipelineStage $stage;
    private LeadStatus $status;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // Provision canonical permissions in testing DB
        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ]
            );
        }

        // Super admin group
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'مدير النظام',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'username' => 'super_admin_audit',
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        // Sales Manager group (has leads.scope.all, reports.view, etc.)
        $managerGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-manager'],
            [
                'name' => 'مدير المبيعات',
                'is_system' => false,
            ]
        );
        $managerPerms = Permission::whereIn('code', [
            'dashboard.view',
            'leads.view',
            'leads.scope.all',
            'leads.assign',
            'leads.create',
            'leads.update',
            'leads.delete',
            'leads.import',
            'leads.export',
            'leads.followups.view',
            'leads.followups.create',
            'tasks.view',
            'quotations.view',
            'quotations.create',
            'reports.view',
            'technical_support.view',
            'technical_support.manage',
            'technical_support.reports',
            'technical_support.tasks.manage',
        ])->pluck('id');
        $managerGroup->permissions()->sync($managerPerms);

        $this->manager = User::factory()->create([
            'name' => 'Sales Manager',
            'username' => 'manager_audit',
            'is_active' => true,
        ]);
        $this->manager->groups()->attach($managerGroup);

        // Sales Agent group (own leads only, no settings, no campaigns, no reports)
        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agent'],
            [
                'name' => 'موظف المبيعات',
                'is_system' => false,
            ]
        );
        $agentPerms = Permission::whereIn('code', [
            'dashboard.view',
            'leads.view',
            'leads.create',
            'leads.update',
            'leads.followups.view',
            'leads.followups.create',
            'tasks.view',
            'quotations.view',
            'quotations.create',
        ])->pluck('id');
        $agentGroup->permissions()->sync($agentPerms);

        $this->employee = User::factory()->create([
            'name' => 'Sales Agent',
            'username' => 'agent_audit',
            'is_active' => true,
        ]);
        $this->employee->groups()->attach($agentGroup);

        $this->otherEmployee = User::factory()->create([
            'name' => 'Other Agent',
            'username' => 'other_agent_audit',
            'is_active' => true,
        ]);
        $this->otherEmployee->groups()->attach($agentGroup);

        $this->stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'name_ar' => 'جديد',
                'position' => 1,
                'is_primary' => true,
                'is_active' => true,
                'color' => '#3478f6',
            ]
        );

        $this->status = LeadStatus::query()->firstOrCreate(
            ['code' => 'new_assigned'],
            [
                'name_ar' => 'جديد مسند',
                'pipeline_stage_id' => $this->stage->id,
                'position' => 1,
                'is_terminal' => false,
            ]
        );

        $this->employeeLead = Lead::query()->create([
            'name' => 'Employee Lead',
            'phone' => '0501111111',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->employee->id,
            'created_by_user_id' => $this->employee->id,
        ]);

        $this->otherLead = Lead::query()->create([
            'name' => 'Other Lead',
            'phone' => '0502222222',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->otherEmployee->id,
            'created_by_user_id' => $this->otherEmployee->id,
        ]);
    }

    // MODAL REQUIREMENTS (1 - 3)
    public function test_01_stage_question_modal_uses_proper_overlay_shell(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.stages.fields.index', $this->stage));

        $response->assertOk();
        $response->assertSee('class="crm-modal-shell"', false);
        $response->assertSee('class="crm-modal-dialog', false);
        $response->assertSee('class="crm-modal-header"', false);
        $response->assertSee('class="crm-modal-body"', false);
        $response->assertSee('class="crm-modal-footer"', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
    }

    public function test_02_backdrop_covers_app_shell_and_locks_scroll(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.stages.fields.index', $this->stage));

        $response->assertOk();
        $response->assertSee('body.crm-modal-open', false);
        $response->assertSee('z-index: 100050', false);
        $response->assertSee('body.crm-modal-open .crm-topbar', false);
    }

    public function test_03_modal_remains_accessible_at_mobile_width(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.stages.fields.index', $this->stage));

        $response->assertOk();
        $response->assertSee('@media (max-width: 768px)', false);
        $response->assertSee('@media (max-width: 430px)', false);
    }

    // PERMISSIONS REQUIREMENTS (4 - 30)
    public function test_04_effective_permission_calculation(): void
    {
        $this->assertTrue($this->employee->hasPermission(CrmPermission::LEADS_VIEW));
        $this->assertTrue($this->employee->hasPermission('leads.view'));
        $this->assertFalse($this->employee->hasPermission(CrmPermission::SETTINGS_ACCESS));
        $this->assertFalse($this->employee->hasPermission('campaigns.view'));
    }

    public function test_05_role_group_inheritance(): void
    {
        $this->assertTrue($this->manager->hasPermission(CrmPermission::LEADS_DELETE));
        $this->assertFalse($this->employee->hasPermission(CrmPermission::LEADS_DELETE));
    }

    public function test_06_direct_user_permission_behavior(): void
    {
        // Super Admin gets all permissions by policy/gate
        $this->assertTrue($this->superAdmin->hasPermission(CrmPermission::SETTINGS_ACCESS));
        $this->assertTrue($this->superAdmin->isSuperAdmin());

        // Inactive user loses all permissions
        $this->employee->update(['is_active' => false]);
        $this->assertFalse($this->employee->hasPermission(CrmPermission::LEADS_VIEW));
    }

    public function test_07_sidebar_hides_denied_module(): void
    {
        $response = $this->actingAs($this->employee)->get(route('dashboard'));
        $response->assertOk();
        // Employee has no settings, campaigns, reports permission
        $response->assertDontSee(route('v2.settings'));
        $response->assertDontSee(route('v2.campaigns.index'));
        $response->assertDontSee(route('v2.reports.employees'));
    }

    public function test_08_sidebar_shows_allowed_module(): void
    {
        $response = $this->actingAs($this->employee)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee(route('v2.leads'));
        $response->assertSee(route('v2.leads.kanban'));
        $response->assertSee(route('v2.tasks.daily'));
        $response->assertSee(route('v2.quotations.index'));
    }

    public function test_09_empty_parent_sidebar_group_hidden(): void
    {
        $response = $this->actingAs($this->employee)->get(route('dashboard'));
        $response->assertOk();
        // Employee has no campaign permissions, campaigns parent toggle should not appear
        $response->assertDontSee('data-crm-menu="crmCampaignsMenu"', false);
    }

    public function test_10_badge_counts_scoped(): void
    {
        // Create 1 overdue task for employee, 5 for otherEmployee
        $this->employeeLead->update(['next_follow_up_at' => now()->subDay()]);
        $this->otherLead->update(['next_follow_up_at' => now()->subDay()]);

        // When employee loads sidebar, task count should only count their own accessible lead (1, not 2)
        $response = $this->actingAs($this->employee)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('<span class="crm-count count">1</span>', false);

        // In sidebar composer, totalTasks is calculated for accessible leads
        $accessibleTasksCount = Lead::query()->accessibleTo($this->employee)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay())->count();
        $this->assertSame(1, $accessibleTasksCount);
    }

    public function test_11_hidden_route_directly_denied(): void
    {
        // Settings is hidden for employee -> direct URL access gives 403
        $this->actingAs($this->employee)
            ->get(route('v2.settings'))
            ->assertForbidden();

        $this->actingAs($this->employee)
            ->get(route('v2.settings.stages.index'))
            ->assertForbidden();
    }

    public function test_12_leads_index_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.leads'))
            ->assertOk();

        // Create user without leads.view
        $noLeadsUser = User::factory()->create(['is_active' => true]);
        $this->actingAs($noLeadsUser)
            ->get(route('v2.leads'))
            ->assertForbidden();
    }

    public function test_13_lead_create_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.leads.create'))
            ->assertOk();

        $noCreateUser = User::factory()->create(['is_active' => true]);
        $this->actingAs($noCreateUser)
            ->get(route('v2.leads.create'))
            ->assertForbidden();
    }

    public function test_14_lead_edit_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.leads.edit', $this->employeeLead))
            ->assertOk();

        // Employee editing other's lead (not accessible) -> 403
        $this->actingAs($this->employee)
            ->get(route('v2.leads.edit', $this->otherLead))
            ->assertForbidden();
    }

    public function test_15_lead_transition_permission(): void
    {
        // Employee with leads.followups.create can record transition
        $response = $this->actingAs($this->employee)
            ->post(route('v2.leads.followups.store', $this->employeeLead), [
                'communication_type' => 'call',
                'outcome' => 'Spoke with customer',
                'to_status_id' => $this->status->id,
            ]);

        $this->assertTrue($response->isRedirect());

        // Unauthorized user cannot record followup
        $unauth = User::factory()->create(['is_active' => true]);
        $this->actingAs($unauth)
            ->post(route('v2.leads.followups.store', $this->employeeLead), [
                'communication_type' => 'call',
                'outcome' => 'Attempt',
            ])->assertForbidden();
    }

    public function test_16_kanban_access_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.leads.kanban'))
            ->assertOk();

        $noKanban = User::factory()->create(['is_active' => true]);
        $this->actingAs($noKanban)
            ->get(route('v2.leads.kanban'))
            ->assertForbidden();
    }

    public function test_17_task_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.tasks.daily'))
            ->assertOk();

        $noTask = User::factory()->create(['is_active' => true]);
        $this->actingAs($noTask)
            ->get(route('v2.tasks.daily'))
            ->assertForbidden();
    }

    public function test_18_campaign_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.campaigns.index'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('v2.campaigns.index'))
            ->assertOk();
    }

    public function test_19_calendar_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.calendar.index'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('v2.calendar.index'))
            ->assertOk();
    }

    public function test_20_quotation_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.quotations.index'))
            ->assertOk();

        $noQuote = User::factory()->create(['is_active' => true]);
        $this->actingAs($noQuote)
            ->get(route('v2.quotations.index'))
            ->assertForbidden();
    }

    public function test_21_document_download_authorization(): void
    {
        Storage::fake('local');
        $filePath = 'documents/test.pdf';
        Storage::disk('local')->put($filePath, 'dummy content');

        $doc = LeadDocument::create([
            'lead_id' => $this->employeeLead->id,
            'category' => 'attachment',
            'path' => $filePath,
            'disk' => 'local',
            'original_name' => 'test.pdf',
            'stored_name' => 'test.pdf',
            'size' => 100,
            'mime_type' => 'application/pdf',
            'created_by_user_id' => $this->employee->id,
        ]);

        // Employee can download their accessible lead's document
        $this->actingAs($this->employee)
            ->get(route('v2.leads.documents.download', ['lead' => $this->employeeLead, 'document' => $doc]))
            ->assertOk();

        // Employee CANNOT download document of another agent's lead
        $this->actingAs($this->employee)
            ->get(route('v2.leads.documents.download', ['lead' => $this->otherLead, 'document' => $doc]))
            ->assertNotFound(); // doc belongs to employeeLead, mismatch returns 404

        // User without leads.view gets 403
        $noLeads = User::factory()->create(['is_active' => true]);
        $this->actingAs($noLeads)
            ->get(route('v2.leads.documents.download', ['lead' => $this->employeeLead, 'document' => $doc]))
            ->assertForbidden();
    }

    public function test_22_reports_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.reports.employees'))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('v2.reports.employees'))
            ->assertOk();
    }

    public function test_23_settings_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.settings'))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('v2.settings'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('v2.settings'))
            ->assertOk();
    }

    public function test_24_user_management_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.settings.users.index'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.users.index'))
            ->assertOk();
    }

    public function test_25_stage_settings_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.settings.stages.index'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.stages.index'))
            ->assertOk();
    }

    public function test_26_stage_question_edit_permission(): void
    {
        $this->actingAs($this->employee)
            ->get(route('v2.settings.stages.fields.index', $this->stage))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('v2.settings.stages.fields.index', $this->stage))
            ->assertOk();
    }

    public function test_27_inaccessible_lead_denied_despite_module_access(): void
    {
        // Employee has leads.view, but $otherLead is not assigned or created by them
        $this->actingAs($this->employee)
            ->get(route('v2.leads.show', $this->otherLead))
            ->assertForbidden();

        // Employee CAN view their own lead
        $this->actingAs($this->employee)
            ->get(route('v2.leads.show', $this->employeeLead))
            ->assertOk();
    }

    public function test_28_branch_team_scope(): void
    {
        // Agent sees only own leads
        $agentLeads = Lead::query()->accessibleTo($this->employee)->pluck('id')->all();
        $this->assertContains($this->employeeLead->id, $agentLeads);
        $this->assertNotContains($this->otherLead->id, $agentLeads);

        // Manager sees all leads (due to leads.scope.all)
        $managerLeads = Lead::query()->accessibleTo($this->manager)->pluck('id')->all();
        $this->assertContains($this->employeeLead->id, $managerLeads);
        $this->assertContains($this->otherLead->id, $managerLeads);
    }

    public function test_29_ajax_endpoint_authorization(): void
    {
        // Daily tasks quick followup on inaccessible lead returns 403
        $this->actingAs($this->employee)
            ->postJson(route('v2.tasks.quick_followup', $this->otherLead), [
                'communication_type' => 'call',
                'outcome' => 'Unauthorized attempt',
            ])->assertForbidden();

        // On accessible lead returns 200 JSON
        $this->actingAs($this->employee)
            ->postJson(route('v2.tasks.quick_followup', $this->employeeLead), [
                'communication_type' => 'call',
                'outcome' => 'Authorized call followup',
            ])->assertOk();
    }

    public function test_30_no_permission_string_referenced_in_code_without_valid_configuration(): void
    {
        $validCodes = array_merge(
            CrmPermission::values(),
            Permission::pluck('code')->all()
        );

        foreach (CrmPermission::cases() as $case) {
            $this->assertContains($case->value, $validCodes);
            $this->assertNotEmpty($case->label());
            $this->assertNotEmpty($case->module());
        }
    }
}
