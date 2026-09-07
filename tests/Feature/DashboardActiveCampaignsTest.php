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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardActiveCampaignsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PipelineStage $startStage;
    private LeadStatus $newStatus;

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
            'username' => 'dash_campaign_admin',
            'name' => 'Campaign Admin',
            'is_active' => true,
        ]);
        $this->admin->groups()->attach($superAdminGroup);

        $this->startStage = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'البداية',
                'position' => 1,
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
                'is_terminal' => false,
            ]
        );
    }

    public function test_dashboard_active_campaign_count_matches_campaign_detail_and_table_and_stage_distribution(): void
    {
        $campaign = Campaign::query()->create([
            'name' => 'حملة رمضان 2026',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 5000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaign->users()->attach($this->admin);

        for ($i = 0; $i < 10; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'عميل ' . $i,
                'phone' => '050111222' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $this->admin->id,
                'created_by_user_id' => $this->admin->id,
            ]);
            $campaign->leads()->attach($lead->id);
        }

        // 1. Dashboard Active Campaign Count
        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $activeCampaigns = $dashResponse->viewData('activeCampaigns');
        $this->assertNotEmpty($activeCampaigns);
        $cardData = collect($activeCampaigns)->firstWhere('id', $campaign->id);
        $this->assertNotNull($cardData);
        $this->assertSame(10, $cardData['total_leads']);

        // 2. Campaign Detail Hero & Stage Distribution & Customer Table
        $detailResponse = $this->actingAs($this->admin)->get(route('v2.campaigns.show', $campaign));
        $detailResponse->assertOk();
        $this->assertSame(10, $detailResponse->viewData('operationalCampaignLeads'));
        $this->assertSame(10, $detailResponse->viewData('leads')->total());

        $stageTotal = 0;
        foreach ($detailResponse->viewData('pipelineStages') as $stage) {
            $stageTotal += $stage->campaign_leads_count ?? 0;
        }
        $this->assertSame(10, $stageTotal);

        // Invariant check
        $this->assertSame($cardData['total_leads'], $detailResponse->viewData('operationalCampaignLeads'));
        $this->assertSame($cardData['total_leads'], $detailResponse->viewData('leads')->total());
        $this->assertSame($cardData['total_leads'], $stageTotal);
    }

    public function test_duplicate_campaign_membership_does_not_inflate_count(): void
    {
        $campaign = Campaign::query()->create([
            'name' => 'حملة العيد',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 3000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaign->users()->attach($this->admin);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'عميل مكرر',
            'phone' => '0509998877',
            'source' => 'campaign',
            'assigned_user_id' => $this->admin->id,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaign->leads()->attach($lead->id);

        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $cardData = collect($dashResponse->viewData('activeCampaigns'))->firstWhere('id', $campaign->id);
        $this->assertSame(1, $cardData['total_leads']);
    }

    public function test_unauthorized_leads_and_branch_scope_are_preserved(): void
    {
        $otherUser = User::factory()->create([
            'username' => 'other_agent',
            'name' => 'Other Agent',
            'is_active' => true,
        ]);

        $campaign = Campaign::query()->create([
            'name' => 'حملة مبيعات سرية',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 2000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaign->users()->attach([$this->admin->id, $otherUser->id]);

        // 3 leads assigned to $otherUser
        for ($i = 0; $i < 3; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'عميل مستخدم آخر ' . $i,
                'phone' => '050777666' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $otherUser->id,
                'created_by_user_id' => $otherUser->id,
            ]);
            $campaign->leads()->attach($lead->id);
        }

        // 2 leads assigned to $restrictedUser
        $restrictedGroup = Group::query()->create([
            'name' => 'Restricted Group',
            'code' => 'restricted-agent',
        ]);
        $restrictedGroup->permissions()->attach(
            Permission::whereIn('code', ['dashboard.view', 'campaigns.view', 'leads.view'])->pluck('id')
        );

        $restrictedUser = User::factory()->create([
            'username' => 'restricted_agent',
            'name' => 'Restricted Agent',
            'is_active' => true,
        ]);
        $restrictedUser->groups()->attach($restrictedGroup);
        $campaign->users()->attach($restrictedUser);

        for ($i = 0; $i < 2; $i++) {
            $lead = Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'عميل مقيد ' . $i,
                'phone' => '050555444' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $restrictedUser->id,
                'created_by_user_id' => $restrictedUser->id,
            ]);
            $campaign->leads()->attach($lead->id);
        }

        $dashResponse = $this->actingAs($restrictedUser)->get(route('dashboard'));
        $dashResponse->assertOk();
        $cardData = collect($dashResponse->viewData('activeCampaigns'))->firstWhere('id', $campaign->id);
        $this->assertNotNull($cardData);
        // Restricted user only sees their own 2 accessible leads, NOT all 5 leads
        $this->assertSame(2, $cardData['total_leads']);
    }

    public function test_multiple_active_campaigns_return_correct_independent_counts(): void
    {
        $campaignA = Campaign::query()->create([
            'name' => 'حملة أ',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 1000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaignA->users()->attach($this->admin);

        $campaignB = Campaign::query()->create([
            'name' => 'حملة ب',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 2000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaignB->users()->attach($this->admin);

        for ($i = 0; $i < 3; $i++) {
            $leadA = Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'عميل أ ' . $i,
                'phone' => '050333111' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $this->admin->id,
            ]);
            $campaignA->leads()->attach($leadA->id);
        }

        for ($i = 0; $i < 7; $i++) {
            $leadB = Lead::query()->create([
                'lead_status_id' => $this->newStatus->id,
                'name' => 'عميل ب ' . $i,
                'phone' => '050444222' . $i,
                'source' => 'campaign',
                'assigned_user_id' => $this->admin->id,
            ]);
            $campaignB->leads()->attach($leadB->id);
        }

        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $campaignsData = collect($dashResponse->viewData('activeCampaigns'));

        $this->assertSame(3, $campaignsData->firstWhere('id', $campaignA->id)['total_leads']);
        $this->assertSame(7, $campaignsData->firstWhere('id', $campaignB->id)['total_leads']);
    }

    public function test_zero_customer_campaign_shows_zero_and_does_not_hide_card(): void
    {
        $campaign = Campaign::query()->create([
            'name' => 'حملة فارغة جديدة',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 500,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaign->users()->attach($this->admin);

        $dashResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $cardData = collect($dashResponse->viewData('activeCampaigns'))->firstWhere('id', $campaign->id);
        $this->assertNotNull($cardData);
        $this->assertSame(0, $cardData['total_leads']);
        $dashResponse->assertSee('حملة فارغة جديدة');
        $dashResponse->assertSee('0');
    }

    public function test_percentage_and_progress_bar_are_absent_from_active_campaign_card(): void
    {
        $campaign = Campaign::query()->create([
            'name' => 'حملة واجهة نظيفة',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'cost' => 1000,
            'created_by_user_id' => $this->admin->id,
        ]);
        $campaign->users()->attach($this->admin);

        $lead = Lead::query()->create([
            'lead_status_id' => $this->newStatus->id,
            'name' => 'عميل اختبار',
            'phone' => '0508887766',
            'source' => 'campaign',
            'assigned_user_id' => $this->admin->id,
        ]);
        $campaign->leads()->attach($lead->id);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // 1. Campaign name and customer count are present
        $response->assertSee('حملة واجهة نظيفة');
        $response->assertSee('dash-campaign-chip', false);
        $response->assertSee('dash-campaign-chip-count', false);

        // 2. Percentage metric, progress track, and progress bar are absent
        $response->assertDontSee('dash-campaign-chip-pct', false);
        $response->assertDontSee('dash-campaign-progress-track', false);
        $response->assertDontSee('dash-campaign-progress-bar', false);

        // 3. View Campaigns link is preserved
        $response->assertSee(route('v2.campaigns.index'));
        $response->assertSee(__('crm.view_campaigns'));
    }
}
