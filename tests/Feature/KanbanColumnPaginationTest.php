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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanColumnPaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $agentA;
    private User $agentB;
    private LeadStatus $statusNew;
    private LeadStatus $statusInterested;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ]
            );
        }

        $superGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'Super Admin',
                'is_system' => true,
            ]
        );
        $superGroup->permissions()->sync(Permission::pluck('id'));

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'username' => 'admin_test',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superGroup);

        $agentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agents-pagination'],
            [
                'name' => 'Sales Agents',
                'is_system' => false,
            ]
        );
        $agentGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_CREATE->value,
            CrmPermission::LEADS_UPDATE->value,
            CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
            CrmPermission::LEADS_FOLLOWUPS_VIEW->value,
        ])->pluck('id'));

        $this->agentA = User::factory()->create([
            'name' => 'Agent A',
            'username' => 'agent_a',
            'is_active' => true,
        ]);
        $this->agentA->groups()->attach($agentGroup);

        $this->agentB = User::factory()->create([
            'name' => 'Agent B',
            'username' => 'agent_b',
            'is_active' => true,
        ]);
        $this->agentB->groups()->attach($agentGroup);

        $stage1 = PipelineStage::query()->firstOrCreate(
            ['code' => 'lead_generation'],
            ['name_ar' => 'توليد العملاء', 'name_en' => 'Lead Generation', 'position' => 1, 'is_active' => true]
        );

        $this->statusNew = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $stage1->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
            ]
        );

        $stage2 = PipelineStage::query()->firstOrCreate(
            ['code' => 'qualification'],
            ['name_ar' => 'التأهيل والاهتمام', 'name_en' => 'Qualification', 'position' => 2, 'is_active' => true]
        );

        $this->statusInterested = LeadStatus::query()->firstOrCreate(
            ['code' => 'interested'],
            [
                'pipeline_stage_id' => $stage2->id,
                'name_ar' => 'مهتم',
                'position' => 2,
                'color' => '#169a64',
            ]
        );
    }

    /**
     * 1. Kanban initial column contains max 10 cards.
     */
    public function test_1_kanban_initial_column_contains_max_10_cards(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "Lead Item {$i}",
                'phone' => "05000000{$i}",
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();

        $kanbanColumns = $response->viewData('kanbanColumns');
        $newColumn = collect($kanbanColumns)->firstWhere('id', $this->statusNew->id);

        $this->assertNotNull($newColumn);
        $this->assertEquals(25, $newColumn['total_count']);
        $this->assertCount(10, $newColumn['all_leads']);
    }

    /**
     * 2. Page 2 returns next 10.
     */
    public function test_2_page_2_returns_next_10(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => sprintf('Sequential Lead %02d', $i),
                'phone' => "05000000{$i}",
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => now()->addMinutes($i),
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'page' => 2,
            'pageSize' => 10,
            'total' => 25,
            'from' => 11,
            'to' => 20,
            'hasMore' => true,
            'hasPrevious' => true,
            'count' => 10,
        ]);

        $html = $response->json('html');
        $this->assertStringContainsString('Sequential Lead 11', $html);
        $this->assertStringContainsString('Sequential Lead 20', $html);
        $this->assertStringNotContainsString('Sequential Lead 01', $html);
        $this->assertStringNotContainsString('Sequential Lead 10', $html);
        $this->assertStringNotContainsString('Sequential Lead 21', $html);
    }

    /**
     * 3. No duplicates between page 1 and page 2.
     */
    public function test_3_no_duplicates_between_page_1_and_page_2(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => sprintf('Unique Lead %02d', $i),
                'phone' => "05000000{$i}",
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => now()->addMinutes($i),
            ]);
        }

        $page1Response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
        ]));
        $page1Response->assertOk();
        $page1Html = $page1Response->json('html');

        $page2Response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 2,
        ]));
        $page2Response->assertOk();
        $page2Html = $page2Response->json('html');

        for ($i = 1; $i <= 10; $i++) {
            $name = sprintf('Unique Lead %02d', $i);
            $this->assertStringContainsString($name, $page1Html);
            $this->assertStringNotContainsString($name, $page2Html);
        }

        for ($i = 11; $i <= 20; $i++) {
            $name = sprintf('Unique Lead %02d', $i);
            $this->assertStringNotContainsString($name, $page1Html);
            $this->assertStringContainsString($name, $page2Html);
        }
    }

    /**
     * 4. Last page correct size.
     */
    public function test_4_last_page_correct_size(): void
    {
        for ($i = 1; $i <= 23; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "Lead 23-{$i}",
                'phone' => "05000000{$i}",
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
                'next_follow_up_at' => now()->addMinutes($i),
            ]);
        }

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 3,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'page' => 3,
            'pageSize' => 10,
            'total' => 23,
            'from' => 21,
            'to' => 23,
            'hasMore' => false,
            'hasPrevious' => true,
            'count' => 3,
        ]);
    }

    /**
     * 5. Unauthorized Lead never returned.
     */
    public function test_5_unauthorized_lead_never_returned(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'Accessible by Agent A',
            'phone' => '0501111111',
            'assigned_user_id' => $this->agentA->id,
            'created_by_user_id' => $this->agentA->id,
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'Secret Lead for Agent B',
            'phone' => '0502222222',
            'assigned_user_id' => $this->agentB->id,
            'created_by_user_id' => $this->agentB->id,
        ]);

        $response = $this->actingAs($this->agentA)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
        ]));

        $response->assertOk();
        $response->assertJson([
            'total' => 1,
            'count' => 1,
        ]);

        $html = $response->json('html');
        $this->assertStringContainsString('Accessible by Agent A', $html);
        $this->assertStringNotContainsString('Secret Lead for Agent B', $html);
    }

    /**
     * 6. Branch scope preserved.
     */
    public function test_6_branch_scope_preserved(): void
    {
        $branchGroup = Group::query()->create([
            'name' => 'Branch North',
            'code' => 'branch-north',
            'is_system' => false,
        ]);
        $branchGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_GROUP->value,
        ])->pluck('id'));

        $branchUser1 = User::factory()->create(['name' => 'North User 1', 'is_active' => true]);
        $branchUser1->groups()->attach($branchGroup);

        $branchUser2 = User::factory()->create(['name' => 'North User 2', 'is_active' => true]);
        $branchUser2->groups()->attach($branchGroup);

        $otherUser = User::factory()->create(['name' => 'South User', 'is_active' => true]);

        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'North Branch Lead 1',
            'assigned_user_id' => $branchUser1->id,
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'North Branch Lead 2',
            'assigned_user_id' => $branchUser2->id,
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'South Branch Lead',
            'assigned_user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($branchUser1)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
        ]));

        $response->assertOk();
        $response->assertJson([
            'total' => 2,
            'count' => 2,
        ]);

        $html = $response->json('html');
        $this->assertStringContainsString('North Branch Lead 1', $html);
        $this->assertStringContainsString('North Branch Lead 2', $html);
        $this->assertStringNotContainsString('South Branch Lead', $html);
    }

    /**
     * 7. Employee filter preserved.
     */
    public function test_7_employee_filter_preserved(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'Lead For Agent A',
            'assigned_user_id' => $this->agentA->id,
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'Lead For Agent B',
            'assigned_user_id' => $this->agentB->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
            'employee_id' => $this->agentA->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'total' => 1,
            'count' => 1,
        ]);

        $html = $response->json('html');
        $this->assertStringContainsString('Lead For Agent A', $html);
        $this->assertStringNotContainsString('Lead For Agent B', $html);
    }

    /**
     * 8. Today/Overdue/Upcoming scope preserved.
     */
    public function test_8_today_overdue_upcoming_scope_preserved(): void
    {
        Lead::query()->create([
            'lead_status_id' => $this->statusInterested->id,
            'name' => 'Today Lead',
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->startOfDay()->addHours(2),
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->statusInterested->id,
            'name' => 'Overdue Lead',
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->subDays(2),
        ]);

        Lead::query()->create([
            'lead_status_id' => $this->statusInterested->id,
            'name' => 'Upcoming Lead',
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDays(3),
        ]);

        $todayResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusInterested->id,
            'scope' => 'today',
            'page' => 1,
        ]));
        $todayResponse->assertOk();
        $this->assertEquals(1, $todayResponse->json('total'));
        $this->assertStringContainsString('Today Lead', $todayResponse->json('html'));
        $this->assertStringNotContainsString('Overdue Lead', $todayResponse->json('html'));
        $this->assertStringNotContainsString('Upcoming Lead', $todayResponse->json('html'));

        $overdueResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusInterested->id,
            'scope' => 'overdue',
            'page' => 1,
        ]));
        $overdueResponse->assertOk();
        $this->assertEquals(1, $overdueResponse->json('total'));
        $this->assertStringContainsString('Overdue Lead', $overdueResponse->json('html'));

        $upcomingResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusInterested->id,
            'scope' => 'upcoming',
            'page' => 1,
        ]));
        $upcomingResponse->assertOk();
        $this->assertEquals(1, $upcomingResponse->json('total'));
        $this->assertStringContainsString('Upcoming Lead', $upcomingResponse->json('html'));
    }

    /**
     * 9. Invalid page/stage rejected safely.
     */
    public function test_9_invalid_page_stage_rejected_safely(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('v2.leads.kanban.column', [
                'status_id' => 999999,
                'scope' => 'all',
                'page' => 1,
            ]))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->getJson(route('v2.leads.kanban.column', [
                'status_id' => $this->statusNew->id,
                'scope' => 'invalid_scope',
                'page' => 1,
            ]))
            ->assertStatus(400);

        $this->actingAs($this->admin)
            ->getJson(route('v2.leads.kanban.column', [
                'status_id' => $this->statusNew->id,
                'scope' => 'all',
                'page' => 'abc',
            ]))
            ->assertStatus(400);
    }

    /**
     * 10. Dynamic PipelineStages supported.
     */
    public function test_10_dynamic_pipeline_stages_supported(): void
    {
        $customStage = PipelineStage::query()->create([
            'name_ar' => 'مرحلة التحقق الخاصة',
            'name_en' => 'Custom Verification Stage',
            'code' => 'custom_verification',
            'position' => 10,
            'color' => '#8b5cf6',
            'is_active' => true,
        ]);

        $customStatus = $customStage->ensureDefaultStatus();

        Lead::query()->create([
            'lead_status_id' => $customStatus->id,
            'name' => 'Dynamic Stage Lead',
            'phone' => '0509999999',
            'assigned_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $customStatus->id,
            'scope' => 'all',
            'page' => 1,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'total' => 1,
            'count' => 1,
        ]);
        $this->assertStringContainsString('Dynamic Stage Lead', $response->json('html'));
        $this->assertStringContainsString('مرحلة التحقق الخاصة', $response->json('html'));
    }

    /**
     * 11. Total count correct.
     */
    public function test_11_total_count_correct(): void
    {
        for ($i = 1; $i <= 17; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "Count Lead {$i}",
                'assigned_user_id' => $this->admin->id,
            ]);
        }

        $page1 = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
        ]));

        $page1->assertOk();
        $this->assertEquals(17, $page1->json('total'));
        $this->assertEquals(1, $page1->json('from'));
        $this->assertEquals(10, $page1->json('to'));
        $this->assertTrue($page1->json('hasMore'));
        $this->assertFalse($page1->json('hasPrevious'));

        $page2 = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 2,
        ]));

        $page2->assertOk();
        $this->assertEquals(17, $page2->json('total'));
        $this->assertEquals(11, $page2->json('from'));
        $this->assertEquals(17, $page2->json('to'));
        $this->assertFalse($page2->json('hasMore'));
        $this->assertTrue($page2->json('hasPrevious'));
    }

    /**
     * 12. crm.more link absent from Kanban.
     */
    public function test_12_crm_more_link_absent_from_kanban(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "More Check {$i}",
                'assigned_user_id' => $this->admin->id,
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('kanban-more-wrap', $content);
        $this->assertStringNotContainsString('kanban-more-btn', $content);
        $this->assertStringNotContainsString('crm.more', $content);
        $this->assertStringContainsString('kanban-column-pagination', $content);
    }

    /**
     * 13. Loaded card retains drag attributes/action URLs.
     */
    public function test_13_loaded_card_retains_drag_attributes_and_action_urls(): void
    {
        $lead = Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'Action Test Lead',
            'phone' => '0501234567',
            'company_name' => 'Acme Corp',
            'assigned_user_id' => $this->admin->id,
            'next_follow_up_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
        ]));

        $response->assertOk();
        $html = $response->json('html');

        $this->assertStringContainsString('class="kanban-card"', $html);
        $this->assertStringContainsString('draggable="true"', $html);
        $this->assertStringContainsString("data-kanban-lead=\"{$lead->id}\"", $html);
        $this->assertStringContainsString('data-kanban-lead-name="Action Test Lead"', $html);
        $this->assertStringContainsString("data-current-status-id=\"{$this->statusNew->id}\"", $html);
        $this->assertStringContainsString(route('v2.leads.followups.index', $lead), $html);
        $this->assertStringContainsString(route('v2.leads.show', $lead), $html);
        $this->assertStringContainsString('tel:0501234567', $html);
        $this->assertStringContainsString('data-kanban-call-dial-popup', $html);
        $this->assertStringContainsString('data-kanban-customer-popup', $html);
        $this->assertStringContainsString('data-kanban-followup-popup', $html);
    }
    /**
     * Test per_page=10, 20, 30, 40, 50 on initial Kanban view and column endpoint.
     */
    public function test_per_page_whitelist_sizes_work_correctly(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "Batch Lead {$i}",
                'assigned_user_id' => $this->admin->id,
                'next_follow_up_at' => now()->addMinutes($i),
            ]);
        }

        foreach ([10, 20, 30, 40, 50] as $size) {
            // Initial Kanban view
            $kanbanResponse = $this->actingAs($this->admin)->get(route('v2.leads.kanban', ['per_page' => $size]));
            $kanbanResponse->assertOk();
            $this->assertEquals($size, $kanbanResponse->viewData('perPage'));

            $columns = $kanbanResponse->viewData('kanbanColumns');
            $newCol = collect($columns)->firstWhere('id', $this->statusNew->id);
            $this->assertCount($size, $newCol['all_leads']);

            // Column AJAX endpoint
            $columnResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
                'status_id' => $this->statusNew->id,
                'scope' => 'all',
                'page' => 1,
                'per_page' => $size,
            ]));
            $columnResponse->assertOk();
            $columnResponse->assertJson([
                'success' => true,
                'page' => 1,
                'pageSize' => $size,
                'total' => 55,
                'from' => 1,
                'to' => $size,
                'count' => $size,
                'hasMore' => true,
            ]);
        }
    }

    /**
     * Test values above 50 are normalized to default (10).
     */
    public function test_per_page_values_above_50_are_normalized_safely(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "Over 50 Lead {$i}",
                'assigned_user_id' => $this->admin->id,
            ]);
        }

        $kanbanResponse = $this->actingAs($this->admin)->get(route('v2.leads.kanban', ['per_page' => 100]));
        $kanbanResponse->assertOk();
        $this->assertEquals(10, $kanbanResponse->viewData('perPage'));

        $columnResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 1,
            'per_page' => 100,
        ]));
        $columnResponse->assertOk();
        $columnResponse->assertJson([
            'pageSize' => 10,
            'from' => 1,
            'to' => 10,
            'count' => 10,
        ]);
    }

    /**
     * Test invalid per_page values (negative, strings, 0) are normalized safely to default (10).
     */
    public function test_invalid_per_page_values_are_handled_safely(): void
    {
        foreach ([-10, 0, 15, 999, 'invalid_text'] as $invalidValue) {
            $columnResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
                'status_id' => $this->statusNew->id,
                'scope' => 'all',
                'page' => 1,
                'per_page' => $invalidValue,
            ]));
            $columnResponse->assertOk();
            $columnResponse->assertJson([
                'pageSize' => 10,
            ]);
        }
    }

    /**
     * Test employee filter + per_page work together seamlessly.
     */
    public function test_employee_filter_and_page_size_work_together(): void
    {
        for ($i = 1; $i <= 35; $i++) {
            Lead::query()->create([
                'lead_status_id' => $this->statusNew->id,
                'name' => "Agent A Lead {$i}",
                'assigned_user_id' => $this->agentA->id,
                'next_follow_up_at' => now()->addMinutes($i),
            ]);
        }

        Lead::query()->create([
            'lead_status_id' => $this->statusNew->id,
            'name' => 'Agent B Single Lead',
            'assigned_user_id' => $this->agentB->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.leads.kanban', [
            'employee_id' => $this->agentA->id,
            'per_page' => 20,
        ]));
        $response->assertOk();
        $this->assertEquals(20, $response->viewData('perPage'));
        $this->assertEquals($this->agentA->id, $response->viewData('selectedEmployeeId'));

        $columns = $response->viewData('kanbanColumns');
        $newCol = collect($columns)->firstWhere('id', $this->statusNew->id);
        $this->assertEquals(35, $newCol['total_count']);
        $this->assertCount(20, $newCol['all_leads']);

        // Column AJAX fetch for page 2 with size 20
        $ajaxResponse = $this->actingAs($this->admin)->getJson(route('v2.leads.kanban.column', [
            'status_id' => $this->statusNew->id,
            'scope' => 'all',
            'page' => 2,
            'per_page' => 20,
            'employee_id' => $this->agentA->id,
        ]));
        $ajaxResponse->assertOk();
        $ajaxResponse->assertJson([
            'total' => 35,
            'page' => 2,
            'pageSize' => 20,
            'from' => 21,
            'to' => 35,
            'count' => 15,
            'hasMore' => false,
            'hasPrevious' => true,
        ]);
    }
}
