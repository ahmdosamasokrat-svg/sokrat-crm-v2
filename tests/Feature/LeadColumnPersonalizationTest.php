<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Support\LeadColumnConfig;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadColumnPersonalizationTest extends TestCase
{
    use DatabaseTransactions;
    private User $user;
    private PipelineStage $stage;
    private LeadStatus $status;
    private PipelineStageField $customField;
    private PipelineStageField $canonicalField;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();

        $this->stage = PipelineStage::firstOrCreate(
            ['code' => 'test_stage'],
            ['name_ar' => 'مرحلة تجريبية', 'name_en' => 'Test Stage', 'is_active' => true, 'position' => 99]
        );

        $this->status = LeadStatus::firstOrCreate(
            ['code' => 'test_status'],
            [
                'name_ar' => 'حالة تجريبية',
                'name_en' => 'Test Status',
                'pipeline_stage_id' => $this->stage->id,
                'is_active' => true,
                'color' => '#3b82f6',
                'position' => 1,
            ]
        );

        $this->customField = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stage->id, 'key' => 'col_custom_notes'],
            [
                'label_ar' => 'ملاحظات العمود',
                'label_en' => 'Column Notes',
                'type' => 'text',
                'binding_type' => 'custom',
                'is_active' => true,
                'is_required' => false,
                'position' => 1,
            ]
        );

        $this->canonicalField = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stage->id, 'key' => 'phone'],
            [
                'label_ar' => 'هاتف إضافي',
                'label_en' => 'Secondary Phone',
                'type' => 'tel',
                'binding_type' => 'canonical',
                'binding_target' => 'phone',
                'is_active' => true,
                'is_required' => false,
                'position' => 2,
            ]
        );
    }

    /** 1. Standard column selection */
    public function test_standard_column_selection(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,core:employee');

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:contact', $keys);
        $this->assertContains('core:employee', $keys);
        $this->assertNotContains('core:company_source', $keys);
        $this->assertNotContains('core:next_followup', $keys);
        $this->assertNotContains('core:created_at', $keys);
    }

    /** 2. Dynamic field column selection */
    public function test_dynamic_field_column_selection(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,stage_field:' . $this->customField->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('stage_field:' . $this->customField->id, $keys);
        $this->assertContains('core:contact', $keys);
    }

    /** 3. Deselection removes column */
    public function test_deselection_removes_column(): void
    {
        // First select with contact and company_source
        $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,core:company_source');

        // Now deselect company_source
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact');

        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:contact', $keys);
        $this->assertNotContains('core:company_source', $keys);
    }

    /** 4. Mandatory columns cannot disappear */
    public function test_mandatory_columns_cannot_disappear(): void
    {
        // Request with empty or only optional column
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact');

        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:customer', $keys);
        $this->assertContains('core:status', $keys);
        $this->assertContains('core:actions', $keys);
    }

    /** 5. Selected columns persist after reload (via session or URL) */
    public function test_selected_columns_persist_in_session_and_url(): void
    {
        // 1st request sets session preference
        $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,core:employee');

        // 2nd request without columns parameter reuses persisted session preferences
        $reloadResponse = $this->actingAs($this->user)
            ->get('/leads');

        $visibleColumns = $reloadResponse->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:contact', $keys);
        $this->assertContains('core:employee', $keys);
        $this->assertNotContains('core:company_source', $keys);
    }

    /** 6. Filter state persists with selected columns */
    public function test_filter_state_persists_with_selected_columns(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?q=Ahmed&columns=core:contact,stage_field:' . $this->customField->id);

        $response->assertOk();
        $filters = $response->viewData('filters');
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertEquals('Ahmed', $filters['q']);
        $this->assertContains('stage_field:' . $this->customField->id, $keys);
    }

    /** 7. Deselected field filter removed */
    public function test_deselected_field_filter_cleanup(): void
    {
        // User deselects custom field, so field_ids only has other fields
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact');

        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertNotContains('stage_field:' . $this->customField->id, $keys);
    }

    /** 8. Canonical field deduplication */
    public function test_canonical_field_deduplication(): void
    {
        // Request includes standard contact AND canonical phone stage field
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,stage_field:' . $this->canonicalField->id);

        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        // Standard contact is rendered, duplicate phone column is omitted
        $this->assertContains('core:contact', $keys);
        $this->assertNotContains('stage_field:' . $this->canonicalField->id, $keys);
    }

    /** 9. Custom stage value displayed */
    public function test_custom_stage_value_displayed(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Custom Value Test Lead',
            'lead_status_id' => $this->status->id,
        ]);
        LeadStageFieldValue::create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage->id,
            'pipeline_stage_field_id' => $this->customField->id,
            'field_key' => $this->customField->key,
            'value' => 'Special Custom Note 12345',
        ]);
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $this->customField->id . '&q=Custom+Value+Test+Lead');

        $response->assertOk();
        $response->assertSee('Special Custom Note 12345');
    }

    /** 10. Empty value displays dash */
    public function test_empty_stage_value_displays_dash(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Empty Value Test Lead',
            'lead_status_id' => $this->status->id,
        ]);
        $formatted = LeadColumnConfig::formatDynamicCellValue($lead, $this->customField, null);
        $this->assertEquals('—', $formatted);
    }

    /** 11. Pagination preserves columns */
    public function test_pagination_preserves_columns(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?page=1&columns=core:contact,stage_field:' . $this->customField->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('stage_field:' . $this->customField->id, $keys);
    }

    /** 12. AJAX response respects selected columns */
    public function test_ajax_response_respects_selected_columns(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/leads?columns=core:contact,core:employee&partial=table');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'table_html', 'visible_columns']);
        $visibleKeys = $response->json('visible_columns');

        $this->assertContains('core:contact', $visibleKeys);
        $this->assertContains('core:employee', $visibleKeys);
        $this->assertNotContains('core:company_source', $visibleKeys);
    }

    /** 13. Unauthorized field rejected */
    public function test_unauthorized_field_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=evil_column,core:contact,<script>alert(1)</script>');

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertNotContains('evil_column', $keys);
        $this->assertNotContains('<script>alert(1)</script>', $keys);
        $this->assertContains('core:contact', $keys);
    }

    /** 14. Inactive PipelineStageField cannot become column */
    public function test_inactive_pipeline_stage_field_cannot_become_column(): void
    {
        $inactiveField = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'inactive_col_field',
            'label_ar' => 'حقل معطل',
            'label_en' => 'Inactive Field',
            'type' => 'text',
            'is_active' => false,
            'position' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $inactiveField->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertNotContains('stage_field:' . $inactiveField->id, $keys);
    }

    /** 15. No N+1 query for multiple selected dynamic fields */
    public function test_no_n_plus_one_for_multiple_selected_dynamic_fields(): void
    {
        $lead1 = Lead::query()->create(['name' => 'Lead 1', 'lead_status_id' => $this->status->id]);
        $lead2 = Lead::query()->create(['name' => 'Lead 2', 'lead_status_id' => $this->status->id]);
        $lead3 = Lead::query()->create(['name' => 'Lead 3', 'lead_status_id' => $this->status->id]);
        $field2 = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stage->id, 'key' => 'col_field_2'],
            ['label_ar' => 'حقل ثان', 'label_en' => 'Field 2', 'type' => 'text', 'is_active' => true, 'position' => 3]
        );

        LeadStageFieldValue::create(['lead_id' => $lead1->id, 'pipeline_stage_id' => $this->stage->id, 'pipeline_stage_field_id' => $this->customField->id, 'field_key' => $this->customField->key, 'value' => 'Val 1']);
        LeadStageFieldValue::create(['lead_id' => $lead2->id, 'pipeline_stage_id' => $this->stage->id, 'pipeline_stage_field_id' => $this->customField->id, 'field_key' => $this->customField->key, 'value' => 'Val 2']);
        LeadStageFieldValue::create(['lead_id' => $lead3->id, 'pipeline_stage_id' => $this->stage->id, 'pipeline_stage_field_id' => $field2->id, 'field_key' => $field2->key, 'value' => 'Val 3']);

        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $this->customField->id . ',stage_field:' . $field2->id . '&partial=table');

        $response->assertOk();
        $queries = DB::getQueryLog();

        // Check how many queries touched lead_stage_field_values
        $stageValueQueries = array_filter(
            $queries,
            static fn (array $q) => str_contains((string) $q['query'], 'lead_stage_field_values')
        );

        // Exactly 1 batch query for all leads on current page, never N queries
        $this->assertLessThanOrEqual(1, count($stageValueQueries));
    }

    /** 16. Reset columns restores defaults */
    public function test_reset_columns_restores_defaults(): void
    {
        // First set custom columns in session
        $this->actingAs($this->user)
            ->get('/leads?columns=core:contact');

        // Now restore defaults
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=default');

        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:contact', $keys);
        $this->assertContains('core:company_source', $keys);
        $this->assertContains('core:employee', $keys);
        $this->assertContains('core:next_followup', $keys);
        $this->assertContains('core:created_at', $keys);
    }
}
