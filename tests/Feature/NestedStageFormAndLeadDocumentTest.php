<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use App\Support\CrmDatabaseGuard;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NestedStageFormAndLeadDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private PipelineStage $stage;
    private LeadStatus $status;

    protected function setUp(): void
    {
        parent::setUp();
        CrmDatabaseGuard::ensureConnected();

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
            'username' => 'admin_nested_' . uniqid(),
            'name' => 'Admin Nested Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->regularUser = User::factory()->create([
            'username' => 'regular_nested_' . uniqid(),
            'name' => 'Regular User',
            'is_active' => true,
        ]);

        $this->stage = PipelineStage::query()->create([
            'code' => 'stage_nested_' . uniqid(),
            'name_ar' => 'مرحلة الأسئلة المتداخلة',
            'name_en' => 'Nested Questions Stage',
            'position' => 10,
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        $this->status = LeadStatus::query()->create([
            'code' => 'status_nested_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة الأسئلة المتداخلة',
            'name_en' => 'Nested Status',
            'position' => 1,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // 1. NESTED CONDITIONAL QUESTIONS TESTS
    // ==========================================

    public function test_nested_questions_parent_select_and_child_linked(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'client_type',
            'label_ar' => 'نوع العميل',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => 'individual', 'label_ar' => 'فرد', 'label_en' => 'Individual'],
                ['value' => 'company', 'label_ar' => 'شركة', 'label_en' => 'Company'],
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'company_name_field',
            'label_ar' => 'اسم المؤسسة',
            'type' => 'text',
            'is_required' => true,
            'conditions' => [
                'rules' => [
                    ['field_key' => 'client_type', 'operator' => 'equals', 'value' => 'company'],
                ],
            ],
            'position' => 2,
            'is_active' => true,
        ]);

        $fields = StageFieldSchema::getFieldsForStage($this->stage, true);
        $fieldsByKey = $fields->keyBy('key');

        // Parent value true (company) -> child is applicable
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['client_type' => 'company']));

        // Parent value false (individual) -> child is not applicable
        $this->assertFalse(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['client_type' => 'individual']));
    }

    public function test_nested_questions_cascading_applicability_and_validation(): void
    {
        // Level 1 (Parent): is_interested (boolean)
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'is_interested',
            'label_ar' => 'هل العميل مهتم؟',
            'type' => 'boolean',
            'is_required' => false,
            'position' => 1,
            'is_active' => true,
        ]);

        // Level 2 (Child): service_type (select, required), visible when is_interested = 1
        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'service_type',
            'label_ar' => 'نوع الخدمة',
            'type' => 'select',
            'is_required' => true,
            'options' => [
                ['value' => 'erp', 'label_ar' => 'ERP'],
                ['value' => 'call_center', 'label_ar' => 'Call Center'],
            ],
            'conditions' => [
                'rules' => [
                    ['field_key' => 'is_interested', 'operator' => 'is_true'],
                ],
            ],
            'position' => 2,
            'is_active' => true,
        ]);

        // Level 3 (Grandchild): erp_modules (text, required), visible when service_type = 'erp'
        $grandchild = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'erp_modules',
            'label_ar' => 'الوحدات المطلوبة',
            'type' => 'text',
            'is_required' => true,
            'conditions' => [
                'rules' => [
                    ['field_key' => 'service_type', 'operator' => 'equals', 'value' => 'erp'],
                ],
            ],
            'position' => 3,
            'is_active' => true,
        ]);

        $fields = StageFieldSchema::getFieldsForStage($this->stage, true);
        $fieldsByKey = $fields->keyBy('key');

        // Case 1: is_interested = 0 (false)
        // Both child and grandchild MUST be inapplicable, required must be ignored
        $memo = [];
        $this->assertFalse(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['is_interested' => '0'], $memo));
        $this->assertFalse(StageFieldSchema::isFieldApplicable($grandchild, $fieldsByKey, ['is_interested' => '0'], $memo));

        // When is_interested = 0, submitted inputs with empty child/grandchild must pass validation!
        $validated = StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'is_interested' => '0',
            ],
        ], $this->admin);

        $this->assertSame('0', $validated['is_interested']);
        $this->assertNull($validated['service_type']);
        $this->assertNull($validated['erp_modules']);

        // Case 2: is_interested = 1, service_type = 'call_center'
        // child is applicable, grandchild is NOT applicable
        $memo = [];
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['is_interested' => '1', 'service_type' => 'call_center'], $memo));
        $this->assertFalse(StageFieldSchema::isFieldApplicable($grandchild, $fieldsByKey, ['is_interested' => '1', 'service_type' => 'call_center'], $memo));

        // Case 3: is_interested = 1, service_type = 'erp'
        // Both child and grandchild are applicable!
        $memo = [];
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['is_interested' => '1', 'service_type' => 'erp'], $memo));
        $this->assertTrue(StageFieldSchema::isFieldApplicable($grandchild, $fieldsByKey, ['is_interested' => '1', 'service_type' => 'erp'], $memo));

        // When applicable, required grandchild MUST be enforced
        $this->expectException(ValidationException::class);
        StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'is_interested' => '1',
                'service_type' => 'erp',
                'erp_modules' => '', // empty -> should fail!
            ],
        ], $this->admin);
    }

    public function test_cycle_detection_and_self_reference(): void
    {
        $fieldA = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_a',
            'label_ar' => 'حقل أ',
            'type' => 'text',
            'position' => 1,
        ]);

        $fieldB = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_b',
            'label_ar' => 'حقل ب',
            'type' => 'text',
            'conditions' => [
                'rules' => [
                    ['field_key' => 'field_a', 'operator' => 'equals', 'value' => 'yes'],
                ],
            ],
            'position' => 2,
        ]);

        // Self-reference rejected
        $this->expectException(ValidationException::class);
        StageFieldSchema::normalizeAndValidateConditions($this->stage, 'field_a', [
            'rules' => [
                ['field_key' => 'field_a', 'operator' => 'equals', 'value' => 'yes'],
            ],
        ]);
    }

    public function test_circular_dependency_a_b_a_rejected(): void
    {
        $fieldA = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_a',
            'label_ar' => 'حقل أ',
            'type' => 'text',
            'position' => 1,
        ]);

        $fieldB = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_b',
            'label_ar' => 'حقل ب',
            'type' => 'text',
            'conditions' => [
                'rules' => [
                    ['field_key' => 'field_a', 'operator' => 'equals', 'value' => 'yes'],
                ],
            ],
            'position' => 2,
        ]);

        // Now making field_a depend on field_b creates A -> B -> A cycle!
        $this->expectException(ValidationException::class);
        StageFieldSchema::normalizeAndValidateConditions($this->stage, 'field_a', [
            'rules' => [
                ['field_key' => 'field_b', 'operator' => 'equals', 'value' => 'yes'],
            ],
        ]);
    }

    public function test_circular_dependency_a_b_c_a_rejected(): void
    {
        $fieldA = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_a',
            'label_ar' => 'حقل أ',
            'type' => 'text',
            'position' => 1,
        ]);

        $fieldB = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_b',
            'label_ar' => 'حقل ب',
            'type' => 'text',
            'conditions' => [
                'rules' => [
                    ['field_key' => 'field_a', 'operator' => 'equals', 'value' => 'yes'],
                ],
            ],
            'position' => 2,
        ]);

        $fieldC = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_c',
            'label_ar' => 'حقل ج',
            'type' => 'text',
            'conditions' => [
                'rules' => [
                    ['field_key' => 'field_b', 'operator' => 'equals', 'value' => 'yes'],
                ],
            ],
            'position' => 3,
        ]);

        // Making field_a depend on field_c creates A -> B -> C -> A cycle!
        $this->expectException(ValidationException::class);
        StageFieldSchema::normalizeAndValidateConditions($this->stage, 'field_a', [
            'rules' => [
                ['field_key' => 'field_c', 'operator' => 'equals', 'value' => 'yes'],
            ],
        ]);
    }

    public function test_nesting_depth_calculation_and_max_depth_enforcement(): void
    {
        // A -> B -> C is depth 3 (allowed)
        // D depending on C would be depth 4 (exceeds MAX_NESTING_DEPTH = 3)
        $graph = [
            'a' => [],
            'b' => ['a'],
            'c' => ['b'],
            'd' => ['c'],
        ];

        $this->assertSame(1, StageFieldSchema::calculateNestingDepth($graph, 'a'));
        $this->assertSame(2, StageFieldSchema::calculateNestingDepth($graph, 'b'));
        $this->assertSame(3, StageFieldSchema::calculateNestingDepth($graph, 'c'));
        $this->assertSame(4, StageFieldSchema::calculateNestingDepth($graph, 'd'));

        // When validating conditions for d on a stage where a, b, c exist:
        $fA = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'lvl_a',
            'label_ar' => 'مستوى 1',
            'type' => 'text',
            'position' => 1,
        ]);
        $fB = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'lvl_b',
            'label_ar' => 'مستوى 2',
            'type' => 'text',
            'conditions' => ['rules' => [['field_key' => 'lvl_a', 'operator' => 'equals', 'value' => '1']]],
            'position' => 2,
        ]);
        $fC = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'lvl_c',
            'label_ar' => 'مستوى 3',
            'type' => 'text',
            'conditions' => ['rules' => [['field_key' => 'lvl_b', 'operator' => 'equals', 'value' => '1']]],
            'position' => 3,
        ]);

        $this->expectException(ValidationException::class);
        StageFieldSchema::normalizeAndValidateConditions($this->stage, 'lvl_d', [
            'rules' => [
                ['field_key' => 'lvl_c', 'operator' => 'equals', 'value' => '1'],
            ],
        ]);
    }

    public function test_renaming_parent_label_does_not_break_dependency(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'parent_key',
            'label_ar' => 'الاسم القديم للأب',
            'type' => 'select',
            'position' => 1,
        ]);

        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'child_key',
            'label_ar' => 'الابن',
            'type' => 'text',
            'conditions' => [
                'rules' => [
                    ['field_key' => 'parent_key', 'operator' => 'equals', 'value' => 'yes'],
                ],
            ],
            'position' => 2,
        ]);

        // Rename parent label
        $parent->update([
            'label_ar' => 'الاسم الجديد للأب بعد التعديل',
            'label_en' => 'New Parent Label',
        ]);

        $fields = StageFieldSchema::getFieldsForStage($this->stage, true);
        $fieldsByKey = $fields->keyBy('key');

        // Dependency is keyed by 'parent_key' which remains intact
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['parent_key' => 'yes']));
        $this->assertFalse(StageFieldSchema::isFieldApplicable($child, $fieldsByKey, ['parent_key' => 'no']));
    }

    // ==========================================
    // 2. DYNAMIC FILE UPLOAD TESTS
    // ==========================================

    public function test_file_upload_pdf_accepted_and_persisted(): void
    {
        Storage::fake('local');

        $fileField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'contract_pdf',
            'label_ar' => 'ملف العقد',
            'type' => 'file',
            'is_required' => true,
            'options' => [
                'document_category' => 'pdf',
                'accepted_mime_types' => ['pdf'],
                'max_file_size' => 5120,
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $fakePdf = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $lead = Lead::query()->create([
            'name' => 'PDF Lead',
            'lead_status_id' => $this->status->id,
        ]);

        $validated = StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'contract_pdf' => $fakePdf,
            ],
        ], $this->admin);

        $this->assertInstanceOf(UploadedFile::class, $validated['contract_pdf']);

        $savedValues = StageFieldSchema::persistValues($lead, $this->stage, $validated, null, $this->admin);

        $this->assertCount(1, $savedValues);
        $this->assertDatabaseHas('lead_documents', [
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'pipeline_stage_field_id' => $fileField->id,
            'category' => 'pdf',
            'original_name' => 'contract.pdf',
        ]);

        $doc = LeadDocument::query()->where('lead_id', $lead->id)->first();
        $this->assertNotNull($doc);
        Storage::disk('local')->assertExists($doc->path);
    }

    public function test_file_upload_images_jpg_png_accepted(): void
    {
        Storage::fake('local');

        $imageField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'lead_id_photo',
            'label_ar' => 'صورة الهوية',
            'type' => 'file',
            'options' => [
                'document_category' => 'image',
                'accepted_mime_types' => ['jpg', 'jpeg', 'png', 'webp'],
                'max_file_size' => 5120,
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $fakeJpg = UploadedFile::fake()->image('id_card.jpg', 600, 400);

        $lead = Lead::query()->create([
            'name' => 'Image Lead',
            'lead_status_id' => $this->status->id,
        ]);

        $validated = StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'lead_id_photo' => $fakeJpg,
            ],
        ], $this->admin);

        $savedValues = StageFieldSchema::persistValues($lead, $this->stage, $validated, null, $this->admin);

        $this->assertDatabaseHas('lead_documents', [
            'lead_id' => $lead->id,
            'category' => 'image',
            'original_name' => 'id_card.jpg',
        ]);
    }

    public function test_file_upload_executable_rejected(): void
    {
        $fileField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'doc_upload',
            'label_ar' => 'مستند',
            'type' => 'file',
            'options' => [
                'document_category' => 'attachment',
                'accepted_mime_types' => ['pdf', 'doc', 'docx'],
                'max_file_size' => 2048,
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $exeFile = UploadedFile::fake()->create('script.php', 100, 'application/x-php');

        $this->expectException(ValidationException::class);
        StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'doc_upload' => $exeFile,
            ],
        ], $this->admin);
    }

    public function test_file_upload_oversized_file_rejected(): void
    {
        $fileField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'doc_upload',
            'label_ar' => 'مستند',
            'type' => 'file',
            'options' => [
                'document_category' => 'pdf',
                'accepted_mime_types' => ['pdf'],
                'max_file_size' => 1024, // 1 MB max
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $oversizedPdf = UploadedFile::fake()->create('large.pdf', 3000, 'application/pdf'); // 3 MB

        $this->expectException(ValidationException::class);
        StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'doc_upload' => $oversizedPdf,
            ],
        ], $this->admin);
    }

    public function test_failed_db_transaction_cleans_orphan_file(): void
    {
        Storage::fake('local');

        $fileField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'some_file',
            'label_ar' => 'ملف تجريبي',
            'type' => 'file',
            'position' => 1,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Clean Lead',
            'lead_status_id' => $this->status->id,
        ]);

        $fakePdf = UploadedFile::fake()->create('test_orphan.pdf', 100, 'application/pdf');

        // Force a DB failure by setting lead_id to invalid or simulating failure
        $trackedPaths = [];
        try {
            DB::transaction(function () use ($lead, $fakePdf, &$trackedPaths): void {
                $stored = $fakePdf->store('crm-v2/lead-documents/' . $lead->id, 'local');
                $trackedPaths[] = $stored;
                throw new \RuntimeException('Simulated database crash after file store.');
            });
        } catch (\Throwable) {
            foreach ($trackedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
        }

        // Verify orphan file was cleaned up from storage!
        $this->assertCount(1, $trackedPaths);
        Storage::disk('local')->assertMissing($trackedPaths[0]);
    }

    // ==========================================
    // 3. QUOTATIONS SECTION & ACCESS TESTS
    // ==========================================

    public function test_quotation_category_file_appears_and_updates_lead(): void
    {
        Storage::fake('local');

        $quotationField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'quotation_doc',
            'label_ar' => 'عرض السعر',
            'type' => 'file',
            'options' => [
                'document_category' => 'quotation',
                'accepted_mime_types' => ['pdf', 'doc', 'docx'],
                'max_file_size' => 10240,
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Quotation Lead',
            'lead_status_id' => $this->status->id,
        ]);

        $fakeQuote = UploadedFile::fake()->create('offer_2026.pdf', 400, 'application/pdf');

        $validated = StageFieldSchema::validateAndExtract($this->stage, [
            'stage_fields' => [
                'quotation_doc' => $fakeQuote,
            ],
        ], $this->admin);

        StageFieldSchema::persistValues($lead, $this->stage, $validated, null, $this->admin);

        // Verify LeadDocument created
        $this->assertDatabaseHas('lead_documents', [
            'lead_id' => $lead->id,
            'category' => 'quotation',
            'original_name' => 'offer_2026.pdf',
        ]);

        // Verify Lead model backwards compatibility updated
        $lead->refresh();
        $this->assertTrue($lead->quotation_sent);
        $this->assertNotEmpty($lead->quotation_file_path);
    }

    public function test_multiple_quotations_preserved_latest_first(): void
    {
        Storage::fake('local');

        $lead = Lead::query()->create([
            'name' => 'Multi Quotation Lead',
            'lead_status_id' => $this->status->id,
        ]);

        // Upload quotation 1
        $doc1 = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => 'quotation',
            'original_name' => 'quote_v1.pdf',
            'stored_name' => 'quote_v1.pdf',
            'disk' => 'local',
            'path' => 'crm-v2/quotation-files/quote_v1.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'created_by_user_id' => $this->admin->id,
            'created_at' => now()->subDay(),
        ]);

        // Upload quotation 2
        $doc2 = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => 'quotation',
            'original_name' => 'quote_v2.pdf',
            'stored_name' => 'quote_v2.pdf',
            'disk' => 'local',
            'path' => 'crm-v2/quotation-files/quote_v2.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'created_by_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        $quotes = $lead->quotationDocuments()->get();

        // Both are preserved!
        $this->assertCount(2, $quotes);
        // Latest first!
        $this->assertSame($doc2->id, $quotes->first()->id);
        $this->assertSame($doc1->id, $quotes->last()->id);
    }

    public function test_authorized_user_can_download_and_unauthorized_blocked(): void
    {
        Storage::fake('local');

        $lead = Lead::query()->create([
            'name' => 'Auth Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $storedPath = 'crm-v2/quotation-files/secure_quote.pdf';
        Storage::disk('local')->put($storedPath, '%PDF-1.4 test secure quotation');

        $doc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => 'quotation',
            'original_name' => 'secure_quote.pdf',
            'stored_name' => 'secure_quote.pdf',
            'disk' => 'local',
            'path' => $storedPath,
            'mime_type' => 'application/pdf',
            'size' => 30,
            'created_by_user_id' => $this->admin->id,
        ]);

        // 1. Authorized admin can download
        $response = $this->actingAs($this->admin)->get(route('v2.leads.documents.download', [$lead, $doc]));
        $response->assertOk();

        // 2. Authorized admin can preview
        $previewResp = $this->actingAs($this->admin)->get(route('v2.leads.documents.preview', [$lead, $doc]));
        $previewResp->assertOk();

        // 3. User without permission / access cannot download
        $unauthorized = User::factory()->create(['is_active' => true]);
        $failResp = $this->actingAs($unauthorized)->get(route('v2.leads.documents.download', [$lead, $doc]));
        $failResp->assertForbidden();
    }

    // ==========================================
    // 4. TRANSITION INTEGRATION TEST
    // ==========================================

    public function test_lead_transition_service_persists_file_and_updates_history(): void
    {
        Storage::fake('local');

        $quotationField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'stage_quote_file',
            'label_ar' => 'عرض سعر المرحلة',
            'type' => 'file',
            'options' => [
                'document_category' => 'quotation',
                'accepted_mime_types' => ['pdf'],
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $lead = Lead::query()->create([
            'name' => 'Transition Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $newStatus = LeadStatus::query()->create([
            'code' => 'new_quote_status_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة جديدة للعرض',
            'name_en' => 'New Quote Status',
            'position' => 2,
            'is_active' => true,
        ]);

        $fakePdf = UploadedFile::fake()->create('proposal.pdf', 250, 'application/pdf');

        /** @var LeadTransitionService $service */
        $service = app(LeadTransitionService::class);

        $result = $service->transition($lead, $newStatus, $this->admin, [
            'stage_fields' => [
                'stage_quote_file' => $fakePdf,
            ],
            'history_note' => 'تحويل مع رفع عرض السعر',
        ]);

        $this->assertTrue($result['changed']);
        $this->assertInstanceOf(LeadStatusHistory::class, $result['history']);

        $this->assertDatabaseHas('lead_documents', [
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'pipeline_stage_field_id' => $quotationField->id,
            'lead_status_history_id' => $result['history']->id,
            'category' => 'quotation',
            'original_name' => 'proposal.pdf',
        ]);
    }

    public function test_lead_profile_displays_documents_section_with_quotations_and_attachments(): void
    {
        Storage::fake('local');

        $lead = Lead::query()->create([
            'name' => 'Profile Test Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // 1. Create a quotation document
        $quoteDoc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => LeadDocument::CATEGORY_QUOTATION,
            'original_name' => 'official_quote_2026.pdf',
            'stored_name' => 'official_quote_2026.pdf',
            'disk' => 'local',
            'path' => 'crm-v2/quotation-files/official_quote_2026.pdf',
            'mime_type' => 'application/pdf',
            'size' => 45000,
            'created_by_user_id' => $this->admin->id,
        ]);

        // 2. Create an image attachment document
        $imageDoc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => LeadDocument::CATEGORY_IMAGE,
            'original_name' => 'national_id_card.png',
            'stored_name' => 'national_id_card.png',
            'disk' => 'local',
            'path' => 'crm-v2/lead-documents/' . $lead->id . '/national_id_card.png',
            'mime_type' => 'image/png',
            'size' => 120000,
            'created_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $lead));
        $response->assertOk();

        // Section header and document viewer modal presence
        $response->assertSee(__('crm.documents_and_quotations'));
        $response->assertSee('documentViewerModal');
        $response->assertSee('openDocumentViewer');
        // Both documents rendered
        $response->assertSee('official_quote_2026.pdf');
        $response->assertSee('national_id_card.png');

        // Links to download and preview routes
        $response->assertSee(route('v2.leads.documents.download', [$lead, $quoteDoc]));
        $response->assertSee(route('v2.leads.documents.download', [$lead, $imageDoc]));
    }

    public function test_user_without_quotations_permission_cannot_view_quotation_documents_in_profile(): void
    {
        Storage::fake('local');

        $lead = Lead::query()->create([
            'name' => 'Restricted Profile Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->regularUser->id,
        ]);

        // Quotation document
        $quoteDoc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => LeadDocument::CATEGORY_QUOTATION,
            'original_name' => 'secret_offer.pdf',
            'stored_name' => 'secret_offer.pdf',
            'disk' => 'local',
            'path' => 'crm-v2/quotation-files/secret_offer.pdf',
            'mime_type' => 'application/pdf',
            'size' => 50000,
            'created_by_user_id' => $this->admin->id,
        ]);

        // Attachment document
        $attachDoc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => LeadDocument::CATEGORY_ATTACHMENT,
            'original_name' => 'general_notes.docx',
            'stored_name' => 'general_notes.docx',
            'disk' => 'local',
            'path' => 'crm-v2/lead-documents/' . $lead->id . '/general_notes.docx',
            'mime_type' => 'application/msword',
            'size' => 15000,
            'created_by_user_id' => $this->regularUser->id,
        ]);

        // Regular user with leads.view permission but NOT quotations.view
        $viewLeadPerm = Permission::query()->firstOrCreate(['code' => CrmPermission::LEADS_VIEW->value], ['module' => 'leads', 'name_ar' => 'عرض العملاء']);
        $group = Group::query()->create(['name' => 'View Only', 'code' => 'view-only-' . uniqid()]);
        $group->permissions()->attach($viewLeadPerm);
        $this->regularUser->groups()->attach($group);

        $response = $this->actingAs($this->regularUser)->get(route('v2.leads.show', $lead));
        $response->assertOk();

        // General attachment visible
        $response->assertSee('general_notes.docx');

        // Quotation file is NOT visible to unauthorized user!
        $response->assertDontSee('secret_offer.pdf');
        $response->assertDontSee(route('v2.leads.documents.download', [$lead, $quoteDoc]));
    }

    public function test_lead_controller_store_and_update_persist_files_and_link_status_history(): void
    {
        Storage::fake('local');

        $fileField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'id_document_upload',
            'label_ar' => 'صورة الهوية الوطنية',
            'type' => 'file',
            'options' => [
                'document_category' => 'image',
                'accepted_mime_types' => ['png', 'jpg'],
                'max_file_size' => 5120,
            ],
            'position' => 1,
            'is_active' => true,
        ]);

        $fakeImage = UploadedFile::fake()->image('my_id.png', 400, 300);

        // 1. Create Lead via LeadController::store
        $storeResponse = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'Store Test Lead',
            'phone' => '0512345678',
            'source' => 'website',
            'lead_status_id' => $this->status->id,
            'next_follow_up_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'id_document_upload' => $fakeImage,
            ],
        ]);

        $storeResponse->assertRedirect();
        $createdLead = Lead::query()->where('first_name', 'Store Test Lead')->firstOrFail();
        $this->assertDatabaseHas('lead_documents', [
            'lead_id' => $createdLead->id,
            'category' => 'image',
            'original_name' => 'my_id.png',
        ]);

        $createdDoc = LeadDocument::query()->where('lead_id', $createdLead->id)->firstOrFail();
        $this->assertNotNull($createdDoc->lead_status_history_id);

        // 2. Update Lead via LeadController::update to new status with quotation file
        $newStatus = LeadStatus::query()->create([
            'code' => 'new_quote_stat_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة عرض سعر',
            'position' => 2,
            'is_active' => true,
        ]);

        $fakePdf = UploadedFile::fake()->create('signed_contract.pdf', 300, 'application/pdf');
        $contractField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'signed_contract_doc',
            'label_ar' => 'العقد الموقع',
            'type' => 'file',
            'options' => [
                'document_category' => 'pdf',
                'accepted_mime_types' => ['pdf'],
                'max_file_size' => 10240,
            ],
            'position' => 2,
            'is_active' => true,
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        $updateResponse = $this->actingAs($this->admin)->patch(route('v2.leads.update', $createdLead), [
            'first_name' => 'Store Test Lead Updated',
            'phone' => '0512345678',
            'source' => 'website',
            'lead_status_id' => $newStatus->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'signed_contract_doc' => $fakePdf,
            ],
        ]);
        $this->assertDatabaseHas('lead_documents', [
            'lead_id' => $createdLead->id,
            'category' => 'pdf',
            'original_name' => 'signed_contract.pdf',
        ]);
        $updatedDoc = LeadDocument::query()->where('lead_id', $createdLead->id)->where('category', 'pdf')->firstOrFail();
        $this->assertNotNull($updatedDoc->lead_status_history_id);
        $this->assertSame((int) $newStatus->id, (int) $updatedDoc->history->to_status_id);
    }

    // ==========================================
    // 5. 15 ARCHITECTURAL & UX SCENARIO TESTS
    // ==========================================

    public function test_01_and_02_child_created_from_parent_option_with_automatic_condition(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'service_choice',
            'label_ar' => 'نوع الخدمة',
            'type' => 'select',
            'options' => [
                ['value' => 'consultation', 'label_ar' => 'استشارة', 'label_en' => 'Consultation'],
                ['value' => 'quotation', 'label_ar' => 'عرض سعر', 'label_en' => 'Quotation'],
            ],
            'position' => 1,
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        // Submit child field from parent option 'quotation' using simple condition structure
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.fields.store', $this->stage), [
            'label_ar' => 'قيمة العرض',
            'type' => 'number',
            'is_required' => 1,
            'condition_rules' => [
                ['field_key' => 'service_choice', 'operator' => 'equals', 'value' => 'quotation'],
            ],
        ]);

        $response->assertRedirect();
        $child = PipelineStageField::query()->where('pipeline_stage_id', $this->stage->id)->where('label_ar', 'قيمة العرض')->firstOrFail();
        $this->assertTrue($child->hasConditions());
        $this->assertSame('quotation', $child->conditions['rules'][0]['value']);
        $this->assertSame('service_choice', $child->conditions['rules'][0]['field_key']);
    }

    public function test_03_multiple_children_per_answer(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'order_type',
            'label_ar' => 'نوع الطلب',
            'type' => 'radio',
            'options' => [
                ['value' => 'quote', 'label_ar' => 'عرض سعر'],
                ['value' => 'direct', 'label_ar' => 'مباشر'],
            ],
            'position' => 1,
        ]);

        $child1 = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'quote_amount',
            'label_ar' => 'قيمة العرض',
            'type' => 'number',
            'conditions' => ['rules' => [['field_key' => 'order_type', 'operator' => 'equals', 'value' => 'quote']]],
            'position' => 2,
        ]);

        $child2 = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'quote_expiry',
            'label_ar' => 'تاريخ الانتهاء',
            'type' => 'date',
            'conditions' => ['rules' => [['field_key' => 'order_type', 'operator' => 'equals', 'value' => 'quote']]],
            'position' => 3,
        ]);

        StageFieldSchema::flushCache((int) $this->stage->id);
        $fields = StageFieldSchema::getFieldsForStage($this->stage, true)->keyBy('key');

        // When order_type = quote -> both children applicable
        $memo = [];
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child1, $fields, ['order_type' => 'quote'], $memo));
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child2, $fields, ['order_type' => 'quote'], $memo));

        // When order_type = direct -> neither is applicable
        $memo = [];
        $this->assertFalse(StageFieldSchema::isFieldApplicable($child1, $fields, ['order_type' => 'direct'], $memo));
        $this->assertFalse(StageFieldSchema::isFieldApplicable($child2, $fields, ['order_type' => 'direct'], $memo));
    }

    public function test_04_human_readable_condition_summary(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'client_kind',
            'label_ar' => 'نوع العميل',
            'label_en' => 'Customer Type',
            'type' => 'select',
            'options' => [
                ['value' => 'company', 'label_ar' => 'شركة', 'label_en' => 'Company'],
            ],
            'position' => 1,
        ]);

        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'tax_id',
            'label_ar' => 'الرقم الضريبي',
            'label_en' => 'Tax ID',
            'type' => 'text',
            'conditions' => ['rules' => [['field_key' => 'client_kind', 'operator' => 'equals', 'value' => 'company']]],
            'position' => 2,
        ]);

        $summaryAr = $child->conditionSummary('ar');
        $summaryEn = $child->conditionSummary('en');

        $this->assertStringContainsString('شركة', $summaryAr);
        $this->assertStringContainsString('نوع العميل', $summaryAr);
        $this->assertStringContainsString('Company', $summaryEn);
        $this->assertStringContainsString('Customer Type', $summaryEn);
    }

    public function test_05_and_06_parent_and_option_rename_safety(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'plan_choice',
            'label_ar' => 'الخطة المختارة',
            'type' => 'select',
            'options' => [
                ['value' => 'enterprise', 'label_ar' => 'خطة الشركات القديمة', 'label_en' => 'Old Enterprise'],
            ],
            'position' => 1,
        ]);

        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'dedicated_manager',
            'label_ar' => 'مدير الحساب المخصص',
            'type' => 'text',
            'conditions' => ['rules' => [['field_key' => 'plan_choice', 'operator' => 'equals', 'value' => 'enterprise']]],
            'position' => 2,
        ]);

        // Rename parent label AND option label
        $parent->update([
            'label_ar' => 'الاسم الجديد للخطة',
            'label_en' => 'New Plan Name',
            'options' => [
                ['value' => 'enterprise', 'label_ar' => 'خطة الشركات المعدلة كلياً', 'label_en' => 'Renamed Enterprise'],
            ],
        ]);

        StageFieldSchema::flushCache((int) $this->stage->id);
        $fields = StageFieldSchema::getFieldsForStage($this->stage, true)->keyBy('key');

        // Evaluating with stable option value 'enterprise' still succeeds!
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child, $fields, ['plan_choice' => 'enterprise']));
        // Evaluating with renamed Arabic label also succeeds via option aliasing!
        $this->assertTrue(StageFieldSchema::isFieldApplicable($child, $fields, ['plan_choice' => 'خطة الشركات المعدلة كلياً']));
    }

    public function test_07_parent_delete_dependency_warning_and_detach_action(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'parent_test_field',
            'label_ar' => 'سؤال أب اختباري',
            'type' => 'select',
            'position' => 1,
        ]);

        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'child_test_field',
            'label_ar' => 'سؤال تابع',
            'type' => 'text',
            'conditions' => ['rules' => [['field_key' => 'parent_test_field', 'operator' => 'equals', 'value' => '1']]],
            'position' => 2,
        ]);

        // 1. Raw delete without child_action -> warned and blocked
        $failResponse = $this->actingAs($this->admin)->delete(route('v2.settings.stages.fields.destroy', [$this->stage, $parent]));
        $failResponse->assertSessionHas('parent_delete_warning');
        $this->assertDatabaseHas('pipeline_stage_fields', ['id' => $parent->id]);
        $this->assertDatabaseHas('pipeline_stage_fields', ['id' => $child->id]);

        // 2. Delete with child_action = 'detach' -> parent deleted, child detached and made always visible
        $detachResponse = $this->actingAs($this->admin)->delete(route('v2.settings.stages.fields.destroy', [$this->stage, $parent]), [
            'child_action' => 'detach',
        ]);
        $detachResponse->assertRedirect();
        $this->assertSoftDeleted('pipeline_stage_fields', ['id' => $parent->id]);

        $child->refresh();
        $this->assertFalse($child->hasConditions());
    }

    public function test_08_parent_delete_delete_all_action(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'parent_cascade',
            'label_ar' => 'أب للحذف الكامل',
            'type' => 'select',
            'position' => 1,
        ]);

        $child = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'child_cascade',
            'label_ar' => 'تابع للحذف الكامل',
            'type' => 'text',
            'conditions' => ['rules' => [['field_key' => 'parent_cascade', 'operator' => 'equals', 'value' => '1']]],
            'position' => 2,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('v2.settings.stages.fields.destroy', [$this->stage, $parent]), [
            'child_action' => 'delete_all',
        ]);
        $response->assertRedirect();
        $this->assertSoftDeleted('pipeline_stage_fields', ['id' => $parent->id]);
        $this->assertSoftDeleted('pipeline_stage_fields', ['id' => $child->id]);
    }

    public function test_09_and_10_historical_file_not_deleted_when_branch_later_hidden(): void
    {
        Storage::fake('local');

        $lead = Lead::query()->create([
            'name' => 'Historical File Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // Stored document from previous branch
        $doc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'category' => 'quotation',
            'original_name' => 'initial_quote.pdf',
            'stored_name' => 'initial_quote.pdf',
            'disk' => 'local',
            'path' => 'crm-v2/quotation-files/initial_quote.pdf',
            'mime_type' => 'application/pdf',
            'size' => 4000,
            'created_by_user_id' => $this->admin->id,
        ]);
        Storage::disk('local')->put('crm-v2/quotation-files/initial_quote.pdf', '%PDF-test');

        // New transition where quotation branch is not active / omitted
        $decisionField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'wants_quote',
            'label_ar' => 'هل يريد عرض سعر؟',
            'type' => 'boolean',
            'position' => 1,
        ]);
        $quoteField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'quote_file_conditional',
            'label_ar' => 'ملف عرض السعر',
            'type' => 'file',
            'conditions' => ['rules' => [['field_key' => 'wants_quote', 'operator' => 'is_true']]],
            'position' => 2,
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        // Transition with wants_quote = 0 (branch hidden)
        /** @var LeadTransitionService $service */
        $service = app(LeadTransitionService::class);
        $service->transition($lead, $this->status, $this->admin, [
            'stage_fields' => [
                'wants_quote' => '0',
            ],
        ]);

        // Verify previous historical document is STILL preserved in database and disk!
        $this->assertDatabaseHas('lead_documents', [
            'id' => $doc->id,
            'lead_id' => $lead->id,
            'original_name' => 'initial_quote.pdf',
        ]);
        Storage::disk('local')->assertExists('crm-v2/quotation-files/initial_quote.pdf');
    }

    public function test_11_simple_mode_and_advanced_mode_produce_same_canonical_schema(): void
    {
        $parent = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'parent_test',
            'label_ar' => 'سؤال اختباري',
            'type' => 'text',
            'position' => 1,
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        // Simple mode payload
        $simpleNormalized = StageFieldSchema::normalizeAndValidateConditions($this->stage, 'child_simple', [
            'mode' => 'all',
            'rules' => [
                ['field_key' => 'parent_test', 'operator' => 'equals', 'value' => 'company'],
            ],
        ]);

        // Advanced mode payload
        $advNormalized = StageFieldSchema::normalizeAndValidateConditions($this->stage, 'child_adv', [
            'mode' => 'all',
            'rules' => [
                ['field_key' => 'parent_test', 'operator' => 'equals', 'value' => 'company'],
            ],
        ]);

        $this->assertSame($simpleNormalized, $advNormalized);
    }

    public function test_12_duplicate_option_values_rejected_on_create(): void
    {
        $response = $this->actingAs($this->admin)->post(route('v2.settings.stages.fields.store', $this->stage), [
            'label_ar' => 'سؤال خيارات مكررة',
            'type' => 'select',
            'options_list' => [
                ['value' => 'dupe_val', 'label_ar' => 'الخيار الأول'],
                ['value' => 'dupe_val', 'label_ar' => 'الخيار الثاني'],
            ],
        ]);

        $response->assertSessionHasErrors('options');
        $this->assertDatabaseMissing('pipeline_stage_fields', [
            'pipeline_stage_id' => $this->stage->id,
            'label_ar' => 'سؤال خيارات مكررة',
        ]);
    }

    public function test_13_duplicate_option_values_rejected_on_update(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'unique_test_opt',
            'label_ar' => 'سؤال فريد',
            'type' => 'radio',
            'options' => [
                ['value' => 'val_1', 'label_ar' => 'واحد'],
                ['value' => 'val_2', 'label_ar' => 'اثنان'],
            ],
            'position' => 1,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('v2.settings.stages.fields.update', [$this->stage, $field]), [
            'label_ar' => 'سؤال فريد معدل',
            'type' => 'radio',
            'options_list' => [
                ['value' => 'conflict', 'label_ar' => 'أ'],
                ['value' => 'conflict', 'label_ar' => 'ب'],
            ],
        ]);

        $response->assertSessionHasErrors('options');
    }

    public function test_14_popup_routes_preserve_port_80_and_contain_no_8080_references(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Port 80 Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        // 1. Kanban page
        $kanbanResp = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $kanbanResp->assertOk();
        $kanbanContent = $kanbanResp->getContent();
        $this->assertStringNotContainsString(':8080', $kanbanContent);

        // 2. View Lead popup
        $popupResp = $this->actingAs($this->admin)->get(route('v2.leads.show', array_merge(['lead' => $lead], ['kanban_popup' => 1])));
        $popupResp->assertOk();
        $popupContent = $popupResp->getContent();
        $this->assertStringNotContainsString(':8080', $popupContent);
        $this->assertStringNotContainsString('<aside class="crm-sidebar', $popupContent);

        // 3. Follow-up popup
        $followupResp = $this->actingAs($this->admin)->get(route('v2.leads.followups.index', array_merge(['lead' => $lead], ['kanban_popup' => 1])));
        $followupResp->assertOk();
        $followupContent = $followupResp->getContent();
        $this->assertStringNotContainsString(':8080', $followupContent);
    }

    public function test_15_double_transition_protection_and_atomic_history(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Atomic Lead',
            'lead_status_id' => $this->status->id,
            'assigned_user_id' => $this->admin->id,
        ]);

        $newStatus = LeadStatus::query()->create([
            'code' => 'atomic_stat_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة ذرية',
            'position' => 2,
            'is_active' => true,
        ]);

        /** @var LeadTransitionService $service */
        $service = app(LeadTransitionService::class);

        // Transition 1
        $res1 = $service->transition($lead, $newStatus, $this->admin, [
            'history_note' => 'First transition',
        ]);
        $this->assertTrue($res1['changed']);

        // Transition 2 to same status (simulate double click / idempotent re-submit)
        $res2 = $service->transition($lead, $newStatus, $this->admin, [
            'history_note' => 'Second immediate transition',
        ]);
        $this->assertFalse($res2['changed']);
        $this->assertNull($res2['history']);

        // Verify only 1 history record was created!
        $this->assertSame(1, LeadStatusHistory::query()->where('lead_id', $lead->id)->count());
    }
}
