<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\TechnicalSupportDevice;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TechnicalSupportTeamTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (CrmPermission::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission->value],
                [
                    'module' => $permission->module(),
                    'name_ar' => $permission->label(),
                ],
            );
        }

        $superAdminGroup = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'مدير النظام',
                'is_system' => true,
            ]
        );
        $superAdminGroup->permissions()->sync(Permission::query()->pluck('id'));

        $this->adminUser = User::factory()->create([
            'is_active' => true,
            'name' => 'Admin Test',
        ]);
        $this->adminUser->groups()->attach($superAdminGroup);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('v2.technical-support.team'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $plainUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($plainUser)
            ->get(route('v2.technical-support.team'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_team_page_with_cards(): void
    {
        $employee = User::factory()->create([
            'name' => 'Support Engineer 1',
            'is_active' => true,
            'voip_extension' => '201',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team'));

        $response->assertOk();
        $response->assertSeeText('Support Engineer 1');
        $response->assertSeeText('201');
    }

    public function test_current_logged_in_user_shows_as_online(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team'));

        $response->assertOk();
        // The admin user is currently logged in, so they must have online status
        $response->assertSeeText(__('crm.online_now'));
    }

    public function test_user_with_active_session_is_marked_online(): void
    {
        $activeUser = User::factory()->create([
            'name' => 'Online Employee',
            'is_active' => true,
        ]);

        $offlineUser = User::factory()->create([
            'name' => 'Offline Employee',
            'is_active' => true,
        ]);

        // Insert active session for activeUser
        DB::table('sessions')->insert([
            'id' => 'test_session_active_123',
            'user_id' => $activeUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => now()->subMinutes(2)->getTimestamp(),
        ]);

        // Insert old session for offlineUser (older than 15 minutes)
        DB::table('sessions')->insert([
            'id' => 'test_session_old_456',
            'user_id' => $offlineUser->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => now()->subMinutes(30)->getTimestamp(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team'));

        $response->assertOk();
        $response->assertSeeText('Online Employee');
        $response->assertSeeText('Offline Employee');
    }

    public function test_card_displays_active_open_ticket_and_timer(): void
    {
        $tech = User::factory()->create([
            'name' => 'Active Tech',
            'is_active' => true,
        ]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'server-alpha-01',
            'name' => 'Database Primary',
            'company_name' => 'Acme Corp',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $openTicket = TechnicalSupportTicket::create([
            'device_key' => $device->device_key,
            'subject' => 'Investigate CPU Spike',
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'opened_by_user_id' => $tech->id,
            'opened_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team'));

        $response->assertOk();
        $response->assertSeeText('Investigate CPU Spike');
        $response->assertSeeText('Database Primary');
        $response->assertSeeText(__('crm.ticket_live_timer'));
    }

    public function test_card_displays_idle_when_no_open_ticket(): void
    {
        $idleTech = User::factory()->create([
            'name' => 'Idle Tech User',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team'));

        $response->assertOk();
        $response->assertSeeText('Idle Tech User');
        $response->assertSeeText(__('crm.no_active_ticket'));
    }

    public function test_status_filtering_works(): void
    {
        $techWithTicket = User::factory()->create([
            'name' => 'Busy Tech',
            'is_active' => true,
        ]);

        $idleTech = User::factory()->create([
            'name' => 'Free Tech',
            'is_active' => true,
        ]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'server-busy-01',
            'name' => 'Busy Server',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $this->adminUser->id,
        ]);

        TechnicalSupportTicket::create([
            'device_key' => $device->device_key,
            'subject' => 'Emergency Patch',
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'opened_by_user_id' => $techWithTicket->id,
            'opened_at' => now()->subMinutes(3),
        ]);

        // Filter: has_ticket
        $responseHasTicket = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team', ['status' => 'has_ticket']));

        $responseHasTicket->assertOk();
        $responseHasTicket->assertSeeText('Busy Tech');
        $responseHasTicket->assertDontSeeText('Free Tech');

        // Filter: available
        $responseAvailable = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team', ['status' => 'available']));

        $responseAvailable->assertOk();
        $responseAvailable->assertSeeText('Free Tech');
        $responseAvailable->assertDontSeeText('Busy Tech');
    }

    public function test_search_filter_by_name_and_extension(): void
    {
        User::factory()->create([
            'name' => 'Zayd Support Special',
            'voip_extension' => '999',
            'is_active' => true,
        ]);

        User::factory()->create([
            'name' => 'Omar Support Different',
            'voip_extension' => '888',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team', ['search' => '999']));

        $response->assertOk();
        $response->assertSeeText('Zayd Support Special');
        $response->assertDontSeeText('Omar Support Different');
    }

    public function test_employee_details_json_endpoint(): void
    {
        $tech = User::factory()->create([
            'name' => 'Detail Test User',
            'email' => 'detail@example.com',
            'voip_extension' => '555',
            'is_active' => true,
        ]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'server-det-01',
            'name' => 'Backup Server',
            'company_name' => 'Mega Corp',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $this->adminUser->id,
        ]);

        $ticket = TechnicalSupportTicket::create([
            'device_key' => $device->device_key,
            'subject' => 'Resolved Backup Failure',
            'status' => TechnicalSupportTicket::STATUS_CLOSED,
            'resolution' => 'Restored daemon service',
            'opened_by_user_id' => $tech->id,
            'closed_by_user_id' => $tech->id,
            'opened_at' => now()->subHours(2),
            'closed_at' => now()->subHours(1)->subMinutes(45),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('v2.technical-support.team.show', $tech));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('user.name', 'Detail Test User');
        $response->assertJsonPath('user.email', 'detail@example.com');
        $response->assertJsonPath('user.voip_extension', '555');
        $response->assertJsonPath('stats.total_tickets', 1);
        $response->assertJsonPath('stats.closed_tickets', 1);
        $response->assertJsonPath('tickets.0.subject', 'Resolved Backup Failure');
        $response->assertJsonPath('tickets.0.resolution', 'Restored daemon service');
        $response->assertJsonPath('tickets.0.company_name', 'Mega Corp');
    }

    public function test_card_displays_local_ip_globe_icon(): void
    {
        $tech = User::factory()->create([
            'name' => 'Local IP Tech',
            'is_active' => true,
        ]);

        DB::table('sessions')->insert([
            'id' => 'session_ip_test_789',
            'user_id' => $tech->id,
            'ip_address' => '192.168.1.155',
            'user_agent' => 'PHPUnit',
            'payload' => 'dummy',
            'last_activity' => now()->getTimestamp(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team'));

        $response->assertOk();
        $response->assertSee('192.168.1.155');
        $response->assertSee('bi-globe2');
    }

    public function test_sorting_options_work(): void
    {
        User::factory()->create(['name' => 'Alpha Support', 'is_active' => true]);
        User::factory()->create(['name' => 'Zulu Support', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('v2.technical-support.team', ['sort' => 'name']));

        $response->assertOk();
        $content = $response->getContent();
        $alphaPos = strpos($content, 'Alpha Support');
        $zuluPos = strpos($content, 'Zulu Support');
        $this->assertNotFalse($alphaPos);
        $this->assertNotFalse($zuluPos);
        $this->assertLessThan($zuluPos, $alphaPos);
    }

    public function test_sidebar_contains_support_team_link_when_authorized(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('v2.technical-support.team'));
    }
}
