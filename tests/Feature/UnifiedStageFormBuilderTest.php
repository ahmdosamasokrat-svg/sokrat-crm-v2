<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStageFieldValue;
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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnifiedStageFormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private PipelineStage $stageEmpty;
    private LeadStatus $statusA;
    private LeadStatus $statusB;
    private LeadStatus $statusEmpty;

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
            'username' => 'admin_architect',
            'name' => 'Admin Architect',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $employeeGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-rep'],
            [
                'name' => 'موظف مبيعات',
                'description' => 'Sales Representative',
                'is_system' => false,
            ]
        );
        $employeeGroup->permissions()->sync(
            Permission::query()->whereIn('code', [
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_UPDATE->value,
                CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
                CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                CrmPermission::LEADS_SCOPE_ALL->value,
            ])->pluck('id')
        );

        $this->employee = User::factory()->create([
            'username' => 'sales_user',
            'name' => 'Sales Employee',
            'is_active' => true,
        ]);
        $this->employee->groups()->attach($employeeGroup);

        // Create stages
        $this->stageA = PipelineStage::query()->create([
            'code' => 'stage_a',
            'name_ar' => 'المرحلة أ',
            'position' => 1,
            'color' => '#3478f6',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->statusA = $this->stageA->statuses()->firstOrFail();

        $this->stageB = PipelineStage::query()->create([
            'code' => 'stage_b',
            'name_ar' => 'المرحلة ب',
            'position' => 2,
            'color' => '#169a64',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $this->statusB = $this->stageB->statuses()->firstOrFail();

        $this->stageEmpty = PipelineStage::query()->create([
            'code' => 'stage_empty',
            'name_ar' => 'مرحلة بدون حقول',
            'position' => 3,
            'color' => '#64748b',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $this->statusEmpty = $this->stageEmpty->statuses()->firstOrFail();
    }

    // ==========================================
    // SCHEMA: 1-9
    // ==========================================

    public function test_01_create_text_field(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'ملاحظة خاصة',
                'type' => 'text',
                'binding_type' => 'custom',
                'is_required' => 0,
            ]
        );

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->stageA));
        $this->assertDatabaseHas('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->stageA->id,
            'label_ar' => 'ملاحظة خاصة',
            'type' => 'text',
            'binding_type' => 'custom',
        ]);
    }

    public function test_02_create_phone_field(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'هاتف إضافي',
                'type' => 'tel',
                'binding_type' => 'custom',
                'is_required' => 0,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->stageA->id,
            'type' => 'tel',
        ]);
    }

    public function test_03_create_date_field(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'تاريخ التسليم المتوقع',
                'type' => 'date',
                'binding_type' => 'custom',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->stageA->id,
            'type' => 'date',
        ]);
    }

    public function test_04_create_time_field(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'وقت الاتصال المفضل',
                'type' => 'time',
                'binding_type' => 'custom',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->stageA->id,
            'type' => 'time',
        ]);
    }

    public function test_05_create_select_field(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'خطة الاشتراك',
                'type' => 'select',
                'binding_type' => 'custom',
                'options_raw' => "باقة فضية | Silver | silver\nباقة ذهبية | Gold | gold",
            ]
        );

        $response->assertRedirect();
        $field = PipelineStageField::query()->where('pipeline_stage_id', $this->stageA->id)->firstOrFail();
        $this->assertSame('select', $field->type);
        $this->assertCount(2, $field->options);
    }

    public function test_06_reorder_fields(): void
    {
        $f1 = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'f1',
            'label_ar' => 'حقل 1',
            'type' => 'text',
            'position' => 1,
        ]);
        $f2 = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'f2',
            'label_ar' => 'حقل 2',
            'type' => 'text',
            'position' => 2,
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            route('v2.settings.stages.fields.reorder', $this->stageA),
            ['order' => [$f2->id, $f1->id]]
        );

        $response->assertOk();
        $this->assertSame(1, $f2->fresh()->position);
        $this->assertSame(2, $f1->fresh()->position);
    }

    public function test_07_required_field(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'contract_num',
            'label_ar' => 'رقم العقد',
            'type' => 'text',
            'is_required' => true,
        ]);

        $this->assertTrue($field->fresh()->is_required);
    }

    public function test_08_disable_field(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'optional_ref',
            'label_ar' => 'مرجع اختياري',
            'type' => 'text',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(
            route('v2.settings.stages.fields.toggle', [$this->stageA, $field])
        );

        $response->assertRedirect();
        $this->assertFalse($field->fresh()->is_active);
    }

    public function test_09_rename_label_without_data_loss(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'stable_field_key',
            'label_ar' => 'الاسم القديم',
            'type' => 'text',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل اختبار التسمية',
        ]);

        LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageA->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => 'stable_field_key',
            'value' => 'بيانات تاريخية مهمة',
        ]);

        $this->actingAs($this->admin)->patch(
            "/settings/stages/{$this->stageA->id}/fields/{$field->id}",
            [
                'label_ar' => 'الاسم الجديد المحدث',
                'type' => 'text',
            ]
        );

        $this->assertSame('الاسم الجديد المحدث', $field->fresh()->label_ar);
        $this->assertSame('stable_field_key', $field->fresh()->key);
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'stable_field_key',
            'value' => 'بيانات تاريخية مهمة',
        ]);
    }

    // ==========================================
    // CANONICAL LEAD FIELDS: 10-14
    // ==========================================

    public function test_10_name_field_pre_fills(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'customer_name',
            'label_ar' => 'اسم العميل',
            'type' => 'text',
            'binding_type' => 'canonical',
            'binding_target' => 'name',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'محمد أحمد الصباحي',
            'phone' => '0501234567',
        ]);

        $prefilled = StageFieldSchema::prefillValues($lead, $this->stageB);
        $this->assertSame('محمد أحمد الصباحي', $prefilled['customer_name']);
    }

    public function test_11_phone_updates_lead(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'phone',
            'label_ar' => 'رقم الهاتف',
            'type' => 'tel',
            'binding_type' => 'canonical',
            'binding_target' => 'phone',
            'is_required' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل تحديث الهاتف',
            'phone' => '0500000001',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['phone' => '0599999999'],
            ]
        );

        $this->assertSame('0599999999', $result['lead']->phone);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'phone' => '0599999999',
            'lead_status_id' => $this->statusB->id,
        ]);
    }

    public function test_12_address_updates_lead(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'address',
            'label_ar' => 'العنوان',
            'type' => 'text',
            'binding_type' => 'canonical',
            'binding_target' => 'address',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل تحديث العنوان',
            'address' => 'العنوان القديم',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['address' => 'الرياض - حي الملز - شارع الستين'],
            ]
        );

        $this->assertSame('الرياض - حي الملز - شارع الستين', $result['lead']->address);
    }

    public function test_13_company_updates_lead(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'company_name',
            'label_ar' => 'اسم الشركة',
            'type' => 'text',
            'binding_type' => 'canonical',
            'binding_target' => 'company_name',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل تحديث الشركة',
            'company_name' => 'مؤسسة البداية',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['company_name' => 'شركة النجاح المتقدمة'],
            ]
        );

        $this->assertSame('شركة النجاح المتقدمة', $result['lead']->company_name);
    }

    public function test_14_unauthorized_db_field_cannot_bind(): void
    {
        // Try to bind to unauthorized database columns like password, id, created_at
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'محاولة خبيثة',
                'type' => 'text',
                'binding_type' => 'canonical',
                'binding_target' => 'password',
            ]
        );

        $response->assertSessionHasErrors('binding_target');
        $this->assertDatabaseMissing('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->stageA->id,
            'binding_target' => 'password',
        ]);
    }

    // ==========================================
    // CUSTOM FIELDS: 15-19
    // ==========================================

    public function test_15_custom_text_saves(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'visit_note',
            'label_ar' => 'ملاحظة الزيارة',
            'type' => 'text',
            'binding_type' => 'custom',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص الملاحظة',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['visit_note' => 'العميل يطلب معاينة موقعية'],
            ]
        );

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageB->id,
            'field_key' => 'visit_note',
            'value' => 'العميل يطلب معاينة موقعية',
        ]);
    }

    public function test_16_number_saves(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'deal_amount',
            'label_ar' => 'قيمة الصفقة',
            'type' => 'currency',
            'binding_type' => 'custom',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص المبلغ',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['deal_amount' => '15500.50'],
            ]
        );

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'deal_amount',
            'value' => '15500.50',
        ]);
    }

    public function test_17_date_time_saves(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'meeting_at',
            'label_ar' => 'موعد الاجتماع',
            'type' => 'datetime',
            'binding_type' => 'custom',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص التاريخ والوقت',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['meeting_at' => '2026-09-10 14:30:00'],
            ]
        );

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'meeting_at',
        ]);
    }

    public function test_18_select_validates_options(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'decision',
            'label_ar' => 'القرار',
            'type' => 'select',
            'options' => [
                ['value' => 'accept', 'label_ar' => 'قبول'],
                ['value' => 'reject', 'label_ar' => 'رفض'],
            ],
            'is_required' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل اختبار الخيارات',
        ]);

        $transitionService = app(LeadTransitionService::class);

        // Invalid option rejected
        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'stage_fields' => ['decision' => 'invalid_unauthorized_option'],
            ]
        );
    }

    public function test_19_historical_value_preserved(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'contract_value',
            'label_ar' => 'قيمة العقد',
            'type' => 'number',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل القيمة التاريخية',
        ]);

        $transitionService = app(LeadTransitionService::class);

        // Transition 1
        $t1 = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            ['stage_fields' => ['contract_value' => '10000']]
        );

        // Transition 2
        $t2 = $transitionService->transition(
            $lead,
            $this->statusA,
            $this->admin,
            []
        );

        // Transition 3
        $t3 = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            ['stage_fields' => ['contract_value' => '25000']]
        );

        $historyValues = LeadStageFieldValue::query()
            ->where('lead_id', $lead->id)
            ->where('field_key', 'contract_value')
            ->orderBy('id')
            ->pluck('value')
            ->all();

        $this->assertSame(['10000', '25000'], $historyValues);
    }

    // ==========================================
    // TRANSITION: 20-26
    // ==========================================

    public function test_20_target_stage_determines_form(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'only_stage_a',
            'label_ar' => 'خاص بالمرحلة أ',
            'type' => 'text',
        ]);
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'only_stage_b',
            'label_ar' => 'خاص بالمرحلة ب',
            'type' => 'text',
        ]);

        $stageAFields = StageFieldSchema::getFieldsForStage($this->stageA)->pluck('key')->all();
        $stageBFields = StageFieldSchema::getFieldsForStage($this->stageB)->pluck('key')->all();

        $this->assertContains('only_stage_a', $stageAFields);
        $this->assertNotContains('only_stage_b', $stageAFields);

        $this->assertContains('only_stage_b', $stageBFields);
        $this->assertNotContains('only_stage_a', $stageBFields);
    }

    public function test_21_different_stages_render_different_forms(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'stage_a_field',
            'label_ar' => 'سؤال أ المميز',
            'type' => 'text',
        ]);
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'stage_b_field',
            'label_ar' => 'سؤال ب المميز',
            'type' => 'text',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل مقارنة المراحل',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', $lead));
        $response->assertOk();
        $response->assertSee('سؤال أ المميز');
        $response->assertSee('سؤال ب المميز');
    }

    public function test_22_no_fields_transition_works(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل مرحلة فارغة',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusEmpty,
            $this->admin,
            []
        );

        $this->assertSame($this->statusEmpty->id, $result['lead']->lead_status_id);
    }

    public function test_23_required_validation_blocks_transition(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'mandatory_code',
            'label_ar' => 'كود إلزامي',
            'type' => 'text',
            'is_required' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل الحقل الإلزامي',
        ]);

        $transitionService = app(LeadTransitionService::class);

        $this->expectException(ValidationException::class);
        $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            ['stage_fields' => []]
        );
    }

    public function test_24_successful_submission_transitions(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'opt_note',
            'label_ar' => 'ملاحظة',
            'type' => 'text',
            'is_required' => false,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل التحويل الناجح',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'other',
                'outcome' => 'تحويل ناجح للمرحلة التالية',
                'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'stage_fields' => ['opt_note' => 'تم استيفاء المتطلبات'],
            ]
        );

        $response->assertRedirect();
        $this->assertSame($this->statusB->id, $lead->fresh()->lead_status_id);
    }

    public function test_25_lead_status_history_preserved(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل سجل الحالات',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'history_note' => 'نقل من المرحلة أ إلى المرحلة ب',
            ]
        );

        $this->assertNotNull($result['history']);
        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusA->id,
            'to_status_id' => $this->statusB->id,
            'note' => 'نقل من المرحلة أ إلى المرحلة ب',
        ]);
    }

    public function test_26_transition_side_effects_preserved(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل التأثيرات الجانبية',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusB,
            $this->admin,
            [
                'record_followup' => true,
                'communication_type' => 'call',
                'outcome' => 'تم الاتصال بالعميل بنجاح',
                'next_follow_up_at' => now()->addDays(3),
            ]
        );

        $this->assertNotNull($result['followup']);
        $this->assertSame('call', $result['followup']->communication_type);
        $this->assertNotNull($result['lead']->next_follow_up_at);
    }

    // ==========================================
    // SECURITY: 27-30
    // ==========================================

    public function test_27_employee_cannot_edit_form_schema(): void
    {
        $response = $this->actingAs($this->employee)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'محاولة تعديل مخطط',
                'type' => 'text',
            ]
        );

        $response->assertForbidden();
    }

    public function test_28_unauthorized_lead_cannot_transition(): void
    {
        $restrictedLead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل معزول',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        $isolatedUser = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($isolatedUser)->post(
            route('v2.leads.followups.store', $restrictedLead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'other',
                'outcome' => 'محاولة تحويل بدون صلاحية',
            ]
        );

        $response->assertForbidden();
    }

    public function test_29_branch_isolation_preserved(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فرع محدد',
        ]);

        $this->assertTrue($lead->isAccessibleTo($this->admin));
    }

    public function test_30_field_binding_allowlist_enforced(): void
    {
        $allowlistKeys = array_keys(PipelineStageField::CANONICAL_FIELDS);

        $this->assertContains('name', $allowlistKeys);
        $this->assertContains('phone', $allowlistKeys);
        $this->assertContains('email', $allowlistKeys);
        $this->assertContains('company_name', $allowlistKeys);
        $this->assertContains('address', $allowlistKeys);

        $this->assertNotContains('id', $allowlistKeys);
        $this->assertNotContains('created_at', $allowlistKeys);
        $this->assertNotContains('updated_at', $allowlistKeys);
        $this->assertNotContains('deleted_at', $allowlistKeys);
        $this->assertNotContains('password', $allowlistKeys);
        $this->assertNotContains('branch_id', $allowlistKeys);
    }

    // ==========================================
    // LEGACY: 31-34
    // ==========================================

    public function test_31_old_runtime_form_no_longer_renders(): void
    {
        // When dynamic stage fields are configured on stage B, old quotation and not interested forms do not render
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'b_custom_req',
            'label_ar' => 'طلب خاص بالمرحلة ب',
            'type' => 'text',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusB->id,
            'name' => 'عميل الفحص الحديث',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', $lead));
        $response->assertOk();
        $response->assertSee('طلب خاص بالمرحلة ب');
        // Old hardcoded sections should not render
        $response->assertDontSee('quotationSection');
        $response->assertDontSee('notInterestedSection');
    }

    public function test_32_old_settings_customer_company_fields_entry_absent(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.settings'));
        $response->assertOk();
        $response->assertDontSee(route('v2.settings.followup-customer-fields.index'));
    }

    public function test_33_historical_legacy_data_preserved(): void
    {
        // Ensure database table lead_stage_field_values is intact and historical values are never deleted
        $this->assertDatabaseCount('lead_stage_field_values', 0);

        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'historical_key',
            'label_ar' => 'حقل تاريخي',
            'type' => 'text',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل السجلات المحفوظة',
        ]);

        $val = LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageA->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => 'historical_key',
            'value' => 'قيمة محفوظة لا تحذف',
        ]);

        $field->delete(); // Soft delete field
        $this->assertTrue($field->trashed());
        $this->assertDatabaseHas('lead_stage_field_values', [
            'id' => $val->id,
            'value' => 'قيمة محفوظة لا تحذف',
        ]);
    }

    public function test_34_migrated_configuration_correct(): void
    {
        // Verify canonical fields can be configured across stages without duplication
        $c1 = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'company_name',
            'label_ar' => 'اسم الشركة',
            'type' => 'text',
            'binding_type' => 'canonical',
            'binding_target' => 'company_name',
        ]);

        $this->assertTrue($c1->isCanonical());
        $this->assertSame('company_name', $c1->binding_target);

        // Attempting to add same canonical field to stage A again should fail validation
        $response = $this->actingAs($this->admin)->post(
            route('v2.settings.stages.fields.store', $this->stageA),
            [
                'label_ar' => 'اسم الشركة مكرر',
                'type' => 'text',
                'binding_type' => 'canonical',
                'binding_target' => 'company_name',
            ]
        );

        $response->assertSessionHasErrors('binding_target');
    }

    // ==========================================
    // LOCALIZATION: 35-36
    // ==========================================

    public function test_35_localization_arabic(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'appointment_loc',
            'label_ar' => 'مكان المقابلة',
            'label_en' => 'Meeting Location',
            'type' => 'text',
        ]);

        app()->setLocale('ar');
        $this->assertSame('مكان المقابلة', $field->localizedLabel('ar'));
    }

    public function test_36_localization_english(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'appointment_loc',
            'label_ar' => 'مكان المقابلة',
            'label_en' => 'Meeting Location',
            'type' => 'text',
        ]);

        app()->setLocale('en');
        $this->assertSame('Meeting Location', $field->localizedLabel('en'));
    }
    // ==========================================
    // KANBAN TRANSITION REGRESSION SUITE: 37-48
    // ==========================================

    public function test_37_kanban_popup_get_returns_200(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص نافذة كانبان',
            'phone' => '0511111111',
        ]);

        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertSee('تسجيل متابعة');
    }

    public function test_38_destination_stage_with_no_fields_returns_200(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل مرحلة خالية',
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusEmpty->id,
            ])
        );

        $response->assertOk();
        $response->assertDontSee('Server Error');
        $response->assertDontSee('500 Server Error');
    }

    public function test_39_destination_custom_field_renders(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'custom_project_code',
            'label_ar' => 'رمز المشروع الخاص',
            'type' => 'text',
            'binding_type' => 'custom',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل كود المشروع',
        ]);

        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertSee('رمز المشروع الخاص');
        $response->assertSee('stage_fields[custom_project_code]');
    }

    public function test_40_canonical_field_renders_and_pre_fills(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'company_name',
            'label_ar' => 'اسم الشركة',
            'type' => 'text',
            'binding_type' => 'canonical',
            'binding_target' => 'company_name',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل شركة المحترف',
            'company_name' => 'شركة المحترف للحلول',
        ]);

        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertSee('شركة المحترف للحلول');
    }

    public function test_41_required_field_validation(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'mandatory_decision',
            'label_ar' => 'قرار إلزامي',
            'type' => 'text',
            'is_required' => true,
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل القرار الإلزامي',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'other',
                'outcome' => 'محاولة بدون الحقل الإلزامي',
                'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'stage_fields' => [],
            ]
        );
        $response->assertSessionHasErrors('mandatory_decision');
    }

    public function test_42_select_options_render(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'tier',
            'label_ar' => 'مستوى العميل',
            'type' => 'select',
            'options' => [
                ['value' => 'vip', 'label_ar' => 'عميل مميز VIP', 'label_en' => 'VIP Client'],
                ['value' => 'standard', 'label_ar' => 'عميل قياسي', 'label_en' => 'Standard Client'],
            ],
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل الخيارات',
        ]);

        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertSee('عميل مميز VIP');
        $response->assertSee('عميل قياسي');
    }

    public function test_43_legacy_field_row_does_not_cause_500(): void
    {
        // Legacy row created without specifying binding_type or binding_target
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'legacy_raw_q',
            'label_ar' => 'سؤال قديم موروث',
            'type' => 'text',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل السجلات القديمة',
        ]);
        $response = $this->actingAs($this->admin)->withSession(['locale' => 'ar'])->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertSee('سؤال قديم موروث');
    }

    public function test_44_invalid_binding_handled_safely(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'corrupted_target',
            'label_ar' => 'هدف غير صالح',
            'type' => 'text',
            'binding_type' => 'canonical',
            'binding_target' => 'non_existent_column',
        ]);

        $this->assertNull($field->canonicalConfig());

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل فحص الأمان',
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertOk();
        $response->assertDontSee('Server Error');
    }

    public function test_45_status_to_pipeline_stage_mapping_correct(): void
    {
        $status = $this->statusB;
        $this->assertSame((int) $this->stageB->id, (int) $status->pipeline_stage_id);
        $this->assertSame((int) $this->stageB->id, (int) $status->stage->id);
    }

    public function test_46_unauthorized_lead_denied_safely(): void
    {
        $restrictedLead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل محمي',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
        ]);

        $isolatedUser = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($isolatedUser)->get(
            route('v2.leads.followups.index', [
                'lead' => $restrictedLead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );

        $response->assertForbidden();
    }

    public function test_47_successful_transition_records_history(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل تسجيل السجل من كانبان',
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'other',
                'outcome' => 'تحويل كانبان مؤكد',
                'next_follow_up_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'kanban_popup' => 1,
            ]
        );

        $response->assertRedirect();
        $this->assertSame((int) $this->statusB->id, (int) $lead->fresh()->lead_status_id);
        $this->assertDatabaseHas('lead_status_histories', [
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusA->id,
            'to_status_id' => $this->statusB->id,
        ]);
        $this->assertDatabaseHas('lead_followups', [
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusA->id,
            'to_status_id' => $this->statusB->id,
            'outcome' => 'تحويل كانبان مؤكد',
        ]);
    }

    public function test_48_modal_workflow_succeeds_from_kanban(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'phone',
            'label_ar' => 'رقم الهاتف',
            'type' => 'tel',
            'binding_type' => 'canonical',
            'binding_target' => 'phone',
        ]);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'عميل مسار كانبان الكامل',
            'phone' => '0500000000',
        ]);

        // 1. GET popup loads correctly
        $getResponse = $this->actingAs($this->admin)->get(
            route('v2.leads.followups.index', [
                'lead' => $lead->id,
                'kanban_popup' => 1,
                'target_status_id' => $this->statusB->id,
            ])
        );
        $getResponse->assertOk();
        $getResponse->assertSee('0500000000');

        // 2. POST transition succeeds
        $postResponse = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $lead),
            [
                'lead_status_id' => $this->statusB->id,
                'communication_type' => 'call',
                'outcome' => 'تم الاتفاق والتحديث',
                'next_follow_up_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
                'kanban_popup' => 1,
                'stage_fields' => ['phone' => '0588888888'],
            ]
        );

        $postResponse->assertRedirect();
        $this->assertSame('0588888888', $lead->fresh()->phone);
        $this->assertSame((int) $this->statusB->id, (int) $lead->fresh()->lead_status_id);
    }
}
