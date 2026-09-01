<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoipAndSettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $agentWithVoip;

    private User $agentWithoutVoip;

    private User $branchAgentB;

    private Lead $leadA;

    private Lead $leadB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        if (Permission::query()->count() < count(CrmPermission::cases())) {
            $perms = array_map(static fn ($p) => [
                'code' => $p->value,
                'module' => $p->module(),
                'name_ar' => $p->label(),
            ], CrmPermission::cases());
            Permission::query()->upsert($perms, ['code'], ['module', 'name_ar']);
        }

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'مدير النظام',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'username' => 'super_admin_voip_sec',
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        // Group with VoIP view permission
        $voipGroup = Group::query()->create([
            'name' => 'فريق الكول سنتر',
            'code' => 'call-center-group',
            'is_system' => false,
        ]);
        $voipGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::VOIP_VIEW->value,
            CrmPermission::USERS_VIEW->value,
            CrmPermission::USERS_CREATE->value,
            CrmPermission::USERS_UPDATE->value,
        ])->pluck('id'));

        $this->agentWithVoip = User::factory()->create([
            'name' => 'Agent With VoIP',
            'username' => 'agent_voip_a',
            'is_active' => true,
        ]);
        $this->agentWithVoip->groups()->attach($voipGroup);

        // Group without VoIP permission
        $noVoipGroup = Group::query()->create([
            'name' => 'فريق بلا هاتف',
            'code' => 'no-voip-group',
            'is_system' => false,
        ]);
        $noVoipGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ])->pluck('id'));

        $this->agentWithoutVoip = User::factory()->create([
            'name' => 'Agent Without VoIP',
            'username' => 'agent_no_voip',
            'is_active' => true,
        ]);
        $this->agentWithoutVoip->groups()->attach($noVoipGroup);

        $this->branchAgentB = User::factory()->create([
            'name' => 'Branch B Agent',
            'username' => 'agent_branch_b_voip',
            'is_active' => true,
        ]);
        $this->branchAgentB->groups()->attach($voipGroup);

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
            'name' => 'Lead Agent A',
            'phone' => '0501119900',
            'assigned_user_id' => $this->agentWithVoip->id,
            'source' => 'web',
        ]);

        $this->leadB = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Lead Agent B Secret',
            'phone' => '0502229900',
            'assigned_user_id' => $this->branchAgentB->id,
            'source' => 'web',
        ]);
    }

    public function test_voip_calls_endpoint_strictly_blocks_unauthorized_lead_idor(): void
    {
        // Agent A has voip.view, but leadB is assigned to Agent B and not accessible to Agent A
        $response = $this->actingAs($this->agentWithVoip)->get(route('v2.leads.calls', $this->leadB));
        $response->assertForbidden();
    }

    public function test_user_without_voip_view_cannot_access_calls_endpoint(): void
    {
        $response = $this->actingAs($this->agentWithoutVoip)->get(route('v2.leads.calls', $this->leadA));
        $response->assertForbidden();
    }

    public function test_incoming_microsip_call_opens_accessible_lead_with_equivalent_phone_format(): void
    {
        $this->leadA->update(['phone' => '010 1234-5678']);

        $response = $this->actingAs($this->agentWithVoip)->get(route('v2.voip.incoming', [
            'phone' => '+201012345678',
        ]));

        $response->assertRedirect(route('v2.leads.show', $this->leadA));
    }
    public function test_incoming_microsip_call_with_sip_uri_and_display_name_opens_lead(): void
    {
        $this->leadA->update(['phone' => '01012345678']);

        $response = $this->actingAs($this->agentWithVoip)->get(route('v2.voip.incoming', [
            'phone' => '"Customer" <sip:+201012345678@192.168.100.128:5060>',
        ]));

        $response->assertRedirect(route('v2.leads.show', $this->leadA));
    }

    public function test_incoming_microsip_call_via_legacy_endpoint_and_caller_param(): void
    {
        $this->leadA->update(['phone' => '01012345678']);

        $responseLegacy = $this->actingAs($this->agentWithVoip)->get('/custom-dashboard/caller-lookup.php?phone=01012345678');
        $responseLegacy->assertRedirect(route('v2.leads.show', $this->leadA));

        $responseCallerParam = $this->actingAs($this->agentWithVoip)->get('/voip/incoming?caller=01012345678');
        $responseCallerParam->assertRedirect(route('v2.leads.show', $this->leadA));
    }


    public function test_incoming_microsip_call_does_not_reveal_inaccessible_lead(): void
    {
        $response = $this->actingAs($this->agentWithVoip)->get(route('v2.voip.incoming', [
            'phone' => $this->leadB->phone,
        ]));

        $response->assertRedirect(route('v2.leads', ['q' => $this->leadB->phone]));
    }

    public function test_incoming_microsip_call_requires_lead_view_permission(): void
    {
        $group = $this->agentWithoutVoip->groups()->first();
        $group->permissions()->detach(Permission::where('code', CrmPermission::LEADS_VIEW->value)->value('id'));

        $this->actingAs($this->agentWithoutVoip)
            ->get(route('v2.voip.incoming', ['phone' => $this->leadA->phone]))
            ->assertForbidden();
    }

    public function test_customer_list_call_action_uses_tel_handler_for_microsip(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('v2.leads'))
            ->assertOk()
            ->assertSee('data-call-href="tel:0501119900"', false)
            ->assertSee(route('v2.leads.followups.index', ['lead' => $this->leadA, 'channel' => 'call']), false);

        $this->actingAs($this->superAdmin)
            ->get(route('v2.leads.followups.index', ['lead' => $this->leadA, 'channel' => 'call']))
            ->assertOk()
            ->assertSee('tel:', false)
            ->assertSee('0501119900', false);

        $this->actingAs($this->agentWithVoip)
            ->get(route('v2.leads'))
            ->assertOk()
            ->assertSee('href="tel:0501119900"', false);
    }
    public function test_lead_show_call_action_opens_followup_and_microsip_tel_handler(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('v2.leads.show', $this->leadA))
            ->assertOk()
            ->assertSee('js-call-followup', false)
            ->assertSee('data-call-href="tel:0501119900"', false)
            ->assertSee(route('v2.leads.followups.index', ['lead' => $this->leadA, 'channel' => 'call']), false);

        $this->actingAs($this->agentWithVoip)
            ->get(route('v2.leads.show', $this->leadA))
            ->assertOk()
            ->assertSee('href="tel:0501119900"', false);
    }


    public function test_user_without_voip_recordings_cannot_stream_recording(): void
    {
        $response = $this->actingAs($this->agentWithVoip)->get(route('v2.voip.recordings.stream', 'rec_12345'));
        $response->assertForbidden();
    }

    public function test_user_without_voip_live_panel_cannot_access_live_panel(): void
    {
        $response = $this->actingAs($this->agentWithVoip)->get(route('v2.voip.live'));
        $response->assertForbidden();
    }

    public function test_user_without_voip_settings_cannot_access_voip_settings_or_pair(): void
    {
        $this->actingAs($this->agentWithVoip)->get(route('v2.settings.voip'))->assertForbidden();
        $this->actingAs($this->agentWithVoip)->post(route('v2.settings.voip.pair'))->assertForbidden();
        $this->actingAs($this->agentWithVoip)->post(route('v2.settings.voip.disconnect'))->assertForbidden();
    }

    public function test_privilege_escalation_non_super_admin_cannot_assign_super_admin_group(): void
    {
        $superAdminGroupId = Group::where('code', Group::SUPER_ADMIN_CODE)->value('id');

        // AgentWithVoip has users.create, but must not be able to assign super-admin group
        $response = $this->actingAs($this->agentWithVoip)->post(route('v2.settings.users.store'), [
            'name' => 'Escalated User',
            'username' => 'escalated_user',
            'email' => 'escalated@example.com',
            'password' => 'SecurePass123456!',
            'password_confirmation' => 'SecurePass123456!',
            'group_ids' => [$superAdminGroupId],
        ]);

        $this->assertTrue($response->isInvalid() || $response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseMissing('users', ['username' => 'escalated_user']);
    }

    public function test_privilege_escalation_non_super_admin_cannot_modify_or_reset_super_admin(): void
    {
        $editResponse = $this->actingAs($this->agentWithVoip)->get(route('v2.settings.users.edit', $this->superAdmin));
        $editResponse->assertForbidden();

        $updateResponse = $this->actingAs($this->agentWithVoip)->patch(route('v2.settings.users.update', $this->superAdmin), [
            'name' => 'Compromised Admin Name',
            'username' => $this->superAdmin->username,
            'group_ids' => [$this->agentWithVoip->groups()->first()->id],
        ]);
        $updateResponse->assertForbidden();

        $resetResponse = $this->actingAs($this->agentWithVoip)->patch(route('v2.settings.users.password', $this->superAdmin), [
            'password' => 'NewHackedPass123!',
            'password_confirmation' => 'NewHackedPass123!',
        ]);
        $resetResponse->assertForbidden();
    }
}
