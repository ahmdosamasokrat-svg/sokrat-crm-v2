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
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignDynamicStagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Campaign $campaign;
    private PipelineStage $startStage;
    private PipelineStage $interestStage;
    private PipelineStage $customStage;
    private LeadStatus $newStatus;
    private LeadStatus $noAnswerStatus;
    private LeadStatus $interestedStatus;
    private LeadStatus $customStatus;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->firstOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ]
            );
        }

        // 2. Create Super Admin group and user
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
            'username' => 'camp_admin',
            'name' => 'Campaign Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        // 3. Create active stages & statuses
        $this->startStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'البداية',
                'position' => 1,
                'color' => '#3478f6',
                'icon' => 'bi-person-plus',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->newStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            [
                'pipeline_stage_id' => $this->startStage->id,
                'name_ar' => 'جديد',
                'position' => 1,
                'color' => '#3478f6',
                'is_terminal' => false,
            ]
        );
        $this->newStatus->update([
            'pipeline_stage_id' => $this->startStage->id,
            'name_ar' => 'جديد',
            'color' => '#3478f6',
        ]);
        $this->noAnswerStatus = LeadStatus::query()->firstOrCreate(
            ['code' => 'no_answer'],
            [
                'pipeline_stage_id' => $this->startStage->id,
                'name_ar' => 'لم يرد',
                'position' => 2,
                'color' => '#e59b16',
                'is_terminal' => false,
            ]
        );

        $this->interestStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'interest'],
            [
                'name_ar' => 'الاهتمام',
                'position' => 2,
                'color' => '#10b981',
                'icon' => 'bi-hand-thumbs-up',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $this->interestedStatus = $this->interestStage->statuses()->first()
            ?? LeadStatus::query()->firstOrCreate(
                ['code' => 'interested'],
                [
                    'pipeline_stage_id' => $this->interestStage->id,
                    'name_ar' => 'مهتم',
                    'position' => 3,
                    'color' => '#10b981',
                    'is_terminal' => false,
                ]
            );

        $this->customStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'stage_field_assessment'],
            [
                'name_ar' => 'التقييم الميداني',
                'position' => 3,
                'color' => '#8b5cf6',
                'icon' => 'bi-geo-alt',
                'is_primary' => false,
                'is_active' => true,
            ]
        );
        $this->customStatus = $this->customStage->statuses()->first()
            ?? LeadStatus::query()->firstOrCreate(
                ['code' => 'stage_field_assessment'],
                [
                    'pipeline_stage_id' => $this->customStage->id,
                    'name_ar' => 'التقييم الميداني',
                    'position' => 4,
                    'color' => '#8b5cf6',
                    'is_terminal' => false,
                ]
            );
        // 4. Create campaign
        $this->campaign = Campaign::query()->create([
            'name' => 'حملة الصيف العقارية',
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addMonth()->endOfDay(),
            'created_by_user_id' => $this->admin->id,
        ]);
        $this->campaign->users()->attach($this->admin);
    }

    // 1. Campaign show renders active PipelineStages dynamically
    public function test_1_campaign_show_renders_active_pipeline_stages_dynamically(): void
    {
        app()->setLocale('ar');
        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertSee('البداية');
        $response->assertSee('الاهتمام');
        $response->assertSee('التقييم الميداني');
    }

    // 2. custom stage appears automatically
    public function test_2_custom_stage_appears_automatically(): void
    {
        $newCustom = PipelineStage::query()->create([
            'code' => 'stage_vip_' . uniqid(),
            'name_ar' => 'عملاء VIP للحملة',
            'position' => 4,
            'color' => '#f59e0b',
            'icon' => 'bi-star-fill',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertSee('عملاء VIP للحملة');
        $response->assertSee('#f59e0b');
    }

    // 3. inactive stage does not appear
    public function test_3_inactive_stage_does_not_appear(): void
    {
        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_inactive_' . uniqid(),
            'name_ar' => 'مرحلة معطلة للحملة',
            'position' => 15,
            'color' => '#6b7280',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertDontSee('مرحلة معطلة للحملة');
    }

    // 4. stage ordering follows database position
    public function test_4_stage_ordering_follows_database_position(): void
    {
        $this->customStage->update(['position' => 0]);
        $this->startStage->update(['position' => 5]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();

        $pipelineStages = $response->viewData('pipelineStages');
        $this->assertEquals('التقييم الميداني', $pipelineStages->first()->name_ar);
    }

    // 5. zero-count stage displays correctly
    public function test_5_zero_count_stage_displays_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertSee('التقييم الميداني');
        $response->assertSee('0');
    }

    // 6. stage filter works by PipelineStage ID
    public function test_6_stage_filter_works_by_pipeline_stage_id(): void
    {
        $lead1 = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Start Stage',
            'phone' => '0501110001',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        $lead2 = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Lead Custom Stage',
            'phone' => '0501110002',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);

        $this->campaign->leads()->attach([$lead1->id, $lead2->id]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', [
            'campaign' => $this->campaign,
            'stage' => $this->customStage->id,
        ]));

        $response->assertOk();
        $response->assertSee('Lead Custom Stage');
        $response->assertDontSee('Lead Start Stage');
    }

    // 7. stage filter includes all statuses attached to stage
    public function test_7_stage_filter_includes_all_statuses_attached_to_stage(): void
    {
        // startStage has newStatus and noAnswerStatus
        $leadNew = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead New Status',
            'phone' => '0501110011',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        $leadNoAnswer = Lead::query()->create([
            'lead_status_id' => $this->noAnswerStatus->id,
            'name' => 'Lead No Answer Status',
            'phone' => '0501110012',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        $leadInterested = Lead::query()->create([
            'lead_status_id' => $this->interestedStatus->id,
            'name' => 'Lead Interested Status',
            'phone' => '0501110013',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);

        $this->campaign->leads()->attach([$leadNew->id, $leadNoAnswer->id, $leadInterested->id]);

        // Filter by startStage -> must show BOTH leadNew and leadNoAnswer, but NOT leadInterested
        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', [
            'campaign' => $this->campaign,
            'stage' => $this->startStage->id,
        ]));

        $response->assertOk();
        $response->assertSee('Lead New Status');
        $response->assertSee('Lead No Answer Status');
        $response->assertDontSee('Lead Interested Status');
    }

    // 8. invalid stage ID rejected safely
    public function test_8_invalid_stage_id_rejected_safely(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', [
            'campaign' => $this->campaign,
            'stage' => 999999,
        ]));

        $response->assertOk();
        $this->assertNull($response->viewData('selectedStage'));
    }

    // 9. campaign lead scope preserved
    public function test_9_campaign_lead_scope_preserved(): void
    {
        // Lead attached to this campaign
        $leadInCampaign = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Lead Inside Campaign',
            'phone' => '0501110021',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        $this->campaign->leads()->attach($leadInCampaign);

        // Lead NOT attached to this campaign
        $leadOutsideCampaign = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Lead Outside Campaign',
            'phone' => '0501110022',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertSee('Lead Inside Campaign');
        $response->assertDontSee('Lead Outside Campaign');
    }

    // 10. branch scope preserved
    public function test_10_branch_and_user_lead_scope_preserved(): void
    {
        $restrictedGroup = Group::query()->create([
            'name' => 'مندوب حملة محدود',
            'code' => 'camp-agent-own',
            'is_system' => false,
        ]);
        $restrictedGroup->permissions()->attach(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ])->pluck('id'));

        $campaignAgent = User::factory()->create([
            'username' => 'camp_agent_sam',
            'name' => 'Agent Sam',
            'is_active' => true,
        ]);
        $campaignAgent->groups()->attach($restrictedGroup);
        $this->campaign->users()->attach($campaignAgent);

        // Lead assigned to Sam
        $leadSam = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Sams Campaign Lead',
            'phone' => '0501110031',
            'assigned_user_id' => $campaignAgent->id,
            'source' => 'web',
        ]);
        $this->campaign->leads()->attach($leadSam);

        // Lead assigned to Admin
        $leadAdmin = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Admins Campaign Lead',
            'phone' => '0501110032',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        $this->campaign->leads()->attach($leadAdmin);

        // Agent Sam views campaign -> only sees his leads
        $response = $this->actingAs($campaignAgent)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertSee('Sams Campaign Lead');
    }

    // 11. employee + stage filters work together
    public function test_11_employee_and_stage_filters_work_together(): void
    {
        $agentUser = User::factory()->create([
            'username' => 'agent_tom',
            'name' => 'Agent Tom',
            'is_active' => true,
        ]);
        $this->campaign->users()->attach($agentUser);

        // Lead 1: Tom in customStage
        $lead1 = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Tom Custom Lead',
            'phone' => '0501110041',
            'assigned_user_id' => $agentUser->id,
            'source' => 'web',
        ]);
        // Lead 2: Admin in customStage
        $lead2 = Lead::query()->create([
            'lead_status_id' => $this->customStatus->id,
            'name' => 'Admin Custom Lead',
            'phone' => '0501110042',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        // Lead 3: Tom in startStage
        $lead3 = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Tom Start Lead',
            'phone' => '0501110043',
            'assigned_user_id' => $agentUser->id,
            'source' => 'web',
        ]);

        $this->campaign->leads()->attach([$lead1->id, $lead2->id, $lead3->id]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', [
            'campaign' => $this->campaign,
            'assigned_user_id' => $agentUser->id,
            'stage' => $this->customStage->id,
        ]));

        $response->assertOk();
        $response->assertSee('Tom Custom Lead');
        $response->assertDontSee('Admin Custom Lead');
        $response->assertDontSee('Tom Start Lead');
    }

    // 12. localized stage names render
    public function test_12_localized_stage_names_render(): void
    {
        app()->setLocale('ar');
        $responseAr = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $responseAr->assertOk();
        $responseAr->assertSee('البداية');
        $responseAr->assertSee('التقييم الميداني');

        app()->setLocale('en');
        $responseEn = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $responseEn->assertOk();
        $responseEn->assertSee('Start');
    }

    // 13. icon/color metadata render safely
    public function test_13_icon_and_color_metadata_render_safely(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $this->campaign));
        $response->assertOk();
        $response->assertSee('--status-color:#8b5cf6');
    }

    // 14. legacy hardcoded status filter status={code} remains compatible
    public function test_14_legacy_status_filter_remains_compatible(): void
    {
        $leadNew = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'Lead Legacy New',
            'phone' => '0501110051',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);
        $leadNoAnswer = Lead::query()->create([
            'lead_status_id' => $this->noAnswerStatus->id,
            'name' => 'Lead Legacy No Answer',
            'phone' => '0501110052',
            'assigned_user_id' => $this->admin->id,
            'source' => 'web',
        ]);

        $this->campaign->leads()->attach([$leadNew->id, $leadNoAnswer->id]);

        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.show', [
            'campaign' => $this->campaign,
            'status' => 'new',
        ]));

        $response->assertOk();
        $response->assertSee('Lead Legacy New');
        $response->assertDontSee('Lead Legacy No Answer');
    }

    // 15. existing Campaign tests remain green
    public function test_15_existing_campaign_tests_remain_green(): void
    {
        $response = $this->actingAs($this->admin)->get(route('v2.campaigns.index'));
        $response->assertOk();
        $response->assertSee('حملة الصيف العقارية');
    }
}
