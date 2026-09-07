<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactiveLeadsFilterAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageA;
    private PipelineStage $stageB;
    private LeadStatus $statusA;
    private LeadStatus $statusB;
    private Lead $leadA;
    private Lead $leadB;
    private Lead $leadC;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::query()->where('username', 'admin')->first() ?? User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin Test User',
            'is_active' => true,
        ]);
        $this->admin->groups()->syncWithoutDetaching([$superAdminGroup->id]);

        $this->stageA = PipelineStage::query()->firstOrCreate(
            ['code' => 'reactive_stage_a'],
            ['name_ar' => 'مرحلة تفاعلية أ', 'position' => 100, 'is_active' => true, 'color' => '#3478f6']
        );

        $this->stageB = PipelineStage::query()->firstOrCreate(
            ['code' => 'reactive_stage_b'],
            ['name_ar' => 'مرحلة تفاعلية ب', 'position' => 101, 'is_active' => true, 'color' => '#10b981']
        );

        $this->statusA = LeadStatus::query()->firstOrCreate(
            ['code' => 'reactive_status_a'],
            ['pipeline_stage_id' => $this->stageA->id, 'name_ar' => 'حالة تفاعلية أ', 'position' => 100]
        );

        $this->statusB = LeadStatus::query()->firstOrCreate(
            ['code' => 'reactive_status_b'],
            ['pipeline_stage_id' => $this->stageB->id, 'name_ar' => 'حالة تفاعلية ب', 'position' => 101]
        );

        $this->leadA = Lead::query()->create([
            'name' => 'محمد أحمد الصالح',
            'phone' => '01011112222',
            'email' => 'ahmed.saleh@example.test',
            'company_name' => 'شركة الصالح التقنية',
            'source' => 'website',
            'lead_status_id' => $this->statusA->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->startOfDay()->addHours(11), // today
        ]);

        $this->leadB = Lead::query()->create([
            'name' => 'سارة محمود خليل',
            'phone' => '01033334444',
            'email' => 'sara.khalil@example.test',
            'company_name' => 'مؤسسة خليل الدولية',
            'source' => 'facebook',
            'lead_status_id' => $this->statusB->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDays(3), // upcoming
        ]);

        $this->leadC = Lead::query()->create([
            'name' => 'خالد عبد الرحمن',
            'phone' => '01055556666',
            'email' => 'khaled.abdo@example.test',
            'company_name' => 'مجموعة عبد الرحمن',
            'source' => 'referral',
            'lead_status_id' => $this->statusA->id,
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->subDays(2), // overdue
        ]);
    }

    /**
     * 1. GET filters still work without JS (no-JS fallback returns full page)
     */
    public function test_1_get_filters_work_without_javascript(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'q' => 'الصالح',
        ]));

        $response->assertOk();
        $response->assertViewIs('leads.index');
        $response->assertSee('محمد أحمد الصالح');
        $response->assertDontSee('سارة محمود خليل');
        $response->assertSee('table-card');
        $response->assertSee('filter-panel');
    }

    /**
     * 2. status filter filters leads by status code or id
     */
    public function test_2_status_filter_filters_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => $this->statusB->code,
        ]));

        $response->assertOk();
        $response->assertSee('سارة محمود خليل');
        $response->assertDontSee('محمد أحمد الصالح');
        $response->assertDontSee('خالد عبد الرحمن');
    }

    /**
     * 3. source filter filters leads by source
     */
    public function test_3_source_filter_filters_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'source' => 'facebook',
        ]));

        $response->assertOk();
        $response->assertSee('سارة محمود خليل');
        $response->assertDontSee('محمد أحمد الصالح');
    }

    /**
     * 4. employee filter filters leads by assigned employee
     */
    public function test_4_employee_filter_filters_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'employee' => $this->admin->name,
        ]));

        $response->assertOk();
        $response->assertSee('محمد أحمد الصالح');
        $response->assertSee('سارة محمود خليل');
    }

    /**
     * 5. date filter (today, upcoming, overdue, none)
     */
    public function test_5_date_filter_filters_correctly(): void
    {
        // Today
        $todayResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'follow_up' => 'today',
        ]));
        $todayResponse->assertOk();
        $todayResponse->assertSee('محمد أحمد الصالح');
        $todayResponse->assertDontSee('سارة محمود خليل');
        $todayResponse->assertDontSee('خالد عبد الرحمن');

        // Upcoming
        $upcomingResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'follow_up' => 'upcoming',
        ]));
        $upcomingResponse->assertOk();
        $upcomingResponse->assertSee('سارة محمود خليل');
        $upcomingResponse->assertDontSee('محمد أحمد الصالح');

        // Overdue
        $overdueResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'follow_up' => 'overdue',
        ]));
        $overdueResponse->assertOk();
        $overdueResponse->assertSee('خالد عبد الرحمن');
        $overdueResponse->assertDontSee('سارة محمود خليل');
    }

    /**
     * 6. sort filter (latest, oldest, name, followup)
     */
    public function test_6_sort_filter_orders_results(): void
    {
        $nameSort = $this->actingAs($this->admin)->get(route('v2.leads', [
            'sort' => 'name',
        ]));
        $nameSort->assertOk();
        $leads = $nameSort->viewData('leads');
        $this->assertNotEmpty($leads);
    }

    /**
     * 7. quick search across multiple columns
     */
    public function test_7_quick_search_matches_phone_email_company(): void
    {
        // Search by phone
        $phoneRes = $this->actingAs($this->admin)->get(route('v2.leads', ['q' => '01033334444']));
        $phoneRes->assertOk();
        $phoneRes->assertSee('سارة محمود خليل');
        $phoneRes->assertDontSee('محمد أحمد الصالح');

        // Search by company
        $compRes = $this->actingAs($this->admin)->get(route('v2.leads', ['q' => 'الصالح التقنية']));
        $compRes->assertOk();
        $compRes->assertSee('محمد أحمد الصالح');
        $compRes->assertDontSee('سارة محمود خليل');
    }

    /**
     * 8. dynamic text field filtering
     */
    public function test_8_dynamic_text_field_filtering(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'dyn_city',
            'label_ar' => 'المدينة',
            'type' => 'text',
            'is_active' => true,
        ]);

        $this->leadA->stageValues()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => 'dyn_city',
            'value' => 'الرياض',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'الرياض'],
        ]));

        $response->assertOk();
        $response->assertSee('محمد أحمد الصالح');
        $response->assertDontSee('سارة محمود خليل');
    }

    /**
     * 9. dynamic select field filtering
     */
    public function test_9_dynamic_select_field_filtering(): void
    {
        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'dyn_plan',
            'label_ar' => 'الباقة المختارة',
            'type' => 'select',
            'options' => [
                ['value' => 'premium', 'label_ar' => 'بريميوم', 'label_en' => 'Premium'],
                ['value' => 'basic', 'label_ar' => 'أساسية', 'label_en' => 'Basic'],
            ],
            'is_active' => true,
        ]);

        $this->leadA->stageValues()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => 'dyn_plan',
            'value' => 'premium',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'premium'],
        ]));

        $response->assertOk();
        $response->assertSee('محمد أحمد الصالح');
        $response->assertDontSee('سارة محمود خليل');
    }

    /**
     * 10. multiple filters combine seamlessly
     */
    public function test_10_multiple_filters_combine_with_and_logic(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => $this->statusA->code,
            'source' => 'website',
            'follow_up' => 'today',
        ]));

        $response->assertOk();
        $response->assertSee('محمد أحمد الصالح');
        $response->assertDontSee('خالد عبد الرحمن'); // different source & follow_up
        $response->assertDontSee('سارة محمود خليل'); // different status
    }

    public function test_11_unauthorized_dynamic_field_rejected(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'field_ids' => [99999],
            'field_filters' => [99999 => 'malicious_input'],
        ]));

        $response->assertOk();
        // Does not crash, ignores invalid foreign field
        $this->assertNotEmpty($response->viewData('leads'));
    }

    /**
     * 12. pagination preserves active filters
     */
    public function test_12_pagination_preserves_active_filters(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Lead::query()->create([
                'name' => 'عميل مرقم ' . $i,
                'lead_status_id' => $this->statusA->id,
                'source' => 'website',
                'assigned_user_id' => $this->admin->id,
            ]);
        }

        $page2Response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'source' => 'website',
            'page' => 2,
        ]));

        $page2Response->assertOk();
        $leads = $page2Response->viewData('leads');
        $this->assertSame(2, $leads->currentPage());
        // Verify pagination query string includes source=website
        $this->assertStringContainsString('source=website', $leads->url(1));
    }

    /**
     * 13. AJAX partial response matches full-page query result
     */
    public function test_13_ajax_partial_response_matches_full_page_query(): void
    {
        $fullPage = $this->actingAs($this->admin)->get(route('v2.leads', [
            'q' => 'الصالح',
        ]));
        $fullPage->assertOk();
        $fullPageTotal = $fullPage->viewData('leads')->total();

        $ajaxResponse = $this->actingAs($this->admin)->getJson(route('v2.leads', [
            'q' => 'الصالح',
            'partial' => 'table',
        ]));

        $ajaxResponse->assertOk();
        $ajaxResponse->assertJsonStructure([
            'success',
            'table_html',
            'stats_html',
            'total',
            'total_leads',
            'count',
        ]);
        $this->assertSame($fullPageTotal, $ajaxResponse->json('total'));
        $this->assertStringContainsString('محمد أحمد الصالح', $ajaxResponse->json('table_html'));
    }

    /**
     * 14. Apply button is absent from view
     */
    public function test_14_apply_button_is_absent_from_ui(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertDontSee('<button type="submit" class="btn primary small"', false);
        $response->assertDontSee('title="' . __('crm.apply_filter') . '"', false);
    }

    /**
     * 15. Reset button is present in view
     */
    public function test_15_reset_button_is_present_in_ui(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="resetFiltersBtn"', false);
        $response->assertSee('reset-filters-btn', false);
    }

    /**
     * 16. Selected status limits available dynamic fields to that stage
     */
    public function test_16_selected_status_limits_available_dynamic_fields(): void
    {
        $fieldA = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'dyn_field_a',
            'label_ar' => 'حقل مرحلة أ',
            'type' => 'text',
            'is_active' => true,
        ]);

        $fieldB = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageB->id,
            'key' => 'dyn_field_b',
            'label_ar' => 'حقل مرحلة ب',
            'type' => 'text',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => $this->statusA->code,
        ]));

        $response->assertOk();
        $availableFields = $response->viewData('availableStageFields');
        $this->assertTrue($availableFields->contains('id', $fieldA->id));
        $this->assertFalse($availableFields->contains('id', $fieldB->id));
    }

    /**
     * 17. Status change clears irrelevant field filters from query
     */
    public function test_17_status_change_clears_irrelevant_field_filters(): void
    {
        $fieldA = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'stage_a_field',
            'label_ar' => 'حقل مرحلة أ',
            'type' => 'text',
            'is_active' => true,
        ]);

        // Request statusB but pass field_id of stageA
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => $this->statusB->code,
            'field_ids' => [$fieldA->id],
            'field_filters' => [$fieldA->id => 'some_val'],
        ]));

        $response->assertOk();
        // fieldA must be pruned from selected fields because it belongs to stageA, not stageB
        $selectedFields = $response->viewData('selectedStageFields');
        $this->assertFalse($selectedFields->contains('id', $fieldA->id));
    }

    /**
     * 18. Active filters survive full-page GET reload
     */
    public function test_18_active_filters_survive_reload(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => $this->statusA->code,
            'source' => 'website',
            'follow_up' => 'today',
            'sort' => 'followup',
        ]));

        $response->assertOk();
        $response->assertSee('value="' . $this->statusA->code . '"', false);
        $response->assertSee('value="website" selected', false);
        $response->assertSee('value="today" selected', false);
        $response->assertSee('value="followup" selected', false);
    }

    /**
     * 19. Inactive PipelineStageField unavailable for filtering
     */
    public function test_19_inactive_stage_field_unavailable_for_filtering(): void
    {
        $inactiveField = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageA->id,
            'key' => 'inactive_field_test',
            'label_ar' => 'حقل معطل تجريبي',
            'type' => 'text',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => $this->statusA->code,
        ]));

        $response->assertOk();
        $availableFields = $response->viewData('availableStageFields');
        $this->assertFalse($availableFields->contains('id', $inactiveField->id));
    }

    /**
     * 20. Primary filter bar has one-row structure on desktop
     */
    public function test_20_primary_filter_bar_has_one_row_structure(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('filter-bar-primary', false);
        $response->assertSee('col-search', false);
        $response->assertSee('col-status', false);
        $response->assertSee('col-source', false);
        $response->assertSee('col-employee', false);
        $response->assertSee('col-followup', false);
        $response->assertSee('col-sort', false);
        $response->assertSee('col-fields', false);
    }

    /**
     * 21. Empty filter values cleaned safely
     */
    public function test_21_empty_filter_values_cleaned_safely(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'status' => '',
            'source' => '',
            'employee' => '',
            'q' => '',
            'follow_up' => '',
        ]));

        $response->assertOk();
        $filters = $response->viewData('filters');
        $this->assertSame('', $filters['status']);
        $this->assertSame('', $filters['source']);
    }
}
