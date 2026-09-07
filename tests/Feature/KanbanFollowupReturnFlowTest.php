<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\PipelineMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KanbanFollowupReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageNew;
    private PipelineStage $stageExecution;
    private PipelineStage $stageNoAnswer;
    private PipelineStage $stageQuotation;
    private LeadStatus $statusNew;
    private LeadStatus $statusExecution;
    private LeadStatus $statusNoAnswer;
    private LeadStatus $statusQuotation;
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'admin_flow_' . uniqid(),
            'name' => 'Admin Flow User',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);
        $this->stageNew = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'is_active' => true, 'is_default' => true]
        );
        $this->statusNew = $this->stageNew->ensureDefaultStatus();

        $this->stageExecution = PipelineStage::query()->firstOrCreate(
            ['code' => 'discussion'],
            ['name_ar' => 'التنفيذ', 'position' => 2, 'is_active' => true, 'is_default' => false]
        );
        $this->statusExecution = $this->stageExecution->ensureDefaultStatus();

        $this->stageNoAnswer = PipelineStage::query()->firstOrCreate(
            ['code' => 'no_answer'],
            ['name_ar' => 'لم يرد', 'position' => 3, 'is_active' => true, 'is_default' => false]
        );
        $this->statusNoAnswer = $this->stageNoAnswer->ensureDefaultStatus();
        // Add callback_at field to no_answer stage if missing
        PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stageNoAnswer->id, 'key' => 'callback_at'],
            [
                'label_ar' => 'موعد إعادة الاتصال',
                'type' => 'datetime',
                'is_required' => true,
                'is_active' => true,
                'position' => 1,
            ]
        );

        $this->stageQuotation = PipelineStage::query()->firstOrCreate(
            ['code' => 'quotation'],
            ['name_ar' => 'عرض سعر', 'position' => 4, 'is_active' => true, 'is_default' => false]
        );
        $this->statusQuotation = $this->stageQuotation->ensureDefaultStatus();
        // Add quotation fields if missing
        PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stageQuotation->id, 'key' => 'client_type'],
            [
                'label_ar' => 'نوع العميل',
                'type' => 'select',
                'is_required' => true,
                'is_active' => true,
                'options' => [
                    ['value' => 'individual', 'label_ar' => 'فرد'],
                    ['value' => 'company', 'label_ar' => 'شركة'],
                ],
            ]
        );
        PipelineStageField::query()->firstOrCreate(
            ['pipeline_stage_id' => $this->stageQuotation->id, 'key' => 'quotation_pdf'],
            [
                'label_ar' => 'ملف عرض السعر',
                'type' => 'file',
                'is_required' => true,
                'is_active' => true,
                'position' => 2,
            ]
        );

        \App\Support\StageFieldSchema::flushCache(0);
        PipelineMappingService::clearCache();

        $this->lead = Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'عميل تدفق كانبان',
            'phone' => '0501234567',
            'source' => 'web',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);
    }

    protected function tearDown(): void
    {
        \App\Support\StageFieldSchema::flushCache(0);
        PipelineMappingService::clearCache();
        parent::tearDown();
    }

    /** 1. Kanban popup valid submit succeeds */
    public function test_1_kanban_popup_valid_submit_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '1',
                'lead_status_id' => $this->statusExecution->id,
                'communication_type' => 'call',
                'outcome' => 'تم الاتفاق مع العميل وبدء مرحلة التنفيذ',
                'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->lead->refresh();
        $this->assertSame($this->statusExecution->id, $this->lead->lead_status_id);
    }

    /** 2. Kanban popup success redirects to followup index with kanban_popup=1 & saved=1 */
    public function test_2_kanban_popup_success_redirects_with_popup_context(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '1',
                'lead_status_id' => $this->statusExecution->id,
                'communication_type' => 'call',
                'outcome' => 'متابعة منبثقة ناجحة',
                'next_follow_up_at' => now()->addDays(3)->format('Y-m-d H:i'),
            ]
        );

        $response->assertRedirect(
            route('v2.leads.followups.index', [
                'lead' => $this->lead->id,
                'kanban_popup' => 1,
                'saved' => 1,
            ])
        );
    }

    /** 3. Followup view with kanban_popup=1 and saved=1 emits redirectUrl = route('v2.leads.kanban') */
    public function test_3_followup_view_emits_kanban_destination_url(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('v2.leads.followups.index', [
                'lead' => $this->lead->id,
                'kanban_popup' => 1,
                'saved' => 1,
            ])
        );

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('redirectUrl: successRedirectUrl', $content);
        $this->assertStringContainsString('leads\/kanban', $content);
        $this->assertStringContainsString('isKanbanPopup ? ', $content);
    }

    /** 4. Non-popup followup success redirects to Leads index */
    public function test_4_non_popup_followup_success_redirects_to_leads_index(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '0',
                'lead_status_id' => $this->statusExecution->id,
                'communication_type' => 'call',
                'outcome' => 'متابعة عادية كاملة',
                'next_follow_up_at' => now()->addDays(3)->format('Y-m-d H:i'),
            ]
        );

        $response->assertRedirect(route('v2.leads'));
    }

    /** 5. Execution stage ("التنفيذ") valid submit succeeds without requiring legacy fields */
    public function test_5_execution_stage_succeeds_without_legacy_quotation_fields(): void
    {
        // Stage Execution (code: discussion) has no fields in PipelineStageField
        $this->assertCount(0, $this->stageExecution->fields);

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '1',
                'lead_status_id' => $this->statusExecution->id,
                'communication_type' => 'other',
                'outcome' => 'متابعة تنفيذية مباشرة',
                'next_follow_up_at' => now()->addDays(2)->format('Y-m-d H:i'),
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->lead->refresh();
        $this->assertSame($this->statusExecution->id, $this->lead->lead_status_id);
    }

    /** 6. No Answer ("لم يرد") callback_at valid submit */
    public function test_6_no_answer_stage_with_callback_at_succeeds(): void
    {
        $callbackDate = now()->addDays(1)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '1',
                'lead_status_id' => $this->statusNoAnswer->id,
                'communication_type' => 'call',
                'outcome' => 'لم يرد العميل وتم تحديد موعد اتصال',
                'stage_fields' => [
                    'callback_at' => $callbackDate,
                ],
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->lead->refresh();
        $this->assertSame($this->statusNoAnswer->id, $this->lead->lead_status_id);
        $this->assertNotNull($this->lead->next_follow_up_at);
    }

    /** 7. Quotation stage valid submit with dynamic fields */
    public function test_7_quotation_stage_with_dynamic_fields_succeeds(): void
    {
        $fakePdf = UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '1',
                'lead_status_id' => $this->statusQuotation->id,
                'communication_type' => 'call',
                'outcome' => 'تم إرسال عرض السعر للعميل',
                'next_follow_up_at' => now()->addDays(3)->format('Y-m-d H:i'),
                'stage_fields' => [
                    'client_type' => 'company',
                    'quotation_pdf' => $fakePdf,
                ],
            ]
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->lead->refresh();
        $this->assertSame($this->statusQuotation->id, $this->lead->lead_status_id);
    }

    /** 8. Single follow-up and single history created (no duplicates) */
    public function test_8_single_followup_and_single_history_created(): void
    {
        $initialFollowupCount = LeadFollowup::query()->where('lead_id', $this->lead->id)->count();
        $initialHistoryCount = LeadStatusHistory::query()->where('lead_id', $this->lead->id)->count();

        $response = $this->actingAs($this->admin)->post(
            route('v2.leads.followups.store', $this->lead),
            [
                'kanban_popup' => '1',
                'lead_status_id' => $this->statusExecution->id,
                'communication_type' => 'meeting',
                'outcome' => 'اجتماع وبدء تنفيذ العقد',
                'next_follow_up_at' => now()->addDays(4)->format('Y-m-d H:i'),
            ]
        );

        $response->assertSessionHasNoErrors();

        $newFollowupCount = LeadFollowup::query()->where('lead_id', $this->lead->id)->count();
        $newHistoryCount = LeadStatusHistory::query()->where('lead_id', $this->lead->id)->count();

        $this->assertSame($initialFollowupCount + 1, $newFollowupCount);
        $this->assertSame($initialHistoryCount + 1, $newHistoryCount);
    }
}
