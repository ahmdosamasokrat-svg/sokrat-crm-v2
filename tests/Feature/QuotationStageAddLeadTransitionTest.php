<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadDocument;
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

class QuotationStageAddLeadTransitionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageQuotation;
    private LeadStatus $statusQuotation;
    private PipelineStageField $fieldClientType;
    private PipelineStageField $fieldInstitutionName;
    private PipelineStageField $fieldQuotationPdf;
    private PipelineStageField $fieldServiceType;

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
            'username' => 'quote_admin_' . uniqid(),
            'name' => 'Admin Quotation Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // Resolve or create Quotation Stage
        $this->stageQuotation = PipelineStage::query()->firstOrCreate(
            ['code' => 'quotation'],
            [
                'name_ar' => 'عرض سعر',
                'position' => 6,
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $this->statusQuotation = $this->stageQuotation->ensureDefaultStatus();
        PipelineStageField::query()->where('pipeline_stage_id', $this->stageQuotation->id)->delete();

        // Field 1: client_type (select, required)
        $this->fieldClientType = PipelineStageField::query()->create([
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

        // Field 2: institution_name (text, conditional on client_type=company, required)
        $this->fieldInstitutionName = PipelineStageField::query()->create([
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

        // Field 3: quotation_pdf (file, required, category quotation)
        $this->fieldQuotationPdf = PipelineStageField::query()->create([
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
                'max_file_size' => 10240,
            ],
        ]);

        // Field 4: q_service (multiselect, required)
        $this->fieldServiceType = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'q_service',
            'label_ar' => 'نوع الخدمه',
            'type' => 'multiselect',
            'is_required' => true,
            'is_active' => true,
            'position' => 4,
            'options' => [
                ['value' => 'callcenter', 'label_ar' => 'Call Center', 'label_en' => 'Call Center'],
                ['value' => 'erp', 'label_ar' => 'ERP', 'label_en' => 'ERP'],
            ],
        ]);

        StageFieldSchema::flushCache((int) $this->stageQuotation->id);
    }

    /** 1. Quotation Stage fields render on Add Lead */
    public function test_1_quotation_stage_fields_render_on_add_lead(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('stage_q_block_' . $this->stageQuotation->id, $html);
        $this->assertStringContainsString('stage_fields[client_type]', $html);
        $this->assertStringContainsString('stage_fields[quotation_pdf]', $html);
        $this->assertStringContainsString('stage_fields[institution_name]', $html);
    }

    /** 7. Form uses multipart/form-data */
    public function test_7_form_uses_multipart(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));
        $response->assertOk();
        $this->assertStringContainsString('enctype="multipart/form-data"', $response->getContent());
    }

    /** 11. Unique stable option values for client_type */
    public function test_11_unique_stable_option_values(): void
    {
        $opts = $this->fieldClientType->normalizedOptions();
        $vals = array_column($opts, 'value');

        $this->assertEquals(['individual', 'company'], $vals);
        $this->assertCount(count($vals), array_unique($vals));
    }

    /** 4. Missing customer_type fails required validation */
    public function test_4_missing_customer_type_fails(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('quote.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'عميل اختبار',
            'phone' => '0501112233',
            'source' => 'web',
            'lead_status_id' => $this->statusQuotation->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => '',
                'q_service' => ['callcenter'],
                'quotation_pdf' => $pdf,
            ],
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors')->getBag('default')->keys();
        $this->assertTrue(in_array('client_type', $errors, true) || in_array('stage_fields.client_type', $errors, true));
    }

    /** 5. Required quotation PDF missing fails */
    public function test_5_required_quotation_pdf_missing_fails(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'عميل بدون ملف',
            'phone' => '0501112233',
            'source' => 'web',
            'lead_status_id' => $this->statusQuotation->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'company',
                'institution_name' => 'شركة بدون ملف',
                'q_service' => ['callcenter'],
            ],
        ]);

        $response->assertSessionHasErrors();
        $errors = session('errors')->getBag('default')->keys();
        $this->assertTrue(in_array('quotation_pdf', $errors, true) || in_array('stage_fields.quotation_pdf', $errors, true));
    }

    /** 2, 3, 6, 8, 10, 12, 13, 14, 15: Valid quotation submission creates lead once, document, history, and stage values */
    public function test_valid_quotation_lead_creation_flow(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('offer_quote.pdf', 500, 'application/pdf');

        $initialLeadsCount = Lead::query()->count();

        $response = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'عميل عرض سعر ناجح',
            'phone' => '0509991122',
            'source' => 'web',
            'lead_status_id' => $this->statusQuotation->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'company',
                'institution_name' => 'شركة النجاح المحدودة',
                'q_service' => ['callcenter', 'erp'],
                'quotation_pdf' => $pdf,
            ],
        ]);

        $response->assertRedirect();
        $this->assertFalse(session()->has('errors'));

        // 12. Lead created once
        $this->assertEquals($initialLeadsCount + 1, Lead::query()->count());
        $lead = Lead::query()->latest('id')->first();
        $this->assertNotNull($lead);
        $this->assertEquals($this->statusQuotation->id, $lead->lead_status_id);
        $this->assertTrue((bool) $lead->quotation_sent);
        $this->assertNotNull($lead->quotation_file_path);

        // 13. LeadDocument linked correctly
        $doc = $lead->documents()->where('category', LeadDocument::CATEGORY_QUOTATION)->first();
        $this->assertNotNull($doc);
        $this->assertEquals($this->stageQuotation->id, $doc->pipeline_stage_id);
        $this->assertEquals($this->fieldQuotationPdf->id, $doc->pipeline_stage_field_id);
        $this->assertEquals('offer_quote.pdf', $doc->original_name);
        Storage::disk('local')->assertExists($doc->path);

        // 14. LeadStatusHistory created
        $history = $lead->statusHistory()->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->statusQuotation->id, $history->to_status_id);

        // 15. Stage values persisted
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'client_type',
            'value' => 'company',
        ]);
        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'institution_name',
            'value' => 'شركة النجاح المحدودة',
        ]);
    }

    /** 8, 9. Conditional institution_name is enforced when company, but ignored when individual */
    public function test_8_and_9_conditional_fields_do_not_block_when_hidden(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('individual_quote.pdf', 300, 'application/pdf');

        // When client_type=individual, institution_name is NOT required and does NOT block
        $response = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'عميل فردي',
            'phone' => '0503334455',
            'source' => 'web',
            'lead_status_id' => $this->statusQuotation->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => 'individual',
                'q_service' => ['erp'],
                'quotation_pdf' => $pdf,
            ],
        ]);

        $response->assertRedirect();
        $this->assertFalse(session()->has('errors'));

        $lead = Lead::query()->latest('id')->first();
        $this->assertEquals('عميل فردي', $lead->name);

        // institution_name was not persisted because condition was not met
        $this->assertDatabaseMissing('lead_stage_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'institution_name',
        ]);
    }

    /** 16. Failed validation creates no partial Lead */
    public function test_16_failed_validation_creates_no_partial_lead(): void
    {
        $initialCount = Lead::query()->count();

        $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'عميل ناقص',
            'phone' => '0501112233',
            'source' => 'web',
            'lead_status_id' => $this->statusQuotation->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'client_type' => '', // Fails
            ],
        ]);

        $this->assertEquals($initialCount, Lead::query()->count());
    }

    /** 17. Kanban quotation transition remains working via LeadTransitionService */
    public function test_17_kanban_quotation_transition_remains_working(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('kanban_quote.pdf', 400, 'application/pdf');

        $stageStart = PipelineStage::query()->create([
            'code' => 'stage_start_' . uniqid(),
            'name_ar' => 'البداية',
            'position' => 1,
            'is_active' => true,
        ]);
        $statusStart = $stageStart->ensureDefaultStatus();

        $lead = Lead::query()->create([
            'lead_status_id' => $statusStart->id,
            'name' => 'عميل تحويل كانبان',
            'phone' => '0507778899',
            'source' => 'web',
        ]);

        $transitionService = app(LeadTransitionService::class);
        $result = $transitionService->transition(
            $lead,
            $this->statusQuotation,
            $this->admin,
            [
                'stage_fields' => [
                    'client_type' => 'company',
                    'institution_name' => 'شركة كانبان المحدودة',
                    'quotation_pdf' => $pdf,
                    'q_service' => ['callcenter'],
                ],
                'next_follow_up_at' => now()->addDays(2),
            ]
        );

        $this->assertEquals($this->statusQuotation->id, $result['lead']->lead_status_id);
        $this->assertEquals(1, $result['lead']->documents()->count());
        $this->assertTrue((bool) $result['lead']->quotation_sent);
    }
}
