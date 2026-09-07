<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\CrmDatabaseGuard;
use App\Support\StageFieldTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageFieldTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stage;

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
            'username' => 'admin_tpl_' . uniqid(),
            'name' => 'Admin Template Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stage = PipelineStage::query()->create([
            'code' => 'tpl_test_stage_' . uniqid(),
            'name_ar' => 'مرحلة اختبار القوالب',
            'name_en' => 'Template Test Stage',
            'position' => 10,
            'color' => '#6366f1',
            'is_active' => true,
        ]);
        $templates = StageFieldTemplates::all();

        $this->assertArrayHasKey('customer_contact', $templates);
        $this->assertArrayHasKey('company_info', $templates);
        $this->assertArrayHasKey('appointment', $templates);
        $this->assertArrayHasKey('followup', $templates);
        $this->assertArrayHasKey('quotation', $templates);
        $this->assertArrayHasKey('contract_service', $templates);

        foreach ($templates as $key => $tpl) {
            $this->assertNotEmpty($tpl['name_ar']);
            $this->assertNotEmpty($tpl['name_en']);
            $this->assertIsArray($tpl['fields']);
            $this->assertNotEmpty($tpl['fields']);

            foreach ($tpl['fields'] as $f) {
                $this->assertNotEmpty($f['label_ar']);
                $this->assertNotEmpty($f['type']);
                $this->assertContains($f['binding_type'], ['canonical', 'custom']);
                if ($f['binding_type'] === 'canonical') {
                    $this->assertNotEmpty($f['binding_target']);
                }
            }
        }
    }

    public function test_apply_customer_contact_template(): void
    {
        $res = StageFieldTemplates::apply($this->stage, 'customer_contact');

        $this->assertGreaterThan(0, $res['added']);
        $this->assertEquals(0, $res['skipped']);

        $fields = PipelineStageField::where('pipeline_stage_id', $this->stage->id)->get();
        $this->assertCount($res['added'], $fields);

        // Check canonical fields were created
        $phoneField = $fields->firstWhere('binding_target', 'phone');
        $this->assertNotNull($phoneField);
        $this->assertTrue($phoneField->isCanonical());
        $this->assertTrue($phoneField->is_required);
    }

    public function test_apply_template_skips_existing_canonical_fields_gracefully(): void
    {
        // First create phone field manually
        PipelineStageField::create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'can_phone_manual',
            'label_ar' => 'رقم الهاتف',
            'binding_type' => 'canonical',
            'binding_target' => 'phone',
            'type' => 'tel',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
        ]);

        $this->assertEquals(1, PipelineStageField::where('pipeline_stage_id', $this->stage->id)->count());

        // Now apply customer_contact template, which also includes phone
        $res = StageFieldTemplates::apply($this->stage, 'customer_contact');

        $this->assertGreaterThan(0, $res['skipped']);
        // Ensure no duplicate canonical phone was added
        $phoneFields = PipelineStageField::where('pipeline_stage_id', $this->stage->id)
            ->where('binding_target', 'phone')
            ->get();
        $this->assertCount(1, $phoneFields);
    }

    public function test_apply_appointment_template_creates_custom_fields_with_keys(): void
    {
        $res = StageFieldTemplates::apply($this->stage, 'appointment');

        $this->assertGreaterThan(0, $res['added']);

        $fields = PipelineStageField::where('pipeline_stage_id', $this->stage->id)->get();
        foreach ($fields as $field) {
            $this->assertNotEmpty($field->key);
            $this->assertTrue($field->is_active);
        }

        $dateField = $fields->firstWhere('type', 'date');
        $this->assertNotNull($dateField);
    }

    public function test_apply_template_via_http_route(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('v2.settings.stages.fields.template', $this->stage), [
                'template_key' => 'quotation',
            ]);

        $response->assertRedirect(route('v2.settings.stages.fields.index', $this->stage));
        $response->assertSessionHas('success');

        $fields = PipelineStageField::where('pipeline_stage_id', $this->stage->id)->get();
        $this->assertGreaterThan(0, $fields->count());
    }

    public function test_apply_invalid_template_key_throws_validation_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('v2.settings.stages.fields.template', $this->stage), [
                'template_key' => 'non_existent_key_12345',
            ]);

        $response->assertSessionHasErrors('template_key');
    }
}
