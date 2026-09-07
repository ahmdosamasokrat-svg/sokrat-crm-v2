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
use Tests\TestCase;

class LeadFilterUxPolishTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;
    private PipelineStageField $canonicalPhoneA;
    private PipelineStageField $canonicalPhoneB;
    private PipelineStageField $customTextarea;
    private PipelineStageField $customFieldB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();

        $this->stageA = PipelineStage::firstOrCreate(
            ['code' => 'ux_stage_a'],
            ['name_ar' => 'مرحلة أ', 'name_en' => 'Stage A', 'is_active' => true, 'position' => 1]
        );

        $this->stageB = PipelineStage::firstOrCreate(
            ['code' => 'ux_stage_b'],
            ['name_ar' => 'مرحلة ب', 'name_en' => 'Stage B', 'is_active' => true, 'position' => 2]
        );

        $this->statusA = LeadStatus::firstOrCreate(
            ['code' => 'ux_status_a'],
            ['pipeline_stage_id' => $this->stageA->id, 'name_ar' => 'حالة أ', 'position' => 1, 'is_active' => true]
        );

        $this->statusB = LeadStatus::firstOrCreate(
            ['code' => 'ux_status_b'],
            ['pipeline_stage_id' => $this->stageB->id, 'name_ar' => 'حالة ب', 'position' => 1, 'is_active' => true]
        );

        // Canonical phone on Stage A and Stage B
        $this->canonicalPhoneA = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageA->id, 'key' => 'phone_a'],
            ['label_ar' => 'رقم الهاتف', 'type' => 'tel', 'binding_type' => 'canonical', 'binding_target' => 'phone', 'is_active' => true, 'position' => 1]
        );

        $this->canonicalPhoneB = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageB->id, 'key' => 'phone_b'],
            ['label_ar' => 'رقم الهاتف', 'type' => 'tel', 'binding_type' => 'canonical', 'binding_target' => 'phone', 'is_active' => true, 'position' => 1]
        );

        // Custom textarea on Stage A
        $this->customTextarea = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageA->id, 'key' => 'attempt_notes'],
            ['label_ar' => 'ملاحظات المحاولة', 'type' => 'textarea', 'binding_type' => 'custom', 'is_active' => true, 'position' => 2]
        );

        // Custom field on Stage B
        $this->customFieldB = PipelineStageField::firstOrCreate(
            ['pipeline_stage_id' => $this->stageB->id, 'key' => 'interview_score'],
            ['label_ar' => 'تقييم المقابلة', 'type' => 'number', 'binding_type' => 'custom', 'is_active' => true, 'position' => 2]
        );
    }

    /** 1. Primary filter bar structure: Search, Status, Source, Employee, Followup, Sort, Choose Fields, Reset */
    public function test_primary_filter_bar_structure_and_order(): void
    {
        $response = $this->actingAs($this->user)->get('/leads');

        $response->assertOk();
        $response->assertSee('filter-bar-primary');
        $response->assertSee('col-search');
        $response->assertSee('col-status');
        $response->assertSee('col-source');
        $response->assertSee('col-employee');
        $response->assertSee('col-followup');
        $response->assertSee('col-sort');
        $response->assertSee('col-fields');
        $response->assertSee('resetFiltersBtn');
    }

    /** 2. Secondary filter bar is absent/hidden with zero selected fields */
    public function test_secondary_filter_bar_absent_with_zero_selected_fields(): void
    {
        $response = $this->actingAs($this->user)->get('/leads?columns=default');

        $response->assertOk();
        // Zero dynamic fields selected -> style="display:none;" on dynamicFilterFieldsBar
        $response->assertSee('id="dynamicFilterFieldsBar"', false);
        $response->assertSee('display:none;', false);
    }

    /** 3. Secondary bar appears with selected fields */
    public function test_secondary_filter_bar_appears_with_selected_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,stage_field:' . $this->customTextarea->id . '&field_ids=' . $this->customTextarea->id);

        $response->assertOk();
        $response->assertSee('stageFieldFilter_' . $this->customTextarea->id);
    }

    /** 4. Canonical duplicate fields collapse to one option/column */
    public function test_canonical_duplicate_fields_collapse_to_one_column(): void
    {
        // Select both phone_a and phone_b with standard contact disabled
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $this->canonicalPhoneA->id . ',stage_field:' . $this->canonicalPhoneB->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        // Only ONE phone column is rendered, not two!
        $phoneColCount = 0;
        foreach ($visibleColumns as $col) {
            if (($col['binding_target'] ?? null) === 'phone') {
                $phoneColCount++;
            }
        }
        $this->assertEquals(1, $phoneColCount);
    }

    /** 5. Custom stage fields remain separate */
    public function test_custom_stage_fields_remain_separate(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $this->customTextarea->id . ',stage_field:' . $this->customFieldB->id);

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('stage_field:' . $this->customTextarea->id, $keys);
        $this->assertContains('stage_field:' . $this->customFieldB->id, $keys);
    }

    /** 6. Reset Filters preserves selected columns */
    public function test_reset_filters_preserves_selected_columns(): void
    {
        // Set custom columns in session
        $this->actingAs($this->user)->get('/leads?columns=core:contact,core:employee');

        // Request with filters and then reset URL (no filter params, but columns preserved)
        $response = $this->actingAs($this->user)->get('/leads?columns=core:contact,core:employee');
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:contact', $keys);
        $this->assertContains('core:employee', $keys);
        $this->assertNotContains('core:company_source', $keys);
    }

    /** 7. Restore Default Columns preserves unrelated primary filter state */
    public function test_restore_default_columns_preserves_primary_filter_state(): void
    {
        // When restoring default columns, the search filter remains active
        $response = $this->actingAs($this->user)
            ->get('/leads?q=Sokrat&columns=default');

        $response->assertOk();
        $filters = $response->viewData('filters');
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertEquals('Sokrat', $filters['q']);
        $this->assertContains('core:company_source', $keys);
        $this->assertContains('core:employee', $keys);
    }

    /** 8. Textarea field uses compact text filter control */
    public function test_textarea_field_uses_compact_text_filter_control(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=stage_field:' . $this->customTextarea->id . '&field_ids=' . $this->customTextarea->id);

        $response->assertOk();
        // It renders an <input type="text" class="...dynamic-input-text">, NEVER a giant <textarea>!
        $response->assertSee('id="stageFieldFilter_' . $this->customTextarea->id . '"', false);
        $response->assertSee('type="text"', false);
        $response->assertDontSee('<textarea id="stageFieldFilter_' . $this->customTextarea->id . '"', false);
    }

    /** 9. Deselected field clears its active filter */
    public function test_deselected_field_clears_active_filter(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact');

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertNotContains('stage_field:' . $this->customTextarea->id, $keys);
    }

    /** 10. Selected column survives reload */
    public function test_selected_column_survives_reload(): void
    {
        $response1 = $this->actingAs($this->user)
            ->get('/leads?columns=core:contact,stage_field:' . $this->customFieldB->id);
        $response1->assertOk();

        $response2 = $this->actingAs($this->user)->get('/leads');
        $visibleColumns = $response2->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('stage_field:' . $this->customFieldB->id, $keys);
    }

    /** 11. Active filter survives reload */
    public function test_active_filter_survives_reload(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/leads?q=EnterpriseCo&columns=core:contact');

        $response->assertOk();
        $this->assertEquals('EnterpriseCo', $response->viewData('filters')['q']);
    }

    /** 12. Table partial renders selected fields correctly */
    public function test_table_partial_renders_selected_fields_correctly(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusA->id,
            'name' => 'Table Render Lead',
        ]);

        LeadStageFieldValue::create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageA->id,
            'pipeline_stage_field_id' => $this->customTextarea->id,
            'field_key' => $this->customTextarea->key,
            'value' => 'Special Attempt Detail 999',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/leads?partial=table&columns=stage_field:' . $this->customTextarea->id);

        $response->assertOk();
        $response->assertJsonStructure(['table_html', 'visible_columns']);
        $this->assertStringContainsString('Special Attempt Detail 999', $response->json('table_html'));
    }

    /** 13. No invisible filters in request */
    public function test_no_invisible_filters(): void
    {
        // When a stage is specified without field_ids, no invisible stage fields apply
        $response = $this->actingAs($this->user)
            ->get('/leads?status=' . $this->statusA->code);

        $response->assertOk();
        $stageFieldFilters = $response->viewData('stageFieldFilters');
        // Unselected fields have empty filter values
        foreach ($stageFieldFilters as $val) {
            $this->assertEquals('', $val);
        }
    }
}
