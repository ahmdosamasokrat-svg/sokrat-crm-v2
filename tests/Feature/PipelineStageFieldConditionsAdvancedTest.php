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
use App\Support\CrmDatabaseGuard;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PipelineStageFieldConditionsAdvancedTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
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
            'username' => 'admin_cond_' . uniqid(),
            'name' => 'Admin Condition Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stage = PipelineStage::query()->create([
            'code' => 'cond_test_stage_' . uniqid(),
            'name_ar' => 'مرحلة اختبار الشروط المتقدمة',
            'name_en' => 'Advanced Condition Stage',
            'position' => 20,
            'color' => '#10b981',
            'is_active' => true,
        ]);

        $this->status = LeadStatus::query()->create([
            'code' => 'cond_status_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة تجريبية',
            'name_en' => 'Test Status',
            'position' => 1,
            'is_active' => true,
        ]);
    }

    public function test_evaluate_condition_mode_all_requires_every_rule_to_pass(): void
    {
        $condition = [
            'mode' => 'all',
            'rules' => [
                ['field' => 'country', 'operator' => 'equals', 'value' => 'Egypt'],
                ['field' => 'company_size', 'operator' => 'not_equals', 'value' => 'micro'],
            ],
        ];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condition, [
            'country' => 'Egypt',
            'company_size' => 'enterprise',
        ]));

        $this->assertFalse(StageFieldSchema::evaluateCondition($condition, [
            'country' => 'Egypt',
            'company_size' => 'micro',
        ]));

        $this->assertFalse(StageFieldSchema::evaluateCondition($condition, [
            'country' => 'Saudi Arabia',
            'company_size' => 'enterprise',
        ]));
    }

    public function test_evaluate_condition_mode_any_passes_if_one_rule_matches(): void
    {
        $condition = [
            'mode' => 'any',
            'rules' => [
                ['field' => 'interest', 'operator' => 'equals', 'value' => 'high'],
                ['field' => 'budget', 'operator' => 'equals', 'value' => 'over_100k'],
            ],
        ];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condition, [
            'interest' => 'high',
            'budget' => 'low',
        ]));

        $this->assertTrue(StageFieldSchema::evaluateCondition($condition, [
            'interest' => 'low',
            'budget' => 'over_100k',
        ]));

        $this->assertFalse(StageFieldSchema::evaluateCondition($condition, [
            'interest' => 'low',
            'budget' => 'under_10k',
        ]));
    }

    public function test_evaluate_condition_operators_contains_and_in(): void
    {
        // Contains operator
        $condContains = [
            'field' => 'tags',
            'operator' => 'contains',
            'value' => 'vip',
        ];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condContains, ['tags' => 'client_vip_priority']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condContains, ['tags' => 'standard_lead']));

        // In operator
        $condIn = [
            'field' => 'city',
            'operator' => 'in',
            'value' => 'Cairo, Alexandria, Giza',
        ];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condIn, ['city' => 'Cairo']));
        $this->assertTrue(StageFieldSchema::evaluateCondition($condIn, ['city' => 'Alexandria']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condIn, ['city' => 'Dubai']));
    }

    public function test_evaluate_condition_operators_is_true_and_is_false(): void
    {
        $condTrue = ['field' => 'has_whatsapp', 'operator' => 'is_true'];
        $condFalse = ['field' => 'has_whatsapp', 'operator' => 'is_false'];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condTrue, ['has_whatsapp' => '1']));
        $this->assertTrue(StageFieldSchema::evaluateCondition($condTrue, ['has_whatsapp' => true]));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condTrue, ['has_whatsapp' => '0']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condTrue, ['has_whatsapp' => false]));

        $this->assertTrue(StageFieldSchema::evaluateCondition($condFalse, ['has_whatsapp' => '0']));
        $this->assertTrue(StageFieldSchema::evaluateCondition($condFalse, ['has_whatsapp' => false]));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condFalse, ['has_whatsapp' => '1']));
    }

    public function test_evaluate_condition_operators_is_empty_and_is_not_empty(): void
    {
        $condEmpty = ['field' => 'tax_number', 'operator' => 'is_empty'];
        $condNotEmpty = ['field' => 'tax_number', 'operator' => 'is_not_empty'];

        $this->assertTrue(StageFieldSchema::evaluateCondition($condEmpty, ['tax_number' => '']));
        $this->assertTrue(StageFieldSchema::evaluateCondition($condEmpty, []));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condEmpty, ['tax_number' => '123456']));

        $this->assertTrue(StageFieldSchema::evaluateCondition($condNotEmpty, ['tax_number' => '123456']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condNotEmpty, ['tax_number' => '']));
        $this->assertFalse(StageFieldSchema::evaluateCondition($condNotEmpty, []));
    }

    public function test_self_referencing_condition_throws_validation_error(): void
    {
        $field = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'custom_decision',
            'label_ar' => 'القرار',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
            'position' => 1,
        ]);

        $this->expectException(ValidationException::class);

        StageFieldSchema::normalizeAndValidateConditions(
            $this->stage,
            'custom_decision',
            ['field' => 'custom_decision', 'operator' => 'equals', 'value' => 'yes']
        );
    }

    public function test_circular_dependency_is_detected_and_rejected(): void
    {
        // Field A depends on Field B
        $fieldA = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_a',
            'label_ar' => 'حقل أ',
            'type' => 'text',
            'is_active' => true,
            'position' => 1,
            'conditions' => ['field' => 'field_b', 'operator' => 'equals', 'value' => '1'],
        ]);

        // Field B depends on Field C
        $fieldB = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_b',
            'label_ar' => 'حقل ب',
            'type' => 'text',
            'is_active' => true,
            'position' => 2,
            'conditions' => ['field' => 'field_c', 'operator' => 'equals', 'value' => '1'],
        ]);

        $fieldC = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'field_c',
            'label_ar' => 'حقل ج',
            'type' => 'text',
            'is_active' => true,
            'position' => 3,
        ]);

        // Now trying to make Field C depend on Field A would complete the cycle: A -> B -> C -> A
        $this->expectException(ValidationException::class);

        StageFieldSchema::normalizeAndValidateConditions(
            $this->stage,
            'field_c',
            ['field' => 'field_a', 'operator' => 'equals', 'value' => '1']
        );
    }

    public function test_conditionally_hidden_required_field_does_not_fail_validation(): void
    {
        // Decision radio: 'yes' or 'no'
        $decisionField = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'has_vat_invoice',
            'label_ar' => 'هل يحتاج فاتورة ضريبية؟',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => [
                ['value' => 'yes', 'label_ar' => 'نعم'],
                ['value' => 'no', 'label_ar' => 'لا'],
            ],
        ]);

        // Tax number is required ONLY when has_vat_invoice == 'yes'
        $taxNumberField = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'vat_tax_number',
            'label_ar' => 'الرقم الضريبي',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 2,
            'conditions' => [
                'field' => 'has_vat_invoice',
                'operator' => 'equals',
                'value' => 'yes',
            ],
        ]);

        // Submission with has_vat_invoice = 'no' and vat_tax_number omitted
        // Should PASS validation because vat_tax_number condition is not met!
        $validated = StageFieldSchema::validateAndExtract($this->stage, [
            'has_vat_invoice' => 'no',
        ]);

        $this->assertEquals('no', $validated['has_vat_invoice']);
        $this->assertNull($validated['vat_tax_number']);
    }

    public function test_conditionally_visible_required_field_fails_validation_when_omitted(): void
    {
        $decisionField = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'has_vat_invoice',
            'label_ar' => 'هل يحتاج فاتورة ضريبية؟',
            'type' => 'select',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'options' => [
                ['value' => 'yes', 'label_ar' => 'نعم'],
                ['value' => 'no', 'label_ar' => 'لا'],
            ],
        ]);

        $taxNumberField = PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'vat_tax_number',
            'label_ar' => 'الرقم الضريبي',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 2,
            'conditions' => [
                'field' => 'has_vat_invoice',
                'operator' => 'equals',
                'value' => 'yes',
            ],
        ]);

        // Submission with has_vat_invoice = 'yes' BUT vat_tax_number omitted
        // MUST fail validation because condition IS met!
        $this->expectException(ValidationException::class);

        StageFieldSchema::validateAndExtract($this->stage, [
            'has_vat_invoice' => 'yes',
            'vat_tax_number' => '',
        ]);
    }
}
