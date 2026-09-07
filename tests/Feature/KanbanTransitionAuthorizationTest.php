<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\PipelineMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanTransitionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $agent;
    private User $otherAgent;
    private User $readOnlyUser;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;
    private Lead $agentLead;

    protected function setUp(): void
    {
        parent::setUp();

        if (Permission::query()->count() < count(CrmPermission::cases())) {
            $perms = array_map(static fn ($p) => [
                'code' => $p->value,
                'module' => $p->module(),
                'name_ar' => $p->label(),
            ], CrmPermission::cases());
            Permission::query()->upsert($perms, ['code'], ['module', 'name_ar']);
        }

        // Super Admin
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'name' => 'Admin User',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // Sales Agent (own scope only, can create followups)
        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agent'],
            ['name' => 'موظف المبيعات', 'is_system' => false]
        );
        $agentGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            ])->pluck('id')
        );

        $this->agent = User::factory()->create([
            'username' => 'agent_' . uniqid(),
            'name' => 'Sales Agent',
            'is_active' => true,
        ]);
        $this->agent->groups()->attach($agentGroup);

        $this->otherAgent = User::factory()->create([
            'username' => 'other_agent_' . uniqid(),
            'name' => 'Other Agent',
            'is_active' => true,
        ]);
        $this->otherAgent->groups()->attach($agentGroup);

        // Read Only User
        $readOnlyGroup = Group::query()->firstOrCreate(
            ['code' => 'read-only'],
            ['name' => 'مشاهدة فقط', 'is_system' => false]
        );
        $readOnlyGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_ALL->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            ])->pluck('id')
        );

        $this->readOnlyUser = User::factory()->create([
            'username' => 'readonly_' . uniqid(),
            'name' => 'Read Only User',
            'is_active' => true,
        ]);
        $this->readOnlyUser->groups()->attach($readOnlyGroup);

        // Stages & Statuses
        $this->stageA = PipelineStage::query()->create([
            'code' => 'stage_a_' . uniqid(),
            'name_ar' => 'مرحلة البداية',
            'position' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->statusA = $this->stageA->ensureDefaultStatus();

        $this->stageB = PipelineStage::query()->create([
            'code' => 'stage_b_' . uniqid(),
            'name_ar' => 'مرحلة المتابعة',
            'position' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->statusB = $this->stageB->ensureDefaultStatus();

        PipelineMappingService::clearCache();

        // Lead assigned to this->agent
        $this->agentLead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل تجريبي للوكيل',
            'phone' => '0501112233',
            'source' => 'web',
            'assigned_user_id' => $this->agent->id,
            'created_by_user_id' => $this->agent->id,
            'next_follow_up_at' => now()->addDay(),
        ]);
    }

    /** 1. Authorized draggable user GETs transition popup = 200 */
    public function test_1_authorized_draggable_user_gets_transition_popup(): void
    {
        $response = $this->actingAs($this->agent)->get(
            route('v2.leads.followups.index', [
                'lead' => $this->agentLead->id,
                'kanban_popup' => 1,
            ])
        );

        $response->assertOk();
    }

    /** 2. Same Lead with target_status_id = 200 */
    public function test_2_same_lead_with_target_status_id_returns_200(): void
    {
        $response = $this->actingAs($this->agent)->get(
            route('v2.leads.followups.index', [
                'lead' => $this->agentLead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
    }

    /** 3. Invalid/deleted target status rejected safely / falls back */
    public function test_3_invalid_or_deleted_target_status_handled_safely(): void
    {
        // Deleted status
        $deletedStage = PipelineStage::query()->create([
            'code' => 'stage_del_' . uniqid(),
            'name_ar' => 'مرحلة محذوفة',
            'position' => 99,
            'is_active' => true,
        ]);
        $deletedStatus = $deletedStage->ensureDefaultStatus();
        $deletedStatus->delete();
        $deletedStage->delete();

        $response = $this->actingAs($this->agent)->get(
            route('v2.leads.followups.index', [
                'lead' => $this->agentLead->id,
                'kanban_popup' => 1,
                'target_status_id' => $deletedStatus->id,
            ])
        );

        // Should load cleanly and fall back to current status
        $response->assertOk();
        $response->assertViewHas('defaultStatusId', $this->agentLead->lead_status_id);
    }

    /** 4. Active target status works */
    public function test_4_active_target_status_works(): void
    {
        $response = $this->actingAs($this->agent)->get(
            route('v2.leads.followups.index', [
                'lead' => $this->agentLead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertViewHas('defaultStatusId', $this->statusB->id);
    }

    /** 5. Active Lead after SoftDeletes migration binds correctly */
    public function test_5_active_lead_binds_correctly(): void
    {
        $this->assertNull($this->agentLead->deleted_at);

        $response = $this->actingAs($this->agent)->get(
            route('v2.leads.followups.index', $this->agentLead)
        );

        $response->assertOk();
    }

    /** 6. Accessible Lead works */
    public function test_6_accessible_lead_works(): void
    {
        $this->assertTrue($this->agentLead->isAccessibleTo($this->agent));

        $response = $this->actingAs($this->agent)->get(
            route('v2.leads.followups.index', $this->agentLead)
        );

        $response->assertOk();
    }

    /** 7. Inaccessible Lead returns 403 */
    public function test_7_inaccessible_lead_returns_403(): void
    {
        // otherAgent has no LEADS_SCOPE_ALL and is not assigned or creator
        $this->assertFalse($this->agentLead->isAccessibleTo($this->otherAgent));

        $response = $this->actingAs($this->otherAgent)->get(
            route('v2.leads.followups.index', [
                'lead' => $this->agentLead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertForbidden();
    }

    /** 8. Read-only card not draggable */
    public function test_8_read_only_card_not_draggable(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get(
            route('v2.leads.kanban', ['scope' => 'all'])
        );

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('draggable="false"', $html);
        $this->assertStringNotContainsString('<article class="kanban-card" draggable="true"', $html);
    }

    /** 9. UI permission matches popup backend permission */
    public function test_9_ui_permission_matches_popup_backend_permission(): void
    {
        // User with leads.followups.create can drag AND can access the popup
        $userWithOnlyCreate = User::factory()->create([
            'username' => 'create_only_' . uniqid(),
            'is_active' => true,
        ]);
        $createGroup = Group::query()->create([
            'code' => 'create_only_' . uniqid(),
            'name' => 'صلاحية إنشاء فقط',
            'is_system' => false,
        ]);
        $createGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            ])->pluck('id')
        );
        $userWithOnlyCreate->groups()->attach($createGroup);

        $ownLead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل الموظف',
            'phone' => '0509998877',
            'source' => 'web',
            'assigned_user_id' => $userWithOnlyCreate->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($userWithOnlyCreate)->get(
            route('v2.leads.followups.index', [
                'lead' => $ownLead->id,
                'kanban_popup' => 1,
            ])
        );

        // Must succeed with 200, NOT 403
        $response->assertOk();
    }

    /** 10. Deleted stage/status excluded from Kanban */
    public function test_10_deleted_stage_and_status_excluded_from_kanban(): void
    {
        $tempStage = PipelineStage::query()->create([
            'code' => 'stage_temp_' . uniqid(),
            'name_ar' => 'مرحلة مؤقتة للحذف',
            'position' => 10,
            'is_active' => true,
        ]);
        $tempStatus = $tempStage->ensureDefaultStatus();

        PipelineMappingService::clearCache();

        // Delete stage and status
        $tempStatus->delete();
        $tempStage->delete();

        PipelineMappingService::clearCache();

        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringNotContainsString('data-kanban-status-id="' . $tempStatus->id . '"', $html);
    }

    /** 11. PipelineMappingService excludes deleted mappings */
    public function test_11_pipeline_mapping_service_excludes_deleted_mappings(): void
    {
        $tempStage = PipelineStage::query()->create([
            'code' => 'stage_check_' . uniqid(),
            'name_ar' => 'مرحلة الفحص',
            'position' => 15,
            'is_active' => true,
        ]);
        $tempStatus = $tempStage->ensureDefaultStatus();

        $tempStatus->delete();
        $tempStage->delete();

        PipelineMappingService::clearCache();

        $activeStatuses = PipelineMappingService::getActiveStatuses();
        $this->assertFalse($activeStatuses->contains('id', $tempStatus->id));

        $activeStages = PipelineMappingService::getActiveStages();
        $this->assertFalse($activeStages->contains('id', $tempStage->id));
    }

    /** 12. Stale deleted destination cannot be used on store */
    public function test_12_stale_deleted_destination_cannot_be_used_on_store(): void
    {
        $delStage = PipelineStage::query()->create([
            'code' => 'stage_stale_' . uniqid(),
            'name_ar' => 'مرحلة معطلة',
            'position' => 20,
            'is_active' => true,
        ]);
        $delStatus = $delStage->ensureDefaultStatus();
        $delStatus->delete();
        $delStage->delete();

        $response = $this->actingAs($this->agent)->post(
            route('v2.leads.followups.store', $this->agentLead),
            [
                'lead_status_id' => $delStatus->id,
                'communication_type' => 'call',
                'summary' => 'محاولة نقل لمرحلة محذوفة',
                'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            ]
        );

        // Validation should reject soft-deleted status
        $response->assertSessionHasErrors('lead_status_id');
    }

    /** 13. Authorized POST still succeeds */
    public function test_13_authorized_post_still_succeeds(): void
    {
        $response = $this->actingAs($this->agent)->post(
            route('v2.leads.followups.store', $this->agentLead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'call',
                'summary' => 'متابعة وحفظ ناجح للوكيل',
                'next_follow_up_at' => now()->addDays(3)->format('Y-m-d H:i'),
            ]
        );

        $response->assertRedirect();
        $this->agentLead->refresh();
        $this->assertSame($this->statusB->id, $this->agentLead->lead_status_id);
    }

    /** 14. No authorization bypass introduced */
    public function test_14_no_authorization_bypass_introduced(): void
    {
        // Unauthenticated user
        $response = $this->get(
            route('v2.leads.followups.index', $this->agentLead)
        );
        $response->assertRedirect(route('login'));

        // User with no lead access
        $noPermUser = User::factory()->create(['is_active' => true]);
        $response = $this->actingAs($noPermUser)->get(
            route('v2.leads.followups.index', $this->agentLead)
        );
        $response->assertForbidden();
    }
}
