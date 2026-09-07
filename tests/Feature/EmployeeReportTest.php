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
use App\Models\Quotation;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $agentA;
    private User $agentB;
    private PipelineStage $stageNew;
    private PipelineStage $stageClosing;
    private LeadStatus $statusNew;
    private LeadStatus $statusClosed;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ]
            );
        }

        $superGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'is_system' => true,
            ]
        );
        $superGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'username' => 'admin_reports',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superGroup);

        $this->agentA = User::factory()->create([
            'name' => 'مندوب المبيعات أ',
            'username' => 'agent_a',
            'is_active' => true,
        ]);
        $this->agentB = User::factory()->create([
            'name' => 'مندوب المبيعات ب',
            'username' => 'agent_b',
            'is_active' => true,
        ]);

        $this->stageNew = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'مرحلة البداية',
                'position' => 1,
                'color' => '#3b82f6',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->statusNew = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $this->stageNew->id,
                'name_ar' => 'جديد',
                'position' => 1,
            ]
        );

        $this->stageClosing = PipelineStage::query()->firstOrCreate(
            ['code' => 'contract_closed'],
            [
                'name_ar' => 'مرحلة التعاقد',
                'position' => 2,
                'color' => '#10b981',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->statusClosed = LeadStatus::query()->firstOrCreate(
            ['code' => 'closed_won'],
            [
                'pipeline_stage_id' => $this->stageClosing->id,
                'name_ar' => 'تم التعاقد',
                'position' => 2,
            ]
        );
        $this->campaign = Campaign::query()->create([
            'name' => 'حملة الصيف 2026',
            'starts_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
            'cost' => 5000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $this->campaign->users()->attach([$this->agentA->id, $this->agentB->id]);
    }

    public function test_unauthorized_user_cannot_access_employee_reports(): void
    {
        $unprivileged = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($unprivileged)->get(route('v2.reports.employees'));
        $response->assertForbidden();
    }

    public function test_authorized_user_can_access_employee_reports(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees'));

        $response->assertOk();
        $response->assertSee(__('تقارير الموظفين'));
        $response->assertSee(__('إجمالي العملاء'));
        $response->assertSee(__('معدل التحويل'));
        $response->assertSee(__('المتابعات المنفذة'));
        $response->assertSee(__('المتابعات المستحقة'));
        $response->assertSee('targetStageSelect');
        $response->assertSee('emp-filter-bar');
        $response->assertSee('empSourcesChart');
        $response->assertSee('مصادر استقطاب العملاء');

        $filters = $response->viewData('filters');
        $this->assertEquals('all', $filters['period']);
        $this->assertNull($filters['employee_id']);
        $this->assertNull($filters['stage_id']);
        $this->assertNull($filters['campaign_id']);
    }

    public function test_employee_reports_filters_by_specific_employee(): void
    {
        $leadA1 = Lead::query()->create([
            'name' => 'عميل للمندوب أ 1',
            'phone' => '0501111111',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);

        $leadA2 = Lead::query()->create([
            'name' => 'عميل محول للمندوب أ',
            'phone' => '0501111112',
            'lead_status_id' => $this->statusClosed->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);

        $leadB1 = Lead::query()->create([
            'name' => 'عميل للمندوب ب',
            'phone' => '0502222221',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentB->id,
            'assigned_employee' => $this->agentB->name,
        ]);

        // Filter by Agent A
        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees', [
            'employee_id' => $this->agentA->id,
            'period' => 'all',
        ]));

        $response->assertOk();
        $response->assertSee($this->agentA->name);
        $response->assertSee('عميل للمندوب أ 1');
        $response->assertSee('عميل محول للمندوب أ');
        $response->assertDontSee('عميل للمندوب ب');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['total_leads']);
        $this->assertEquals(1, $metrics['converted_leads']);
        $this->assertEquals(50.0, $metrics['conversion_rate']);
    }

    public function test_employee_reports_filters_by_campaign_and_stage(): void
    {
        $leadCamp = Lead::query()->create([
            'name' => 'عميل الحملة والمرحلة',
            'phone' => '0503333331',
            'lead_status_id' => $this->statusClosed->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);
        $this->campaign->leads()->attach($leadCamp->id);

        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees', [
            'campaign_id' => $this->campaign->id,
            'stage_id' => $this->stageClosing->id,
            'period' => 'all',
        ]));

        $response->assertOk();
        $response->assertSee('عميل الحملة والمرحلة');

        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total_leads']);
        $this->assertEquals(1, $metrics['converted_leads']);
        $this->assertEquals(100.0, $metrics['conversion_rate']);
    }

    public function test_employee_reports_calculates_followups_and_quotations(): void
    {
        $lead = Lead::query()->create([
            'name' => 'عميل المتابعات والعروض',
            'phone' => '0504444441',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);

        LeadFollowup::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $this->agentA->id,
            'employee_name' => $this->agentA->name,
            'to_status_id' => $this->statusNew->id,
            'communication_type' => 'call',
            'outcome' => 'مهتم جداً',
            'followed_up_at' => now(),
        ]);

        Quotation::query()->create([
            'quotation_no' => 'Q-999',
            'client_name' => 'شركة المتابعات',
            'prepared_by' => $this->agentA->name,
            'created_by' => $this->agentA->name,
            'created_by_user_id' => $this->agentA->id,
            'quote_date' => now()->toDateString(),
            'grand_total' => 12500.00,
            'payload' => ['items' => []],
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees', [
            'employee_id' => $this->agentA->id,
            'period' => 'all',
        ]));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total_followups']);
        $this->assertArrayHasKey('due_followups', $metrics);
    }

    public function test_employee_reports_calculates_dynamic_target_stage_conversion(): void
    {
        $leadInClosing = Lead::query()->create([
            'name' => 'عميل في مرحلة التعاقد',
            'phone' => '0505555551',
            'lead_status_id' => $this->statusClosed->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);

        $leadInNew = Lead::query()->create([
            'name' => 'عميل في مرحلة البداية',
            'phone' => '0505555552',
            'lead_status_id' => $this->statusNew->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees', [
            'employee_id' => $this->agentA->id,
            'target_stage_id' => $this->stageClosing->id,
            'period' => 'all',
        ]));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['total_leads']);
        $this->assertEquals(1, $metrics['target_stage_leads']);
        $this->assertEquals(50.0, $metrics['target_stage_rate']);
        $this->assertEquals($this->stageClosing->id, $metrics['target_stage_id']);
    }

    public function test_employee_reports_renders_pipeline_stages_strip(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees'));

        $response->assertOk();
        $response->assertSee('dash-pipeline-strip-wrap');
        $response->assertSee('pipeline-flow-pill');
        $response->assertSee('مراحل مسار المبيعات');
        $response->assertSee('updateTargetStageAsync');
    }

    public function test_employee_reports_returns_json_on_ajax_stage_switch(): void
    {
        Lead::query()->create([
            'name' => 'عميل اختبار الأجاكس',
            'phone' => '0501234567',
            'lead_status_id' => $this->statusClosed->id,
            'assigned_user_id' => $this->agentA->id,
            'assigned_employee' => $this->agentA->name,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.reports.employees', [
            'employee_id' => $this->agentA->id,
            'target_stage_id' => $this->stageClosing->id,
            'ajax' => '1',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'target_stage_id' => $this->stageClosing->id,
            'target_stage_leads' => 1,
        ]);
    }
}
