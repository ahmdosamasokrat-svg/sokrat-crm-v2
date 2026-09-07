<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LeadBulkActionsRemovalTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private PipelineStage $stage;
    private LeadStatus $status;
    private Lead $lead1;
    private Lead $lead2;

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
            ['name' => 'مدير النظام', 'description' => 'Super Admin', 'is_system' => true]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->stage = PipelineStage::query()->firstWhere('code', 'new') ?? PipelineStage::query()->first();
        $this->status = $this->stage->statuses()->first();

        $this->lead1 = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'Bulk Removal Lead 1',
            'phone' => '0501110001',
            'source' => 'web',
        ]);

        $this->lead2 = Lead::query()->create([
            'lead_status_id' => $this->status->id,
            'name' => 'Bulk Removal Lead 2',
            'phone' => '0501110002',
            'source' => 'web',
        ]);
    }

    /** 1. Leads Index has bulk toolbar container */
    public function test_leads_index_has_bulk_toolbar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="bulkActionsBar"', false);
        $response->assertSee('class="bulk-actions-bar"', false);
    }

    /** 2. Has Clear Selection action */
    public function test_has_clear_selection_action(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="bulkClearSelection"', false);
    }

    /** 3. Has Reassign To control */
    public function test_has_reassign_to_control(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="bulkAssignUserSelect"', false);
        $response->assertSee('إعادة تعيين إلى...', false);
    }

    /** 4. Has Reassign action button */
    public function test_has_reassign_action_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="bulkAssignButton"', false);
    }

    /** 5. Has Delete bulk action */
    public function test_has_delete_bulk_action(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="bulkDeleteButton"', false);
    }

    /** 6. Bulk Export action and button */
    public function test_bulk_export_action(): void
    {
        $viewResponse = $this->actingAs($this->admin)->get(route('v2.leads'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('id="bulkExportButton"', false);

        $response = $this->actingAs($this->admin)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$this->lead1->id],
        ]);

        $response->assertOk();
    }

    /** 7. Has select-all header checkbox */
    public function test_has_select_all_header_checkbox(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('id="selectAllLeads"', false);
        $response->assertSee('class="lead-select-all"', false);
    }

    /** 8. Has row checkboxes */
    public function test_has_row_checkboxes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads'));

        $response->assertOk();
        $response->assertSee('class="lead-select-checkbox"', false);
        $response->assertSee('name="lead_ids[]"', false);
    }

    /** 9. Filters still work */
    public function test_filters_still_work(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', ['q' => 'Bulk Removal Lead 1']));

        $response->assertOk();
        $response->assertSee('Bulk Removal Lead 1');
        $response->assertDontSee('Bulk Removal Lead 2');
    }

    /** 10. Column chooser still works */
    public function test_column_chooser_still_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', ['columns' => 'core:contact,core:company_source']));

        $response->assertOk();
        $visibleColumns = $response->viewData('visibleColumns');
        $keys = array_column($visibleColumns, 'key');

        $this->assertContains('core:contact', $keys);
        $this->assertContains('core:company_source', $keys);
        $this->assertNotContains('core:employee', $keys);
    }

    /** 11. Pagination still works */
    public function test_pagination_still_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.leads', ['page' => 1]));

        $response->assertOk();
        $this->assertNotNull($response->viewData('leads'));
    }

    /** 12. Backend endpoints remain globally intact and functional */
    public function test_backend_bulk_endpoints_remain_functional(): void
    {
        $targetUser = User::factory()->create(['is_active' => true]);

        // Bulk assign backend route remains active
        $assignResponse = $this->actingAs($this->admin)->post(route('v2.leads.bulk-assign'), [
            'lead_ids' => [$this->lead1->id, $this->lead2->id],
            'assigned_user_id' => $targetUser->id,
        ]);
        $assignResponse->assertRedirect();
        $this->lead1->refresh();
        $this->assertEquals($targetUser->id, $this->lead1->assigned_user_id);

        // Bulk export backend route remains active
        $exportResponse = $this->actingAs($this->admin)->post(route('v2.leads.export-selected'), [
            'lead_ids' => [$this->lead1->id],
        ]);
        $exportResponse->assertOk();

        // Bulk destroy backend route remains active
        $destroyResponse = $this->actingAs($this->admin)->post(route('v2.leads.bulk-destroy'), [
            'lead_ids' => [$this->lead2->id],
        ]);
        $destroyResponse->assertRedirect();
        $this->assertSoftDeleted('leads', ['id' => $this->lead2->id]);
    }
}
