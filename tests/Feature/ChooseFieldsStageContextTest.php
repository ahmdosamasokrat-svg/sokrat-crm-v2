<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChooseFieldsStageContextTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private PipelineStage $stageNew;
    private PipelineStage $stageNoAnswer;
    private PipelineStage $stageInterested;
    private LeadStatus $statusNew;
    private LeadStatus $statusNoAnswer;
    private LeadStatus $statusInterested;
    private PipelineStageField $fieldNewName;
    private PipelineStageField $fieldNewPhone;
    private PipelineStageField $fieldNoAnswerCallback;
    private PipelineStageField $fieldNoAnswerNotes;
    private PipelineStageField $fieldInterestedCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();

        $this->stageNew = PipelineStage::firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'name_en' => 'New', 'is_active' => true, 'position' => 1]
        );

        $this->stageNoAnswer = PipelineStage::firstOrCreate(
            ['code' => 'no_answer'],
            ['name_ar' => 'لم يرد', 'name_en' => 'No Answer', 'is_active' => true, 'position' => 2]
        );

        $this->stageInterested = PipelineStage::firstOrCreate(
            ['code' => 'interested'],
            ['name_ar' => 'مهتم', 'name_en' => 'Interested', 'is_active' => true, 'position' => 3]
        );

        $this->statusNew = LeadStatus::firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $this->stageNew->id, 'name_ar' => 'جديد', 'position' => 1, 'is_active' => true]
        );

        $this->statusNoAnswer = LeadStatus::firstOrCreate(
            ['code' => 'no_answer'],
            ['pipeline_stage_id' => $this->stageNoAnswer->id, 'name_ar' => 'لم يرد', 'position' => 2, 'is_active' => true]
        );

        $this->statusInterested = LeadStatus::firstOrCreate(
            ['code' => 'interested'],
            ['pipeline_stage_id' => $this->stageInterested->id, 'name_ar' => 'مهتم', 'position' => 3, 'is_active' => true]
        );

        $this->fieldNewName = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageNew->id, 'key' => 'name'],
            ['label_ar' => 'اسم العميل بالكامل', 'label_en' => 'Customer Full Name', 'type' => 'text', 'binding_type' => 'canonical', 'binding_target' => 'name', 'is_active' => true, 'position' => 1]
        );

        $this->fieldNewPhone = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageNew->id, 'key' => 'phone'],
            ['label_ar' => 'رقم الهاتف', 'label_en' => 'Phone Number', 'type' => 'tel', 'binding_type' => 'canonical', 'binding_target' => 'phone', 'is_active' => true, 'position' => 2]
        );

        $this->fieldNoAnswerCallback = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageNoAnswer->id, 'key' => 'callback_at'],
            ['label_ar' => 'موعد إعادة الاتصال', 'label_en' => 'Callback Date', 'type' => 'datetime', 'binding_type' => 'custom', 'is_active' => true, 'position' => 1]
        );

        $this->fieldNoAnswerNotes = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageNoAnswer->id, 'key' => 'attempt_notes'],
            ['label_ar' => 'ملاحظات المحاولة', 'label_en' => 'Attempt Notes', 'type' => 'textarea', 'binding_type' => 'custom', 'is_active' => true, 'position' => 2]
        );

        $this->fieldInterestedCompany = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageInterested->id, 'key' => 'company_name'],
            ['label_ar' => 'اسم الشركة / المؤسسة', 'label_en' => 'Company Name', 'type' => 'text', 'binding_type' => 'canonical', 'binding_target' => 'company_name', 'is_active' => true, 'position' => 1]
        );
    }

    /** 1. Translation keys resolve in Arabic */
    public function test_translation_keys_resolve_in_arabic(): void
    {
        app()->setLocale('ar');

        $this->assertEquals('الأعمدة الأساسية', __('crm.standard_columns'));
        $this->assertEquals('بيانات العميل الإضافية', __('crm.additional_customer_data'));
        $this->assertEquals('أسئلة المرحلة', __('crm.stage_fields'));
        $this->assertEquals('أسئلة مرحلة جديد', __('crm.stage_questions_for', ['stage' => 'جديد']));
        $this->assertEquals('الأعمدة الافتراضية', __('crm.restore_default_columns'));
    }

    /** 2. Translation keys resolve in English */
    public function test_translation_keys_resolve_in_english(): void
    {
        app()->setLocale('en');

        $this->assertEquals('Standard Columns', __('crm.standard_columns'));
        $this->assertEquals('Additional Customer Data', __('crm.additional_customer_data'));
        $this->assertEquals('Stage Questions', __('crm.stage_fields'));
        $this->assertEquals('New Questions', __('crm.stage_questions_for', ['stage' => 'New']));
        $this->assertEquals('Restore Default Columns', __('crm.restore_default_columns'));
    }

    /** 3. Raw crm.standard_columns absent in UI */
    public function test_raw_crm_standard_columns_absent(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads');

        $response->assertOk();
        $response->assertDontSee('crm.standard_columns', false);
        $response->assertSee('الأعمدة الأساسية');
    }

    /** 4. Raw crm.additional_customer_data absent in UI */
    public function test_raw_crm_additional_customer_data_absent(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads');

        $response->assertOk();
        $response->assertDontSee('crm.additional_customer_data', false);
    }

    /** 5. Raw crm.stage_fields absent in UI */
    public function test_raw_crm_stage_fields_absent(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads');

        $response->assertOk();
        $response->assertDontSee('crm.stage_fields', false);
        $response->assertSee('أسئلة المرحلة');
    }

    /** 6. Selected New status shows questions for stage New */
    public function test_selected_new_status_shows_questions_for_stage_new(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads?status=new');

        $response->assertOk();
        $response->assertSee('أسئلة مرحلة جديد');
        $response->assertDontSee('crm.stage_questions_for', false);
    }

    /** 7. Selected No Answer returns only No Answer fields */
    public function test_selected_no_answer_returns_no_answer_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads?status=no_answer');

        $response->assertOk();
        $response->assertSee('أسئلة مرحلة لم يرد');
        $response->assertSee('موعد إعادة الاتصال');
        $response->assertSee('ملاحظات المحاولة');
    }

    /** 8. Selected Interested returns only Interested fields */
    public function test_selected_interested_returns_interested_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads?status=interested');

        $response->assertOk();
        $response->assertSee('أسئلة مرحلة مهتم');
        $response->assertSee('اسم الشركة / المؤسسة');
    }

    /** 9. All Statuses groups fields by stage */
    public function test_all_statuses_groups_fields_by_stage(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ar'])
            ->get('/leads');

        $response->assertOk();
        $response->assertSee('أسئلة المرحلة');
        $response->assertSee('مرحلة جديد');
        $response->assertSee('مرحلة لم يرد');
        $response->assertSee('مرحلة مهتم');
    }

    /** 10. Inactive stage fields excluded from popover */
    public function test_inactive_stage_fields_excluded(): void
    {
        $inactiveField = PipelineStageField::create([
            'pipeline_stage_id' => $this->stageNew->id,
            'key' => 'inactive_secret_field',
            'label_ar' => 'سؤال ملغى وسري',
            'type' => 'text',
            'is_active' => false,
            'position' => 99,
        ]);

        $response = $this->actingAs($this->user)->get('/leads');

        $response->assertOk();
        $response->assertDontSee('سؤال ملغى وسري');
    }

    /** 11. Canonical duplicates deduplicated in table columns */
    public function test_canonical_duplicates_deduplicated(): void
    {
        $phoneB = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageInterested->id, 'key' => 'phone_dup'],
            ['label_ar' => 'رقم الهاتف', 'type' => 'tel', 'binding_type' => 'canonical', 'binding_target' => 'phone', 'is_active' => true, 'position' => 4]
        );

        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $this->fieldNewPhone->id . ',stage_field:' . $phoneB->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');

        $phoneColCount = 0;
        foreach ($visibleColumns as $col) {
            if (($col['binding_target'] ?? null) === 'phone') {
                $phoneColCount++;
            }
        }
        $this->assertEquals(1, $phoneColCount);
    }

    /** 12. Selected stage field appears as table column */
    public function test_selected_stage_field_appears_as_table_column(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,stage_field:' . $this->fieldNoAnswerCallback->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('stage_field:' . $this->fieldNoAnswerCallback->id, $keys);
    }

    /** 13. Selected stage field filter renders */
    public function test_selected_stage_field_filter_renders(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?field_ids=' . $this->fieldNoAnswerCallback->id);

        $response->assertOk();
        $response->assertSee('id="stageFieldFilter_' . $this->fieldNoAnswerCallback->id . '"', false);
    }

    /** 14. No invisible stale field filters applied when changing status */
    public function test_no_invisible_stale_field_filters(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?status=new');

        $response->assertOk();
        $stageFieldFilters = $response->viewData('stageFieldFilters');
        foreach ($stageFieldFilters as $val) {
            $this->assertEquals('', $val);
        }
    }
}
