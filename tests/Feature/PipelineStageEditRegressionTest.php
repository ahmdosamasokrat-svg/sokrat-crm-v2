<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageEditRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private PipelineStage $stage1;
    private PipelineStage $stage2;

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
            [
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'admin_tester_edit',
            'name' => 'Admin Tester Edit',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $regularGroup = Group::query()->firstOrCreate(
            ['code' => 'regular-user-group'],
            [
                'name' => 'مستخدم عادي',
                'is_system' => false,
            ]
        );
        $this->regularUser = User::factory()->create([
            'username' => 'regular_user_edit',
            'name' => 'Regular User Edit',
            'is_active' => true,
        ]);
        $this->regularUser->groups()->attach($regularGroup);

        $this->stage1 = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'name_ar' => 'جديد',
                'description_ar' => 'العملاء الجدد',
                'position' => 1,
                'is_primary' => true,
                'color' => '#3478f6',
                'icon' => 'bi-person-plus',
                'is_active' => true,
            ]
        );

        LeadStatus::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stage1->id, 'name_ar' => 'جديد'],
            [
                'color' => '#3478f6',
                'position' => 1,
                'is_default' => true,
            ]
        );

        $this->stage2 = PipelineStage::query()->firstOrCreate(
            ['code' => 'interested'],
            [
                'name_ar' => 'مهتم',
                'description_ar' => 'عملاء مهتمون',
                'position' => 2,
                'is_primary' => false,
                'color' => '#169a64',
                'icon' => 'bi-hand-thumbs-up',
                'is_active' => true,
            ]
        );

        LeadStatus::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stage2->id, 'name_ar' => 'مهتم'],
            [
                'color' => '#169a64',
                'position' => 1,
                'is_default' => true,
            ]
        );
    }

    public function test_settings_stages_page_renders_edit_controls_and_modal_architecture(): void
    {
        $response = $this->actingAs($this->admin)->get('/settings/stages');

        $response->assertOk();
        $html = $response->getContent();

        // 1. Edit controls rendered in table rows
        $this->assertStringContainsString('openEditModal(', $html);
        $this->assertStringContainsString(__('crm.edit'), $html);

        // 2. Both modals exist in DOM
        $this->assertStringContainsString('id="addStageModal"', $html);
        $this->assertStringContainsString('id="editStageModal"', $html);

        // 3. Verify modal architecture: editStageModal is NOT a child of addStageModal
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $addModalNodes = $xpath->query('//*[@id="addStageModal"]');
        $this->assertEquals(1, $addModalNodes->length, 'addStageModal must exist once in DOM');
        $addModal = $addModalNodes->item(0);

        $editModalNodes = $xpath->query('//*[@id="editStageModal"]');
        $this->assertEquals(1, $editModalNodes->length, 'editStageModal must exist once in DOM');
        $editModal = $editModalNodes->item(0);

        // Crucial regression assert: editStageModal must not be nested inside addStageModal
        $nestedCheck = $xpath->query('.//*[@id="editStageModal"]', $addModal);
        $this->assertEquals(0, $nestedCheck->length, 'editStageModal must NOT be nested inside addStageModal');

        // Verify both are body-level siblings in @stack('modals')
        $this->assertSame($addModal->parentNode, $editModal->parentNode, 'addStageModal and editStageModal must be siblings');
    }

    public function test_authorized_user_can_update_stage_via_edit_route(): void
    {
        $updateData = [
            'name_ar' => 'مهتم جداً',
            'position' => 2,
            'color' => '#10b981',
            'description_ar' => 'عملاء مهتمون جداً بالخدمة',
            'is_active' => '1',
            'icon' => 'bi-star',
        ];

        $response = $this->actingAs($this->admin)
            ->patch("/settings/stages/{$this->stage2->id}", $updateData);

        $response->assertRedirect('/settings/stages');
        $response->assertSessionHas('success');

        $this->stage2->refresh();
        $this->assertSame('مهتم جداً', $this->stage2->name_ar);
        $this->assertSame('#10b981', $this->stage2->color);
        $this->assertSame('عملاء مهتمون جداً بالخدمة', $this->stage2->description_ar);
        $this->assertSame('bi-star', $this->stage2->icon);
    }

    public function test_unauthorized_user_cannot_access_stages_or_update(): void
    {
        // 1. Cannot view settings stages index
        $indexResponse = $this->actingAs($this->regularUser)->get('/settings/stages');
        $indexResponse->assertForbidden();

        // 2. Cannot update stage
        $updateResponse = $this->actingAs($this->regularUser)
            ->patch("/settings/stages/{$this->stage2->id}", [
                'name_ar' => 'اسم غير مصرح',
                'position' => 2,
            ]);
        $updateResponse->assertForbidden();
    }

    public function test_stage_questions_action_remains_intact(): void
    {
        $response = $this->actingAs($this->admin)->get('/settings/stages');
        $response->assertOk();

        // Check Stage Questions button links exist
        $fieldsUrl1 = route('v2.settings.stages.fields.index', $this->stage1);
        $fieldsUrl2 = route('v2.settings.stages.fields.index', $this->stage2);

        $response->assertSee($fieldsUrl1);
        $response->assertSee($fieldsUrl2);

        // Follow link to stage fields
        $fieldsResponse = $this->actingAs($this->admin)->get($fieldsUrl1);
        $fieldsResponse->assertOk();
    }
}
