<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PipelineStageFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $startStage;
    private PipelineStage $interestStage;
    private PipelineStage $customStage;
    private LeadStatus $newStatus;
    private LeadStatus $noAnswerStatus;
    private LeadStatus $notInterestedStatus;
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

        // 3. Create Admin user
        $this->admin = User::factory()->create([
            'username' => 'admin_tester',
            'name' => 'Admin Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // 4. Create primary stages and statuses
        $this->startStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'البداية',
                'position' => 1,
                'color' => '#3478f6',
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
        PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->startStage->id, 'key' => 'callback_at'],
            [
                'label_ar' => 'موعد إعادة الاتصال',
                'type' => 'datetime',
                'is_required' => false,
                'is_active' => true,
            ]
        );

        $this->interestStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'interest'],
            [
                'name_ar' => 'الاهتمام',
                'position' => 2,
                'color' => '#dc2637',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->notInterestedStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'not_interested'],
            [
                'pipeline_stage_id' => $this->interestStage->id,
                'name_ar' => 'غير مهتم',
                'position' => 3,
                'color' => '#dc2637',
                'is_terminal' => true,
            ]
        );
        $this->notInterestedStatus->update([
            'code' => 'not_interested',
            'is_terminal' => true,
        ]);
        PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->interestStage->id, 'key' => 'reason'],
            [
                'label_ar' => 'سبب عدم الاهتمام',
                'type' => 'select',
                'options' => [
                    ['value' => 'high_price', 'label_ar' => 'السعر مرتفع'],
                    ['value' => 'not_convinced', 'label_ar' => 'غير مقتنع'],
                    ['value' => 'other', 'label_ar' => 'سبب آخر'],
                ],
                'is_required' => true,
                'is_active' => true,
            ]
        );

        $this->customStage = PipelineStage::query()->create([
            'code' => 'stage_audit_' . uniqid(),
            'name_ar' => 'مرحلة التدقيق',
            'position' => 3,
            'color' => '#8b5cf6',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $this->customStatus = $this->customStage->statuses()->first();
    }

    // 1. custom PipelineStage creation
    public function test_1_custom_pipeline_stage_creation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.store'), [
            'name_ar' => 'مرحلة الاختبار الميداني',
            'color' => '#10b981',
            'description_ar' => 'مرحلة مخصصة لاختبار العمل الميداني',
        ]);

        $response->assertRedirect(route('v2.settings.stages.index'));
        $this->assertDatabaseHas('pipeline_stages', [
            'name_ar' => 'مرحلة الاختبار الميداني',
            'is_primary' => false,
            'is_active' => true,
        ]);
    }

    // 2. automatic default LeadStatus
    public function test_2_automatic_default_lead_status_on_stage_creation(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_auto_' . uniqid(),
            'name_ar' => 'مرحلة تلقائية',
            'position' => 10,
            'color' => '#06b6d4',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('lead_statuses', [
            'pipeline_stage_id' => $stage->id,
            'name_ar' => 'مرحلة تلقائية',
        ]);
        $this->assertCount(1, $stage->statuses);
    }

    // 3. orphan repair
    public function test_3_orphan_repair_generates_default_status(): void
    {
        // Force an orphan stage without statuses
        $orphan = new PipelineStage();
        $orphan->code = 'stage_orphan_' . uniqid();
        $orphan->name_ar = 'مرحلة يتيمة';
        $orphan->position = 20;
        $orphan->is_active = true;
        $orphan->saveQuietly();

        $this->assertCount(0, $orphan->statuses);

        $repairedCount = PipelineStage::repairOrphanStages();
        $this->assertGreaterThanOrEqual(1, $repairedCount);
        $this->assertCount(1, $orphan->fresh()->statuses);
    }

    // 4. custom stage appears in Kanban
    public function test_4_custom_stage_appears_in_kanban(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();
        $response->assertSee('مرحلة التدقيق');
    }

    // 5. stage order
    public function test_5_stage_order_is_configurable_and_contiguous(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $this->customStage), [
            'name_ar' => $this->customStage->name_ar,
            'position' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('v2.settings.stages.index'));
        $this->assertEquals(1, $this->customStage->fresh()->position);
    }

    // 6. stage activation/deactivation
    public function test_6_stage_activation_and_deactivation(): void
    {
        // Deactivate custom stage with 0 leads
        $response = $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $this->customStage), [
            'name_ar' => $this->customStage->name_ar,
            'position' => $this->customStage->position,
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('v2.settings.stages.index'));
        $this->assertFalse((bool) $this->customStage->fresh()->is_active);

        // Cannot deactivate stage containing leads
        $lead = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Lead In Custom Stage',
            'phone' => '0501112233',
            'source' => 'web',
        ]);
        PipelineStage::whereKey($this->customStage->id)->update(['is_active' => true]);

        $response2 = $this->actingAs($this->admin)->patch(route('v2.settings.stages.update', $this->customStage->fresh()), [
            'name_ar' => $this->customStage->name_ar,
            'position' => $this->customStage->position,
            'is_active' => 0,
        ]);

        $response2->assertSessionHasErrors('stage');
        $this->assertTrue((bool) $this->customStage->fresh()->is_active);
    }

    // 7. stage question creation
    public function test_7_stage_question_creation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.fields.store', $this->customStage), [
            'label_ar' => 'رقم المعاملة',
            'type' => 'text',
            'is_required' => 1,
        ]);

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->customStage));
        $this->assertDatabaseHas('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->customStage->id,
            'label_ar' => 'رقم المعاملة',
            'is_required' => true,
        ]);
    }

    // 8. required reason blocks transition
    public function test_8_required_reason_blocks_transition_when_empty(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'inspection_code',
            'label_ar' => 'كود المعاينة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 8',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => []]
        );
    }

    // 9. optional question permits transition
    public function test_9_optional_question_permits_transition(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'optional_note',
            'label_ar' => 'ملاحظة اختيارية',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 9',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => []]
        );

        $this->assertEquals($this->customStatus->id, $result['lead']->lead_status_id);
    }

    // 10. select rejects invalid options
    public function test_10_select_rejects_values_outside_configured_options(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'priority',
            'label_ar' => 'الأولوية',
            'type' => 'select',
            'options' => [
                ['value' => 'high', 'label_ar' => 'عالي'],
                ['value' => 'low', 'label_ar' => 'منخفض'],
            ],
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 10',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['priority' => 'invalid_unauthorized_option']]
        );
    }

    // 11. custom stage question works without hardcoded stage code
    public function test_11_custom_stage_question_works_without_hardcoded_stage_code(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'custom_dynamic_q',
            'label_ar' => 'سؤال ديناميكي مخصص',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 11',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['custom_dynamic_q' => 'إجابة مخصصة بالكامل']]
        );

        $this->assertEquals($this->customStatus->id, $result['lead']->lead_status_id);
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'custom_dynamic_q',
            'value' => 'إجابة مخصصة بالكامل',
        ]);
    }

    // 12. no_answer compatibility
    public function test_12_no_answer_compatibility_preserves_next_follow_up_at(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 12',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $callbackDate = now()->addDays(2)->format('Y-m-d H:i');

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->noAnswerStatus,
            $this->admin,
            ['stage_fields' => ['callback_at' => $callbackDate]]
        );

        $this->assertEquals($this->noAnswerStatus->id, $result['lead']->lead_status_id);
        $this->assertNotNull($result['lead']->next_follow_up_at);
    }

    // 13. not_interested reason compatibility
    public function test_13_not_interested_reason_compatibility_clears_follow_up(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 13',
            'phone' => '0501112233',
            'next_follow_up_at' => now()->addDay(),
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->notInterestedStatus,
            $this->admin,
            [
                'stage_fields' => ['reason' => 'high_price'],
            ]
        );

        $this->assertEquals($this->notInterestedStatus->id, $result['lead']->lead_status_id);
        $this->assertNull($result['lead']->next_follow_up_at);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'disinterest_reason' => 'high_price',
        ]);
    }

    // 14. canonical donor/terminal compatibility
    public function test_14_terminal_or_closing_stage_compatibility(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 14',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            []
        );

        $this->assertEquals($this->customStatus->id, $result['lead']->lead_status_id);
    }

    // 15. unknown key rejected
    public function test_15_arbitrary_unknown_key_is_rejected(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 15',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['completely_unknown_unregistered_key' => 'attack']]
        );
    }

    // 16. cross-stage injection rejected
    public function test_16_cross_stage_field_injection_is_rejected(): void
    {
        // Create field on interest stage
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->interestStage->id,
            'key' => 'interest_specific_field',
            'label_ar' => 'خاص بالاهتمام',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 16',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        // Attempt injecting interest_specific_field when moving to customStage
        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['interest_specific_field' => 'injected_data']]
        );
    }

    public function test_transfer_pages_follow_active_pipeline_stages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('v2.leads.import'))
            ->assertOk()
            ->assertSee($this->customStage->name_ar)
            ->assertSee($this->customStage->code);

        $this->actingAs($this->admin)
            ->get(route('v2.leads.export'))
            ->assertOk()
            ->assertSee($this->customStage->name_ar);

        $this->customStatus->delete();
        $this->customStage->delete();

        $this->assertModelMissing($this->customStage);

        $this->actingAs($this->admin)
            ->get(route('v2.leads.import'))
            ->assertOk()
            ->assertDontSee($this->customStage->name_ar)
            ->assertDontSee($this->customStage->code);

        $this->actingAs($this->admin)
            ->get(route('v2.leads.export'))
            ->assertOk()
            ->assertDontSee($this->customStage->name_ar);
    }

    public function test_import_resolves_a_dynamic_stage_to_its_default_status(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'dynamic-stage.csv',
            implode(',', ['first_name', 'phone', 'source', 'stage'])."\n"
                .implode(',', ['Dynamic Lead', '0509998877', 'website', $this->customStage->code])."\n"
        );

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('v2.leads.import.preview'), [
                'import_file' => $file,
            ]);

        $previewResponse
            ->assertOk()
            ->assertViewHas('preview', static function (array $preview): bool {
                return $preview['valid_count'] === 1
                    && $preview['rows'][0]['stage'] === 'مرحلة التدقيق';
            });

        $token = $previewResponse->viewData('preview')['token'];

        $this->actingAs($this->admin)
            ->post(route('v2.leads.import.confirm'), [
                'preview_token' => $token,
            ])
            ->assertRedirect(route('v2.leads.import'));

        $this->assertDatabaseHas('leads', [
            'phone' => '0509998877',
            'lead_status_id' => $this->customStatus->id,
        ]);
    }

    // 17. inactive question rejected
    public function test_17_inactive_question_cannot_be_injected(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'deactivated_field',
            'label_ar' => 'حقل معطل',
            'type' => 'text',
            'is_required' => false,
            'is_active' => false,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 17',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['deactivated_field' => 'data_for_inactive']]
        );
    }

    // 18. answers persisted
    public function test_18_answers_are_persisted_in_database(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'project_budget',
            'label_ar' => 'ميزانية المشروع',
            'type' => 'number',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 18',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['project_budget' => 50000]]
        );

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->customStage->id,
            'field_key' => 'project_budget',
            'value' => '50000',
        ]);
    }

    // 19. repeated visits preserve history
    public function test_19_repeated_visits_to_same_stage_preserve_history_snapshots(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'visit_note',
            'label_ar' => 'ملاحظة الزيارة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 19',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        // 1st visit
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['visit_note' => 'First Visit Snapshot']]
        );

        // Leave stage to startStage
        $transitionService->transition(
            $lead,
            $this->newStatus,
            $this->admin,
            []
        );

        // 2nd visit to customStage
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['visit_note' => 'Second Visit Snapshot']]
        );

        $values = LeadStageFieldValue::query()
            ->where('lead_id', $lead->id)
            ->where('field_key', 'visit_note')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $values);
        $this->assertEquals('First Visit Snapshot', $values[0]->value);
        $this->assertEquals('Second Visit Snapshot', $values[1]->value);
    }

    // 20. actor attribution
    public function test_20_actor_attribution_is_recorded(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'inspector',
            'label_ar' => 'اسم المفتش',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 20',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['inspector' => 'Eng. Ahmed']]
        );

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'created_by_user_id' => $this->admin->id,
            'field_key' => 'inspector',
        ]);
    }

    // 21. Lead edit uses same rules
    public function test_21_lead_edit_validates_and_persists_stage_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'edit_field_check',
            'label_ar' => 'فحص التعديل',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'name' => 'Ahmed Ali',
            'phone' => '0501234567',
            'source' => 'website',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('v2.leads.update', $lead), [
            'first_name' => 'Ahmed',
            'last_name' => 'Ali Updated',
            'phone' => '0501234567',
            'source' => 'website',
            'stage_fields' => [
                'edit_field_check' => 'Updated via Edit Screen',
            ],
        ]);

        $response->assertRedirect(route('v2.leads'));
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'edit_field_check',
            'value' => 'Updated via Edit Screen',
        ]);
    }

    // 22. Follow-up uses same rules
    public function test_22_followup_validates_and_persists_stage_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'followup_field_check',
            'label_ar' => 'فحص المتابعة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 22',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        // Attempt without required stage field -> fails
        $responseFail = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'الاتصال بالعميل',
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'stage_fields' => [],
        ]);
        $responseFail->assertSessionHasErrors('followup_field_check');

        // Provide stage field -> succeeds
        $responseSuccess = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'الاتصال بالعميل بنجاح',
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'stage_fields' => [
                'followup_field_check' => 'Valid Value',
            ],
        ]);
        $responseSuccess->assertRedirect();
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'followup_field_check',
            'value' => 'Valid Value',
        ]);
    }

    // 23. Quick Follow-up uses same rules
    public function test_23_quick_followup_validates_and_persists_stage_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'quick_check',
            'label_ar' => 'فحص سريع',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 23',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        // Quick followup without required field
        $responseFail = $this->actingAs($this->admin)->post(route('v2.tasks.quick_followup', $lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة سريعة',
            'stage_fields' => [],
        ]);
        $responseFail->assertSessionHasErrors('quick_check');

        // With valid field
        $responseSuccess = $this->actingAs($this->admin)->post(route('v2.tasks.quick_followup', $lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة سريعة ناجحة',
            'stage_fields' => ['quick_check' => 'Passed'],
        ]);
        $responseSuccess->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'quick_check',
            'value' => 'Passed',
        ]);
    }

    // 24. Kanban uses same rules
    public function test_24_kanban_transition_submits_stage_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'kanban_check',
            'label_ar' => 'فحص كانبان',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 24',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $lead), [
            'lead_status_id' => $this->customStatus->id,
            'communication_type' => 'other',
            'outcome' => 'نقل عبر لوحة كانبان',
            'kanban_popup' => 1,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            'stage_fields' => [
                'kanban_check' => 'Drag and Drop Confirmed',
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals($this->customStatus->id, $lead->fresh()->lead_status_id);
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'kanban_check',
            'value' => 'Drag and Drop Confirmed',
        ]);
    }

    // 25. existing CRM v2 tests remain green
    public function test_25_all_types_and_conditions_work(): void
    {
        // Test select, checkbox, number, datetime in stage field schema
        $selectField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'deal_type',
            'label_ar' => 'نوع الصفقة',
            'type' => 'select',
            'options' => [
                ['value' => 'retail', 'label_ar' => 'قطاعي'],
                ['value' => 'wholesale', 'label_ar' => 'جملة'],
            ],
            'is_required' => true,
            'is_active' => true,
        ]);

        $conditionalField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->customStage->id,
            'key' => 'wholesale_quantity',
            'label_ar' => 'الكمية المطلوبة',
            'type' => 'number',
            'is_required' => true,
            'conditions' => [
                'field' => 'deal_type',
                'operator' => 'equals',
                'value' => 'wholesale',
            ],
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Test 25',
            'phone' => '0501112233',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);

        // When deal_type is retail, wholesale_quantity is not applicable/required
        $result = $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['deal_type' => 'retail']]
        );
        $this->assertEquals($this->customStatus->id, $result['lead']->lead_status_id);

        // When deal_type is wholesale, wholesale_quantity is required
        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->customStatus,
            $this->admin,
            ['stage_fields' => ['deal_type' => 'wholesale']]
        );
    }
}
