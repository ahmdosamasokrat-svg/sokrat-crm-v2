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
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrossStageUnifiedFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
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
            'username' => 'cross_stage_admin_' . uniqid(),
            'name' => 'Admin Cross Stage',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $initialStage = PipelineStage::query()->create([
            'code' => 'stage_init_' . uniqid(),
            'name_ar' => 'مرحلة أولية',
            'position' => 1,
            'is_active' => true,
        ]);
        $initialStatus = $initialStage->ensureDefaultStatus();

        $this->lead = Lead::query()->create([
            'lead_status_id' => $initialStatus->id,
            'name' => 'عميل اختبار كل المراحل',
            'phone' => '0501234567',
            'source' => 'web',
            'assigned_user_id' => $this->admin->id,
        ]);
    }

    /** Test Stage 1: Simple required text / number stage */
    public function test_simple_required_text_stage_validation_and_success(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_text_' . uniqid(),
            'name_ar' => 'مرحلة نصوص',
            'position' => 2,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $stage->fields()->create([
            'key' => 'city_name',
            'label_ar' => 'المدينة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
        ]);
        StageFieldSchema::flushCache((int) $stage->id);

        // Invalid: missing city_name
        $respInvalid = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة بدون مدينة',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => [],
        ]);
        $respInvalid->assertSessionHasErrors();

        // Valid: with city_name
        $respValid = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة بالمدينة',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => ['city_name' => 'الرياض'],
        ]);
        $respValid->assertRedirect(route('v2.leads'));
        $this->assertDatabaseHas('lead_stage_field_values', ['field_key' => 'city_name', 'value' => 'الرياض']);
    }

    /** Test Stage 2: Date / Datetime stage with callback_at */
    public function test_datetime_stage_validation_and_success(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_date_' . uniqid(),
            'name_ar' => 'مرحلة مواعيد',
            'position' => 3,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $stage->fields()->create([
            'key' => 'callback_at',
            'label_ar' => 'موعد إعادة الاتصال',
            'type' => 'datetime',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
        ]);
        StageFieldSchema::flushCache((int) $stage->id);

        // Valid submit using callback_at
        $respValid = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'موعد اتصال جديد',
            'stage_fields' => ['callback_at' => '2026-09-10 14:00:00'],
        ]);
        $respValid->assertRedirect(route('v2.leads'));
        $this->lead->refresh();
        $this->assertNotNull($this->lead->next_follow_up_at);
    }

    /** Test Stage 3: Conditional / Nested question stage */
    public function test_conditional_nested_stage_validation_and_success(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_cond_' . uniqid(),
            'name_ar' => 'مرحلة شروط',
            'position' => 4,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $stage->fields()->create([
            'key' => 'has_license',
            'label_ar' => 'هل توجد رخصة؟',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => [
                ['value' => 'yes', 'label_ar' => 'نعم'],
                ['value' => 'no', 'label_ar' => 'لا'],
            ],
        ]);

        $stage->fields()->create([
            'key' => 'license_number',
            'label_ar' => 'رقم الرخصة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 2,
            'conditions' => [
                'rules' => [['field_key' => 'has_license', 'operator' => 'equals', 'value' => 'yes']],
            ],
        ]);
        StageFieldSchema::flushCache((int) $stage->id);

        // has_license=no -> license_number not required -> succeeds
        $respNo = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'لا توجد رخصة',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => ['has_license' => 'no'],
        ]);
        $respNo->assertRedirect(route('v2.leads'));

        // has_license=yes without license_number -> fails
        $respYesFail = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'توجد رخصة بدون رقم',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => ['has_license' => 'yes'],
        ]);
        $respYesFail->assertSessionHasErrors();
    }

    /** Test Stage 4: Multiselect stage */
    public function test_multiselect_stage_validation_and_success(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_multi_' . uniqid(),
            'name_ar' => 'مرحلة خيارات متعددة',
            'position' => 5,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $stage->fields()->create([
            'key' => 'tech_stack',
            'label_ar' => 'التقنيات المطلوبة',
            'type' => 'multiselect',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => [
                ['value' => 'voip', 'label_ar' => 'VoIP'],
                ['value' => 'crm', 'label_ar' => 'CRM'],
            ],
        ]);
        StageFieldSchema::flushCache((int) $stage->id);

        $resp = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'اختيار تقنيات متعددة',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => ['tech_stack' => ['voip', 'crm']],
        ]);
        $resp->assertRedirect(route('v2.leads'));
    }

    /** Test Stage 5: File-required stage */
    public function test_file_required_stage_validation_and_success(): void
    {
        Storage::fake('local');
        $stage = PipelineStage::query()->create([
            'code' => 'stage_file_' . uniqid(),
            'name_ar' => 'مرحلة ملفات',
            'position' => 6,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $stage->fields()->create([
            'key' => 'contract_doc',
            'label_ar' => 'مستند العقد',
            'type' => 'file',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => ['accepted_mime_types' => ['pdf']],
        ]);
        StageFieldSchema::flushCache((int) $stage->id);

        // Missing file -> fails
        $respFail = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'بدون ملف',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => [],
        ]);
        $respFail->assertSessionHasErrors();

        // Valid file -> succeeds
        $file = UploadedFile::fake()->create('contract.pdf', 300, 'application/pdf');
        $respSuccess = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'مع ملف العقد',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => ['contract_doc' => $file],
        ]);
        $respSuccess->assertRedirect(route('v2.leads'));
    }

    /** Test Stage 6: Quotation stage with quotation_pdf */
    public function test_quotation_stage_validation_and_success(): void
    {
        Storage::fake('local');
        $stage = PipelineStage::query()->create([
            'code' => 'quotation_' . uniqid(),
            'name_ar' => 'مرحلة عرض سعر متكامل',
            'position' => 7,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $stage->fields()->create([
            'key' => 'client_type',
            'label_ar' => 'نوع العميل',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => [['value' => 'company', 'label_ar' => 'شركة']],
        ]);
        $stage->fields()->create([
            'key' => 'quotation_pdf',
            'label_ar' => 'رفع عرض السعر',
            'type' => 'file',
            'is_required' => true,
            'is_active' => true,
            'position' => 2,
            'options' => ['document_category' => 'quotation', 'accepted_mime_types' => ['pdf']],
        ]);
        StageFieldSchema::flushCache((int) $stage->id);

        $file = UploadedFile::fake()->create('quote.pdf', 250, 'application/pdf');
        $resp = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة عرض سعر',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'company',
                'quotation_pdf' => $file,
            ],
        ]);
        $resp->assertRedirect(route('v2.leads'));
        $this->lead->refresh();
        $this->assertEquals($status->id, $this->lead->lead_status_id);
        $this->assertTrue((bool) $this->lead->quotation_sent);
    }

    /** Test Stage 7: Stage with no required custom fields */
    public function test_stage_with_no_required_custom_fields_succeeds(): void
    {
        $stage = PipelineStage::query()->create([
            'code' => 'stage_empty_' . uniqid(),
            'name_ar' => 'مرحلة بدون حقول إجبارية',
            'position' => 8,
            'is_active' => true,
        ]);
        $status = $stage->ensureDefaultStatus();

        $resp = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $status->id,
            'communication_type' => 'other',
            'outcome' => 'انتقال لمرحلة فارغة من الأسئلة',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => [],
        ]);
        $resp->assertRedirect(route('v2.leads'));
        $this->lead->refresh();
        $this->assertEquals($status->id, $this->lead->lead_status_id);
    }
}
