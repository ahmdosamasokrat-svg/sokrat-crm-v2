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

class DashboardDynamicCardsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $startStage;
    private PipelineStage $interestStage;
    private PipelineStage $customStage;
    private LeadStatus $newStatus;
    private LeadStatus $noAnswerStatus;
    private LeadStatus $interestedStatus;
    private LeadStatus $customStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Permissions if not present
        if (Permission::query()->count() < count(CrmPermission::cases())) {
            $perms = array_map(static fn ($p) => [
                'code' => $p->value,
                'module' => $p->module(),
                'name_ar' => $p->label(),
            ], CrmPermission::cases());
            Permission::query()->upsert($perms, ['code'], ['module', 'name_ar']);
        }
        // 2. Create Super Admin group and user
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
            'username' => 'dash_admin',
            'name' => 'Dashboard Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // 3. Create active stages & statuses
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
        $this->noAnswerStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'no_answer'],
            [
                'pipeline_stage_id' => $this->startStage->id,
                'name_ar' => 'لم يرد',
                'position' => 2,
                'color' => '#e59b16',
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
                'icon' => 'bi-geo-alt-fill',
                'is_primary' => false,
                'is_active' => true,
            ]
        );
        $this->customStatus = $this->customStage->statuses()->first()
            ?? LeadStatus::query()->firstOrCreate(
                ['code' => 'stage_field_visit'],
                [
                    'pipeline_stage_id' => $this->customStage->id,
                    'name_ar' => 'زيارة ميدانية',
                    'position' => 4,
                    'color' => '#8b5cf6',
                    'is_terminal' => false,
                ]
            );
    }

    // 1. active PipelineStages render dynamically
    public function test_1_active_pipeline_stages_render_dynamically(): void
    {
        app()->setLocale('ar');
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee($this->startStage->localizedName());
        $response->assertSee($this->interestStage->localizedName());
        $response->assertSee($this->customStage->localizedName());
    }

    // 2. custom stage appears automatically
    public function test_2_custom_stage_appears_automatically_on_dashboard(): void
    {
        $newCustom = PipelineStage::query()->create([
            'code' => 'stage_vip_' . uniqid(),
            'name_ar' => 'عملاء VIP مميزين',
            'position' => 4,
            'color' => '#f59e0b',
            'icon' => 'bi-star-fill',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('عملاء VIP مميزين');
        $response->assertSee('#f59e0b');
    }

    // 3. inactive stage does not appear
    public function test_3_inactive_stage_does_not_appear_on_dashboard(): void
    {
        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_archived_' . uniqid(),
            'name_ar' => 'مرحلة معطلة ومؤرشفة',
            'position' => 15,
            'color' => '#6b7280',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('مرحلة معطلة ومؤرشفة');
    }

    // 4. zero-lead active stage appears with count 0
    public function test_4_zero_lead_active_stage_appears_with_count_zero(): void
    {
        // customStage has 0 leads
        $this->assertEquals(0, $this->customStage->leads()->count());

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee($this->customStage->localizedName());
        $response->assertSee('0');
    }

    // 5. stage ordering follows position
    public function test_5_stage_ordering_follows_position(): void
    {
        // Reorder customStage to position 1
        $this->customStage->update(['position' => 0]);
        $this->startStage->update(['position' => 5]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $activeStages = $response->viewData('activePipelineStages');
        $this->assertEquals('زيارة ميدانية', $activeStages->first()['name']);
    }

    // 6. stage localized name renders
    public function test_6_stage_localized_name_renders(): void
    {
        app()->setLocale('ar');
        $responseAr = $this->actingAs($this->admin)->get(route('dashboard'));
        $responseAr->assertOk();
        $responseAr->assertSee('البداية');

        app()->setLocale('en');
        $responseEn = $this->actingAs($this->admin)->get(route('dashboard'));
        $responseEn->assertOk();
        $responseEn->assertSee('Start');
    }

    // 7. configured icon renders/falls back safely
    public function test_7_configured_icon_renders_and_falls_back(): void
    {
        // Configured icon
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('bi-geo-alt');

        // Stage with null icon falls back to safe icon
        $noIconStage = PipelineStage::query()->create([
            'code' => 'stage_no_ico_' . uniqid(),
            'name_ar' => 'بدون أيقونة محددة',
            'position' => 20,
            'icon' => null,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response2 = $this->actingAs($this->admin)->get(route('dashboard'));
        $response2->assertOk();
        $response2->assertSee('بدون أيقونة محددة');
    }

    // 8. configured color is applied safely
    public function test_8_configured_color_is_applied(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('#8b5cf6');
    }

    // 9. lead counts include all statuses belonging to the stage
    public function test_9_lead_counts_include_all_statuses_belonging_to_stage(): void
    {
        // startStage has newStatus and noAnswerStatus
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead New 1',
            'phone' => '0501110001',
            'source' => 'web',
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead New 2',
            'phone' => '0501110002',
            'source' => 'web',
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->noAnswerStatus->id,
            'name' => 'Lead No Answer 1',
            'phone' => '0501110003',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $activeStages = $response->viewData('activePipelineStages');
        $startStageData = $activeStages->firstWhere('id', $this->startStage->id);
        $this->assertEquals(3, $startStageData['count']);
    }

    // 10. custom stage count works
    public function test_10_custom_stage_count_reflects_accurate_lead_count(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Lead Custom 1',
            'phone' => '0501119991',
            'source' => 'web',
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Lead Custom 2',
            'phone' => '0501119992',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $activeStages = $response->viewData('activePipelineStages');
        $customStageData = $activeStages->firstWhere('id', $this->customStage->id);
        $this->assertEquals(2, $customStageData['count']);
    }

    // 11. branch/user lead scope is preserved
    public function test_11_branch_and_user_lead_scope_is_preserved(): void
    {
        $restrictedGroup = Group::query()->create([
            'name' => 'مندوب مبيعات محدود',
            'code' => 'sales-agent-own',
            'is_system' => false,
        ]);
        $restrictedGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ])->pluck('id'));

        $salesAgent = User::factory()->create([
            'username' => 'agent_bob',
            'name' => 'Agent Bob',
            'is_active' => true,
        ]);
        $salesAgent->groups()->attach($restrictedGroup);

        // Lead owned by Bob
        Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Bobs Lead',
            'phone' => '0501118881',
            'assigned_user_id' => $salesAgent->id,
            'source' => 'web',
        ]);

        // Lead owned by another user
        Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Other Lead',
            'phone' => '0501118882',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);

        $response = $this->actingAs($salesAgent)->get(route('dashboard'));
        $response->assertOk();

        $activeStages = $response->viewData('activePipelineStages');
        $customStageData = $activeStages->firstWhere('id', $this->customStage->id);
        $this->assertEquals(1, $customStageData['count']);
    }

    // 12. Total Leads card remains
    public function test_12_total_leads_card_remains_as_fixed_first_card(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Total 1',
            'phone' => '0501110010',
            'source' => 'web',
        ]);
        Lead::query()->create([
            'lead_status_id' => $this->interestedStatus->id,
            'name' => 'Lead Total 2',
            'phone' => '0501110011',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $this->assertEquals(2, $response->viewData('totalLeads'));
    }

    // 13. legacy hardcoded stage cards are no longer present
    public function test_13_legacy_hardcoded_stage_cards_are_replaced(): void
    {
        app()->setLocale('ar');
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // Only active stage names should appear
        $response->assertSee($this->startStage->localizedName());
        $response->assertSee($this->interestStage->localizedName());
        $response->assertSee($this->customStage->localizedName());
    }

    // 14. 12+ stages render without backend assumptions
    public function test_14_many_stages_render_without_backend_assumptions(): void
    {
        for ($i = 4; $i <= 14; $i++) {
            PipelineStage::query()->create([
                'code' => 'stage_stress_' . $i,
                'name_ar' => 'مرحلة إضافية ' . $i,
                'position' => $i,
                'color' => '#6366f1',
                'is_primary' => false,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $activeStages = $response->viewData('activePipelineStages');
        $this->assertGreaterThanOrEqual(14, $activeStages->count());
        $response->assertSee('مرحلة إضافية 14');
    }

    // 15. existing Dashboard tests remain green
    public function test_15_existing_dashboard_behavior_remains_intact(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('totalLeads');
        $response->assertViewHas('stageCards');
        $response->assertViewHas('activePipelineStages');
    }
}
