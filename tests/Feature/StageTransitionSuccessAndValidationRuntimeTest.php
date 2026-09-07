<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadDocument;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StageTransitionSuccessAndValidationRuntimeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageStart;
    private LeadStatus $statusStart;
    private PipelineStage $stageQuotation;
    private LeadStatus $statusQuotation;
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
            'username' => 'runtime_admin_' . uniqid(),
            'name' => 'Admin Runtime Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stageStart = PipelineStage::query()->create([
            'code' => 'stage_start_' . uniqid(),
            'name_ar' => 'مرحلة البداية',
            'position' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->statusStart = $this->stageStart->ensureDefaultStatus();

        $this->stageQuotation = PipelineStage::query()->create([
            'code' => 'stage_quotation_' . uniqid(),
            'name_ar' => 'مرحلة عرض السعر',
            'position' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->statusQuotation = $this->stageQuotation->ensureDefaultStatus();

        // Field 1: client_type (select)
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'client_type',
            'label_ar' => 'نوع العميل',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => [
                ['value' => 'individual', 'label_ar' => 'فرد', 'label_en' => 'Individual'],
                ['value' => 'company', 'label_ar' => 'شركة', 'label_en' => 'Company'],
            ],
        ]);

        // Field 2: institution_name (text, conditional on client_type=company)
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'institution_name',
            'label_ar' => 'اسم المؤسسة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 2,
            'conditions' => [
                'rules' => [
                    ['field_key' => 'client_type', 'operator' => 'equals', 'value' => 'company'],
                ],
            ],
        ]);

        // Field 3: quotation_pdf (file)
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'quotation_pdf',
            'label_ar' => 'رفع عرض السعر',
            'type' => 'file',
            'is_required' => true,
            'is_active' => true,
            'position' => 3,
            'options' => [
                'document_category' => 'quotation',
                'accepted_mime_types' => ['pdf'],
            ],
        ]);

        // Field 4: q_service (multiselect)
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'q_service',
            'label_ar' => 'نوع الخدمة',
            'type' => 'multiselect',
            'is_required' => true,
            'is_active' => true,
            'position' => 4,
            'options' => [
                ['value' => 'callcenter', 'label_ar' => 'Call Center', 'label_en' => 'Call Center'],
                ['value' => 'erp', 'label_ar' => 'ERP', 'label_en' => 'ERP'],
            ],
        ]);

        // Field 5: q_lines (text, conditional on q_service=callcenter)
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'q_lines',
            'label_ar' => 'عدد الخطوط',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 5,
            'conditions' => [
                'rules' => [
                    ['field_key' => 'q_service', 'operator' => 'equals', 'value' => 'callcenter'],
                ],
            ],
        ]);

        StageFieldSchema::flushCache((int) $this->stageQuotation->id);

        $this->lead = Lead::query()->create([
            'lead_status_id' => $this->statusStart->id,
            'name' => 'عميل فحص الانتقال الناجح',
            'phone' => '0501112233',
            'source' => 'web',
            'assigned_user_id' => $this->admin->id,
        ]);
    }

    /** 1. Normal full-page submission redirects to Leads Index */
    public function test_1_normal_full_page_submission_redirects_to_leads_index(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('quote.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->statusQuotation->id,
            'communication_type' => 'call',
            'outcome' => 'تم الاتفاق وإرسال عرض السعر',
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'company',
                'institution_name' => 'شركة الاختبار',
                'quotation_pdf' => $pdf,
                'q_service' => ['callcenter'],
                'q_lines' => '5',
            ],
        ]);

        $response->assertRedirect(route('v2.leads'));
        $response->assertSessionHas('success');
    }

    /** 2. Popup submission redirects with saved=1 and view renders parent handshake */
    public function test_2_popup_submission_emits_parent_handshake_and_leads_redirect(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('quote_popup.pdf', 300, 'application/pdf');

        $postResponse = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'kanban_popup' => 1,
            'lead_status_id' => $this->statusQuotation->id,
            'communication_type' => 'call',
            'outcome' => 'متابعة عبر البوب أب',
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'company',
                'institution_name' => 'شركة البوب أب',
                'quotation_pdf' => $pdf,
                'q_service' => ['callcenter'],
                'q_lines' => '10',
            ],
        ]);

        $postResponse->assertRedirect(route('v2.leads.followups.index', [
            'lead' => $this->lead->id,
            'kanban_popup' => 1,
            'saved' => 1,
        ]));

        // Follow redirect to followups.index with saved=1
        $viewResponse = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', [
            'lead' => $this->lead->id,
            'kanban_popup' => 1,
            'saved' => 1,
        ]));

        $viewResponse->assertOk();
        $html = $viewResponse->getContent();

        // Verifies parent handshake and destination is Leads Index
        $this->assertStringContainsString('crm-kanban-followup-saved', $html);
        $this->assertStringContainsString(route('v2.leads'), $html);
        $this->assertStringContainsString('window.parent.location.href', $html);
    }

    /** 3. Hidden conditional fields do not block submission */
    public function test_3_hidden_conditional_fields_do_not_block_submission(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('individual.pdf', 200, 'application/pdf');

        // When client_type=individual, institution_name is hidden and must not block
        // When q_service=erp (not callcenter), q_lines is hidden and must not block
        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->statusQuotation->id,
            'communication_type' => 'meeting',
            'outcome' => 'مقابلة مع عميل فردي لبرنامج ERP',
            'next_follow_up_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'individual',
                'quotation_pdf' => $pdf,
                'q_service' => ['erp'],
            ],
        ]);

        $response->assertRedirect(route('v2.leads'));
        $this->assertFalse(session()->has('errors'));

        $this->lead->refresh();
        $this->assertEquals($this->statusQuotation->id, $this->lead->lead_status_id);

        // Hidden conditional values are not persisted
        $this->assertDatabaseMissing('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'field_key' => 'institution_name',
        ]);
        $this->assertDatabaseMissing('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'field_key' => 'q_lines',
        ]);
    }

    /** 4. Visible conditional required fields are enforced */
    public function test_4_visible_conditional_required_fields_are_enforced(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('company_fail.pdf', 200, 'application/pdf');

        // client_type=company makes institution_name visible & required -> missing institution_name must fail
        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->statusQuotation->id,
            'communication_type' => 'call',
            'outcome' => 'تجربة شركة بدون اسم',
            'next_follow_up_at' => now()->addDays(1)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'company',
                // institution_name is omitted!
                'quotation_pdf' => $pdf,
                'q_service' => ['erp'],
            ],
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors')->getBag('default')->keys();
        $this->assertTrue(in_array('institution_name', $errors, true) || in_array('stage_fields.institution_name', $errors, true));
    }

    /** 5. Multiselect condition evaluation correctly evaluates arrays */
    public function test_5_multiselect_condition_evaluation_with_array(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('multiselect.pdf', 200, 'application/pdf');

        // q_service=['erp', 'callcenter'] contains callcenter -> q_lines is visible and provided -> must succeed
        $response = $this->actingAs($this->admin)->post(route('v2.leads.followups.store', $this->lead), [
            'lead_status_id' => $this->statusQuotation->id,
            'communication_type' => 'call',
            'outcome' => 'تجربة اختيار متعدد ناجحة',
            'next_follow_up_at' => now()->addDays(1)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'individual',
                'quotation_pdf' => $pdf,
                'q_service' => ['erp', 'callcenter'],
                'q_lines' => '20',
            ],
        ]);

        $response->assertRedirect(route('v2.leads'));
        $this->assertFalse(session()->has('errors'));

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $this->lead->id,
            'field_key' => 'q_lines',
            'value' => '20',
        ]);
    }
}
