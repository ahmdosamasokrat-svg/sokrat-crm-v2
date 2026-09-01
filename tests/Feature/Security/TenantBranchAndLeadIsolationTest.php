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

class TenantBranchAndLeadIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $agentBranchA;
    private User $agentBranchB;
    private User $managerBranchA;
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

        // Branch A Group & Users
        $groupBranchA = Group::query()->create([
            'name' => 'فرع الرياض',
            'code' => 'branch-riyadh',
            'is_system' => false,
        ]);
        $groupBranchA->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
            CrmPermission::QUOTATIONS_VIEW->value,
            CrmPermission::VOIP_VIEW->value,
        ])->pluck('id'));

        $this->agentBranchA = User::factory()->create([
            'name' => 'مندوب فرع أ',
            'username' => 'agent_branch_a',
            'is_active' => true,
        ]);
        $this->agentBranchA->groups()->attach($groupBranchA);

        // Branch A Manager (has leads.scope.group)
        $managerGroupA = Group::query()->create([
            'name' => 'مدراء فرع الرياض',
            'code' => 'managers-riyadh',
            'is_system' => false,
        ]);
        $managerGroupA->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_GROUP->value,
            CrmPermission::LEADS_ASSIGN->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_EXPORT->value,
        ])->pluck('id'));

        $this->managerBranchA = User::factory()->create([
            'name' => 'مدير فرع أ',
            'username' => 'manager_branch_a',
            'is_active' => true,
        ]);
        $this->managerBranchA->groups()->attach([$groupBranchA->id, $managerGroupA->id]);

        // Branch B Group & User
        $groupBranchB = Group::query()->create([
            'name' => 'فرع جدة',
            'code' => 'branch-jeddah',
            'is_system' => false,
        ]);
        $groupBranchB->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_DELETE->value,
            CrmPermission::LEADS_EXPORT->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::TASKS_VIEW->value,
            CrmPermission::QUOTATIONS_VIEW->value,
            CrmPermission::VOIP_VIEW->value,
        ])->pluck('id'));

        $this->agentBranchB = User::factory()->create([
            'name' => 'مندوب فرع ب',
            'username' => 'agent_branch_b',
            'is_active' => true,
        ]);
        $this->agentBranchB->groups()->attach($groupBranchB);

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
            'name' => 'عميل فرع الرياض المميز',
            'phone' => '0501111111',
            'email' => 'riyadh.client@example.com',
            'assigned_user_id' => $this->agentBranchA->id,
            'source' => 'riyadh_campaign',
        ]);

        $this->leadBranchB = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'عميل فرع جدة السري',
            'phone' => '0502222222',
            'email' => 'jeddah.client@example.com',
            'assigned_user_id' => $this->agentBranchB->id,
            'source' => 'jeddah_campaign',
        ]);
    }

    public function test_agent_a_sees_only_branch_a_leads_in_index(): void
    {
        $response = $this->actingAs($this->agentBranchA)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('عميل فرع الرياض المميز');
        $response->assertDontSee('عميل فرع جدة السري');
        $response->assertDontSee('0502222222');
        $response->assertDontSee('jeddah.client@example.com');
    }

    public function test_agent_a_cannot_view_branch_b_lead_details(): void
    {
        $response = $this->actingAs($this->agentBranchA)->get(route('v2.leads.show', $this->leadBranchB));
        $response->assertForbidden();
    }

    public function test_agent_a_cannot_edit_or_update_branch_b_lead(): void
    {
        $editResponse = $this->actingAs($this->agentBranchA)->get(route('v2.leads.edit', $this->leadBranchB));
        $editResponse->assertForbidden();

        $patchResponse = $this->actingAs($this->agentBranchA)->patch(route('v2.leads.update', $this->leadBranchB), [
            'name' => 'Tampered Lead Name',
        ]);
        $patchResponse->assertForbidden();

        $this->assertEquals('عميل فرع جدة السري', $this->leadBranchB->fresh()->name);
    }

    public function test_agent_a_cannot_delete_branch_b_lead(): void
    {
        $response = $this->actingAs($this->agentBranchA)->delete(route('v2.leads.destroy', $this->leadBranchB));
        $response->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $this->leadBranchB->id]);
    }

    public function test_agent_a_cannot_view_or_create_followups_for_branch_b_lead(): void
    {
        $indexResponse = $this->actingAs($this->agentBranchA)->get(route('v2.leads.followups.index', $this->leadBranchB));
        $indexResponse->assertForbidden();

        $storeResponse = $this->actingAs($this->agentBranchA)->post(route('v2.leads.followups.store', $this->leadBranchB), [
            'communication_type' => 'call',
            'outcome' => 'Unauthorized followup attempt',
        ]);
        $storeResponse->assertForbidden();
    }

    public function test_agent_a_cannot_view_quotation_preview_for_branch_b_lead(): void
    {
        $response = $this->actingAs($this->agentBranchA)->get(route('v2.leads.quotation.preview', $this->leadBranchB));
        $response->assertForbidden();
    }

    public function test_agent_a_cannot_view_voip_calls_for_branch_b_lead(): void
    {
        $response = $this->actingAs($this->agentBranchA)->get(route('v2.leads.calls', $this->leadBranchB));
        $response->assertForbidden();
    }

    public function test_agent_a_cannot_reschedule_or_quick_followup_branch_b_lead(): void
    {
        $rescheduleResponse = $this->actingAs($this->agentBranchA)->post(route('v2.tasks.reschedule', $this->leadBranchB), [
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]);
        $rescheduleResponse->assertForbidden();

        $quickResponse = $this->actingAs($this->agentBranchA)->post(route('v2.tasks.quick_followup', $this->leadBranchB), [
            'communication_type' => 'call',
            'outcome' => 'Quick attempt',
        ]);
        $quickResponse->assertForbidden();
    }

    public function test_lead_export_strictly_excludes_unauthorized_leads(): void
    {
        // 1. Attempting to export unauthorized lead ID is rejected with 422
        $unauthorizedAttempt = $this->actingAs($this->agentBranchA)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$this->leadBranchA->id, $this->leadBranchB->id],
        ]);
        $this->assertEquals(422, $unauthorizedAttempt->getStatusCode());

        // 2. Exporting authorized lead succeeds and only contains authorized lead data
        $authorizedExport = $this->actingAs($this->agentBranchA)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$this->leadBranchA->id],
        ]);
        $authorizedExport->assertOk();

        $tmpFile = tempnam(sys_get_temp_dir(), 'exp_test_');
        file_put_contents($tmpFile, $authorizedExport->streamedContent());
        $zip = new \ZipArchive();
        $zip->open($tmpFile);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml') ?: '';
        $zip->close();
        @unlink($tmpFile);

        $this->assertStringContainsString('عميل فرع الرياض المميز', $sheetXml);
        $this->assertStringNotContainsString('عميل فرع جدة السري', $sheetXml);
        $this->assertStringNotContainsString('0502222222', $sheetXml);
        $this->assertStringNotContainsString('jeddah.client@example.com', $sheetXml);
    }

    public function test_manager_branch_a_can_see_branch_a_leads_but_not_branch_b(): void
    {
        $response = $this->actingAs($this->managerBranchA)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('عميل فرع الرياض المميز');
        $response->assertDontSee('عميل فرع جدة السري');
    }
}
