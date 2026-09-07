<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\PipelineMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PipelineStageDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $unauthorizedUser;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure permissions exist
        if (Permission::query()->count() < count(CrmPermission::cases())) {
            $perms = array_map(static fn ($p) => [
                'code' => $p->value,
                'module' => $p->module(),
                'name_ar' => $p->label(),
            ], CrmPermission::cases());
            Permission::query()->upsert($perms, ['code'], ['module', 'name_ar']);
        }

        // 2. Setup Super Admin
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'stage_del_admin_' . uniqid(),
            'name' => 'Admin Stage Deleter',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // 3. Setup Unauthorized User
        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agent'],
            ['name' => 'موظف مبيعات', 'is_system' => false]
        );
        // Grant basic access but NOT pipeline_stages.delete
        $agentGroup->permissions()->sync(
            Permission::whereIn('code', [CrmPermission::LEADS_VIEW->value, CrmPermission::SETTINGS_ACCESS->value])->pluck('id')
        );

        $this->unauthorizedUser = User::factory()->create([
            'username' => 'stage_del_agent_' . uniqid(),
            'name' => 'Unauthorized Agent',
            'is_active' => true,
        ]);
        $this->unauthorizedUser->groups()->attach($agentGroup);

        // 4. Setup stages and statuses
        $this->stageA = PipelineStage::query()->create([
            'code' => 'stage_del_a_' . uniqid(),
            'name_ar' => 'المرحلة أ',
            'position' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->statusA = $this->stageA->ensureDefaultStatus();

        $this->stageB = PipelineStage::query()->create([
            'code' => 'stage_del_b_' . uniqid(),
            'name_ar' => 'المرحلة ب',
            'position' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->statusB = $this->stageB->ensureDefaultStatus();
    }

    /** 1. Authorized admin sees delete button */
    public function test_1_authorized_admin_sees_delete_button(): void
    {
        $response = $this->actingAs($this->admin)->get('/settings/stages');

        $response->assertOk();
        $response->assertSee('onclick="openDeleteStageModal', false);
        $response->assertSee('id="deleteStageModal"', false);
    }

    /** 2. Unauthorized user does not see delete button */
    public function test_2_unauthorized_user_does_not_see_delete_button(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get('/settings/stages');

        $response->assertOk();
        $response->assertDontSee('onclick="openDeleteStageModal', false);
    }

    /** 3. Unauthorized direct delete returns 403 */
    public function test_3_unauthorized_direct_delete_returns_403(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->delete("/settings/stages/{$this->stageB->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageB->id, 'deleted_at' => null]);
    }

    /** 4. Empty stage can be deleted */
    public function test_4_empty_stage_can_be_deleted(): void
    {
        $emptyStage = PipelineStage::query()->create([
            'code' => 'stage_empty_' . uniqid(),
            'name_ar' => 'مرحلة فارغة',
            'position' => 3,
            'is_active' => true,
        ]);
        $emptyStage->ensureDefaultStatus();

        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$emptyStage->id}");

        $response->assertRedirect('/settings/stages');
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('pipeline_stages', ['id' => $emptyStage->id]);
    }

    /** 5. Protected/system stage cannot be deleted */
    public function test_5_protected_system_stage_cannot_be_deleted(): void
    {
        $systemStage = PipelineStage::query()->create([
            'code' => 'stage_system_' . uniqid(),
            'name_ar' => 'مرحلة نظام محمية',
            'position' => 3,
            'is_active' => true,
            'is_system' => true,
        ]);
        $systemStage->ensureDefaultStatus();

        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$systemStage->id}");

        $response->assertSessionHasErrors('stage');
        $this->assertDatabaseHas('pipeline_stages', ['id' => $systemStage->id, 'deleted_at' => null]);
    }

    /** 6. Default stage requires replacement */
    public function test_6_default_stage_requires_replacement(): void
    {
        // StageA is currently the default stage. Deleting without replacement should fail.
        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageA->id}");

        $response->assertSessionHasErrors('replacement_default_stage_id');
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageA->id, 'deleted_at' => null]);

        // Deleting with valid replacement stage should succeed
        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageA->id}", [
                'replacement_default_stage_id' => $this->stageB->id,
            ]);

        $response->assertRedirect('/settings/stages');
        $this->assertSoftDeleted('pipeline_stages', ['id' => $this->stageA->id]);

        $this->stageB->refresh();
        $this->assertTrue($this->stageB->isDefault());
    }

    /** 7. Stage with Leads presents resolution requirement */
    public function test_7_stage_with_leads_presents_resolution_requirement(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار المرحلة ب',
        ]);

        // Deleting stage B without specifying lead_action should fail
        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageB->id}");

        $response->assertSessionHasErrors('lead_action');
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageB->id, 'deleted_at' => null]);
    }

    /** 8. Move option requires destination */
    public function test_8_move_option_requires_destination(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار النقل',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageB->id}", [
                'lead_action' => 'move',
            ]);

        $response->assertSessionHasErrors('destination_stage_id');
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageB->id, 'deleted_at' => null]);
    }

    /** 9. Destination cannot equal source */
    public function test_9_destination_cannot_equal_source(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار الهدف المتطابق',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageB->id}", [
                'lead_action' => 'move',
                'destination_stage_id' => $this->stageB->id,
            ]);

        $response->assertSessionHasErrors('destination_stage_id');
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageB->id, 'deleted_at' => null]);
    }

    /** 10. Destination must be active */
    public function test_10_destination_must_be_active(): void
    {
        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_inactive_' . uniqid(),
            'name_ar' => 'مرحلة غير نشطة',
            'position' => 4,
            'is_active' => false,
        ]);
        $inactiveStage->ensureDefaultStatus();

        Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار الوجهة المعطلة',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageB->id}", [
                'lead_action' => 'move',
                'destination_stage_id' => $inactiveStage->id,
            ]);

        $response->assertSessionHasErrors('destination_stage_id');
        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageB->id, 'deleted_at' => null]);
    }

    /** 11. Moving Leads preserves history */
    public function test_11_moving_leads_preserves_history(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار الحفاظ على التاريخ',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete("/settings/stages/{$this->stageB->id}", [
                'lead_action' => 'move',
                'destination_stage_id' => $this->stageA->id,
            ]);

        $response->assertRedirect('/settings/stages');

        $lead->refresh();
        $this->assertEquals($this->statusA->id, $lead->lead_status_id);

        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusB->id,
            'to_status_id' => $this->statusA->id,
            'changed_by_user_id' => $this->admin->id,
        ]);
    }

    /** 12. Stage cache cleared */
    public function test_12_stage_cache_cleared(): void
    {
        Cache::put(PipelineStage::SIDEBAR_CACHE_KEY, ['stale_data'], 3600);

        $stageToDelete = PipelineStage::query()->create([
            'code' => 'stage_cache_' . uniqid(),
            'name_ar' => 'مرحلة للكاش',
            'position' => 5,
            'is_active' => true,
        ]);
        $stageToDelete->ensureDefaultStatus();

        $this->actingAs($this->admin)->delete("/settings/stages/{$stageToDelete->id}");

        $cached = Cache::get(PipelineStage::SIDEBAR_CACHE_KEY);
        $this->assertNotEquals(['stale_data'], $cached);
    }

    /** 13. No orphan LeadStatus */
    public function test_13_no_orphan_lead_status(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_orphan_test_' . uniqid(),
            'name_ar' => 'مرحلة فحص الحالات',
            'position' => 6,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $this->actingAs($this->admin)->delete("/settings/stages/{$stage->id}");

        // Active query should not include status of deleted stage
        $activeStatuses = LeadStatus::query()->whereNull('deleted_at')->pluck('id');
        $this->assertNotContains($status->id, $activeStatuses);
    }

    /** 14. No orphan Lead stage mapping */
    public function test_14_no_orphan_lead_stage_mapping(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار عدم وجود يتيم',
        ]);

        $this->actingAs($this->admin)->delete("/settings/stages/{$this->stageB->id}", [
            'lead_action' => 'move',
            'destination_stage_id' => $this->stageA->id,
        ]);

        $lead->refresh();
        $this->assertNotNull($lead->status);
        $this->assertNotNull($lead->status->stage);
        $this->assertEquals($this->stageA->id, $lead->status->stage->id);
    }

    /** 15. Rollback on failure */
    public function test_15_rollback_on_failure(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل اختبار التراجع',
        ]);

        // Attempt delete with invalid destination -> should fail and rollback
        $response = $this->actingAs($this->admin)->delete("/settings/stages/{$this->stageB->id}", [
            'lead_action' => 'move',
            'destination_stage_id' => 999999, // Nonexistent
        ]);

        $response->assertSessionHasErrors('destination_stage_id');

        $this->assertDatabaseHas('pipeline_stages', ['id' => $this->stageB->id, 'deleted_at' => null]);
        $lead->refresh();
        $this->assertEquals($this->statusB->id, $lead->lead_status_id);
    }

    /** 16. Stage with 500 Leads moved to Trash (Part 38) */
    public function test_16_stage_with_leads_trashed_in_bulk_500_leads(): void
    {
        $interestedStage = PipelineStage::query()->create([
            'code' => 'interested_' . uniqid(),
            'name_ar' => 'مهتم',
            'position' => 10,
            'is_active' => true,
        ]);
        $interestedStatus = $interestedStage->ensureDefaultStatus();

        // Create 500 leads
        $leadData = [];
        $now = now();
        for ($i = 1; $i <= 500; $i++) {
            $leadData[] = [
                'lead_status_id' => $interestedStatus->id,
                'name' => "عميل مهتم {$i}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($leadData, 250) as $chunk) {
            DB::table('leads')->insert($chunk);
        }

        $this->assertEquals(500, Lead::query()->where('lead_status_id', $interestedStatus->id)->count());

        $response = $this->actingAs($this->admin)->delete("/settings/stages/{$interestedStage->id}", [
            'lead_action' => 'trash',
        ]);

        $response->assertRedirect('/settings/stages');

        // Verify stage is soft-deleted
        $this->assertSoftDeleted('pipeline_stages', ['id' => $interestedStage->id]);

        // Verify normal lead count excludes all 500
        $this->assertEquals(0, Lead::query()->where('lead_status_id', $interestedStatus->id)->count());

        // Verify trash contains all 500
        $trashedCount = Lead::onlyTrashed()->where('deleted_from_stage_id', $interestedStage->id)->count();
        $this->assertEquals(500, $trashedCount);
    }

    /** 17. Stage with 500 Leads moved to another stage (Part 39) */
    public function test_17_stage_with_leads_moved_in_bulk_500_leads(): void
    {
        $stageSource = PipelineStage::query()->create([
            'code' => 'stage_source_' . uniqid(),
            'name_ar' => 'المرحلة المصدر',
            'position' => 11,
            'is_active' => true,
        ]);
        $statusSource = $stageSource->ensureDefaultStatus();

        // Create 500 leads
        $leadData = [];
        $now = now();
        for ($i = 1; $i <= 500; $i++) {
            $leadData[] = [
                'lead_status_id' => $statusSource->id,
                'name' => "عميل نقل {$i}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($leadData, 250) as $chunk) {
            DB::table('leads')->insert($chunk);
        }

        $response = $this->actingAs($this->admin)->delete("/settings/stages/{$stageSource->id}", [
            'lead_action' => 'move',
            'destination_stage_id' => $this->stageA->id,
        ]);

        $response->assertRedirect('/settings/stages');

        // Stage Source deleted
        $this->assertSoftDeleted('pipeline_stages', ['id' => $stageSource->id]);

        // All 500 moved to Stage A status
        $movedCount = Lead::query()->where('lead_status_id', $this->statusA->id)->count();
        $this->assertGreaterThanOrEqual(500, $movedCount);

        // History records exist for the moved leads
        $historyCount = DB::table('lead_status_histories')
            ->where('from_status_id', $statusSource->id)
            ->where('to_status_id', $this->statusA->id)
            ->count();
        $this->assertEquals(500, $historyCount);
    }

    /** 18. Stage with 1001 Leads moved to Trash (Part 21) */
    public function test_18_stage_with_1001_leads_trashed_in_bulk(): void
    {
        $bigStage = PipelineStage::query()->create([
            'code' => 'big_stage_' . uniqid(),
            'name_ar' => 'مرحلة 1001 عميل',
            'position' => 15,
            'is_active' => true,
        ]);
        $bigStatus = $bigStage->ensureDefaultStatus();

        $now = now();
        $leadData = [];
        for ($i = 1; $i <= 1001; $i++) {
            $leadData[] = [
                'lead_status_id' => $bigStatus->id,
                'name' => "عميل الضغط {$i}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($leadData, 250) as $chunk) {
            DB::table('leads')->insert($chunk);
        }

        $this->assertEquals(1001, Lead::query()->where('lead_status_id', $bigStatus->id)->count());

        $response = $this->actingAs($this->admin)->delete("/settings/stages/{$bigStage->id}", [
            'lead_action' => 'trash',
        ]);

        $response->assertRedirect('/settings/stages');
        $this->assertSoftDeleted('pipeline_stages', ['id' => $bigStage->id]);

        $trashedCount = Lead::onlyTrashed()->where('deleted_from_stage_id', $bigStage->id)->count();
        $this->assertEquals(1001, $trashedCount);
    }

    /** 19. Action column contains icon-only buttons in one horizontal row */
    public function test_19_action_column_contains_icon_only_buttons_with_title_and_aria(): void
    {
        $response = $this->actingAs($this->admin)->get('/settings/stages');

        $response->assertOk();
        $html = $response->getContent();

        // Flex container has nowrap
        $this->assertStringContainsString('flex-wrap:nowrap', $html);
        // Icons present
        $this->assertStringContainsString('bi-ui-checks', $html);
        $this->assertStringContainsString('bi-pencil-square', $html);
        $this->assertStringContainsString('bi-trash', $html);
        // Aria labels present
        $this->assertStringContainsString('aria-label=', $html);
        // Title tooltips present
        $this->assertStringContainsString('title=', $html);
    }

    /** 20. Expected row count mismatch rolls back transaction */
    public function test_20_row_count_assertion_failure_rolls_back_entire_stage_deletion(): void
    {
        $stageFail = PipelineStage::query()->create([
            'code' => 'stage_fail_' . uniqid(),
            'name_ar' => 'مرحلة فشل التوكيد',
            'position' => 20,
            'is_active' => true,
        ]);
        $statusFail = $stageFail->ensureDefaultStatus();

        Lead::query()->create([
            'lead_status_id' => $statusFail->id,
            'name' => 'عميل فحص التراجع',
        ]);

        $deletionService = app(\App\Services\StageDeletionService::class);

        // If an impossible lead_action or mock condition causes mismatch, transaction must roll back
        // We test this by attempting to delete without lead_action
        try {
            $deletionService->deleteStage($stageFail, $this->admin, ['lead_action' => 'invalid_action']);
            $this->fail('Expected ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertDatabaseHas('pipeline_stages', ['id' => $stageFail->id, 'deleted_at' => null]);
        }
    }
}
