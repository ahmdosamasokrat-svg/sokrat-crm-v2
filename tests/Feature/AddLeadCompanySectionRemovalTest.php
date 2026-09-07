<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Support\StageFieldSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AddLeadCompanySectionRemovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stage;
    private LeadStatus $status;
    private Lead $lead;

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

        $this->admin = User::query()->where('username', 'admin')->first() ?? User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin User',
            'is_active' => true,
        ]);
        $this->admin->groups()->syncWithoutDetaching([$superAdminGroup->id]);

        $this->stage = PipelineStage::query()->create([
            'code' => 'stage_removal_' . uniqid(),
            'name_ar' => 'مرحلة حذف بيانات الشركة',
            'name_en' => 'Company Removal Stage',
            'position' => 95,
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        $this->status = LeadStatus::query()->create([
            'code' => 'status_removal_' . uniqid(),
            'pipeline_stage_id' => $this->stage->id,
            'name_ar' => 'حالة جديدة مخصصة',
            'position' => 95,
        ]);

        StageFieldSchema::flushCache((int) $this->stage->id);

        $this->lead = Lead::query()->create([
            'first_name' => 'Existing',
            'last_name' => 'Lead',
            'name' => 'Existing Lead',
            'phone' => '0501122334',
            'source' => 'Direct',
            'lead_status_id' => $this->status->id,
            'company_name' => 'Acme Corp',
            'activity' => 'Software',
            'governorate' => 'Cairo',
            'address' => '123 Test St',
            'users_count' => 10,
            'branches_count' => 2,
            'job_title' => 'Manager',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->stage)) {
            StageFieldSchema::flushCache((int) $this->stage->id);
        }
        parent::tearDown();
    }

    /**
     * 1. Add Lead page does NOT render Company/Customer hardcoded section.
     */
    public function test_add_lead_page_does_not_render_company_customer_hardcoded_section(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));

        $response->assertOk();
        $response->assertDontSee('id="businessDetailsSection"', false);
        $response->assertDontSee('بيانات الشركة والمقر والنشاط');
        $response->assertDontSee('id="companyName"', false);
        $response->assertDontSee('id="activity"', false);
        $response->assertDontSee('id="governorate"', false);
        $response->assertDontSee('id="usersCount"', false);
        $response->assertDontSee('id="branchesCount"', false);
        $response->assertDontSee('id="jobTitle"', false);
        $response->assertDontSee('businessSection', false);
        $response->assertDontSee('businessStatuses', false);
    }

    /**
     * 2. Add Lead still renders dynamic PipelineStageField questions.
     */
    public function test_add_lead_still_renders_dynamic_pipeline_stage_field_questions(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'custom_dynamic_q',
            'label_ar' => 'سؤال ديناميكي مخصص',
            'type' => 'text',
            'is_required' => false,
            'is_active' => true,
            'position' => 1,
            'sort_order' => 1,
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.create'));

        $response->assertOk();
        $response->assertSee('stage_q_block_' . $this->stage->id, false);
        $response->assertSee('stage_fields[custom_dynamic_q]', false);
        $response->assertSee('سؤال ديناميكي مخصص');
    }

    /**
     * 3. Dynamic required stage fields still validate.
     */
    public function test_dynamic_required_stage_fields_still_validate(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'required_stage_info',
            'label_ar' => 'معلومة مرحلة إلزامية',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'sort_order' => 1,
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'John',
            'phone' => '0599887766',
            'source' => 'Web',
            'lead_status_id' => $this->status->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [],
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 4. Add Lead submit succeeds with correct dynamic fields.
     */
    public function test_add_lead_submit_succeeds_with_correct_dynamic_fields(): void
    {
        PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage->id,
            'key' => 'company_name',
            'label_ar' => 'اسم الشركة للمرحلة',
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'position' => 1,
            'sort_order' => 1,
            'binding_type' => 'canonical',
            'binding_target' => 'company_name',
        ]);
        StageFieldSchema::flushCache((int) $this->stage->id);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '0599112233',
            'source' => 'Campaign',
            'lead_status_id' => $this->status->id,
            'next_follow_up_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'stage_fields' => [
                'company_name' => 'Dynamic Enterprise LLC',
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('v2.leads'));

        $lead = Lead::query()->where('phone', '0599112233')->first();
        $this->assertNotNull($lead);
        $this->assertSame('Dynamic Enterprise LLC', $lead->company_name);
    }

    /**
     * 5. Edit Lead remains unchanged and renders company data section.
     */
    public function test_edit_lead_remains_unchanged(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.edit', $this->lead));

        $response->assertOk();
        $response->assertSee('id="businessDetailsSection"', false);
        $response->assertSee(__('crm.company_lead_data'));
        $response->assertSee('name="company_name"', false);
        $response->assertSee('name="activity"', false);
    }

    /**
     * 6. Lead Profile remains unchanged.
     */
    public function test_lead_profile_remains_unchanged(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $this->lead));

        $response->assertOk();
        $response->assertSee($this->lead->name);
        $response->assertSee($this->lead->phone);
    }

    /**
     * 7. No database schema changes.
     */
    public function test_no_database_schema_changes(): void
    {
        $this->assertTrue(Schema::hasTable('leads'));
        $this->assertTrue(Schema::hasColumn('leads', 'company_name'));
        $this->assertTrue(Schema::hasColumn('leads', 'activity'));
        $this->assertTrue(Schema::hasColumn('leads', 'governorate'));
        $this->assertTrue(Schema::hasColumn('leads', 'address'));
        $this->assertTrue(Schema::hasColumn('leads', 'users_count'));
        $this->assertTrue(Schema::hasColumn('leads', 'branches_count'));
        $this->assertTrue(Schema::hasColumn('leads', 'job_title'));
    }

    /**
     * 8. No legacy company/customer card markup remains on /leads/create.
     */
    public function test_no_legacy_company_customer_card_markup_remains_on_create_view(): void
    {
        $this->actingAs($this->admin);

        $content = view('leads.create', [
            'statuses' => collect([$this->status]),
            'statusGroups' => collect(['البداية' => collect([$this->status])]),
            'activeStages' => collect([$this->stage->fresh()->load('activeFields')]),
            'sources' => collect(['Direct']),
            'assignedEmployee' => 'Admin User',
            'canAssignLead' => true,
            'assignableUsers' => collect([$this->admin]),
            'totalLeads' => 1,
            'campaign' => null,
            'campaigns' => collect(),
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringNotContainsString('id="businessDetailsSection"', $content);
        $this->assertStringNotContainsString('بيانات الشركة والمقر والنشاط', $content);
        $this->assertStringNotContainsString('id="companyName"', $content);
        $this->assertStringNotContainsString('name="company_name"', $content);
        $this->assertStringNotContainsString('name="activity"', $content);
        $this->assertStringNotContainsString('name="governorate"', $content);
        $this->assertStringNotContainsString('name="users_count"', $content);
        $this->assertStringNotContainsString('name="branches_count"', $content);
        $this->assertStringNotContainsString('name="job_title"', $content);
        $this->assertStringNotContainsString('businessSection', $content);
    }
}
