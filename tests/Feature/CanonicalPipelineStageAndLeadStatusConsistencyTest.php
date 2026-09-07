<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\PipelineMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CanonicalPipelineStageAndLeadStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $group = \App\Models\Group::query()->create([
            'name' => 'Consistency Test Group ' . uniqid(),
            'code' => 'group-' . uniqid(),
        ]);

        $permissions = [
            'leads.view',
            'leads.create',
            'tasks.view',
            'dashboard.view',
        ];

        foreach ($permissions as $code) {
            $perm = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['name_ar' => $code, 'category' => 'general']
            );
            $group->permissions()->syncWithoutDetaching([$perm->id]);
        }

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->groups()->attach($group);
    }

    /**
     * 1. Active sidebar statuses equal active pipeline statuses
     */
    public function test_active_sidebar_statuses_equal_active_pipeline_statuses(): void
    {
        $stageNew = PipelineStage::query()->create([
            'code' => 'stage_test_active_1',
            'name_ar' => 'مرحلة نشطة أولى',
            'position' => 1,
            'is_active' => true,
        ]);

        $stageSecond = PipelineStage::query()->create([
            'code' => 'stage_test_active_2',
            'name_ar' => 'مرحلة نشطة ثانية',
            'position' => 2,
            'is_active' => true,
        ]);

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $canonicalStages = PipelineMappingService::getActiveStages();

        $this->assertEquals(
            $canonicalStages->pluck('id')->all(),
            $sidebarStages->pluck('id')->all()
        );

        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('مرحلة نشطة أولى');
        $response->assertSee('مرحلة نشطة ثانية');
    }

    /**
     * 2. Inactive statuses excluded from active navigation
     */
    public function test_inactive_statuses_and_stages_excluded_from_active_navigation(): void
    {
        $activeStage = PipelineStage::query()->create([
            'code' => 'stage_active_' . uniqid(),
            'name_ar' => 'مرحلة مرئية',
            'position' => 1,
            'is_active' => true,
        ]);

        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_inactive_' . uniqid(),
            'name_ar' => 'مرحلة غير نشطة مخفية',
            'position' => 2,
            'is_active' => false,
        ]);

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $this->assertTrue($sidebarStages->contains('id', $activeStage->id));
        $this->assertFalse($sidebarStages->contains('id', $inactiveStage->id));

        $activeStatuses = LeadStatus::activeForPipeline();
        $this->assertTrue($activeStatuses->contains('pipeline_stage_id', $activeStage->id));
        $this->assertFalse($activeStatuses->contains('pipeline_stage_id', $inactiveStage->id));

        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertSee('مرحلة مرئية');
        $response->assertDontSee('مرحلة غير نشطة مخفية');
    }

    /**
     * 3. Orphan status excluded from active navigation
     */
    public function test_orphan_status_excluded_from_active_navigation(): void
    {
        $validStage = PipelineStage::query()->create([
            'code' => 'stage_valid_' . uniqid(),
            'name_ar' => 'مرحلة صحيحة',
            'position' => 1,
            'is_active' => true,
        ]);

        // Create an orphan status without a valid pipeline stage
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $orphanStatus = LeadStatus::query()->create([
            'pipeline_stage_id' => 999999, // non-existent stage
            'code' => 'orphan_status_b',
            'name_ar' => 'حالة يتيمة B',
            'position' => 99,
            'color' => '#ff0000',
            'is_terminal' => false,
        ]);
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $activeStatuses = LeadStatus::activeForPipeline();
        $this->assertFalse($activeStatuses->contains('id', $orphanStatus->id));

        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get(route('v2.tasks.daily'));

        $response->assertOk();
        $response->assertDontSee('حالة يتيمة B');
    }

    /**
     * 4. Historical status remains readable in history
     */
    public function test_historical_status_remains_readable_in_history(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_hist_' . uniqid(),
            'name_ar' => 'مرحلة الأرشيف',
            'position' => 1,
            'is_active' => true,
        ]);

        $status = $stage->statuses()->first();

        $lead = Lead::query()->create([
            'name' => 'عميل أرشيفي',
            'phone' => '0500000000',
            'lead_status_id' => $status->id,
            'created_by' => $this->user->id,
        ]);

        $history = LeadStatusHistory::query()->create([
            'lead_id' => $lead->id,
            'from_status_id' => null,
            'to_status_id' => $status->id,
            'changed_by_user_id' => $this->user->id,
            'changed_at' => now(),
        ]);

        // Even if stage becomes inactive
        $stage->update(['is_active' => false]);

        $loadedHistory = LeadStatusHistory::query()->with(['toStatus.stage'])->find($history->id);
        $this->assertNotNull($loadedHistory);
        $this->assertEquals($status->id, $loadedHistory->to_status_id);
        $this->assertEquals('مرحلة الأرشيف', $loadedHistory->toStatus?->stage?->name_ar);
    }

    /**
     * 5. Kanban and Sidebar use same canonical mapping
     */
    public function test_kanban_and_sidebar_use_same_canonical_mapping(): void
    {
        $stageA = PipelineStage::query()->create([
            'code' => 'stage_kanban_a',
            'name_ar' => 'مرحلة الكانبان أ',
            'position' => 1,
            'is_active' => true,
        ]);

        $stageB = PipelineStage::query()->create([
            'code' => 'stage_kanban_b',
            'name_ar' => 'مرحلة الكانبان ب',
            'position' => 2,
            'is_active' => true,
        ]);

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $kanbanStatuses = LeadStatus::activeForPipeline();

        $sidebarStageIds = $sidebarStages->pluck('id')->sort()->values()->all();
        $kanbanStageIds = $kanbanStatuses->pluck('pipeline_stage_id')->unique()->sort()->values()->all();

        $this->assertEquals($sidebarStageIds, $kanbanStageIds);
    }

    /**
     * 6. Leads filter and Sidebar use same canonical mapping
     */
    public function test_leads_filter_and_sidebar_use_same_canonical_mapping(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_filter_match',
            'name_ar' => 'مرحلة الفلترة الموحدة',
            'position' => 1,
            'is_active' => true,
        ]);

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $activeStatuses = PipelineMappingService::getActiveStatuses();

        $this->assertTrue($sidebarStages->contains('id', $stage->id));
        $this->assertTrue($activeStatuses->contains('pipeline_stage_id', $stage->id));

        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('مرحلة الفلترة الموحدة');
    }

    /**
     * 7. Status labels use localizedName, not raw code fallback
     */
    public function test_status_labels_use_localized_name_not_raw_code_fallback(): void
    {
        $stage = PipelineStage::query()->firstWhere('code', 'new') ?? PipelineStage::query()->create([
            'code' => 'new',
            'name_ar' => 'جديد',
            'position' => 1,
            'is_active' => true,
        ]);

        $status = $stage->statuses()->first();

        // In Arabic
        $this->assertEquals('جديد', $stage->localizedName('ar'));
        $this->assertEquals('جديد', $status->localizedName('ar'));

        // In English
        $this->assertEquals('New', $stage->localizedName('en'));
        $this->assertEquals('New', $status->localizedName('en'));
    }

    /**
     * 8. No duplicate stage/status entry in navigation
     */
    public function test_no_duplicate_stage_or_status_entry_in_navigation(): void
    {
        PipelineStage::query()->create([
            'code' => 'stage_unique_1',
            'name_ar' => 'مرحلة فريدة 1',
            'position' => 1,
            'is_active' => true,
        ]);

        PipelineStage::query()->create([
            'code' => 'stage_unique_2',
            'name_ar' => 'مرحلة فريدة 2',
            'position' => 2,
            'is_active' => true,
        ]);

        $sidebarStages = PipelineStage::getActiveStagesForSidebar();
        $stageIds = $sidebarStages->pluck('id')->all();
        $this->assertEquals(count($stageIds), count(array_unique($stageIds)));

        $activeStatuses = LeadStatus::activeForPipeline();
        $statusIds = $activeStatuses->pluck('id')->all();
        $this->assertEquals(count($statusIds), count(array_unique($statusIds)));
    }

    /**
     * Self-healing cache verification: ghost stage in cache is automatically evicted.
     */
    public function test_self_healing_cache_evicts_stale_orphan_entries(): void
    {
        $realStage = PipelineStage::query()->create([
            'code' => 'stage_real_one',
            'name_ar' => 'مرحلة حقيقية',
            'position' => 1,
            'is_active' => true,
        ]);

        // Inject a fake/stale "B" record into cache
        $fakeCached = [
            [
                'id' => $realStage->id,
                'code' => $realStage->code,
                'name_ar' => $realStage->name_ar,
                'color' => $realStage->color,
                'icon' => $realStage->icon,
                'position' => 1,
                'is_primary' => false,
                'is_active' => true,
            ],
            [
                'id' => 99999,
                'code' => 'test_b',
                'name_ar' => 'B',
                'color' => null,
                'icon' => null,
                'position' => 2,
                'is_primary' => false,
                'is_active' => true,
            ],
        ];

        Cache::put(PipelineStage::SIDEBAR_CACHE_KEY, $fakeCached, now()->addHours(24));

        // Calling getActiveStagesForSidebar should detect that 99999 doesn't exist in DB, evict cache and heal
        $healedStages = PipelineStage::getActiveStagesForSidebar();

        $this->assertFalse($healedStages->contains('id', 99999));
        $this->assertFalse($healedStages->contains('name_ar', 'B'));
        $this->assertTrue($healedStages->contains('id', $realStage->id));
    }
}
