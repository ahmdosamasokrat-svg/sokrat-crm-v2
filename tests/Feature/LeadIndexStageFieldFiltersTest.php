<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStageFieldValue;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadIndexStageFieldFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PipelineStage $stage;

    private int $leadSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ar');

        Permission::query()->upsert(
            array_map(static fn (CrmPermission $permission): array => [
                'code' => $permission->value,
                'module' => $permission->module(),
                'name_ar' => $permission->label(),
            ], CrmPermission::cases()),
            ['code'],
            ['module', 'name_ar'],
        );

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ],
        );
        $superAdminGroup->permissions()->sync(Permission::query()->pluck('id'));

        $this->admin = User::factory()->create([
            'name' => 'Stage Filter Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stage = PipelineStage::query()->create([
            'code' => 'dynamic_filter_stage',
            'name_ar' => 'مرحلة الفلاتر الديناميكية',
            'position' => 50,
            'color' => '#2563eb',
            'is_primary' => false,
            'is_active' => true,
        ]);
    }

    public function test_all_leads_page_exposes_active_visible_fields_from_all_stages(): void
    {
        $field = $this->createField('city', 'المدينة');
        $inactiveField = $this->createField('secret', 'حقل غير نشط', ['is_active' => false]);
        $hiddenField = $this->createField('internal_note', 'حقل مخفي', ['show_on_stage_view' => false]);
        $otherStage = PipelineStage::query()->create([
            'code' => 'other_filter_stage',
            'name_ar' => 'مرحلة أخرى',
            'position' => 51,
            'color' => '#64748b',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $otherField = $this->createField('other_field', 'حقل مرحلة أخرى', [
            'pipeline_stage_id' => $otherStage->id,
        ]);
        $inactiveStage = PipelineStage::query()->create([
            'code' => 'inactive_filter_stage',
            'name_ar' => 'مرحلة معطلة',
            'position' => 52,
            'color' => '#94a3b8',
            'is_primary' => false,
            'is_active' => false,
        ]);
        $inactiveStageField = $this->createField('inactive_stage_field', 'حقل مرحلة معطلة', [
            'pipeline_stage_id' => $inactiveStage->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'field_ids' => [
                $field->id,
                $inactiveField->id,
                $hiddenField->id,
                $otherField->id,
                $inactiveStageField->id,
            ],
        ]));

        $response->assertOk();
        $response->assertSee('المدينة');
        $response->assertSee('حقل مرحلة أخرى');
        $response->assertDontSee('حقل غير نشط');
        $response->assertDontSee('حقل مخفي');
        $response->assertDontSee('حقل مرحلة معطلة');
        $response->assertSee('data-select-all-dynamic-fields', false);
        $this->assertSame([$field->id, $otherField->id], $response->viewData('selectedStageFieldIds'));
    }

    public function test_filter_uses_the_latest_saved_value_for_each_lead(): void
    {
        $field = $this->createField('city', 'المدينة');
        $updatedLead = $this->createLead('Latest Alexandria Lead');
        $cairoLead = $this->createLead('Current Cairo Lead');

        $this->saveValue($updatedLead, $field, 'Cairo');
        $this->saveValue($updatedLead, $field, 'Alexandria');
        $this->saveValue($cairoLead, $field, 'Cairo');

        $alexandriaResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'Alexandria'],
        ]));

        $alexandriaResponse->assertOk();
        $alexandriaResponse->assertSee('Latest Alexandria Lead');
        $alexandriaResponse->assertDontSee('Current Cairo Lead');

        $cairoResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'Cairo'],
        ]));

        $cairoResponse->assertOk();
        $cairoResponse->assertSee('Current Cairo Lead');
        $cairoResponse->assertDontSee('Latest Alexandria Lead');
    }

    public function test_multiple_dynamic_filters_are_combined_with_and_semantics(): void
    {
        $cityField = $this->createField('city', 'المدينة');
        $sizeField = $this->createField('company_size', 'حجم الشركة', [
            'type' => 'select',
            'options' => [
                ['value' => 'small', 'label_ar' => 'صغيرة', 'label_en' => 'Small'],
                ['value' => 'large', 'label_ar' => 'كبيرة', 'label_en' => 'Large'],
            ],
        ]);

        $target = $this->createLead('Alexandria Large Company');
        $wrongCity = $this->createLead('Cairo Large Company');
        $wrongSize = $this->createLead('Alexandria Small Company');

        $this->saveValue($target, $cityField, 'Alexandria East');
        $this->saveValue($target, $sizeField, 'large');
        $this->saveValue($wrongCity, $cityField, 'Cairo');
        $this->saveValue($wrongCity, $sizeField, 'large');
        $this->saveValue($wrongSize, $cityField, 'Alexandria West');
        $this->saveValue($wrongSize, $sizeField, 'small');

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$cityField->id, $sizeField->id],
            'field_filters' => [
                $cityField->id => 'Alexandria',
                $sizeField->id => 'large',
            ],
        ]));

        $response->assertOk();
        $response->assertSee('Alexandria Large Company');
        $response->assertDontSee('Cairo Large Company');
        $response->assertDontSee('Alexandria Small Company');
    }

    public function test_multiselect_filter_matches_a_saved_option(): void
    {
        $field = $this->createField('products', 'المنتجات', [
            'type' => 'multiselect',
            'options' => [
                ['value' => 'crm', 'label_ar' => 'إدارة العملاء', 'label_en' => 'CRM'],
                ['value' => 'erp', 'label_ar' => 'إدارة الموارد', 'label_en' => 'ERP'],
            ],
        ]);
        $matchingLead = $this->createLead('CRM Product Lead');
        $otherLead = $this->createLead('ERP Product Lead');
        $this->saveValue($matchingLead, $field, json_encode(['crm', 'erp'], JSON_THROW_ON_ERROR));
        $this->saveValue($otherLead, $field, json_encode(['erp'], JSON_THROW_ON_ERROR));

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'crm'],
        ]));

        $response->assertOk();
        $response->assertSee('CRM Product Lead');
        $response->assertDontSee('ERP Product Lead');

        $clearedValues = StageFieldSchema::validateAndExtract(
            $this->stage,
            ['stage_fields' => [$field->key => null]],
            $this->admin,
        );
        StageFieldSchema::persistValues(
            $matchingLead,
            $this->stage,
            $clearedValues,
            null,
            $this->admin,
        );

        $clearedResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'crm'],
        ]));

        $clearedResponse->assertOk();
        $clearedResponse->assertDontSee('CRM Product Lead');
    }

    public function test_clearing_a_field_supersedes_its_previous_value(): void
    {
        $field = $this->createField('city', 'المدينة');
        $clearedLead = $this->createLead('Cleared City Lead');
        $currentLead = $this->createLead('Current City Lead');
        $this->saveValue($clearedLead, $field, 'Cairo');
        $clearedValues = StageFieldSchema::validateAndExtract(
            $this->stage,
            ['stage_fields' => [$field->key => null]],
            $this->admin,
        );
        StageFieldSchema::persistValues(
            $clearedLead,
            $this->stage,
            $clearedValues,
            null,
            $this->admin,
        );
        $this->saveValue($currentLead, $field, 'Cairo');

        $this->assertDatabaseHas('lead_stage_field_values', [
            'lead_id' => $clearedLead->id,
            'pipeline_stage_field_id' => $field->id,
            'value' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'Cairo'],
        ]));

        $response->assertOk();
        $response->assertSee('Current City Lead');
        $response->assertDontSee('Cleared City Lead');
    }

    public function test_datetime_filter_accepts_saved_space_and_browser_formats(): void
    {
        $field = $this->createField('appointment_at', 'موعد المعاينة', ['type' => 'datetime']);
        $spaceFormatLead = $this->createLead('Space Datetime Lead');
        $browserFormatLead = $this->createLead('Browser Datetime Lead');
        $otherLead = $this->createLead('Other Datetime Lead');
        $this->saveValue($spaceFormatLead, $field, '2026-09-02 14:30:00');
        $this->saveValue($browserFormatLead, $field, '2026-09-02T14:30');
        $this->saveValue($otherLead, $field, '2026-09-02 15:30:00');

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => '2026-09-02T14:30'],
        ]));

        $response->assertOk();
        $response->assertSee('Space Datetime Lead');
        $response->assertSee('Browser Datetime Lead');
        $response->assertDontSee('Other Datetime Lead');
    }

    public function test_dynamic_filters_preserve_restricted_lead_scope(): void
    {
        $permissionGroup = Group::query()->create([
            'name' => 'موظف بفلاتر محدودة',
            'code' => 'restricted-stage-filter-user',
            'is_system' => false,
        ]);
        $permissionGroup->permissions()->sync(
            Permission::query()
                ->where('code', CrmPermission::LEADS_VIEW->value)
                ->pluck('id'),
        );
        $restrictedUser = User::factory()->create(['is_active' => true]);
        $restrictedUser->groups()->attach($permissionGroup);

        $field = $this->createField('city', 'المدينة');
        $accessibleLead = $this->createLead('Accessible Matching Lead');
        $accessibleLead->update(['assigned_user_id' => $restrictedUser->id]);
        $inaccessibleLead = $this->createLead('Inaccessible Matching Lead');
        $inaccessibleLead->update(['assigned_user_id' => $this->admin->id]);
        $this->saveValue($accessibleLead, $field, 'Cairo');
        $this->saveValue($inaccessibleLead, $field, 'Cairo');

        $response = $this->actingAs($restrictedUser)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => [$field->id],
            'field_filters' => [$field->id => 'Cairo'],
        ]));

        $response->assertOk();
        $response->assertSee('Accessible Matching Lead');
        $response->assertDontSee('Inaccessible Matching Lead');
    }

    public function test_all_leads_page_filters_by_a_field_from_any_stage(): void
    {
        $otherStage = PipelineStage::query()->create([
            'code' => 'foreign_filter_stage',
            'name_ar' => 'مرحلة حقل خارجي',
            'position' => 52,
            'color' => '#7c3aed',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $foreignField = $this->createField('foreign', 'الحقل الخارجي', [
            'pipeline_stage_id' => $otherStage->id,
        ]);
        $firstLead = $this->createLead('First Stage Lead');
        $secondLead = $this->createLead('Second Stage Lead');
        $this->saveValue($firstLead, $foreignField, 'match', $otherStage);

        $response = $this->actingAs($this->admin)->get(route('v2.leads', [
            'field_ids' => [$foreignField->id],
            'field_filters' => [$foreignField->id => 'match'],
        ]));

        $response->assertOk();
        $response->assertSee('First Stage Lead');
        $response->assertDontSee('Second Stage Lead');
        $this->assertSame([$foreignField->id], $response->viewData('selectedStageFieldIds'));

        $stageResponse = $this->actingAs($this->admin)->get(route('v2.leads', [
            'stage' => $this->stage->id,
            'field_ids' => (string) $foreignField->id,
            'field_filters' => [$foreignField->id => 'match'],
        ]));

        $stageResponse->assertOk();
        $stageResponse->assertSee('First Stage Lead');
        $stageResponse->assertDontSee('Second Stage Lead');
        $this->assertSame(
            (string) $foreignField->id,
            $stageResponse->viewData('queryWithoutStatus')['field_ids'],
        );
        $this->assertSame(
            [$foreignField->id => 'match'],
            $stageResponse->viewData('queryWithoutStatus')['field_filters'],
        );
    }

    /** @param array<string, mixed> $attributes */
    private function createField(string $key, string $label, array $attributes = []): PipelineStageField
    {
        return PipelineStageField::query()->create(array_merge([
            'pipeline_stage_id' => $this->stage->id,
            'key' => $key,
            'label_ar' => $label,
            'label_en' => ucfirst(str_replace('_', ' ', $key)),
            'type' => 'text',
            'is_active' => true,
            'position' => 1,
        ], $attributes));
    }

    private function createLead(string $name): Lead
    {
        $this->leadSequence++;

        return Lead::query()->create([
            'lead_status_id' => $this->stage->statuses()->firstOrFail()->id,
            'name' => $name,
            'phone' => '010000'.str_pad((string) $this->leadSequence, 5, '0', STR_PAD_LEFT),
            'source' => 'dynamic-filter-test',
        ]);
    }

    private function saveValue(
        Lead $lead,
        PipelineStageField $field,
        string $value,
        ?PipelineStage $stage = null,
    ): LeadStageFieldValue {
        return LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => ($stage ?? $this->stage)->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => $field->key,
            'field_type' => $field->type,
            'value' => $value,
            'created_by_user_id' => $this->admin->id,
        ]);
    }
}
