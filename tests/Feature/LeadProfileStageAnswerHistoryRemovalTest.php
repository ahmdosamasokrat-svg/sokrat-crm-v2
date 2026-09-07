<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\LeadFollowup;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadProfileStageAnswerHistoryRemovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $stageNew;
    private PipelineStage $stageQuotation;
    private LeadStatus $statusNew;
    private LeadStatus $statusQuotation;

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
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'profile_admin_' . uniqid(),
            'name' => 'Admin Profile Tester',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stageNew = PipelineStage::query()->create([
            'code' => 'stage_new_' . uniqid(),
            'name_ar' => 'جديد',
            'position' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->statusNew = $this->stageNew->ensureDefaultStatus();

        $this->stageQuotation = PipelineStage::query()->create([
            'code' => 'stage_quotation_' . uniqid(),
            'name_ar' => 'عرض سعر',
            'position' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->statusQuotation = $this->stageQuotation->ensureDefaultStatus();
    }

    /** 1. Lead Profile does NOT render standalone Stage Question Answer History section */
    public function test_lead_profile_does_not_render_standalone_stage_question_answer_history_card(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'عميل اختبار إزالة القسم المكرر',
            'assigned_user_id' => $this->admin->id,
        ]);

        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageNew->id,
            'key' => 'customer_type',
            'label_ar' => 'نوع العميل',
            'type' => 'text',
            'show_in_history' => true,
        ]);

        LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageNew->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => $field->key,
            'value' => 'شركة تجارية كبرى',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $lead));

        $response->assertOk();
        $html = $response->getContent();

        // Must NOT render standalone section header
        $this->assertStringNotContainsString('سجل إجابات أسئلة المراحل', $html);
        $this->assertStringNotContainsString('stage_history_section_title', $html);

        // Still renders customer details and timeline
        $this->assertStringContainsString('عميل اختبار إزالة القسم المكرر', $html);
        $this->assertStringContainsString('سجل زمني لجميع المتابعات وتغييرات الحالات', $html);
    }

    /** 2. Activity Timeline still renders stage transitions and stage-question answers */
    public function test_activity_timeline_still_renders_stage_transitions_and_question_answers(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusQuotation->id,
            'name' => 'عميل فحص الجدول الزمني والأسئلة',
            'assigned_user_id' => $this->admin->id,
        ]);

        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'organization_name',
            'label_ar' => 'اسم المؤسسة',
            'type' => 'text',
            'show_in_history' => true,
        ]);

        $history = LeadStatusHistory::query()->create([
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusNew->id,
            'to_status_id' => $this->statusQuotation->id,
            'changed_by' => 'مدير النظام',
            'changed_by_user_id' => $this->admin->id,
            'note' => 'تم الانتقال إلى مرحلة عرض السعر',
            'changed_at' => now(),
        ]);

        LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageQuotation->id,
            'pipeline_stage_field_id' => $field->id,
            'lead_status_history_id' => $history->id,
            'field_key' => $field->key,
            'value' => 'مؤسسة التقنية المتقدمة',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $lead));

        $response->assertOk();
        $html = $response->getContent();

        // Standalone section is gone
        $this->assertStringNotContainsString('سجل إجابات أسئلة المراحل', $html);

        // Timeline entry is rendered with transition note and stage question answers
        $this->assertStringContainsString('تم الانتقال إلى مرحلة عرض السعر', $html);
        $this->assertStringContainsString('إجابات أسئلة المرحلة', $html);
        $this->assertStringContainsString('اسم المؤسسة', $html);
        $this->assertStringContainsString('مؤسسة التقنية المتقدمة', $html);
    }

    /** 3. Timeline still renders quotation/file answers */
    public function test_activity_timeline_still_renders_quotation_and_file_answers(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusQuotation->id,
            'name' => 'عميل فحص مستندات عرض السعر',
            'assigned_user_id' => $this->admin->id,
        ]);

        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageQuotation->id,
            'key' => 'quotation_file',
            'label_ar' => 'رفع عرض السعر',
            'type' => 'file',
            'show_in_history' => true,
        ]);

        $history = LeadStatusHistory::query()->create([
            'lead_id' => $lead->id,
            'from_status_id' => $this->statusNew->id,
            'to_status_id' => $this->statusQuotation->id,
            'changed_by' => 'مدير النظام',
            'changed_by_user_id' => $this->admin->id,
            'note' => 'تم رفع عرض السعر بنجاح',
            'changed_at' => now(),
        ]);

        LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageQuotation->id,
            'pipeline_stage_field_id' => $field->id,
            'lead_status_history_id' => $history->id,
            'field_key' => $field->key,
            'value' => 'quotations/sample_offer.pdf',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.show', $lead));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('سجل إجابات أسئلة المراحل', $html);
        $this->assertStringContainsString('رفع عرض السعر', $html);
        $this->assertStringContainsString('إجابات أسئلة المرحلة', $html);
    }

    /** 4. LeadStageFieldValue and LeadStatusHistory records remain completely untouched in DB */
    public function test_historical_records_remain_intact_in_database(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'عميل الحفاظ على البيانات',
        ]);

        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stageNew->id,
            'key' => 'notes_question',
            'label_ar' => 'ملاحظات أولية',
            'type' => 'textarea',
            'show_in_history' => true,
        ]);

        $history = LeadStatusHistory::query()->create([
            'lead_id' => $lead->id,
            'from_status_id' => null,
            'to_status_id' => $this->statusNew->id,
            'changed_by' => 'System',
            'changed_at' => now(),
        ]);

        $val = LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stageNew->id,
            'pipeline_stage_field_id' => $field->id,
            'lead_status_history_id' => $history->id,
            'field_key' => $field->key,
            'value' => 'بيانات تاريخية لا يجب مسحها أبداً',
        ]);

        $this->actingAs($this->admin)->get(route('v2.leads.show', $lead))->assertOk();

        // Assert database records still exist untouched
        $this->assertDatabaseHas('lead_status_histories', ['id' => $history->id]);
        $this->assertDatabaseHas('lead_stage_field_values', ['id' => $val->id, 'value' => 'بيانات تاريخية لا يجب مسحها أبداً']);
    }
}
