<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondPassIsolationVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $agentBranchA;
    private User $agentBranchB;
    private LeadStatus $statusStart;
    private LeadStatus $statusInterest;
    private Lead $leadA;
    private Lead $leadB;

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

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'مدير النظام',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Verification',
            'username' => 'super_admin_v2_sec',
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        // Branch A Group & Agent
        $groupA = Group::query()->firstOrCreate(
            ['code' => 'branch-eastern'],
            [
                'name' => 'فرع المنطقة الشرقية',
                'is_system' => false,
            ]
        );
        $groupA->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
        ])->pluck('id'));

        $this->agentBranchA = User::factory()->create([
            'name' => 'مندوب الشرقية',
            'username' => 'agent_eastern_user',
            'is_active' => true,
        ]);
        $this->agentBranchA->groups()->attach($groupA);

        // Branch B Group & Agent
        $groupB = Group::query()->firstOrCreate(
            ['code' => 'branch-western'],
            [
                'name' => 'فرع المنطقة الغربية',
                'is_system' => false,
            ]
        );
        $groupB->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
        ])->pluck('id'));

        $this->agentBranchB = User::factory()->create([
            'name' => 'مندوب الغربية',
            'username' => 'agent_western_user',
            'is_active' => true,
        ]);
        $this->agentBranchB->groups()->attach($groupB);

        $stageStart = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'البداية',
                'position' => 1,
                'color' => '#3478f6',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->statusStart = $stageStart->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $stageStart->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
            ]
        );
        $this->statusStart->update(['code' => 'new', 'name_ar' => 'جديد']);

        $stageInterest = PipelineStage::query()->firstOrCreate(
            ['code' => 'interest'],
            [
                'name_ar' => 'الاهتمام',
                'position' => 2,
                'color' => '#10b981',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->statusInterest = $stageInterest->statuses()->first();
        $this->statusInterest->update(['code' => 'interested', 'name_ar' => 'مهتم']);

        $this->leadA = Lead::query()->create([
            'lead_status_id' => $this->statusStart->id,
            'name' => 'BRANCH_A_SECRET_LEAD_AHMED',
            'phone' => '0501119999',
            'email' => 'ahmed.eastern@example.com',
            'assigned_user_id' => $this->agentBranchA->id,
            'next_follow_up_at' => now(),
            'source' => 'web',
        ]);

        $this->leadB = Lead::query()->create([
            'lead_status_id' => $this->statusStart->id,
            'name' => 'BRANCH_B_SECRET_LEAD_OMAR',
            'phone' => '0502228888',
            'email' => 'omar.western@example.com',
            'assigned_user_id' => $this->agentBranchB->id,
            'next_follow_up_at' => now(),
            'source' => 'web',
        ]);
    }

    public function test_dashboard_aggregate_isolation_with_10_vs_37_leads(): void
    {
        // Add 9 more leads to Branch A (total 10)
        for ($i = 2; $i <= 10; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusStart->id,
                'name' => 'Branch A Lead ' . $i,
                'phone' => '05011100' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'assigned_user_id' => $this->agentBranchA->id,
                'source' => 'web',
            ]);
        }

        // Add 36 more leads to Branch B (total 37)
        for ($j = 2; $j <= 37; $j++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusStart->id,
                'name' => 'Branch B Lead ' . $j,
                'phone' => '05022200' . str_pad((string) $j, 2, '0', STR_PAD_LEFT),
                'assigned_user_id' => $this->agentBranchB->id,
                'source' => 'web',
            ]);
        }

        // 1. Agent A must strictly see 10 total leads (never 37, never 47)
        $responseA = $this->actingAs($this->agentBranchA)->get(route('dashboard'));
        $responseA->assertOk();
        $responseA->assertViewHas('totalLeads', 10);

        // 2. Super Admin must see all 47 leads
        $responseAdmin = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertViewHas('totalLeads', 47);
    }

    public function test_negative_response_does_not_leak_sensitive_contact_details(): void
    {
        $response = $this->actingAs($this->agentBranchA)->get(route('v2.leads.show', $this->leadB));

        $response->assertForbidden();
        $content = $response->getContent();

        $this->assertStringNotContainsString('BRANCH_B_SECRET_LEAD_OMAR', $content);
        $this->assertStringNotContainsString('0502228888', $content);
        $this->assertStringNotContainsString('omar.western@example.com', $content);
    }

    public function test_unauthorized_lead_status_transition_is_rejected_and_leaves_db_unchanged(): void
    {
        $initialStatusId = $this->leadB->lead_status_id;

        $response = $this->actingAs($this->agentBranchA)->patch(route('v2.leads.update', $this->leadB), [
            'name' => 'Tampered Name',
            'lead_status_id' => $this->statusInterest->id,
        ]);
        $response->assertForbidden();
        $this->assertEquals($initialStatusId, $this->leadB->fresh()->lead_status_id);
    }

    public function test_search_and_autocomplete_enumeration_returns_zero_foreign_matches(): void
    {
        // 1. Search by exact foreign lead name - yields 0 lead results
        $nameSearch = $this->actingAs($this->agentBranchA)->get(route('v2.leads', ['q' => 'BRANCH_B_SECRET_LEAD_OMAR']));
        $nameSearch->assertOk();
        $this->assertCount(0, $nameSearch->viewData('leads'));
        $nameSearch->assertDontSee('0502228888');
        $nameSearch->assertDontSee('omar.western@example.com');

        // 2. Search by foreign phone fragment - yields 0 lead results
        $phoneSearch = $this->actingAs($this->agentBranchA)->get(route('v2.leads', ['q' => '0502228888']));
        $phoneSearch->assertOk();
        $this->assertCount(0, $phoneSearch->viewData('leads'));
        $phoneSearch->assertDontSee('BRANCH_B_SECRET_LEAD_OMAR');
        $phoneSearch->assertDontSee('omar.western@example.com');

        // 3. Search by foreign email fragment - yields 0 lead results
        $emailSearch = $this->actingAs($this->agentBranchA)->get(route('v2.leads', ['q' => 'omar.western']));
        $emailSearch->assertOk();
        $this->assertCount(0, $emailSearch->viewData('leads'));
        $emailSearch->assertDontSee('BRANCH_B_SECRET_LEAD_OMAR');
    }
    public function test_super_admin_protections_cannot_be_bypassed_by_lower_roles(): void
    {
        // Manager in Branch A cannot assign super-admin group
        $superAdminGroupId = Group::where('code', Group::SUPER_ADMIN_CODE)->value('id');

        $managerGroup = Group::query()->create([
            'name' => 'مدير الفرع',
            'code' => 'branch-manager-role',
            'is_system' => false,
        ]);
        $managerGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::USERS_VIEW->value,
            CrmPermission::USERS_CREATE->value,
            CrmPermission::USERS_UPDATE->value,
            CrmPermission::GROUPS_VIEW->value,
            CrmPermission::GROUPS_UPDATE->value,
        ])->pluck('id'));

        $manager = User::factory()->create([
            'username' => 'branch_mgr_user',
            'is_active' => true,
        ]);
        $manager->groups()->attach($managerGroup);

        // Attempt to create user with super-admin group
        $createResponse = $this->actingAs($manager)->post(route('v2.settings.users.store'), [
            'name' => 'Rogue Super Admin',
            'username' => 'rogue_admin',
            'password' => 'Pass123456789!',
            'password_confirmation' => 'Pass123456789!',
            'group_ids' => [$superAdminGroupId],
        ]);
        $this->assertTrue($createResponse->isInvalid() || $createResponse->isForbidden() || $createResponse->isRedirect());
        $this->assertDatabaseMissing('users', ['username' => 'rogue_admin']);

        // Attempt to edit super admin user
        $editResponse = $this->actingAs($manager)->get(route('v2.settings.users.edit', $this->superAdmin));
        $editResponse->assertForbidden();
    }
}
