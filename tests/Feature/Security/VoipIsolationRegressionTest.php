<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoipIsolationRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $agentA;
    private User $agentB;
    private User $unpermittedUser;
    private Lead $leadA;
    private Lead $leadB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ]
            );
        }

        // Group with voip.view and leads.view
        $voipViewGroup = Group::query()->create([
            'name' => 'فريق مشاهدة المكالمات',
            'code' => 'voip-viewers',
            'is_system' => false,
        ]);
        $voipViewGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::VOIP_VIEW->value,
        ])->pluck('id'));

        $this->agentA = User::factory()->create([
            'name' => 'Agent A Voip',
            'username' => 'agent_a_voip_reg',
            'is_active' => true,
        ]);
        $this->agentA->groups()->attach($voipViewGroup);

        $this->agentB = User::factory()->create([
            'name' => 'Agent B Voip',
            'username' => 'agent_b_voip_reg',
            'is_active' => true,
        ]);
        $this->agentB->groups()->attach($voipViewGroup);

        $this->unpermittedUser = User::factory()->create([
            'name' => 'Unpermitted User',
            'username' => 'unpermitted_voip_reg',
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->create([
            'code' => 'start',
            'name_ar' => 'البداية',
            'position' => 1,
            'color' => '#3478f6',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $status = $stage->statuses()->first();
        $status->update(['code' => 'new', 'name_ar' => 'جديد']);

        $this->leadA = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Lead A Reg',
            'phone' => '0501114444',
            'assigned_user_id' => $this->agentA->id,
            'source' => 'web',
        ]);

        $this->leadB = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Lead B Reg',
            'phone' => '0502224444',
            'assigned_user_id' => $this->agentB->id,
            'source' => 'web',
        ]);
    }

    public function test_voip_calls_endpoint_returns_403_for_foreign_lead_even_with_voip_permission(): void
    {
        // Agent A has voip.view, but leadB belongs to Agent B
        $response = $this->actingAs($this->agentA)->get(route('v2.leads.calls', $this->leadB));
        $response->assertForbidden();
    }

    public function test_voip_calls_endpoint_succeeds_for_accessible_lead(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.leads.calls', $this->leadA));
        $response->assertOk();
    }

    public function test_voip_calls_endpoint_returns_403_without_voip_view_permission(): void
    {
        $response = $this->actingAs($this->unpermittedUser)->get(route('v2.leads.calls', $this->leadA));
        $response->assertForbidden();
    }

    public function test_voip_recordings_stream_returns_403_without_recordings_permission(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.voip.recordings.stream', 'rec_abc123'));
        $response->assertForbidden();
    }

    public function test_voip_live_panel_returns_403_without_live_panel_permission(): void
    {
        $response = $this->actingAs($this->agentA)->get(route('v2.voip.live'));
        $response->assertForbidden();
    }

    public function test_voip_extension_stats_returns_403_without_voip_view_permission(): void
    {
        $response = $this->actingAs($this->unpermittedUser)->get(route('v2.voip.stats', '101'));
        $response->assertForbidden();
    }

    public function test_voip_settings_routes_return_403_without_voip_settings_permission(): void
    {
        $this->actingAs($this->agentA)->get(route('v2.settings.voip'))->assertForbidden();
        $this->actingAs($this->agentA)->post(route('v2.settings.voip.pair'))->assertForbidden();
        $this->actingAs($this->agentA)->post(route('v2.settings.voip.disconnect'))->assertForbidden();
    }
}
