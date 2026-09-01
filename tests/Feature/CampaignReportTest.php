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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CampaignReportTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
    public function test_1_campaign_report_requires_report_permission(): void
    {
        $unauthorized = User::factory()->create(['is_active' => true]);

        $this->actingAs($unauthorized)
            ->get(route('v2.campaigns.reports'))
            ->assertForbidden();
    }

    public function test_2_super_admin_can_view_campaign_report_page(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('v2.campaigns.reports'))
            ->assertOk()
            ->assertViewIs('campaigns.reports')
            ->assertSee(__('crm.campaign_reports'));
    }

    public function test_3_report_calculates_period_metrics_and_current_donor_conversion(): void
    {
        CarbonImmutable::setTestNow('2026-08-22 12:00:00');

        $admin = $this->superAdmin();
        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $donorStatus = $this->leadStatus('donor', 'متبرع', false, '#16a34a', 2);

        $endedCampaign = $this->campaign(
            $admin,
            'August Ended Campaign',
            '2026-08-03 10:00:00',
            '2026-08-04 09:00:00',
            '2026-08-10 18:00:00',
            1000.0
        );
        $activeCampaign = $this->campaign(
            $admin,
            'August Active Campaign',
            '2026-08-05 10:00:00',
            '2026-08-05 09:00:00',
            '2026-08-30 18:00:00',
            2000.0
        );
        $this->campaign(
            $admin,
            'July Ended Campaign',
            '2026-07-02 10:00:00',
            '2026-07-02 09:00:00',
            '2026-07-20 18:00:00',
            1500.0
        );

        $sharedDonor = $this->lead($donorStatus, 'Shared Donor');
        $secondDonor = $this->lead($donorStatus, 'Second Donor');
        $newLead = $this->lead($newStatus, 'New Campaign Lead');

        $endedCampaign->leads()->attach([$sharedDonor->id, $newLead->id]);
        $activeCampaign->leads()->attach([$sharedDonor->id, $secondDonor->id]);

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'period' => 'custom',
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('campaigns.reports')
            ->assertSee(__('crm.campaigns_created'))
            ->assertSee(__('crm.campaigns_ended'))
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['created_campaigns'] === 2
                && $metrics['ended_campaigns'] === 1
                && $metrics['current_leads'] === 3
                && $metrics['donor_leads'] === 2
                && $metrics['total_campaign_cost'] === 4500.0
                && $metrics['lead_cost'] === 1500.0
                && $metrics['conversion_rate'] === 66.7)
            ->assertViewHas('timeline', static fn (array $timeline): bool => collect($timeline)->sum('created') === 2
                && collect($timeline)->sum('ended') === 1)
            ->assertViewHas('stageDistribution', static fn ($stages): bool => $stages->firstWhere('code', 'donor')['count'] === 2
                && $stages->firstWhere('code', 'donor')['percentage'] === 66.7
                && $stages->firstWhere('code', 'new')['count'] === 1);
    }

    public function test_4_report_can_filter_metrics_and_conversion_by_campaign(): void
    {
        CarbonImmutable::setTestNow('2026-08-22 12:00:00');

        $admin = $this->superAdmin();
        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $donorStatus = $this->leadStatus('donor', 'متبرع', false, '#16a34a', 2);

        $targetCampaign = $this->campaign(
            $admin,
            'Target Campaign',
            '2026-08-01 09:00:00',
            '2026-08-01 09:00:00',
            '2026-08-25 18:00:00',
            2400.0
        );
        $otherCampaign = $this->campaign(
            $admin,
            'Other Campaign',
            '2026-08-05 09:00:00',
            '2026-08-05 09:00:00',
            '2026-08-28 18:00:00',
            1200.0
        );

        $targetLead = $this->lead($donorStatus, 'Target Donor');
        $otherLead = $this->lead($newStatus, 'Other Lead');

        $targetCampaign->leads()->attach([$targetLead->id]);
        $otherCampaign->leads()->attach([$otherLead->id]);

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'campaign_id' => $targetCampaign->id,
            'period' => 'month',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('selectedCampaign', static fn (?Campaign $campaign): bool => $campaign?->id === $targetCampaign->id)
            ->assertViewHas('timeline', static fn (array $timeline): bool => $timeline === [])
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['current_leads'] === 1
                && $metrics['donor_leads'] === 1
                && $metrics['total_campaign_cost'] === 2400.0
                && $metrics['lead_cost'] === 2400.0
                && $metrics['conversion_rate'] === 100.0);
    }

    public function test_5_report_filters_selected_campaign_metrics_by_employee(): void
    {
        CarbonImmutable::setTestNow('2026-08-22 12:00:00');

        $admin = $this->superAdmin();
        $manager = $this->userWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_ALL->value,
        ]);
        $agentA = $this->userWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::LEADS_VIEW->value,
        ]);
        $agentB = $this->userWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::LEADS_VIEW->value,
        ]);

        $donorStatus = $this->leadStatus('donor', 'متبرع', false, '#16a34a', 2);
        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);

        $campaign = $this->campaign(
            $manager,
            'Multi User Campaign',
            '2026-08-01 09:00:00',
            '2026-08-01 09:00:00',
            '2026-08-31 18:00:00',
            5000.0
        );
        $campaign->users()->syncWithoutDetaching([$agentA->id, $agentB->id, $manager->id]);
        $leadA = $this->lead($donorStatus, 'Lead Agent A', $agentA->id);
        $leadB = $this->lead($newStatus, 'Lead Agent B', $agentB->id);

        $campaign->leads()->attach([$leadA->id, $leadB->id]);

        $response = $this->actingAs($manager)->get(route('v2.campaigns.reports', [
            'campaign_id' => $campaign->id,
            'employee_id' => $agentA->id,
            'period' => 'month',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('selectedEmployee', static fn (?User $employee): bool => $employee?->id === $agentA->id)
            ->assertViewHas('metrics', static fn (array $metrics): bool => $metrics['current_leads'] === 1
                && $metrics['donor_leads'] === 1
                && $metrics['conversion_rate'] === 100.0);
    }

    public function test_6_report_rejects_campaign_outside_the_users_visible_scope(): void
    {
        $agent = $this->userWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_VIEW->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
            CrmPermission::LEADS_VIEW->value,
        ]);
        $otherUser = $this->superAdmin();

        $hiddenCampaign = $this->campaign(
            $otherUser,
            'Hidden Campaign',
            '2026-08-01 09:00:00',
            '2026-08-01 09:00:00',
            '2026-08-31 18:00:00',
            1000.0
        );

        $this->actingAs($agent)
            ->get(route('v2.campaigns.reports', [
                'campaign_id' => $hiddenCampaign->id,
            ]))
            ->assertNotFound();
    }

    public function test_7_report_rejects_an_excessive_custom_period(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('v2.campaigns.reports', [
                'period' => 'custom',
                'from' => '1995-01-01',
                'to' => '2026-01-01',
            ]))
            ->assertSessionHasErrors('to');
    }

    public function test_8_active_and_custom_stages_appear_dynamically_in_report(): void
    {
        $admin = $this->superAdmin();

        $customStage = PipelineStage::query()->create([
            'code' => 'stage_vip_ops',
            'name_ar' => 'عمليات كبار العملاء',
            'position' => 10,
            'color' => '#8b5cf6',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $customStatus = $customStage->ensureDefaultStatus();

        $inactiveStage = PipelineStage::query()->create([
            'code' => 'stage_archived_camp_report',
            'name_ar' => 'مرحلة معطلة',
            'position' => 20,
            'color' => '#6b7280',
            'is_primary' => false,
            'is_active' => false,
        ]);

        $camp = $this->createCampaign('حملة المراحل المخصصة', 2000.0, '2026-01-01', '2026-12-31');
        $lead = $this->lead($customStatus, 'عميل VIP للمرحلة');
        $camp->leads()->attach($lead);

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'campaign_id' => $camp->id,
        ]));

        $response->assertOk();
        $stageDist = $response->viewData('stageDistribution');

        $this->assertContains('عمليات كبار العملاء', $stageDist->pluck('label')->all());
        $this->assertNotContains('مرحلة معطلة', $stageDist->pluck('label')->all());
        $this->assertEquals(1, $stageDist->firstWhere('code', 'stage_vip_ops')['count']);
        $this->assertEquals(100.0, $stageDist->firstWhere('code', 'stage_vip_ops')['percentage']);
    }

    public function test_9_zero_count_stage_displays_zero_percentage(): void
    {
        $admin = $this->superAdmin();

        $emptyStage = PipelineStage::query()->create([
            'code' => 'stage_empty_testing',
            'name_ar' => 'مرحلة فارغة للاختبار',
            'position' => 15,
            'color' => '#3b82f6',
            'is_primary' => false,
            'is_active' => true,
        ]);
        $emptyStage->ensureDefaultStatus();

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports'));
        $response->assertOk();

        $stageDist = $response->viewData('stageDistribution');
        $emptyData = $stageDist->firstWhere('code', 'stage_empty_testing');
        $this->assertNotNull($emptyData);
        $this->assertEquals(0, $emptyData['count']);
        $this->assertEquals(0.0, $emptyData['percentage']);
    }

    public function test_10_cost_per_lead_handles_zero_leads_safely(): void
    {
        $admin = $this->superAdmin();
        $camp = $this->createCampaign('حملة بدون عملاء', 5000.0, '2026-01-01', '2026-12-31');

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'campaign_id' => $camp->id,
        ]));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(0, $metrics['current_leads']);
        $this->assertEquals(0.0, $metrics['lead_cost']);
        $this->assertEquals(0.0, $metrics['conversion_rate']);
    }
    public function test_11_report_can_calculate_conversion_for_selected_stage(): void
    {
        $admin = $this->superAdmin();
        $interestedStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'interested'],
            [
                'name_ar' => 'مهتم',
                'position' => 3,
                'color' => '#f59e0b',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $interestedStatus = $interestedStage->ensureDefaultStatus();

        $newStatus = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $camp = $this->createCampaign('حملة قياس مرحلة مهتم', 3000.0, '2026-01-01', '2026-12-31');

        $lead1 = $this->lead($interestedStatus, 'عميل مهتم 1');
        $lead2 = $this->lead($interestedStatus, 'عميل مهتم 2');
        $lead3 = $this->lead($newStatus, 'عميل جديد 1');
        $lead4 = $this->lead($newStatus, 'عميل جديد 2');

        $camp->leads()->attach([$lead1->id, $lead2->id, $lead3->id, $lead4->id]);

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'campaign_id' => $camp->id,
            'stage_id' => $interestedStage->id,
        ]));

        $response->assertOk();
        $metrics = $response->viewData('metrics');
        $this->assertEquals(4, $metrics['current_leads']);
        $this->assertEquals(2, $metrics['converted_leads']);
        $this->assertEquals(50.0, $metrics['conversion_rate']);
        $this->assertEquals($interestedStage->id, $metrics['conversion_stage_id']);
        $this->assertEquals($interestedStage->localizedName(), $metrics['conversion_stage_name']);
        $this->assertEquals($interestedStage->id, $response->viewData('selectedStage')->id);
    }

    public function test_12_report_view_does_not_contain_donor_word_under_conversion(): void
    {
        $admin = $this->superAdmin();
        $camp = $this->createCampaign('حملة واجهة التحويل', 1000.0, '2026-01-01', '2026-12-31');
        $status = $this->leadStatus('new', 'جديد', false, '#3478f6', 1);
        $lead = $this->lead($status, 'عميل فحص واجهة');
        $camp->leads()->attach($lead);

        $response = $this->actingAs($admin)->get(route('v2.campaigns.reports', [
            'campaign_id' => $camp->id,
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('متبرع', $content);
    }
    private function campaign(
        User $creator,
        string $name,
        string $createdAt,
        string $startsAt,
        string $endsAt,
        float $cost = 1000.0
    ): Campaign {
        $campaign = new Campaign();
        $campaign->name = $name;
        $campaign->cost = $cost;
        $campaign->created_by_user_id = $creator->id;
        $campaign->starts_at = CarbonImmutable::parse($startsAt);
        $campaign->ends_at = CarbonImmutable::parse($endsAt);
        $campaign->created_at = CarbonImmutable::parse($createdAt);
        $campaign->updated_at = CarbonImmutable::parse($createdAt);
        $campaign->save();

        $campaign->users()->syncWithoutDetaching([$creator->id]);

        return $campaign;
    }

    private function createCampaign(string $name, float $cost, string $startsAt, string $endsAt): Campaign
    {
        $admin = $this->superAdmin();
        $camp = Campaign::query()->create([
            'name' => $name,
            'cost' => $cost,
            'starts_at' => CarbonImmutable::parse($startsAt)->startOfDay(),
            'ends_at' => CarbonImmutable::parse($endsAt)->endOfDay(),
            'created_by_user_id' => $admin->id,
        ]);
        $camp->users()->attach($admin);

        return $camp;
    }

    private function lead(LeadStatus $status, string $name, ?int $assignedUserId = null): Lead
    {
        return Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => $name,
            'phone' => '050' . random_int(1000000, 9999999),
            'assigned_user_id' => $assignedUserId ?? $this->superAdmin()->id,
            'source' => 'campaign',
        ]);
    }

    private function leadStatus(
        string $code,
        string $name,
        bool $terminal,
        string $color,
        int $position
    ): LeadStatus {
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => $code],
            [
                'name_ar' => $name,
                'position' => $position,
                'color' => $color,
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        return LeadStatus::query()->firstOrCreate(
            ['code' => $code],
            [
                'pipeline_stage_id' => $stage->id,
                'name_ar' => $name,
                'position' => $position,
                'color' => $color,
                'is_terminal' => $terminal,
            ]
        );
    }

    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'مجموعة الاختبار ' . uniqid(),
            'code' => 'group_' . uniqid(),
            'is_system' => false,
        ]);

        $permissions = Permission::query()->whereIn('code', $permissionCodes)->get();
        $missing = array_diff($permissionCodes, $permissions->pluck('code')->all());
        foreach ($missing as $code) {
            $permissions->push(Permission::query()->create([
                'code' => $code,
                'module' => explode('.', $code, 2)[0] ?? 'crm',
                'name_ar' => $code,
            ]));
        }
        $group->permissions()->attach($permissions->pluck('id'));

        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group);

        return $user;
    }

    private function superAdmin(): User
    {
        $superAdminGroup = Group::query()->where('code', Group::SUPER_ADMIN_CODE)->first();
        if ($superAdminGroup === null) {
            $superAdminGroup = Group::query()->create([
                'code' => Group::SUPER_ADMIN_CODE,
                'name' => 'مدير النظام',
                'description' => 'Super Admin',
                'is_system' => true,
            ]);
        }

        $user = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'name' => 'Super Administrator',
            'is_active' => true,
        ]);
        $user->groups()->attach($superAdminGroup);

        return $user;
    }
}
