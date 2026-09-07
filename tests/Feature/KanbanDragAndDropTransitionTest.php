<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanDragAndDropTransitionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $readOnlyUser;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;
    private Lead $lead;

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
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'kanban_admin_' . uniqid(),
            'name' => 'Admin Kanban Drag',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // Read-only user (no leads.followups.create)
        $readOnlyGroup = Group::query()->firstOrCreate(
            ['code' => 'read-only'],
            ['name' => 'مشاهدة فقط', 'is_system' => false]
        );
        $readOnlyGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_ALL->value,
                CrmPermission::DASHBOARD_VIEW->value,
            ])->pluck('id')
        );
        $this->readOnlyUser = User::factory()->create([
            'username' => 'kanban_ro_' . uniqid(),
            'name' => 'Read Only User',
            'is_active' => true,
        ]);
        $this->readOnlyUser->groups()->attach($readOnlyGroup);

        // Setup Stage A & Status A
        $this->stageA = PipelineStage::query()->create([
            'code' => 'stage_k_a_' . uniqid(),
            'name_ar' => 'مرحلة كانبان أ',
            'position' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->statusA = $this->stageA->ensureDefaultStatus();

        // Setup Stage B & Status B
        $this->stageB = PipelineStage::query()->create([
            'code' => 'stage_k_b_' . uniqid(),
            'name_ar' => 'مرحلة كانبان ب',
            'position' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->statusB = $this->stageB->ensureDefaultStatus();

        // Stage B has a dynamic field
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'b_notes',
            'label_ar' => 'ملاحظات المرحلة ب',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
        ]);
        StageFieldSchema::flushCache((int) $this->stageB->id);

        $this->lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل كانبان تجريبي',
            'phone' => '0501112233',
            'source' => 'web',
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now(),
        ]);
    }

    /** 1. Authorized Kanban card renders draggable="true" with lead and status identifiers */
    public function test_1_authorized_kanban_card_renders_draggable(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban', ['scope' => 'all']));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('draggable="true"', $html);
        $this->assertStringContainsString('data-kanban-lead="' . $this->lead->id . '"', $html);
        $this->assertStringContainsString('data-current-status-id="' . $this->statusA->id . '"', $html);
        $this->assertStringContainsString('data-followup-url=', $html);
    }

    /** 2. Unauthorized card does not render draggable="true" */
    public function test_2_unauthorized_card_does_not_render_draggable(): void
    {
        $response = $this->actingAs($this->readOnlyUser)->get(route('v2.leads.kanban', ['scope' => 'all']));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('draggable="false"', $html);
    }

    /** 3. Destination columns render status identifiers */
    public function test_3_destination_columns_render_status_identifiers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-kanban-status-id="' . $this->statusA->id . '"', $html);
        $this->assertStringContainsString('data-kanban-status-id="' . $this->statusB->id . '"', $html);
    }

    /** 4, 8, 11, 12. Cross-stage drag transition updates once, creates 1 history and 1 followup */
    public function test_4_cross_stage_transition_updates_once_with_single_history_and_followup(): void
    {
        $transitionService = app(LeadTransitionService::class);

        $result = $transitionService->transition(
            $this->lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['b_notes' => 'ملاحظات الانتقال إلى ب'],
                'record_followup' => true,
                'communication_type' => 'call',
                'outcome' => 'تم الاتصال والانتقال',
                'next_follow_up_at' => now()->addDays(2),
            ]
        );

        $this->assertEquals($this->statusB->id, $result['lead']->lead_status_id);

        // Exactly 1 history entry for this transition
        $histories = LeadStatusHistory::query()
            ->where('lead_id', $this->lead->id)
            ->where('to_status_id', $this->statusB->id)
            ->get();
        $this->assertCount(1, $histories);

        // Exactly 1 followup for this transition
        $followups = LeadFollowup::query()
            ->where('lead_id', $this->lead->id)
            ->where('to_status_id', $this->statusB->id)
            ->get();
        $this->assertCount(1, $followups);
    }

    /** 5. Same-stage drag produces no duplicate history or mutation */
    public function test_5_same_stage_drag_produces_no_mutation(): void
    {
        $initialHistoryCount = LeadStatusHistory::query()->where('lead_id', $this->lead->id)->count();

        // Same stage transition without status change
        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $this->lead,
            $this->statusA,
            $this->admin,
            [
                'record_followup' => false,
            ]
        );

        $this->assertEquals($this->statusA->id, $result['lead']->lead_status_id);
        $this->assertFalse($result['changed']);

        // No new status history created when status didn't change
        $finalHistoryCount = LeadStatusHistory::query()->where('lead_id', $this->lead->id)->count();
        $this->assertEquals($initialHistoryCount, $finalHistoryCount);
    }

    /** 6. AJAX-loaded column pagination cards retain draggable="true" */
    public function test_6_ajax_loaded_column_pagination_cards_retain_draggable(): void
    {
        // Create 15 leads in status A to generate page 2
        for ($i = 1; $i <= 15; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusA->id,
                'name' => "عميل عمود صفحة {$i}",
                'phone' => "05022200{$i}",
                'source' => 'web',
                'assigned_user_id' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusA->id,
            'page' => 2,
            'per_page' => 10,
        ]));

        $response->assertOk();
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertStringContainsString('draggable="true"', $json['html']);
        $this->assertStringContainsString('data-kanban-lead=', $json['html']);
    }

    /** 7. Destination dynamic schema is rendered when target_status_id is passed */
    public function test_7_destination_dynamic_schema_renders_for_target_status(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead->id,
            'target_status_id' => $this->statusB->id,
            'kanban_popup' => 1,
        ]));

        $response->assertOk();
        $html = $response->getContent();

        // Must show stage B block and field
        $this->assertStringContainsString('stage_q_block_' . $this->stageB->id, $html);
        $this->assertStringContainsString('stage_fields[b_notes]', $html);
    }

    /** 8. Canceled transition leaves Lead in original stage */
    public function test_8_canceled_transition_leaves_lead_in_original_stage(): void
    {
        // User opens popup for target status B, but never submits
        $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead->id,
            'target_status_id' => $this->statusB->id,
            'kanban_popup' => 1,
        ]))->assertOk();

        // Lead remains in status A
        $this->lead->refresh();
        $this->assertEquals($this->statusA->id, $this->lead->lead_status_id);
    }
}
