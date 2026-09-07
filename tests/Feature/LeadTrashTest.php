<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\LeadFollowup;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadTrashTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $agent;
    private User $otherAgent;
    private PipelineStage $stage1;
    private PipelineStage $stage2;
    private LeadStatus $status1;
    private LeadStatus $status2;

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

        // 1. Super Admin Group
        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => 'super-admin'],
            ['name' => 'مدير النظام', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'username' => 'trash_admin_' . uniqid(),
            'name' => 'Trash Super Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // 2. Sales Manager Group (can view & restore trash, cannot force delete)
        $managerGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-manager'],
            ['name' => 'مدير المبيعات', 'is_system' => false]
        );
        $managerGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_SCOPE_ALL->value,
                CrmPermission::LEADS_DELETE->value,
                CrmPermission::LEADS_TRASH_VIEW->value,
                CrmPermission::LEADS_TRASH_RESTORE->value,
                CrmPermission::SETTINGS_ACCESS->value,
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::TASKS_VIEW->value,
            ])->pluck('id')
        );

        $this->manager = User::factory()->create([
            'username' => 'trash_mgr_' . uniqid(),
            'name' => 'Sales Manager',
            'is_active' => true,
        ]);
        $this->manager->groups()->attach($managerGroup);

        // 3. Sales Agent (No trash permissions)
        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agent'],
            ['name' => 'موظف مبيعات', 'is_system' => false]
        );
        $agentGroup->permissions()->sync(
            Permission::whereIn('code', [
                CrmPermission::LEADS_VIEW->value,
                CrmPermission::LEADS_DELETE->value,
                CrmPermission::DASHBOARD_VIEW->value,
                CrmPermission::TASKS_VIEW->value,
            ])->pluck('id')
        );

        $this->agent = User::factory()->create([
            'username' => 'trash_agent_' . uniqid(),
            'name' => 'Sales Agent 1',
            'is_active' => true,
        ]);
        $this->agent->groups()->attach($agentGroup);

        $this->otherAgent = User::factory()->create([
            'username' => 'trash_agent2_' . uniqid(),
            'name' => 'Sales Agent 2',
            'is_active' => true,
        ]);
        $this->otherAgent->groups()->attach($agentGroup);

        // Stages
        $this->stage1 = PipelineStage::query()->create([
            'code' => 'trash_stage_1_' . uniqid(),
            'name_ar' => 'المرحلة الأولى',
            'position' => 1,
            'is_active' => true,
            'is_default' => true,
        ]);
        $this->status1 = $this->stage1->ensureDefaultStatus();

        $this->stage2 = PipelineStage::query()->create([
            'code' => 'trash_stage_2_' . uniqid(),
            'name_ar' => 'المرحلة الثانية',
            'position' => 2,
            'is_active' => true,
            'is_default' => false,
        ]);
        $this->status2 = $this->stage2->ensureDefaultStatus();
    }

    /** 16. Lead soft delete sets deleted_at and metadata */
    public function test_16_lead_soft_delete_sets_deleted_at_and_metadata(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل اختبار الحذف الناعم',
            'assigned_user_id' => $this->agent->id,
        ]);

        $response = $this->actingAs($this->admin)->delete("/leads/{$lead->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);

        $lead->refresh();
        $this->assertNotNull($lead->deleted_at);
        $this->assertEquals($this->admin->id, $lead->deleted_by_user_id);
        $this->assertEquals($this->stage1->id, $lead->deleted_from_stage_id);
    }

    /** 17. Lead disappears from Leads index */
    public function test_17_lead_disappears_from_leads_index(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل محذوف مخفي من الفهرس',
        ]);

        $lead->delete();

        $response = $this->actingAs($this->admin)->get('/leads');

        $response->assertOk();
        $response->assertDontSee('عميل محذوف مخفي من الفهرس');
    }

    /** 18. Lead disappears from Kanban */
    public function test_18_lead_disappears_from_kanban(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل محذوف مخفي من الكانبان',
        ]);

        $lead->delete();

        $response = $this->actingAs($this->admin)->get('/leads/kanban');

        $response->assertOk();
        $response->assertDontSee('عميل محذوف مخفي من الكانبان');
    }

    /** 19. Dashboard counts exclude trashed Lead */
    public function test_19_dashboard_counts_exclude_trashed_lead(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل محذوف لتقرير لوحة التحكم',
            'assigned_user_id' => $this->admin->id,
        ]);

        $countBefore = Lead::query()->accessibleTo($this->admin)->count();
        $this->assertEquals(1, $countBefore);

        $lead->delete();

        $countAfter = Lead::query()->accessibleTo($this->admin)->count();
        $this->assertEquals(0, $countAfter);

        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertOk();
    }

    /** 20. Daily Tasks exclude trashed Lead */
    public function test_20_daily_tasks_exclude_trashed_lead(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل محذوف للمهام اليومية',
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addHour(),
        ]);

        $lead->delete();

        $response = $this->actingAs($this->admin)->get('/followups');
        $response->assertOk();
        $response->assertDontSee('عميل محذوف للمهام اليومية');
    }

    /** 21. Trash index shows deleted Lead */
    public function test_21_trash_index_shows_deleted_lead(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل في سلة المهملات',
        ]);

        $lead->deleted_by_user_id = $this->admin->id;
        $lead->deleted_from_stage_id = $this->stage1->id;
        $lead->delete();

        $response = $this->actingAs($this->admin)->get('/leads/trash');

        $response->assertOk();
        $response->assertSee('عميل في سلة المهملات');
        $response->assertSee($this->stage1->name_ar);
        $response->assertDontSee('<i class="bi bi-people"></i> ' . __('crm.view_leads'), false);
    }

    /** 22. Unauthorized user cannot view trash */
    public function test_22_unauthorized_user_cannot_view_trash(): void
    {
        $response = $this->actingAs($this->agent)->get('/leads/trash');

        $response->assertForbidden();
    }

    /** 23. Restore works */
    public function test_23_restore_works(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل قابل للاستعادة',
        ]);
        $lead->delete();

        $response = $this->actingAs($this->manager)->post("/leads/trash/{$lead->id}/restore");

        $response->assertRedirect('/leads/trash');

        $lead->refresh();
        $this->assertFalse($lead->trashed());
        $this->assertNull($lead->deleted_at);
        $this->assertNull($lead->deleted_by_user_id);
    }

    /** 24. Restore preserves documents */
    public function test_24_restore_preserves_documents(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل مستندات',
        ]);

        $doc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage1->id,
            'category' => LeadDocument::CATEGORY_ATTACHMENT,
            'original_name' => 'test.pdf',
            'stored_name' => 'test_' . uniqid() . '.pdf',
            'path' => 'documents/test.pdf',
            'disk' => 'local',
            'size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $lead->delete();
        $this->assertDatabaseHas('lead_documents', ['id' => $doc->id]);

        $this->actingAs($this->admin)->post("/leads/trash/{$lead->id}/restore");

        $lead->refresh();
        $this->assertEquals(1, $lead->documents()->count());
        $this->assertDatabaseHas('lead_documents', ['id' => $doc->id]);
    }

    /** 25. Restore preserves followups */
    public function test_25_restore_preserves_followups(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل متابعات',
        ]);

        $followup = LeadFollowup::query()->create([
            'lead_id' => $lead->id,
            'from_status_id' => $this->status1->id,
            'to_status_id' => $this->status1->id,
            'employee_name' => 'موظف تجريبي',
            'communication_type' => 'call',
            'outcome' => 'متابعة ناجحة',
            'followed_up_at' => now(),
        ]);

        $lead->delete();
        $this->actingAs($this->admin)->post("/leads/trash/{$lead->id}/restore");

        $lead->refresh();
        $this->assertEquals(1, $lead->followups()->count());
        $this->assertDatabaseHas('lead_followups', ['id' => $followup->id]);
    }

    /** 26. Restore preserves stage values */
    public function test_26_restore_preserves_stage_values(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل قيم المرحلة',
        ]);

        $field = PipelineStageField::query()->create([
            'pipeline_stage_id' => $this->stage1->id,
            'key' => 'custom_project_type',
            'label_ar' => 'نوع المشروع',
            'type' => 'text',
        ]);

        $stageVal = LeadStageFieldValue::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage1->id,
            'pipeline_stage_field_id' => $field->id,
            'field_key' => $field->key,
            'value' => 'مستودع تجاري',
        ]);

        $lead->delete();
        $this->actingAs($this->admin)->post("/leads/trash/{$lead->id}/restore");

        $lead->refresh();
        $this->assertEquals(1, $lead->stageValues()->count());
        $this->assertDatabaseHas('lead_stage_field_values', ['id' => $stageVal->id]);
    }

    /** 27. Restore to valid previous stage */
    public function test_27_restore_to_valid_previous_stage(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status2->id,
            'name' => 'عميل استعادة للمرحلة السابقة',
        ]);
        $lead->delete();

        $this->actingAs($this->admin)->post("/leads/trash/{$lead->id}/restore");

        $lead->refresh();
        $this->assertEquals($this->status2->id, $lead->lead_status_id);
    }

    /** 28. Restore requires new stage if original removed */
    public function test_28_restore_requires_new_stage_if_original_removed(): void
    {
        $tempStage = PipelineStage::query()->create([
            'code' => 'temp_stage_' . uniqid(),
            'name_ar' => 'مرحلة مؤقتة سيتم حذفها',
            'position' => 10,
            'is_active' => true,
        ]);
        $tempStatus = $tempStage->ensureDefaultStatus();

        $lead = Lead::query()->create([
            'lead_status_id' => $tempStatus->id,
            'name' => 'عميل المرحلة المحذوفة',
        ]);
        $lead->delete();

        // Delete the temp stage
        $tempStage->delete();

        // Restoring should automatically fall back to default stage or accept requested destination
        $this->actingAs($this->admin)->post("/leads/trash/{$lead->id}/restore", [
            'destination_stage_id' => $this->stage2->id,
        ]);

        $lead->refresh();
        $this->assertFalse($lead->trashed());
        $this->assertEquals($this->status2->id, $lead->lead_status_id);
    }

    /** 29. Force delete requires permission */
    public function test_29_force_delete_requires_permission(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل للحذف النهائي المصرح',
        ]);
        $lead->delete();

        $response = $this->actingAs($this->admin)->delete("/leads/trash/{$lead->id}/force-delete");

        $response->assertRedirect('/leads/trash');
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    /** 30. Unauthorized force delete denied */
    public function test_30_unauthorized_force_delete_denied(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل للحذف النهائي غير المصرح',
        ]);
        $lead->delete();

        // Sales Manager does NOT have leads.trash.force_delete
        $response = $this->actingAs($this->manager)->delete("/leads/trash/{$lead->id}/force-delete");

        $response->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    /** 31. Trash count scoped */
    public function test_31_trash_count_scoped(): void
    {
        $leadOwn = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل خاص بالوكيل',
            'assigned_user_id' => $this->agent->id,
        ]);
        $leadOwn->delete();

        $leadOther = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل خاص بوكيل آخر',
            'assigned_user_id' => $this->otherAgent->id,
        ]);
        $leadOther->delete();

        // Manager has LEADS_SCOPE_ALL -> sees 2
        $this->assertEquals(2, Lead::onlyTrashed()->accessibleTo($this->manager)->count());

        // Agent has individual scope -> only sees 1
        $this->assertEquals(1, Lead::onlyTrashed()->accessibleTo($this->agent)->count());
    }

    /** 32. Foreign/user-inaccessible deleted Lead remains inaccessible */
    public function test_32_foreign_deleted_lead_remains_inaccessible(): void
    {
        $leadOther = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل غير متاح للمحاولة',
            'assigned_user_id' => $this->otherAgent->id,
        ]);
        $leadOther->delete();

        $this->assertFalse($leadOther->isAccessibleTo($this->agent));
    }

    /** 35. Trashed lead document accessible to trash manager, denied to normal agent */
    public function test_35_trashed_lead_document_access_permissions(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/contract.pdf', 'dummy-contract-content');

        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل فحص المستندات',
            'assigned_user_id' => $this->agent->id,
        ]);

        $doc = LeadDocument::query()->create([
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $this->stage1->id,
            'category' => LeadDocument::CATEGORY_PDF,
            'original_name' => 'contract.pdf',
            'stored_name' => 'contract_' . uniqid() . '.pdf',
            'path' => 'documents/contract.pdf',
            'disk' => 'local',
            'size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $lead->delete();

        // Agent has leads.view, but does NOT have leads.trash.view -> should be 403
        $responseAgent = $this->actingAs($this->agent)
            ->get("/leads/{$lead->id}/documents/{$doc->id}/download");
        $responseAgent->assertForbidden();

        // Manager has leads.trash.view -> should be allowed
        $responseManager = $this->actingAs($this->manager)
            ->get("/leads/{$lead->id}/documents/{$doc->id}/download");
        $responseManager->assertOk();
    }

    /** 33. Bulk restore works */
    public function test_33_bulk_restore_works(): void
    {
        $lead1 = Lead::query()->create(['lead_status_id' => $this->status1->id, 'name' => 'عميل استعادة جماعية 1']);
        $lead2 = Lead::query()->create(['lead_status_id' => $this->status1->id, 'name' => 'عميل استعادة جماعية 2']);
        $lead1->delete();
        $lead2->delete();

        $response = $this->actingAs($this->manager)->post('/leads/trash/bulk-restore', [
            'lead_ids' => [$lead1->id, $lead2->id],
        ]);

        $response->assertRedirect('/leads/trash');
        $this->assertFalse($lead1->fresh()->trashed());
        $this->assertFalse($lead2->fresh()->trashed());
    }

    /** 34. Bulk force delete works */
    public function test_34_bulk_force_delete_works(): void
    {
        $lead1 = Lead::query()->create(['lead_status_id' => $this->status1->id, 'name' => 'عميل حذف نهائي 1']);
        $lead2 = Lead::query()->create(['lead_status_id' => $this->status1->id, 'name' => 'عميل حذف نهائي 2']);
        $lead1->delete();
        $lead2->delete();

        $response = $this->actingAs($this->admin)->post('/leads/trash/bulk-force-delete', [
            'lead_ids' => [$lead1->id, $lead2->id],
        ]);

        $response->assertRedirect('/leads/trash');
        $this->assertDatabaseMissing('leads', ['id' => $lead1->id]);
        $this->assertDatabaseMissing('leads', ['id' => $lead2->id]);
    }

    /** 36. Deleted Stage and child Status does not hide Trash Lead (Part 22) */
    public function test_36_deleted_stage_and_status_does_not_hide_trash_lead(): void
    {
        $stageDeleted = PipelineStage::query()->create([
            'code' => 'stage_repro_' . uniqid(),
            'name_ar' => 'مرحلة حذف واختفاء',
            'position' => 12,
            'is_active' => true,
        ]);
        $statusDeleted = $stageDeleted->ensureDefaultStatus();

        $lead = Lead::query()->create([
            'lead_status_id' => $statusDeleted->id,
            'name' => 'عميل حادثة الاختفاء',
        ]);

        $deletionService = app(\App\Services\StageDeletionService::class);
        $deletionService->deleteStage($stageDeleted, $this->admin, ['lead_action' => 'trash']);

        $this->assertSoftDeleted('pipeline_stages', ['id' => $stageDeleted->id]);
        $this->assertSoftDeleted('lead_statuses', ['id' => $statusDeleted->id]);
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);

        // Open trash query
        $trashService = app(\App\Services\LeadTrashService::class);
        $trashedLeads = $trashService->getTrashQuery($this->admin)->get();

        $this->assertTrue($trashedLeads->contains('id', $lead->id));

        // Check trash index page
        $response = $this->actingAs($this->admin)->get('/leads/trash');
        $response->assertOk();
        $response->assertSee('عميل حادثة الاختفاء');
        $response->assertSee($stageDeleted->name_ar);
    }

    /** 37. Trash count equals query count */
    public function test_37_trash_count_equals_query_count(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->status1->id,
            'name' => 'عميل فحص التطابق',
            'assigned_user_id' => $this->manager->id,
        ]);
        $lead->delete();

        $trashService = app(\App\Services\LeadTrashService::class);
        $count = $trashService->getTrashCount($this->manager);
        $queryCount = $trashService->getTrashQuery($this->manager)->count();

        $this->assertEquals($count, $queryCount);
    }

    /** 38. Trash page renders without raw translation keys (Part 18) */
    public function test_38_trash_page_renders_without_raw_translation_keys(): void
    {
        $response = $this->actingAs($this->admin)->get('/leads/trash');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString('crm.filter', $html);
        $this->assertStringNotContainsString('crm.search_placeholder', $html);
        $this->assertStringNotContainsString('crm.customer', $html);
    }

    /** 39. deletedFromStage renders withTrashed */
    public function test_39_deleted_from_stage_renders_with_trashed_stage(): void
    {
        $stageOld = PipelineStage::query()->create([
            'code' => 'stage_old_' . uniqid(),
            'name_ar' => 'المرحلة السابقة المحذوفة',
            'position' => 14,
            'is_active' => true,
        ]);
        $statusOld = $stageOld->ensureDefaultStatus();

        $lead = Lead::query()->create([
            'lead_status_id' => $statusOld->id,
            'name' => 'عميل اختبار المرحلة السابقة',
        ]);

        $lead->deleted_from_stage_id = $stageOld->id;
        $lead->save();
        $lead->delete();
        $stageOld->delete();

        $lead->refresh();
        $this->assertNotNull($lead->deletedFromStage);
        $this->assertEquals($stageOld->id, $lead->deletedFromStage->id);
        $this->assertTrue($lead->deletedFromStage->trashed());
    }
}
