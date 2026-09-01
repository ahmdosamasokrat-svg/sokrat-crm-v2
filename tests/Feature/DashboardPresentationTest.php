<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPresentationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $startStage;
    private PipelineStage $interestStage;
    private PipelineStage $closingStage;
    private LeadStatus $newStatus;
    private LeadStatus $interestedStatus;
    private LeadStatus $closedStatus;

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

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'dash_ux_admin',
            'name' => 'Dashboard UX Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->startStage = PipelineStage::query()->updateOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'البداية',
                'position' => 1,
                'color' => '#3478f6',
                'icon' => 'bi-person-plus',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->newStatus = $this->startStage->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $this->startStage->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
            ]
        );
        $this->newStatus->update([
            'code' => 'new',
            'name_ar' => 'جديد',
            'color' => '#3478f6',
        ]);

        $this->interestStage = PipelineStage::query()->updateOrCreate(
            ['code' => 'interest'],
            [
                'name_ar' => 'الاهتمام',
                'position' => 2,
                'color' => '#10b981',
                'icon' => 'bi-hand-thumbs-up',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->interestedStatus = $this->interestStage->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'interested'],
            [
                'pipeline_stage_id' => $this->interestStage->id,
                'name_ar' => 'مهتم',
                'position' => 2,
                'color' => '#10b981',
            ]
        );
        $this->interestedStatus->update([
            'code' => 'interested',
            'name_ar' => 'مهتم',
            'color' => '#10b981',
        ]);

        $this->closingStage = PipelineStage::query()->updateOrCreate(
            ['code' => 'closing_execution'],
            [
                'name_ar' => 'التعاقد',
                'position' => 3,
                'color' => '#7b61df',
                'icon' => 'bi-check-circle',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->closedStatus = $this->closingStage->statuses()->first() ?? LeadStatus::query()->firstOrCreate(
            ['code' => 'contract_closed'],
            [
                'pipeline_stage_id' => $this->closingStage->id,
                'name_ar' => 'تم التعاقد',
                'position' => 3,
                'color' => '#7b61df',
            ]
        );
        $this->closedStatus->update([
            'code' => 'contract_closed',
            'name_ar' => 'تم التعاقد',
            'color' => '#7b61df',
        ]);
    }

    public function test_dashboard_loads_and_does_not_render_sales_pipeline_stage_card_block(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('sales-pipeline-panel');
        $response->assertDontSee('sales-pipeline');
        $response->assertDontSee('pipeline-kanban-btn');
        $response->assertDontSee('Kanban View');
        $response->assertDontSee('تقدم العملاء من أول تواصل حتى التنفيذ');
    }

    public function test_mini_calendar_renders_with_real_event_data_and_current_month(): void
    {
        CalendarEvent::query()->create([
            'user_id' => $this->admin->id,
            'title' => 'متابعة هامة للعميل أحمد',
            'start_time' => now()->startOfDay()->addHours(10),
            'end_time' => now()->startOfDay()->addHours(11),
            'type' => 'call',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('mini-calendar-panel');
        $response->assertSee('mini-cal-grid');
        $response->assertSee('التقويم');
        $response->assertSee('عرض التقويم الكامل');
        $response->assertSee(route('v2.calendar.index'));
        $response->assertSee('متابعة هامة للعميل أحمد');
    }

    public function test_mini_calendar_in_english_renders_proper_labels(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('mini-calendar-panel');
        $response->assertSee('Calendar');
        $response->assertSee('View Full Calendar');
    }

    public function test_calendar_event_authorization_scoping_is_respected(): void
    {
        $restrictedGroup = Group::query()->create([
            'name' => 'مندوب مبيعات فردي',
            'code' => 'sales-agent-own',
            'is_system' => false,
        ]);
        $restrictedGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ])->pluck('id'));

        $agent = User::factory()->create([
            'username' => 'agent_sam',
            'name' => 'Agent Sam',
            'is_active' => true,
        ]);
        $agent->groups()->attach($restrictedGroup);

        // Own event for Agent Sam
        CalendarEvent::query()->create([
            'user_id' => $agent->id,
            'title' => 'حدث سام الشخصي',
            'start_time' => now()->startOfDay()->addHours(9),
            'end_time' => now()->startOfDay()->addHours(10),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);
        // Secret Admin Event (not accessible to Agent Sam)
        CalendarEvent::query()->create([
            'user_id' => $this->admin->id,
            'title' => 'اجتماع سري للإدارة العليا',
            'start_time' => now()->startOfDay()->addHours(14),
            'end_time' => now()->startOfDay()->addHours(15),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($agent)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('حدث سام الشخصي');
        $response->assertDontSee('اجتماع سري للإدارة العليا');
    }

    public function test_performance_chart_renders_canvas_when_meaningful_data_exists(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Active Lead Today',
            'phone' => '0501112233',
            'created_at' => now(),
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<canvas id="performanceChart"></canvas>', false);
    }

    public function test_performance_chart_omits_canvas_and_shows_compact_empty_state_when_zero_data(): void
    {
        // Genuinely 0 leads / 0 activity
        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('<canvas id="performanceChart"></canvas>', false);
        $response->assertSee('chart-empty-container');
        $response->assertSee('لا توجد حركة كافية لعرض الرسم البياني');
        $response->assertSee('لم يتم تسجيل نشاط كاف خلال الفترة المحددة.');
    }

    public function test_performance_chart_empty_state_in_english(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('<canvas id="performanceChart"></canvas>', false);
        $response->assertSee('chart-empty-container');
        $response->assertSee('Not enough activity to display the chart');
        $response->assertSee('There is not enough recorded activity for the selected period.');
    }

    public function test_kanban_route_and_sidebar_remain_intact(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();

        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee(route('v2.leads.kanban'));
    }

    public function test_western_digits_preserved_in_both_locales(): void
    {
        Lead::query()->create(['lead_status_id' => $this->newStatus->id, 'name' => 'Lead 106', 'phone' => '0500000106', 'source' => 'web']);

        $responseAr = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $responseAr->assertOk();
        $responseAr->assertSee('1'); // Western digit 1
        $responseAr->assertDontSee('١'); // No Arabic-Indic digit

        $responseEn = $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $responseEn->assertOk();
        $responseEn->assertSee('1');
        $responseEn->assertDontSee('١');
    }

    public function test_mini_calendar_renders_today_events_with_type_and_status_badges_and_action_link(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'شركة النخبة للتقنية',
            'company_name' => 'Elite Tech Co',
            'phone' => '0554433221',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        $event = CalendarEvent::query()->create([
            'user_id' => $this->admin->id,
            'lead_id' => $lead->id,
            'title' => 'جلسة استشارية مغلقة',
            'start_time' => now()->startOfDay()->addHours(11),
            'end_time' => now()->startOfDay()->addHours(12),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('جلسة استشارية مغلقة');
        $response->assertSee('شركة النخبة للتقنية');
        $response->assertSee(route('v2.leads.show', $lead));
        $response->assertSee('event-type-meeting');
        $response->assertSee('event-status-scheduled');
    }

    public function test_mini_calendar_group_manager_scoping_is_respected(): void
    {
        $group = Group::query()->create([
            'name' => 'فرع الرياض',
            'code' => 'branch-riyadh',
            'is_system' => false,
        ]);
        $group->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CALENDAR_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_GROUP->value,
        ])->pluck('id'));

        $manager = User::factory()->create([
            'username' => 'mgr_riyadh',
            'name' => 'Riyadh Branch Manager',
            'is_active' => true,
        ]);
        $manager->groups()->attach($group);

        $branchEmployee = User::factory()->create([
            'username' => 'emp_riyadh',
            'name' => 'Riyadh Employee',
            'is_active' => true,
        ]);
        $branchEmployee->groups()->attach($group);

        $otherEmployee = User::factory()->create([
            'username' => 'emp_jeddah',
            'name' => 'Jeddah Employee',
            'is_active' => true,
        ]);

        // Event by employee in the same group
        CalendarEvent::query()->create([
            'user_id' => $branchEmployee->id,
            'title' => 'اجتماع فرع الرياض',
            'start_time' => now()->startOfDay()->addHours(10),
            'end_time' => now()->startOfDay()->addHours(11),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        // Event by employee in another group/unrelated
        CalendarEvent::query()->create([
            'user_id' => $otherEmployee->id,
            'title' => 'اجتماع فرع جدة المستقل',
            'start_time' => now()->startOfDay()->addHours(10),
            'end_time' => now()->startOfDay()->addHours(11),
            'type' => 'meeting',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('اجتماع فرع الرياض');
        $response->assertDontSee('اجتماع فرع جدة المستقل');
    }
}
