<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use DatabaseTransactions;

    public function test_super_admin_can_open_campaign_form_and_create_campaign(): void
    {
        $admin = $this->superAdmin();
        $assignedUser = User::factory()->create([
            'name' => 'Agent Member',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.create'))
            ->assertOk()
            ->assertSee('بيانات الحملة')
            ->assertSee('Agent Member');

        $response = $this->actingAs($admin)->post(route('v2.campaigns.store'), [
            'name' => 'حملة العودة للمدارس',
            'cost' => '15000.50',
            'starts_at' => '2026-09-01T09:00',
            'ends_at' => '2026-09-30T18:00',
            'user_ids' => [$assignedUser->id],
        ]);

        $response->assertRedirect(route('v2.campaigns.index'));

        $campaign = Campaign::query()->firstWhere('name', 'حملة العودة للمدارس');
        $this->assertNotNull($campaign);
        $this->assertSame('15000.50', (string) $campaign->cost);
        $this->assertSame($admin->id, $campaign->created_by_user_id);
        $this->assertTrue($campaign->users->contains('id', $assignedUser->id));
    }

    public function test_campaign_image_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $admin = $this->superAdmin();
        $assignedUser = User::factory()->create();

        $image = UploadedFile::fake()->image('campaign.jpg', 600, 400);

        $this->actingAs($admin)->post(route('v2.campaigns.store'), [
            'name' => 'حملة مع صورة',
            'cost' => '5000',
            'starts_at' => '2026-08-20T09:00',
            'ends_at' => '2026-08-30T18:00',
            'user_ids' => [$assignedUser->id],
            'image' => $image,
        ])->assertRedirect(route('v2.campaigns.index'));

        $campaign = Campaign::query()->firstWhere('name', 'حملة مع صورة');
        $this->assertNotNull($campaign);
        $this->assertNotNull($campaign->image_path);
        Storage::disk('public')->assertExists($campaign->image_path);

        $newImage = UploadedFile::fake()->image('replacement.png', 700, 500);
        $oldPath = $campaign->image_path;

        $this->actingAs($admin)->patch(route('v2.campaigns.update', $campaign), [
            'name' => 'حملة مع صورة محدثة',
            'cost' => '6500',
            'starts_at' => '2026-08-20T09:00',
            'ends_at' => '2026-08-30T18:00',
            'user_ids' => [$assignedUser->id],
            'image' => $newImage,
        ])->assertRedirect(route('v2.campaigns.show', $campaign));

        $campaign->refresh();
        $this->assertNotSame($oldPath, $campaign->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($campaign->image_path);
    }

    public function test_campaign_requires_valid_timing_and_active_users(): void
    {
        $admin = $this->superAdmin();
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin)->post(route('v2.campaigns.store'), [
            'name' => '',
            'cost' => '-10',
            'starts_at' => '2026-08-30T18:00',
            'ends_at' => '2026-08-20T09:00',
            'user_ids' => [$inactiveUser->id],
        ]);

        $response->assertSessionHasErrors([
            'name',
            'cost',
            'ends_at',
            'user_ids.0',
        ]);
    }

    public function test_campaign_list_can_be_filtered_by_name_and_user(): void
    {
        $admin = $this->superAdmin();
        $firstAgent = User::factory()->create(['name' => 'Filter First Agent']);
        $secondAgent = User::factory()->create(['name' => 'Filter Second Agent']);

        $campaignA = $this->campaign($admin, [$firstAgent]);
        $campaignA->update(['name' => 'حملة القاهرة']);

        $campaignB = $this->campaign($admin, [$secondAgent]);
        $campaignB->update(['name' => 'حملة الإسكندرية']);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.index', ['q' => 'القاهرة']))
            ->assertOk()
            ->assertSee('حملة القاهرة')
            ->assertDontSee('حملة الإسكندرية');

        $this->actingAs($admin)
            ->get(route('v2.campaigns.index', ['user_id' => $secondAgent->id]))
            ->assertOk()
            ->assertSee('حملة الإسكندرية')
            ->assertDontSee('حملة القاهرة');
    }

    public function test_campaign_manager_can_edit_campaign_information(): void
    {
        $admin = $this->superAdmin();
        $firstAgent = User::factory()->create(['name' => 'First Agent']);
        $secondAgent = User::factory()->create(['name' => 'Second Agent']);

        $campaign = $this->campaign($admin, [$firstAgent]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('تعديل الحملة')
            ->assertSee($campaign->name);

        $this->actingAs($admin)->patch(route('v2.campaigns.update', $campaign), [
            'name' => 'حملة محدثة بعد التعديل',
            'cost' => '8200.00',
            'starts_at' => '2026-09-01T10:00',
            'ends_at' => '2026-09-15T20:00',
            'user_ids' => [$secondAgent->id],
        ])->assertRedirect(route('v2.campaigns.show', $campaign));

        $campaign->refresh();
        $this->assertSame('حملة محدثة بعد التعديل', $campaign->name);
        $this->assertSame('8200.00', (string) $campaign->cost);
        $this->assertFalse($campaign->users->contains('id', $firstAgent->id));
        $this->assertTrue($campaign->users->contains('id', $secondAgent->id));
    }

    public function test_campaign_creator_cannot_edit_another_creators_campaign(): void
    {
        $creator = $this->userWithPermissions([
            'campaigns.view',
            'campaigns.create',
        ]);
        $otherCreator = User::factory()->create();

        $campaign = $this->campaign($otherCreator, [$creator]);

        $this->actingAs($creator)
            ->get(route('v2.campaigns.edit', $campaign))
            ->assertForbidden();

        $this->actingAs($creator)
            ->patch(route('v2.campaigns.update', $campaign), [
                'name' => 'تعديل غير مسموح',
                'cost' => '1000',
                'starts_at' => '2026-08-01T00:00',
                'ends_at' => '2026-08-02T00:00',
                'user_ids' => [$creator->id],
            ])
            ->assertForbidden();
    }

    public function test_campaign_manager_can_delete_campaign_without_deleting_leads(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $lead = $this->lead(['name' => 'Safe Lead After Campaign Delete']);
        $campaign->leads()->attach($lead->id);

        $this->actingAs($admin)
            ->delete(route('v2.campaigns.destroy', $campaign))
            ->assertRedirect(route('v2.campaigns.index'));

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
        $this->assertDatabaseMissing('campaign_lead', [
            'campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
        ]);
    }

    public function test_campaign_creator_cannot_delete_another_creators_campaign(): void
    {
        $creator = $this->userWithPermissions([
            'campaigns.view',
            'campaigns.create',
        ]);
        $otherCreator = User::factory()->create();
        $campaign = $this->campaign($otherCreator, [$creator]);

        $this->actingAs($creator)
            ->delete(route('v2.campaigns.destroy', $campaign))
            ->assertForbidden();

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    public function test_campaign_manager_can_manually_add_lead_to_campaign(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $status = $this->leadStatus();

        $this->actingAs($admin)->post(route('v2.leads.store'), [
            'first_name' => 'Manual Campaign',
            'last_name' => 'Attached Lead',
            'phone' => '01099887766',
            'source' => 'Facebook Campaign',
            'lead_status_id' => $status->id,
            'campaign_id' => $campaign->id,
            'assigned_user_id' => $admin->id,
        ])->assertRedirect(route('v2.campaigns.show', [
            'campaign' => $campaign,
            'assigned_user_id' => $admin->id,
        ]));

        $lead = Lead::query()->firstWhere('phone', '01099887766');
        $this->assertNotNull($lead);
        $this->assertSame($admin->id, $lead->assigned_user_id);
        $this->assertTrue($campaign->leads()->whereKey($lead->id)->exists());
    }

    public function test_manager_can_bulk_assign_campaign_leads_to_campaign_user(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create(['name' => 'Assigned Agent']);
        $campaign = $this->campaign($admin, [$admin, $agent]);

        $leadA = $this->lead(['name' => 'Bulk Lead A', 'assigned_user_id' => $admin->id]);
        $leadB = $this->lead(['name' => 'Bulk Lead B', 'assigned_user_id' => $admin->id]);
        $campaign->leads()->attach([$leadA->id, $leadB->id]);

        $response = $this->actingAs($admin)->patch(
            route('v2.campaigns.leads.assign', $campaign),
            [
                'lead_ids' => [$leadA->id, $leadB->id],
                'target_user_id' => $agent->id,
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertSame($agent->id, $leadA->fresh()->assigned_user_id);
        $this->assertSame($agent->id, $leadB->fresh()->assigned_user_id);
        $this->assertSame($agent->name, $leadA->fresh()->assigned_employee);
    }

    public function test_campaign_user_only_sees_leads_assigned_to_them(): void
    {
        $admin = $this->superAdmin();
        $firstAgent = $this->userWithPermissions(['campaigns.view']);
        $secondAgent = $this->userWithPermissions(['campaigns.view']);

        $campaign = $this->campaign($admin, [$firstAgent, $secondAgent]);

        $leadForFirst = $this->lead([
            'name' => 'Lead For First Agent',
            'assigned_user_id' => $firstAgent->id,
        ]);
        $leadForSecond = $this->lead([
            'name' => 'Lead For Second Agent',
            'assigned_user_id' => $secondAgent->id,
        ]);

        $campaign->leads()->attach([$leadForFirst->id, $leadForSecond->id]);

        $this->actingAs($firstAgent)
            ->get(route('v2.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Lead For First Agent')
            ->assertDontSee('Lead For Second Agent');

        $this->actingAs($secondAgent)
            ->get(route('v2.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Lead For Second Agent')
            ->assertDontSee('Lead For First Agent');
    }

    public function test_campaign_manager_can_filter_leads_by_campaign_user(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create(['name' => 'Filter Agent']);
        $campaign = $this->campaign($admin, [$admin, $agent]);

        $leadAdmin = $this->lead([
            'name' => 'Lead Assigned To Admin',
            'assigned_user_id' => $admin->id,
        ]);
        $leadAgent = $this->lead([
            'name' => 'Lead Assigned To Agent',
            'assigned_user_id' => $agent->id,
        ]);
        $campaign->leads()->attach([$leadAdmin->id, $leadAgent->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'assigned_user_id' => $agent->id,
            ]))
            ->assertOk()
            ->assertSee('Lead Assigned To Agent')
            ->assertDontSee('Lead Assigned To Admin');
    }

    public function test_campaign_manager_can_filter_unassigned_leads(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);

        $unassignedLead = $this->lead([
            'name' => 'Unassigned Campaign Lead',
            'assigned_user_id' => null,
            'assigned_employee' => null,
        ]);
        $assignedLead = $this->lead([
            'name' => 'Assigned Campaign Lead',
            'assigned_user_id' => $admin->id,
        ]);
        $campaign->leads()->attach([$unassignedLead->id, $assignedLead->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'assigned_user_id' => 'unassigned',
            ]))
            ->assertOk()
            ->assertSee('Unassigned Campaign Lead')
            ->assertDontSee('Assigned Campaign Lead');
    }

    public function test_campaign_leads_can_be_filtered_by_status(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);
        $newStatus = $this->leadStatus();
        $wonStatus = LeadStatus::query()->create([
            'pipeline_stage_id' => $newStatus->pipeline_stage_id,
            'code' => 'campaign-won',
            'name_ar' => 'مكتمل',
            'position' => 99,
            'is_terminal' => true,
        ]);
        $newLead = $this->lead([
            'name' => 'New Status Campaign Lead',
            'assigned_user_id' => $admin->id,
        ]);
        $wonLead = $this->lead([
            'name' => 'Won Status Campaign Lead',
            'assigned_user_id' => $admin->id,
            'lead_status_id' => $wonStatus->id,
        ]);
        $campaign->leads()->attach([$newLead->id, $wonLead->id]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', [
                'campaign' => $campaign,
                'status' => $wonStatus->code,
            ]))
            ->assertOk()
            ->assertSee('Won Status Campaign Lead')
            ->assertDontSee('New Status Campaign Lead');
    }

    public function test_campaign_show_renders_dynamic_pipeline_stages_and_filters_by_stage(): void
    {
        $admin = $this->superAdmin();
        $agent = User::factory()->create();
        $campaign = $this->campaign($admin, [$agent]);

        $stageA = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'is_active' => true]
        );
        $statusA = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $stageA->id, 'name_ar' => 'جديد', 'position' => 1, 'is_terminal' => false]
        );

        $customStage = PipelineStage::query()->create([
            'code' => 'stage_custom_campaign',
            'name_ar' => 'مرحلة حملة مخصصة',
            'position' => 5,
            'color' => '#8b5cf6',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $customStatus = $customStage->statuses()->first() ?? LeadStatus::query()->create([
            'pipeline_stage_id' => $customStage->id,
            'code' => 'status_custom_campaign',
            'name_ar' => 'حالة حملة مخصصة',
            'position' => 5,
            'is_terminal' => false,
        ]);

        $lead1 = $this->lead(['name' => 'عميل مرحلة أ', 'lead_status_id' => $statusA->id, 'assigned_user_id' => $admin->id]);
        $lead2 = $this->lead(['name' => 'عميل مرحلة مخصصة', 'lead_status_id' => $customStatus->id, 'assigned_user_id' => $admin->id]);

        $campaign->leads()->attach([$lead1->id, $lead2->id]);

        // 1. Show page renders custom stage card
        $response = $this->actingAs($admin)->get(route('v2.campaigns.show', $campaign));
        $response->assertOk();
        $response->assertSee('مرحلة حملة مخصصة');
        $response->assertSee(__('crm.customer_stages'));

        // 2. Filter by custom stage
        $filteredResponse = $this->actingAs($admin)->get(route('v2.campaigns.show', [
            'campaign' => $campaign,
            'stage' => $customStage->id,
        ]));
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('عميل مرحلة مخصصة');
        $filteredResponse->assertDontSee('عميل مرحلة أ');
    }

    public function test_inactive_pipeline_stage_does_not_appear_in_campaign_show(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);

        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_inactive_test',
            'name_ar' => 'مرحلة معطلة غير مرئية',
            'position' => 99,
            'color' => '#64748b',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', $campaign))
            ->assertOk()
            ->assertDontSee('مرحلة معطلة غير مرئية');
    }

    public function test_unauthorized_user_cannot_access_campaigns(): void
    {
        $userWithoutPerms = User::factory()->create();

        $this->actingAs($userWithoutPerms)
            ->get(route('v2.campaigns.index'))
            ->assertForbidden();

        $this->actingAs($userWithoutPerms)
            ->get(route('v2.campaigns.create'))
            ->assertForbidden();
    }

    public function test_campaign_index_and_show_link_to_campaign_reports(): void
    {
        $admin = $this->superAdmin();
        $campaign = $this->campaign($admin, [$admin]);

        $this->actingAs($admin)
            ->get(route('v2.campaigns.index'))
            ->assertOk()
            ->assertSee(route('v2.campaigns.reports'));

        $this->actingAs($admin)
            ->get(route('v2.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee(route('v2.campaigns.reports', ['campaign_id' => $campaign->id]));
    }

    private function campaign(User $creator, array $users): Campaign
    {
        $campaign = Campaign::query()->create([
            'name' => 'Campaign Workspace',
            'cost' => 1000,
            'starts_at' => '2026-08-20 09:00',
            'ends_at' => '2026-08-31 18:00',
            'created_by_user_id' => $creator->id,
        ]);
        $campaign->users()->attach(
            collect($users)->pluck('id')->all(),
        );

        return $campaign;
    }

    private function lead(array $attributes = []): Lead
    {
        return Lead::query()->create(array_merge([
            'lead_status_id' => $this->leadStatus()->id,
            'name' => 'Campaign Lead',
            'first_name' => 'Campaign',
            'phone' => fake()->unique()->numerify('010########'),
            'source' => 'Campaign',
            'created_by' => 'Test',
        ], $attributes));
    }

    private function leadStatus(): LeadStatus
    {
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'name_ar' => 'جديد',
                'position' => 1,
                'is_active' => true,
            ],
        );

        return LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => 'جديد',
                'position' => 1,
            ],
        );
    }

    public function test_campaign_detail_topbar_is_unified_and_has_no_duplicate_command_bar(): void
    {
        $user = $this->superAdmin();
        $campaign = Campaign::query()->create([
            'name' => 'Marketing Campaign',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 1000,
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('v2.campaigns.show', $campaign));
        $response->assertOk();
        $response->assertSee('data-crm-topbar', false);
        $response->assertSee('class="crm-topbar-actions"', false);
        $response->assertSee(__('crm.add_lead'));
        $response->assertSee(__('crm.import_leads'));
        $response->assertSee(__('crm.edit_campaign'));
        $response->assertSee(__('crm.campaign_reports'));
        $response->assertSee(__('crm.all_campaigns'));
    }

    public function test_campaign_list_and_detail_total_customer_counts_are_consistent(): void
    {
        $user = $this->superAdmin();
        $assignedUser = User::factory()->create(['name' => 'Assigned Agent', 'is_active' => true]);
        $campaign = Campaign::query()->create([
            'name' => 'Counts Test Campaign',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 1000,
            'created_by_user_id' => $user->id,
        ]);
        $campaign->users()->attach($assignedUser);
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            ['name_ar' => 'البداية', 'position' => 1, 'is_active' => true]
        );
        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $stage->id, 'name_ar' => 'جديد', 'position' => 1]
        );

        // Attach 5 leads assigned to $user
        for ($i = 0; $i < 5; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $status->id,
                'name' => 'Campaign Customer ' . $i,
                'phone' => '050900100' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $user->id,
            ]);
            $campaign->leads()->attach($lead->id);
        }

        // List page check
        $listResponse = $this->actingAs($user)->get(route('v2.campaigns.index'));
        $listResponse->assertOk();
        $listCampaign = $listResponse->viewData('campaigns')->firstWhere('id', $campaign->id);
        $this->assertEquals(5, $listCampaign->leads_count);

        // Detail page check (Super Admin with assigned_user_id=all or specific assigned user)
        $detailResponse = $this->actingAs($user)->get(route('v2.campaigns.show', [
            'campaign' => $campaign,
            'assigned_user_id' => 'all',
        ]));
        $detailResponse->assertOk();
        $this->assertEquals(5, $detailResponse->viewData('totalCampaignLeads'));
        $this->assertEquals(5, $detailResponse->viewData('operationalCampaignLeads'));
        $detailResponse->assertSee('5');
    }

    public function test_campaign_detail_allows_filtering_by_all_leads(): void
    {
        $user = $this->superAdmin();
        $assignedUser = User::factory()->create(['name' => 'Agent Alpha', 'is_active' => true]);
        $campaign = Campaign::query()->create([
            'name' => 'All Filter Test',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 1000,
            'created_by_user_id' => $user->id,
        ]);
        $campaign->users()->attach($assignedUser);
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            ['name_ar' => 'البداية', 'position' => 1, 'is_active' => true]
        );
        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $stage->id, 'name_ar' => 'جديد', 'position' => 1]
        );

        for ($i = 0; $i < 3; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $status->id,
                'name' => 'Lead ' . $i,
                'phone' => '050900200' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $assignedUser->id,
            ]);
            $campaign->leads()->attach($lead->id);
        }

        $responseAll = $this->actingAs($user)->get(route('v2.campaigns.show', [
            'campaign' => $campaign,
            'assigned_user_id' => 'all',
        ]));
        $responseAll->assertOk();
        $this->assertTrue($responseAll->viewData('showAllAssignees'));
        $this->assertEquals(3, $responseAll->viewData('leads')->total());
    }
    public function test_campaign_list_uses_distinct_customer_counts_and_matches_operational_view(): void
    {
        $user = $this->superAdmin();
        $campaign = Campaign::query()->create([
            'name' => 'Distinct Campaign',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cost' => 500,
            'created_by_user_id' => $user->id,
        ]);
        $campaign->users()->attach($user);
        $status = $this->leadStatus();

        $lead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Single Unique Customer',
            'phone' => '0509988776',
            'source' => 'campaign',
            'assigned_user_id' => $user->id,
        ]);
        $campaign->leads()->attach($lead->id);

        $listResponse = $this->actingAs($user)->get(route('v2.campaigns.index'));
        $listResponse->assertOk();
        $listCamp = $listResponse->viewData('campaigns')->firstWhere('id', $campaign->id);
        $this->assertEquals(1, $listCamp->leads_count);

        $detailResponse = $this->actingAs($user)->get(route('v2.campaigns.show', $campaign));
        $detailResponse->assertOk();
        $this->assertEquals(1, $detailResponse->viewData('operationalCampaignLeads'));
        $this->assertEquals(1, $detailResponse->viewData('leads')->total());
    }

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Campaign User '.fake()->unique()->word(),
            'code' => 'campaign-user-'.fake()->unique()->numerify('#####'),
        ]);

        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'module' => explode('.', $code, 2)[0],
                    'name_ar' => $code,
                ],
            );
            $group->permissions()->attach($permission);
        }

        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }

    private function superAdmin(): User
    {
        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'Super Admin',
                'is_system' => true,
            ],
        );
        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }
}
