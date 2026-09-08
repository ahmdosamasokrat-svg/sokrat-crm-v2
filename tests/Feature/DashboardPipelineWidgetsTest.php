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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPipelineWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $startStage;
    private PipelineStage $interestStage;
    private PipelineStage $customStage;
    private LeadStatus $newStatus;
    private LeadStatus $interestedStatus;
    private LeadStatus $customStatus;

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
            'username' => 'widget_admin',
            'name' => 'Widget Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->startStage = PipelineStage::query()->firstOrCreate(
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
        $this->newStatus = $this->startStage->statuses()->first()
            ?? LeadStatus::query()->firstOrCreate(
                ['code' => 'new'],
                [
                    'pipeline_stage_id' => $this->startStage->id,
                    'name_ar' => 'جديد',
                    'position' => 1,
                    'color' => '#3478f6',
                    'is_terminal' => false,
                ]
            );

        $this->interestStage = PipelineStage::query()->firstOrCreate(
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
        $this->interestedStatus = $this->interestStage->statuses()->first()
            ?? LeadStatus::query()->firstOrCreate(
                ['code' => 'interested'],
                [
                    'pipeline_stage_id' => $this->interestStage->id,
                    'name_ar' => 'مهتم',
                    'position' => 3,
                    'color' => '#10b981',
                    'is_terminal' => false,
                ]
            );

        $this->customStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'stage_field_visit'],
            [
                'name_ar' => 'زيارة ميدانية',
                'position' => 3,
                'color' => '#8b5cf6',
                'icon' => 'bi-geo-alt',
                'is_primary' => false,
                'is_active' => true,
            ]
        );
        $this->customStatus = $this->customStage->statuses()->first()
            ?? LeadStatus::query()->firstOrCreate(
                ['code' => 'status_visited'],
                [
                    'pipeline_stage_id' => $this->customStage->id,
                    'name_ar' => 'تمت الزيارة',
                    'position' => 10,
                    'color' => '#8b5cf6',
                    'is_terminal' => false,
                ]
            );
    }

    // 1. Stage selector loads active PipelineStages
    public function test_1_stage_selector_loads_active_pipeline_stages(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // Selectors must contain active stages
        $response->assertSee('id="conversionStageSelect"', false);
        $response->assertSee('id="stageKpi1Select"', false);
        $response->assertSee('id="stageKpi2Select"', false);
        $response->assertSee('id="stageActivitySelect"', false);

        $response->assertSee($this->startStage->localizedName());
        $response->assertSee($this->interestStage->localizedName());
        $response->assertSee($this->customStage->localizedName());
    }

    // 2. Inactive PipelineStages are excluded
    public function test_2_inactive_pipeline_stages_are_excluded(): void
    {
        $inactive = PipelineStage::query()->create([
            'code' => 'inactive_stage_' . uniqid(),
            'name_ar' => 'مرحلة معطلة خاصة',
            'position' => 99,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('value="' . $inactive->id . '"', false);
        $response->assertDontSee($inactive->name_ar);
    }

    // 3. Conversion KPI changes when selected stage changes
    public function test_3_conversion_kpi_changes_when_selected_stage_changes(): void
    {
        // 5 leads in startStage, 2 leads in interestStage
        for ($i = 0; $i < 5; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'Start Lead ' . $i,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->interestedStatus->id,
                'name' => 'Interest Lead ' . $i,
            ]);
        }

        // SSR request with conversion_stage_id = interestStage->id
        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'conversion_stage_id' => $this->interestStage->id,
        ]));
        $response->assertOk();

        $metrics = $response->viewData('conversionMetrics');
        $this->assertEquals($this->interestStage->id, $metrics['stage_id']);
        $this->assertTrue($metrics['has_previous_stage']);
        // Numerator = 2, Denominator = 5 + 2 = 7, Rate = round(2/7 * 100, 1) = 28.6%
        $this->assertEquals(28.6, $metrics['rate']);
        $this->assertEquals('28.6%', $metrics['display_value']);

        // AJAX request returns clean JSON
        $ajaxResponse = $this->actingAs($this->admin)->get(route('dashboard', [
            'ajax' => 1,
            'widget' => 'conversion',
            'conversion_stage_id' => $this->interestStage->id,
        ]));
        $ajaxResponse->assertOk();
        $ajaxResponse->assertJson([
            'success' => true,
            'widget' => 'conversion',
            'stage_id' => $this->interestStage->id,
            'rate' => 28.6,
            'display_value' => '28.6%',
        ]);
    }

    // 4. First-stage conversion handles N/A safely without division by zero
    public function test_4_first_stage_conversion_handles_na_safely(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'conversion_stage_id' => $this->startStage->id,
        ]));
        $response->assertOk();

        $metrics = $response->viewData('conversionMetrics');
        $this->assertEquals($this->startStage->id, $metrics['stage_id']);
        $this->assertFalse($metrics['has_previous_stage']);
        $this->assertNull($metrics['rate']);
        $this->assertEquals(__('crm.not_available'), $metrics['display_value']);

        $response->assertSee(__('crm.not_available'));
        $response->assertSee(__('crm.first_stage_no_previous'));
    }

    // 5. Dynamic KPI stage name follows stage rename
    public function test_5_dynamic_kpi_stage_name_follows_stage_rename(): void
    {
        app()->setLocale('ar');
        $this->interestStage->update(['name_ar' => 'مرحلة الاهتمام المعدلة']);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'stage_kpi_1' => $this->interestStage->id,
        ]));
        $response->assertOk();

        $kpi1 = $response->viewData('stageKpi1');
        $this->assertEquals('مرحلة الاهتمام المعدلة', $kpi1['stage_name']);
        $response->assertSee('مرحلة الاهتمام المعدلة');
    }

    // 6. Dynamic KPI follows stage reorder
    public function test_6_dynamic_kpi_follows_stage_reorder(): void
    {
        // Reorder customStage to position 0 (before startStage)
        $this->customStage->update(['position' => 0]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $stages = $response->viewData('activePipelineStages');
        $this->assertEquals($this->customStage->id, $stages->first()['id']);
    }

    // 7. Stage count respects accessibleTo scope
    public function test_7_stage_count_respects_accessible_to_scope(): void
    {
        $restrictedUser = User::factory()->create([
            'username' => 'restricted_rep',
            'is_active' => true,
        ]);

        $repGroup = Group::query()->create(['code' => 'rep', 'name' => 'مندوب']);
        $repGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
            ])->pluck('id')
        );
        $restrictedUser->groups()->attach($repGroup);

        // Lead assigned to restrictedUser
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Mine',
            'assigned_user_id' => $restrictedUser->id,
        ]);
        // Lead assigned to someone else
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Other',
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($restrictedUser)->get(route('dashboard', [
            'stage_kpi_1' => $this->startStage->id,
        ]));
        $response->assertOk();

        $kpi1 = $response->viewData('stageKpi1');
        $this->assertEquals(1, $kpi1['count']);
    }

    // 8. Branch isolation preserved
    public function test_8_branch_isolation_preserved(): void
    {
        $branchAGroup = Group::query()->create(['code' => 'branch_a', 'name' => 'فرع أ']);
        $branchAGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_GROUP->value,
            ])->pluck('id')
        );

        $branchBGroup = Group::query()->create(['code' => 'branch_b', 'name' => 'فرع ب']);
        $branchBGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_GROUP->value,
            ])->pluck('id')
        );

        $agentA = User::factory()->create(['is_active' => true]);
        $agentA->groups()->attach($branchAGroup);

        $agentB = User::factory()->create(['is_active' => true]);
        $agentB->groups()->attach($branchBGroup);

        // Lead in Branch A
        Lead::query()->create([
            'lead_status_id' => $this->interestedStatus->id,
            'name' => 'Branch A Lead',
            'assigned_user_id' => $agentA->id,
        ]);

        // Agent B views dashboard
        $responseB = $this->actingAs($agentB)->get(route('dashboard', [
            'stage_kpi_1' => $this->interestStage->id,
        ]));
        $responseB->assertOk();
        $this->assertEquals(0, $responseB->viewData('stageKpi1')['count']);

        // Agent A views dashboard
        $responseA = $this->actingAs($agentA)->get(route('dashboard', [
            'stage_kpi_1' => $this->interestStage->id,
        ]));
        $responseA->assertOk();
        $this->assertEquals(1, $responseA->viewData('stageKpi1')['count']);
    }

    // 9. Employee filter preserved
    public function test_9_employee_filter_preserved(): void
    {
        $employeeA = User::factory()->create(['name' => 'Amr Diab', 'is_active' => true]);
        $employeeB = User::factory()->create(['name' => 'Tamer Hosny', 'is_active' => true]);

        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Amr',
            'assigned_user_id' => $employeeA->id,
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Tamer',
            'assigned_user_id' => $employeeB->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'stage_kpi_1' => $this->startStage->id,
            'employee' => 'Amr Diab',
        ]));
        $response->assertOk();

        $kpi1 = $response->viewData('stageKpi1');
        $this->assertEquals(1, $kpi1['count']);
    }

    // 10. Meetings / Stage Activity panel changes by selected stage
    public function test_10_stage_activity_panel_changes_by_selected_stage(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Start Activity Lead',
            'next_follow_up_at' => now()->startOfDay()->addHours(10),
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->interestedStatus->id,
            'name' => 'Interest Activity Lead',
            'next_follow_up_at' => now()->startOfDay()->addHours(14),
        ]);

        // When startStage is selected
        $responseStart = $this->actingAs($this->admin)->get(route('dashboard', [
            'activity_stage_id' => $this->startStage->id,
        ]));
        $responseStart->assertOk();
        $actStart = $responseStart->viewData('stageActivity');
        $this->assertEquals($this->startStage->id, $actStart['stage_id']);
        $this->assertEquals(1, $actStart['today_count']);
        $this->assertEquals('Start Activity Lead', $actStart['leads'][0]['name']);

        // When interestStage is selected
        $responseInterest = $this->actingAs($this->admin)->get(route('dashboard', [
            'activity_stage_id' => $this->interestStage->id,
        ]));
        $responseInterest->assertOk();
        $actInterest = $responseInterest->viewData('stageActivity');
        $this->assertEquals($this->interestStage->id, $actInterest['stage_id']);
        $this->assertEquals(1, $actInterest['today_count']);
        $this->assertEquals('Interest Activity Lead', $actInterest['leads'][0]['name']);
    }

    // 11. Today grouping correct
    public function test_11_stage_activity_today_grouping_correct(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Today Lead',
            'next_follow_up_at' => now()->startOfDay()->addHours(12),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'activity_stage_id' => $this->startStage->id,
        ]));
        $response->assertOk();

        $activity = $response->viewData('stageActivity');
        $this->assertEquals(1, $activity['today_count']);
        $this->assertEquals(0, $activity['overdue_count']);
        $this->assertEquals(0, $activity['upcoming_count']);
    }

    // 12. Overdue grouping correct
    public function test_12_stage_activity_overdue_grouping_correct(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Overdue Lead',
            'next_follow_up_at' => now()->subDays(2)->startOfDay()->addHours(10),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'activity_stage_id' => $this->startStage->id,
        ]));
        $response->assertOk();

        $activity = $response->viewData('stageActivity');
        $this->assertEquals(0, $activity['today_count']);
        $this->assertEquals(1, $activity['overdue_count']);
        $this->assertEquals(0, $activity['upcoming_count']);
    }

    // 13. Upcoming grouping correct
    public function test_13_stage_activity_upcoming_grouping_correct(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Upcoming Lead',
            'next_follow_up_at' => now()->addDays(3)->startOfDay()->addHours(15),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'activity_stage_id' => $this->startStage->id,
        ]));
        $response->assertOk();

        $activity = $response->viewData('stageActivity');
        $this->assertEquals(0, $activity['today_count']);
        $this->assertEquals(0, $activity['overdue_count']);
        $this->assertEquals(1, $activity['upcoming_count']);
    }

    // 14. Empty state works
    public function test_14_stage_activity_empty_state_works(): void
    {
        // customStage has 0 leads with scheduled follow-ups
        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'activity_stage_id' => $this->customStage->id,
        ]));
        $response->assertOk();

        $activity = $response->viewData('stageActivity');
        $this->assertEquals(0, $activity['total_count']);
        $this->assertEmpty($activity['leads']);

        $response->assertSee(__('crm.no_activity_for_stage'));
    }

    // 15. Import action absent from Dashboard
    public function test_15_import_action_absent_from_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // In Quick actions container, import shortcut should not appear
        $response->assertDontSee('<a href="' . route('v2.leads.import') . '" class="quick-action-btn"', false);
    }

    // 16. Export action absent from Dashboard
    public function test_16_export_action_absent_from_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // In Quick actions container, export shortcut should not appear
        $response->assertDontSee('<a href="' . route('v2.leads.export') . '" class="quick-action-btn"', false);
    }

    // 17. Import/export routes remain functional elsewhere
    public function test_17_import_export_routes_remain_functional_elsewhere(): void
    {
        $importResponse = $this->actingAs($this->admin)->get(route('v2.leads.import'));
        $importResponse->assertOk();

        $exportResponse = $this->actingAs($this->admin)->get(route('v2.leads.export'));
        $this->assertTrue(in_array($exportResponse->status(), [200, 302], true));
    }

    // 18. FROM/TO selectors load active stages and exclude inactive stages
    public function test_18_from_to_selectors_load_active_stages_and_exclude_inactive(): void
    {
        $inactiveStage = PipelineStage::query()->create([
            'code' => 'inactive_stage_test',
            'name_ar' => 'مرحلة غير نشطة',
            'position' => 99,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('id="conversionFromStageSelect"', false);
        $response->assertSee('id="conversionStageSelect"', false);
        $response->assertSee($this->startStage->name_ar);
        $response->assertSee($this->interestStage->name_ar);
        $response->assertDontSee('مرحلة غير نشطة');
    }

    // 19. Forward stage conversion calculation is accurate
    public function test_19_forward_stage_conversion_calculation_is_accurate(): void
    {
        // 4 leads in startStage, 2 transitioned to interestStage
        for ($i = 0; $i < 4; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'Start Lead ' . $i,
                'phone' => '050100200' . $i,
                'source' => 'web',
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->interestedStatus->id,
                'name' => 'Interest Lead ' . $i,
                'phone' => '050200300' . $i,
                'source' => 'web',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'from_stage_id' => $this->startStage->id,
            'to_stage_id' => $this->interestStage->id,
        ]));
        $response->assertOk();
        $metrics = $response->viewData('conversionMetrics');
        $this->assertEquals($this->startStage->id, $metrics['from_stage_id']);
        $this->assertEquals($this->interestStage->id, $metrics['to_stage_id']);
        // Numerator = 2, Denominator = 6, Rate = round(2/6 * 100, 1) = 33.3%
        $this->assertEquals(33.3, $metrics['rate']);
        $this->assertEquals('33.3%', $metrics['display_value']);
        $this->assertTrue($metrics['is_valid_direction']);
    }

    // 20. Same-stage conversion returns 100%
    public function test_20_same_stage_conversion_returns_100_percent(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Same Stage Lead',
            'phone' => '0503004001',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'from_stage_id' => $this->startStage->id,
            'to_stage_id' => $this->startStage->id,
        ]));
        $response->assertOk();
        $metrics = $response->viewData('conversionMetrics');
        $this->assertEquals(100.0, $metrics['rate']);
        $this->assertEquals('100%', $metrics['display_value']);
    }

    // 21. Reverse stage conversion returns safe invalid direction state
    public function test_21_reverse_stage_conversion_returns_safe_invalid_direction(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'from_stage_id' => $this->interestStage->id,
            'to_stage_id' => $this->startStage->id,
        ]));
        $response->assertOk();
        $metrics = $response->viewData('conversionMetrics');
        $this->assertNull($metrics['rate']);
        $this->assertEquals('—', $metrics['display_value']);
        $this->assertFalse($metrics['is_valid_direction']);
    }

    // 22. Stage rename is reflected in conversion metrics and subtitles
    public function test_22_stage_rename_reflected_in_conversion_metrics(): void
    {
        $this->startStage->update(['name_ar' => 'مرحلة البداية الجديدة', 'code' => 'custom_start_code']);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard', [
                'from_stage_id' => $this->startStage->id,
                'to_stage_id' => $this->interestStage->id,
            ]));
        $response->assertOk();
        $metrics = $response->viewData('conversionMetrics');
        $this->assertEquals('مرحلة البداية الجديدة', $metrics['from_stage_name']);
        $this->assertStringContainsString('مرحلة البداية الجديدة', $metrics['subtitle']);
    }

    // 23. Scope and employee filters are respected in conversion
    public function test_23_employee_filter_respected_in_conversion(): void
    {
        $userA = User::factory()->create(['name' => 'Employee A', 'is_active' => true]);
        $userB = User::factory()->create(['name' => 'Employee B', 'is_active' => true]);

        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'assigned_user_id' => $userA->id,
            'name' => 'Lead of A 1',
            'phone' => '0504005001',
            'source' => 'web',
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->interestedStatus->id,
            'assigned_user_id' => $userA->id,
            'name' => 'Lead of A 2',
            'phone' => '0504005002',
            'source' => 'web',
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'assigned_user_id' => $userB->id,
            'name' => 'Lead of B 1',
            'phone' => '0504005003',
            'source' => 'web',
        ]);

        $responseA = $this->actingAs($this->admin)->get(route('dashboard', [
            'employee' => 'Employee A',
            'from_stage_id' => $this->startStage->id,
            'to_stage_id' => $this->interestStage->id,
        ]));
        $responseA->assertOk();
        $metricsA = $responseA->viewData('conversionMetrics');
        // Employee A: 1 in start, 1 in interest -> total 2, converted 1 -> 50%
        $this->assertEquals(50.0, $metricsA['rate']);

        $responseB = $this->actingAs($this->admin)->get(route('dashboard', [
            'employee' => 'Employee B',
            'from_stage_id' => $this->startStage->id,
            'to_stage_id' => $this->interestStage->id,
        ]));
        $responseB->assertOk();
        $metricsB = $responseB->viewData('conversionMetrics');
        // Employee B: 1 in start, 0 in interest -> 0%
        $this->assertEquals(0.0, $metricsB['rate']);
    }

    // 24. Performance chart selected stages determine series
    public function test_24_performance_chart_selected_stages_determine_series(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'New Lead Chart Test',
            'phone' => '0505006001',
            'source' => 'web',
            'created_at' => now(),
        ]);

        // Request with single stage selected
        $response = $this->actingAs($this->admin)->get(route('dashboard', [
            'chart_stages' => [$this->startStage->id],
        ]));
        $response->assertOk();
        $timeline = $response->viewData('performanceTimeline');
        $this->assertCount(1, $timeline['series']);
        $this->assertEquals($this->startStage->id, $timeline['series'][0]['id']);

        // Request with multiple stages selected
        $responseMulti = $this->actingAs($this->admin)->get(route('dashboard', [
            'chart_stages' => [$this->startStage->id, $this->interestStage->id],
        ]));
        $responseMulti->assertOk();
        $timelineMulti = $responseMulti->viewData('performanceTimeline');
        $this->assertCount(2, $timelineMulti['series']);
    }

    // 25. Final active stage name appears dynamically in conversion card title
    public function test_25_final_active_stage_name_appears_in_card_title(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();
        $finalStage = PipelineStage::query()->where('is_active', true)->orderBy('position')->get()->last();
        $this->assertNotNull($finalStage);
        $expectedTitle = 'معدل تحويل ' . $finalStage->localizedName('ar');
        $response->assertSee($expectedTitle);
    }

    // 26. Final stage rename updates donut title automatically
    public function test_26_final_stage_rename_updates_donut_title(): void
    {
        $finalStage = PipelineStage::query()->where('is_active', true)->orderBy('position')->get()->last();
        $this->assertNotNull($finalStage);
        $finalStage->update(['name_ar' => 'مرحلة التنفيذ النهائية', 'code' => 'custom_final_exec']);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('معدل تحويل مرحلة التنفيذ النهائية');
    }

    // 27. Reorder stages updates target to new last stage
    public function test_27_reorder_stages_updates_donut_target(): void
    {
        $stages = PipelineStage::query()->where('is_active', true)->orderBy('position')->get();
        $first = $stages->first();

        // Move first stage to position 50 to make it the new last stage
        $first->update(['position' => 50, 'name_ar' => 'مرحلة جديدة في النهاية', 'code' => 'new_last_stage']);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('معدل تحويل مرحلة جديدة في النهاية');
    }

    // 28. New active stage after current final stage updates target
    public function test_28_new_final_stage_updates_donut_target(): void
    {
        PipelineStage::query()->create([
            'code' => 'stage_after_final',
            'name_ar' => 'مرحلة ما بعد التعاقد',
            'position' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('معدل تحويل مرحلة ما بعد التعاقد');
    }

    // 29. Inactive final stage is ignored, previous active stage becomes target
    public function test_29_inactive_final_stage_ignored(): void
    {
        $activeStages = PipelineStage::query()->where('is_active', true)->orderBy('position')->get();
        $lastActive = $activeStages->last();

        // Create an inactive stage with higher position
        PipelineStage::query()->create([
            'code' => 'stage_inactive_last',
            'name_ar' => 'مرحلة ملغاة في النهاية',
            'position' => 70,
            'is_active' => false,
        ]);
        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('مرحلة ملغاة في النهاية');
        $response->assertSee('معدل تحويل ' . $lastActive->localizedName('ar'));
    }

    // 30. Donut metric calculates conversion to final stage accurately
    public function test_30_donut_metric_calculates_conversion_to_final_stage(): void
    {
        $stages = PipelineStage::query()->where('is_active', true)->orderBy('position')->get();
        $first = $stages->first();
        $last = $stages->last();

        $firstStatus = $first->statuses->first();
        $lastStatus = $last->statuses->first();

        // Create 3 leads in first status and 1 lead in final status
        for ($i = 0; $i < 3; $i++) {
            Lead::query()->create([
                'lead_status_id' => $firstStatus->id,
                'name' => 'Cohort Lead ' . $i,
                'phone' => '050777111' . $i,
                'source' => 'web',
            ]);
        }
        Lead::query()->create([
            'lead_status_id' => $lastStatus->id,
            'name' => 'Final Lead Reached',
            'phone' => '0507772222',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();

        // Denominator = 4, Numerator = 1, Rate = 25.0%
        $finalRate = $response->viewData('finalConversionRate');
        $this->assertEquals(25.0, $finalRate);
    }

    // 31. Single active stage handles conversion safely showing N/A
    public function test_31_single_active_stage_handled_safely(): void
    {
        // Deactivate all stages except the first one
        $stages = PipelineStage::query()->orderBy('position')->get();
        foreach ($stages->skip(1) as $stg) {
            $stg->update(['is_active' => false]);
        }

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));
        $response->assertOk();

        $this->assertNull($response->viewData('finalConversionRate'));
        $response->assertSee(__('crm.not_available'));
    }

    // 32. Localization in English renders "{Stage} Conversion Rate"
    public function test_32_localization_in_english_renders_stage_conversion_rate(): void
    {
        $finalStage = PipelineStage::query()->where('is_active', true)->orderBy('position')->get()->last();
        $this->assertNotNull($finalStage);

        $response = $this->actingAs($this->admin)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));
        $response->assertOk();

        $expectedEnTitle = $finalStage->localizedName('en') . ' Conversion Rate';
        $response->assertSee($expectedEnTitle);
    }
}
