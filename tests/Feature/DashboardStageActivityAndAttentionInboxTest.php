<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStageActivityAndAttentionInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageMeeting;
    private PipelineStage $stageOffer;
    private LeadStatus $statusMeeting;
    private LeadStatus $statusOffer;

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
            'username' => 'stage_admin',
            'name' => 'Stage Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stageMeeting = PipelineStage::query()->firstWhere('code', 'meeting') ?? PipelineStage::query()->create([
            'code' => 'meeting',
            'name_ar' => 'مقابلة',
            'position' => 1,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->statusMeeting = LeadStatus::query()->firstWhere('code', 'meeting_status') ?? LeadStatus::query()->create([
            'pipeline_stage_id' => $this->stageMeeting->id,
            'code' => 'meeting_status',
            'name_ar' => 'حالة المقابلة',
            'position' => 1,
            'is_terminal' => false,
        ]);

        $this->stageOffer = PipelineStage::query()->firstWhere('code', 'quotation') ?? PipelineStage::query()->create([
            'code' => 'quotation',
            'name_ar' => 'عرض سعر',
            'position' => 2,
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->statusOffer = LeadStatus::query()->firstWhere('code', 'quotation_status') ?? LeadStatus::query()->create([
            'pipeline_stage_id' => $this->stageOffer->id,
            'code' => 'quotation_status',
            'name_ar' => 'حالة العرض',
            'position' => 1,
            'is_terminal' => false,
        ]);
    }

    // ==========================================
    // STAGE ACTIVITY TESTS (1 - 10)
    // ==========================================

    public function test_stage_activity_today_metric_returns_exact_matching_leads_and_equals_kpi(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        // 3 leads due today
        for ($i = 0; $i < 3; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل اليوم ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->addHours($i + 1),
            ]);
        }

        // 2 leads overdue
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل متأخر ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDays(2),
            ]);
        }

        // 1. Check Stage Activity endpoint KPI
        $kpiResponse = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity',
            'activity_stage_id' => $this->stageMeeting->id,
        ]));
        $kpiResponse->assertOk()
            ->assertJsonPath('today_count', 3)
            ->assertJsonPath('overdue_count', 2);

        // 2. Check Stage Activity Leads endpoint for Today bucket
        $leadsResponse = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'today',
        ]));
        $leadsResponse->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'leads')
            ->assertJsonPath('leads.0.timing', 'today');

        // Verify invariant: KPI count == Modal total
        $this->assertSame($kpiResponse->json('today_count'), $leadsResponse->json('total'));
    }

    public function test_stage_activity_overdue_and_upcoming_metrics_match_modal_totals(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        // 4 overdue
        for ($i = 0; $i < 4; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'متأخر ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDays($i + 1),
            ]);
        }

        // 5 upcoming
        for ($i = 0; $i < 5; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'قادم ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->addDays($i + 2),
            ]);
        }

        // Overdue modal
        $overdueRes = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'overdue',
        ]));
        $overdueRes->assertOk()
            ->assertJsonPath('total', 4)
            ->assertJsonCount(4, 'leads')
            ->assertJsonPath('leads.0.timing', 'overdue');

        // Upcoming modal
        $upcomingRes = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'upcoming',
        ]));
        $upcomingRes->assertOk()
            ->assertJsonPath('total', 5)
            ->assertJsonCount(5, 'leads')
            ->assertJsonPath('leads.0.timing', 'upcoming');
    }

    public function test_stage_activity_respects_selected_stage(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        // 3 leads in Meeting stage
        for ($i = 0; $i < 3; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل مقابلة ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDay(),
            ]);
        }

        // 2 leads in Offer stage
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusOffer->id,
                'name' => 'عميل عرض ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDay(),
            ]);
        }

        $meetingRes = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'overdue',
        ]));
        $meetingRes->assertOk()->assertJsonPath('total', 3);

        $offerRes = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageOffer->id,
            'bucket' => 'overdue',
        ]));
        $offerRes->assertOk()->assertJsonPath('total', 2);
    }

    public function test_stage_activity_respects_employee_filter(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        $agent = User::factory()->create([
            'username' => 'filter_agent',
            'name' => 'Filter Agent',
            'is_active' => true,
        ]);

        // 2 leads assigned to $agent
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل وكيل ' . $i,
                'assigned_user_id' => $agent->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDay(),
            ]);
        }

        // 3 leads assigned to admin
        for ($i = 0; $i < 3; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل أدمن ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDay(),
            ]);
        }

        $filteredRes = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'overdue',
            'employee' => 'Filter Agent',
        ]));
        $filteredRes->assertOk()->assertJsonPath('total', 2);
    }

    public function test_stage_activity_excludes_unauthorized_leads_and_preserves_scope(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        $restrictedGroup = Group::query()->create([
            'name' => 'Restricted Group',
            'code' => 'restricted-agent',
        ]);
        $restrictedGroup->permissions()->attach(
            Permission::whereIn('code', ['dashboard.view', 'leads.view'])->pluck('id')
        );

        $restrictedUser = User::factory()->create([
            'username' => 'restricted_stage_user',
            'name' => 'Restricted User',
            'is_active' => true,
        ]);
        $restrictedUser->groups()->attach($restrictedGroup);

        $otherUser = User::factory()->create(['is_active' => true]);

        // 2 leads assigned to restricted user
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميلي المصرح ' . $i,
                'assigned_user_id' => $restrictedUser->id,
                'created_by_user_id' => $restrictedUser->id,
                'next_follow_up_at' => $now->copy()->subDay(),
            ]);
        }

        // 5 leads assigned to other user
        for ($i = 0; $i < 5; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل مستخدم آخر ' . $i,
                'assigned_user_id' => $otherUser->id,
                'created_by_user_id' => $otherUser->id,
                'next_follow_up_at' => $now->copy()->subDay(),
            ]);
        }

        $response = $this->actingAs($restrictedUser)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'overdue',
        ]));
        $response->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonMissing(['name' => 'عميل مستخدم آخر 0']);
    }

    public function test_stage_activity_modal_pagination_works(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        // 25 leads overdue
        for ($i = 0; $i < 25; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'عميل صفحة ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDays($i + 1),
            ]);
        }

        // Page 1 with per_page 10
        $page1Res = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'overdue',
            'page' => 1,
            'per_page' => 10,
        ]));
        $page1Res->assertOk()
            ->assertJsonPath('total', 25)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('last_page', 3)
            ->assertJsonCount(10, 'leads');

        // Page 3
        $page3Res = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'overdue',
            'page' => 3,
            'per_page' => 10,
        ]));
        $page3Res->assertOk()
            ->assertJsonPath('current_page', 3)
            ->assertJsonCount(5, 'leads');
    }

    public function test_stage_activity_zero_state_returns_empty_response(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('dashboard', [
            'ajax' => 1,
            'widget' => 'stage_activity_leads',
            'activity_stage_id' => $this->stageMeeting->id,
            'bucket' => 'today',
        ]));
        $response->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('empty', true)
            ->assertJsonCount(0, 'leads');
    }

    // ==========================================
    // ATTENTION PANEL TESTS (11 - 20)
    // ==========================================

    public function test_due_followups_overdue_today_tomorrow_and_later_filters(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        // 1 Overdue (yesterday)
        Lead::query()->create([
            'lead_status_id' => $this->statusMeeting->id,
            'name' => 'Overdue Lead',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
            'next_follow_up_at' => $now->copy()->subDay(),
        ]);

        // 2 Today
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'Today Lead ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->addHours($i + 1),
            ]);
        }

        // 3 Tomorrow
        for ($i = 0; $i < 3; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'Tomorrow Lead ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->addDay()->addHours($i + 1),
            ]);
        }

        // 4 Later
        for ($i = 0; $i < 4; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'Later Lead ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->addDays(5),
            ]);
        }

        // Test overdue filter
        $overdueRes = $this->actingAs($this->admin)->getJson(route('v2.notifications.due-followups', ['filter' => 'overdue']));
        $overdueRes->assertOk()
            ->assertJsonPath('meta.overdue', 1)
            ->assertJsonPath('meta.today', 2)
            ->assertJsonPath('meta.tomorrow', 3)
            ->assertJsonPath('meta.later', 4)
            ->assertJsonPath('meta.total_for_filter', 1)
            ->assertJsonCount(1, 'items');

        // Test today filter
        $todayRes = $this->actingAs($this->admin)->getJson(route('v2.notifications.due-followups', ['filter' => 'today']));
        $todayRes->assertOk()
            ->assertJsonPath('meta.total_for_filter', 2)
            ->assertJsonCount(2, 'items');

        // Test tomorrow filter
        $tomorrowRes = $this->actingAs($this->admin)->getJson(route('v2.notifications.due-followups', ['filter' => 'tomorrow']));
        $tomorrowRes->assertOk()
            ->assertJsonPath('meta.total_for_filter', 3)
            ->assertJsonCount(3, 'items');

        // Test later filter
        $laterRes = $this->actingAs($this->admin)->getJson(route('v2.notifications.due-followups', ['filter' => 'later']));
        $laterRes->assertOk()
            ->assertJsonPath('meta.total_for_filter', 4)
            ->assertJsonCount(4, 'items');
    }

    public function test_attention_panel_enforces_visible_limit_and_preserves_total_count(): void
    {
        $now = Carbon::parse('2026-09-02 12:00:00');
        Carbon::setTestNow($now);

        // 8 overdue leads
        for ($i = 0; $i < 8; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusMeeting->id,
                'name' => 'Overdue Mass ' . $i,
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => $now->copy()->subDays($i + 1),
            ]);
        }

        $res = $this->actingAs($this->admin)->getJson(route('v2.notifications.due-followups', ['filter' => 'overdue', 'limit' => 5]));
        $res->assertOk()
            ->assertJsonPath('meta.total_for_filter', 8)
            ->assertJsonPath('meta.limit', 5)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('meta.more_count', 3)
            ->assertJsonCount(5, 'items');
    }

    public function test_notification_drawer_markup_has_attention_filters_and_no_lower_section(): void
    {
        $view = $this->actingAs($this->admin)->get(route('dashboard'));
        $view->assertOk();

        // Check Attention Inbox Elements
        $view->assertSee('crm-attention-filters', false);
        $view->assertSee('data-attention-filter="overdue"', false);
        $view->assertSee('data-attention-filter="today"', false);
        $view->assertSee('data-attention-filter="tomorrow"', false);
        $view->assertSee('data-attention-filter="later"', false);
        $view->assertSee('crm-attention-footer', false);
        $view->assertSee('crm-attention-view-all', false);

        // Check Lower Section Removed
        $view->assertDontSee('crm-notification-controls', false);
        $view->assertDontSee('crm-notification-read-all', false);
        $view->assertDontSee('id="crmNotificationList"', false);
        $view->assertDontSee('id="crmNotificationLoadMore"', false);
    }
}
