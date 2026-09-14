<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\PhoneMask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelephonyApiTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;
    private User $maskedUser;
    private Lead $lead;

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

        Permission::query()->firstOrCreate(
            ['code' => 'leads.phone.view'],
            [
                'module' => 'leads',
                'name_ar' => 'عرض أرقام الهواتف غير المقنعة',
            ]
        );

        $authGroup = Group::query()->create([
            'name' => 'Full View Group',
            'code' => 'full-view',
            'is_system' => false,
        ]);
        $phoneViewCode = defined(CrmPermission::class.'::LEADS_PHONE_VIEW')
            ? CrmPermission::LEADS_PHONE_VIEW->value
            : 'leads.phone.view';
        $authGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_ALL->value,
            $phoneViewCode,
        ])->pluck('id'));

        $this->authorizedUser = User::factory()->create([
            'name' => 'Agent Unmasked',
            'username' => 'agent_unmasked',
            'is_active' => true,
            'voip_extension' => '150',
        ]);
        $this->authorizedUser->groups()->attach($authGroup);

        $maskedGroup = Group::query()->create([
            'name' => 'Masked View Group',
            'code' => 'masked-view',
            'is_system' => false,
        ]);
        $maskedGroup->permissions()->sync(Permission::whereIn('code', [
            CrmPermission::LEADS_VIEW->value,
            CrmPermission::LEADS_SCOPE_ALL->value,
        ])->pluck('id'));

        $this->maskedUser = User::factory()->create([
            'name' => 'Agent Masked',
            'username' => 'agent_masked',
            'is_active' => true,
            'voip_extension' => '151',
        ]);
        $this->maskedUser->groups()->attach($maskedGroup);

        $stage = PipelineStage::query()->create([
            'name_ar' => 'مرحلة تجريبية',
            'code' => 'test-stage',
            'position' => 1,
            'color' => '#10b981',
            'is_active' => true,
        ]);

        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'test-status'],
            [
                'name_ar' => 'حالة تجريبية',
                'pipeline_stage_id' => $stage->id,
                'position' => 1,
                'color' => '#10b981',
            ]
        );

        $this->lead = Lead::query()->create([
            'name' => 'عميل تجريبي للاتصال',
            'phone' => '01012345678',
            'lead_status_id' => $status->id,
            'assigned_user_id' => $this->authorizedUser->id,
            'created_by_user_id' => $this->authorizedUser->id,
        ]);
    }

    public function test_telephony_lookup_returns_unmasked_phone_for_authorized_user(): void
    {
        $response = $this->actingAs($this->authorizedUser)
            ->getJson('/v2/api/telephony/lookup?phone=01012345678');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('match_count', 1);
        $response->assertJsonPath('leads.0.id', $this->lead->id);
        $response->assertJsonPath('leads.0.phone', '01012345678');
    }

    public function test_telephony_lookup_returns_masked_phone_for_user_without_phone_view_permission(): void
    {
        $response = $this->actingAs($this->maskedUser)
            ->getJson('/v2/api/telephony/lookup?phone=01012345678');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('match_count', 1);
        $response->assertJsonPath('leads.0.id', $this->lead->id);
        $this->assertEquals(PhoneMask::mask('01012345678'), $response->json('leads.0.phone'));
    }

    public function test_telephony_lookup_with_unknown_phone_returns_empty(): void
    {
        $response = $this->actingAs($this->authorizedUser)
            ->getJson('/v2/api/telephony/lookup?phone=01099999999');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('match_count', 0);
        $response->assertJsonPath('leads', []);
    }

    public function test_missed_call_records_notification_for_extension_user(): void
    {
        $response = $this->actingAs($this->authorizedUser)
            ->postJson('/v2/api/telephony/missed-call', [
                'extension' => '150',
                'phone' => '01012345678',
                'time' => now()->toIso8601String(),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $notification = $this->authorizedUser->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertEquals('مكالمة فائتة', $notification->data['title']);
        $this->assertEquals('150', $notification->data['extension']);
    }

    public function test_softphone_session_rejects_unauthenticated_user(): void
    {
        $response = $this->postJson('/voip/softphone/session');

        $this->assertTrue(in_array($response->getStatusCode(), [401, 302], true));
    }

    public function test_softphone_session_returns_403_when_user_has_no_voip_extension(): void
    {
        $userWithoutExt = User::factory()->create([
            'name' => 'No Ext Agent',
            'username' => 'no_ext_agent',
            'is_active' => true,
            'voip_extension' => null,
        ]);

        $response = $this->actingAs($userWithoutExt)
            ->postJson('/voip/softphone/session');

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_softphone_session_creates_session_and_derives_extension_strictly_from_authenticated_user(): void
    {
        config([
            'voip.api_url' => 'http://192.168.100.128:8090',
            'voip.api_key' => 'sokrat-crm-secret-key-2026',
        ]);

        Http::fake([
            'http://192.168.100.128:8090/api/v1/softphone-sessions' => Http::response([
                'success' => true,
                'sessionUrl' => '/phone?session=mocked-token-1234567890abcdef',
            ], 200),
        ]);

        // Attempt to pass client-supplied extension "999" to verify it is ignored
        $response = $this->actingAs($this->authorizedUser)
            ->postJson('/voip/softphone/session', [
                'extension' => '999',
            ]);

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        $data = $response->json();
        // Return ONLY { success:true, sessionUrl:<validated VoIP-origin URL>, extension:<assigned extension> }
        $this->assertEquals(['success', 'sessionUrl', 'extension'], array_keys($data));
        $this->assertTrue($data['success']);
        $this->assertEquals('150', $data['extension']);
        $this->assertEquals('http://192.168.100.128:8090/phone?session=mocked-token-1234567890abcdef', $data['sessionUrl']);

        // Assert exactly one upstream request sent with extension 150 (not client's 999)
        Http::assertSent(function ($request) {
            return $request->url() === 'http://192.168.100.128:8090/api/v1/softphone-sessions'
                && $request['extension'] === '150'
                && $request->hasHeader('X-VoIP-API-Key', 'sokrat-crm-secret-key-2026');
        });
        Http::assertNotSent(function ($request) {
            return isset($request['extension']) && $request['extension'] === '999';
        });
    }

    public function test_softphone_session_sanitizes_upstream_failure_and_returns_502(): void
    {
        config([
            'voip.api_url' => 'http://192.168.100.128:8090',
            'voip.api_key' => 'sokrat-crm-secret-key-2026',
        ]);

        Http::fake([
            'http://192.168.100.128:8090/api/v1/softphone-sessions' => Http::response([
                'success' => false,
                'error' => 'Asterisk internal peer error on trunk PJSIP/150 secret: xyz_super_secret',
            ], 500),
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->postJson('/voip/softphone/session');

        $response->assertStatus(502);
        $response->assertJsonPath('success', false);
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        // Crucial: Must NOT leak upstream error detail or internal secrets
        $content = $response->getContent();
        $this->assertStringNotContainsString('Asterisk', $content);
        $this->assertStringNotContainsString('xyz_super_secret', $content);
    }

    public function test_softphone_session_rejects_attacker_origin_and_returns_502(): void
    {
        config([
            'voip.api_url' => 'http://192.168.100.128:8090',
            'voip.api_key' => 'sokrat-crm-secret-key-2026',
        ]);

        Http::fake([
            'http://192.168.100.128:8090/api/v1/softphone-sessions' => Http::response([
                'success' => true,
                'sessionUrl' => 'http://attacker.example.com/phone?session=evil-token',
            ], 200),
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->postJson('/voip/softphone/session');

        $response->assertStatus(502);
        $response->assertJsonPath('success', false);
        $this->assertStringNotContainsString('attacker.example.com', $response->getContent());
    }

    public function test_all_required_telephony_and_softphone_routes_resolve(): void
    {
        config([
            'voip.api_url' => 'http://192.168.100.128:8090',
            'voip.api_key' => 'sokrat-crm-secret-key-2026',
        ]);

        Http::fake([
            'http://192.168.100.128:8090/api/v1/softphone-sessions' => Http::response([
                'success' => true,
                'sessionUrl' => '/phone?session=route-check-token',
            ], 200),
        ]);

        // 1. GET /api/leads/by-phone
        $resByPhone = $this->actingAs($this->authorizedUser)->getJson('/api/leads/by-phone?phone=01012345678');
        $resByPhone->assertOk();

        // 2. GET /v2/api/telephony/lookup
        $resLookup = $this->actingAs($this->authorizedUser)->getJson('/v2/api/telephony/lookup?phone=01012345678');
        $resLookup->assertOk();

        // 3. POST /v2/api/telephony/missed-call
        $resMissed = $this->actingAs($this->authorizedUser)->postJson('/v2/api/telephony/missed-call', [
            'extension' => '150',
            'phone' => '01012345678',
        ]);
        $resMissed->assertStatus(201);

        // 4. POST /voip/softphone/session
        $resSession = $this->actingAs($this->authorizedUser)->postJson('/voip/softphone/session');
        $resSession->assertOk();

        // 5. GET /voip/softphone
        $resEmbed = $this->actingAs($this->authorizedUser)->get('/voip/softphone');
        $resEmbed->assertOk();
    }

    public function test_softphone_embed_uses_session_bootstrap_without_hardcoded_credentials(): void
    {
        config([
            'voip.api_url' => 'http://192.168.100.128:8090',
            'voip.api_key' => 'sokrat-crm-secret-key-2026',
        ]);

        Http::fake([
            'http://192.168.100.128:8090/api/v1/softphone-sessions' => Http::response([
                'success' => true,
                'sessionUrl' => '/phone?session=embed-test-token-abcdef',
            ], 200),
        ]);

        $response = $this->actingAs($this->authorizedUser)->get('/voip/softphone');
        $response->assertOk();
        $response->assertSee('embed-test-token-abcdef');

        // Verify absence of hardcoded query parameters
        $response->assertDontSee('extension=150');
        $response->assertDontSee('autologin=1');
    }
}
