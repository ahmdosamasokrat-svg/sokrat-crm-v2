<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageOrderKanbanSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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

        $superGroup = Group::firstOrCreate(
            ['code' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true]
        );
        $superGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->groups()->sync([$superGroup->id]);
    }

    public function test_kanban_columns_order_strictly_follows_pipeline_stage_positions(): void
    {
        $stages = PipelineStage::query()->orderBy('position')->get();
        $this->assertNotEmpty($stages);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();

        $kanbanColumns = $response->viewData('kanbanColumns');
        $stageIdsInKanban = array_map(static fn ($col) => $col['stage_id'], $kanbanColumns);

        $expectedStageIds = PipelineStage::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame($expectedStageIds, $stageIdsInKanban);
    }

    public function test_updating_stage_position_updates_kanban_column_order(): void
    {
        $allStages = PipelineStage::query()->orderBy('position')->get();
        $this->assertGreaterThanOrEqual(3, $allStages->count());

        $targetStage = $allStages->last();
        $targetNewPos = 2;

        $response = $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $targetStage), [
            'name_ar' => $targetStage->name_ar,
            'position' => $targetNewPos,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('v2.settings.stages.index'));

        // Verify that in the database, targetStage now has position 2
        $targetStage->refresh();
        $this->assertSame($targetNewPos, (int) $targetStage->position);

        // Verify that LeadStatus position is also synchronized
        $status = LeadStatus::query()->where('pipeline_stage_id', $targetStage->id)->first();
        $this->assertNotNull($status);
        $this->assertSame($targetNewPos, (int) $status->position);

        // Verify that Kanban columns are in the new order
        $kanbanResponse = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $kanbanResponse->assertOk();

        $kanbanColumns = $kanbanResponse->viewData('kanbanColumns');
        $this->assertSame($targetStage->id, $kanbanColumns[1]['stage_id']);
    }

    public function test_stage_creation_and_update_with_has_followups_flag(): void
    {
        // 1. Create stage without follow-ups
        $storeResponse = $this->actingAs($this->admin)->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة بدون متابعات',
            'color' => '#10b981',
            'has_followups' => false,
        ]);
        $storeResponse->assertRedirect(route('v2.settings.stages.index'));

        $createdStage = PipelineStage::query()->where('name_ar', 'مرحلة بدون متابعات')->first();
        $this->assertNotNull($createdStage);
        $this->assertFalse($createdStage->has_followups);

        // Check in Kanban view data
        $kanbanResponse = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $kanbanResponse->assertOk();
        $kanbanColumns = $kanbanResponse->viewData('kanbanColumns');

        $col = collect($kanbanColumns)->firstWhere('stage_id', $createdStage->id);
        $this->assertNotNull($col);
        $this->assertFalse($col['has_followups']);

        // 2. Update stage to have follow-ups
        $updateResponse = $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $createdStage), [
            'name_ar' => 'مرحلة بدون متابعات',
            'position' => $createdStage->position,
            'is_active' => true,
            'has_followups' => true,
        ]);
        $updateResponse->assertRedirect(route('v2.settings.stages.index'));

        $createdStage->refresh();
        $this->assertTrue($createdStage->has_followups);

        // Check in Kanban view data again
        $kanbanResponse2 = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $kanbanResponse2->assertOk();
        $kanbanColumns2 = $kanbanResponse2->viewData('kanbanColumns');

        $col2 = collect($kanbanColumns2)->firstWhere('stage_id', $createdStage->id);
        $this->assertNotNull($col2);
        $this->assertTrue($col2['has_followups']);
    }
}
