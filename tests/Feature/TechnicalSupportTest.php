<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\TechnicalSupportDevice;
use App\Models\TechnicalSupportIp;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicalSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutMiddleware(Authorize::class);
    }

    public function test_guest_cannot_view_tailscale_network_status(): void
    {
        $this->get(route('v2.technical-support.index'))
            ->assertRedirect(route('login'));
    }

    public function test_every_active_user_can_view_online_and_offline_devices_in_card_layout(): void
    {
        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $user = User::factory()->create(['is_active' => true]);

        TechnicalSupportDevice::create([
            'device_key' => 'online-node',
            'name' => 'sales-laptop',
            'os' => 'windows',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportDevice::create([
            'device_key' => 'offline-node',
            'name' => 'old-workstation',
            'os' => 'windows',
            'is_online' => false,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportIp::create([
            'device_key' => 'online-node',
            'ip_address' => '100.64.0.20',
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportIp::create([
            'device_key' => 'offline-node',
            'ip_address' => '100.64.0.30',
            'created_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('v2.technical-support.index'))
            ->assertOk()
            ->assertSeeText('Technical Support')
            ->assertSeeText('sales-laptop')
            ->assertSeeText('old-workstation')
            ->assertSee('100.64.0.20')
            ->assertSeeText('Online Server')
            ->assertSeeText('Offline Server');
    }

    public function test_page_shows_recovery_state_when_tailscale_is_unavailable(): void
    {
        Process::fake([
            '*' => Process::result(
                errorOutput: 'tailscaled is not running',
                exitCode: 1,
            ),
        ]);

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('v2.technical-support.index'))
            ->assertOk()
            ->assertSeeText('Tailscale status is unavailable')
            ->assertSeeText('The server could not read the local Tailscale client.');
    }

    public function test_user_can_view_card_details_page(): void
    {
        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $user = User::factory()->create(['is_active' => true]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'custom_card_view_1',
            'name' => 'HQ Firewall',
            'company_name' => 'Global Logistics Inc',
            'employees_count' => 120,
            'lines_count' => 30,
            'os' => 'other',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportIp::create([
            'device_key' => 'custom_card_view_1',
            'ip_address' => '100.64.0.20', // Matches online node in Tailscale
            'label' => 'Tailscale IP',
            'created_by_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('v2.technical-support.cards.show', 'custom_card_view_1'))
            ->assertOk()
            ->assertSeeText('HQ Firewall')
            ->assertSeeText('Global Logistics Inc')
            ->assertSeeText('120')
            ->assertSeeText('30')
            ->assertSeeText('100.64.0.20')
            ->assertDontSee('fd7a:115c:a1e0::10');
    }

    public function test_user_can_update_card_details_and_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['is_active' => true]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'custom_card_edit_1',
            'name' => 'Old Server Name',
            'company_name' => 'Old Company',
            'employees_count' => 10,
            'lines_count' => 2,
            'os' => 'windows',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        $file = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($user)
            ->putJson(route('v2.technical-support.cards.update', $device->device_key), [
                'name' => 'Updated DB Node',
                'company_name' => 'New Enterprise Co',
                'employees_count' => 50,
                'lines_count' => 15,
                'dns_name' => 'newdb.internal',
                'os' => 'linux',
                'notes' => 'Updated production specs',
                'image' => $file,
                'new_ip_address' => '192.168.1.200',
                'new_ip_label' => 'Backup Interface',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('technical_support_devices', [
            'id' => $device->id,
            'name' => 'Updated DB Node',
            'company_name' => 'New Enterprise Co',
            'employees_count' => 50,
            'lines_count' => 15,
            'dns_name' => 'newdb.internal',
            'os' => 'linux',
            'notes' => 'Updated production specs',
        ]);

        $this->assertDatabaseHas('technical_support_ips', [
            'device_key' => $device->device_key,
            'ip_address' => '192.168.1.200',
            'label' => 'Backup Interface',
        ]);
    }

    public function test_user_can_add_ip_to_device_card(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->createOwnedDevice($user, 'online-node');

        $response = $this->actingAs($user)
            ->postJson(route('v2.technical-support.ips.store'), [
                'device_key' => 'online-node',
                'ip_address' => '192.168.1.105',
                'label' => 'LAN Office',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'ip' => [
                    'device_key' => 'online-node',
                    'ip' => '192.168.1.105',
                    'label' => 'LAN Office',
                    'is_custom' => true,
                ],
            ]);

        $this->assertDatabaseHas('technical_support_ips', [
            'device_key' => 'online-node',
            'ip_address' => '192.168.1.105',
            'label' => 'LAN Office',
            'created_by_user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_add_duplicate_ip_to_same_card(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->createOwnedDevice($user, 'online-node');

        TechnicalSupportIp::create([
            'device_key' => 'online-node',
            'ip_address' => '192.168.1.105',
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('v2.technical-support.ips.store'), [
                'device_key' => 'online-node',
                'ip_address' => '192.168.1.105',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_can_delete_custom_ip(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->createOwnedDevice($user, 'online-node');

        $ip = TechnicalSupportIp::create([
            'device_key' => 'online-node',
            'ip_address' => '192.168.1.105',
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('v2.technical-support.ips.destroy', $ip));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'id' => $ip->id,
            ]);

        $this->assertDatabaseMissing('technical_support_ips', [
            'id' => $ip->id,
        ]);
    }

    public function test_user_can_create_custom_support_card_with_company_details_and_tailscale_ip(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)
            ->postJson(route('v2.technical-support.devices.store'), [
                'company_name' => 'Acme Technology Solutions',
                'name' => 'Main DB Cluster',
                'employees_count' => 45,
                'lines_count' => 12,
                'dns_name' => 'db.internal.corp',
                'os' => 'linux',
                'is_online' => true,
                'notes' => 'Primary PostgreSQL production cluster',
                'selected_tailscale_ip' => '100.64.0.20',
                'initial_ip' => '10.0.0.50',
                'initial_ip_label' => 'Internal VPC',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('technical_support_devices', [
            'company_name' => 'Acme Technology Solutions',
            'name' => 'Main DB Cluster',
            'employees_count' => 45,
            'lines_count' => 12,
            'dns_name' => 'db.internal.corp',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
        ]);

        $this->assertDatabaseHas('technical_support_ips', [
            'ip_address' => '100.64.0.20',
            'label' => 'Tailscale',
        ]);

        $this->assertDatabaseHas('technical_support_ips', [
            'ip_address' => '10.0.0.50',
            'label' => 'Internal VPC',
        ]);
    }

    public function test_user_can_delete_custom_support_card(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'custom_test_123',
            'name' => 'Temporary Server',
            'os' => 'windows',
            'is_online' => false,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportIp::create([
            'device_key' => 'custom_test_123',
            'ip_address' => '192.168.10.10',
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('v2.technical-support.cards.destroy', $device->device_key));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('technical_support_devices', [
            'id' => $device->id,
            'is_hidden' => true,
        ]);

        $this->assertDatabaseMissing('technical_support_ips', [
            'device_key' => 'custom_test_123',
        ]);
    }

    public function test_user_can_open_support_ticket_for_server_card(): void
    {
        $user = User::factory()->create([
            'name' => 'Support Agent',
            'is_active' => true,
        ]);

        $device = TechnicalSupportDevice::create([
            'device_key' => 'ticket_server_1',
            'name' => 'Production Server',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('v2.technical-support.cards.show', $device->device_key))
            ->post(route('v2.technical-support.tickets.store', $device->device_key), [
                'subject' => 'Phone service unavailable',
            ]);

        $response
            ->assertRedirect(route('v2.technical-support.cards.show', $device->device_key))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('technical_support_tickets', [
            'device_key' => $device->device_key,
            'subject' => 'Phone service unavailable',
            'description' => null,
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'opened_by_user_id' => $user->id,
            'closed_at' => null,
        ]);
    }

    public function test_user_can_record_support_work_and_close_ticket(): void
    {
        $openingUser = User::factory()->create([
            'name' => 'Opening Agent',
            'is_active' => true,
        ]);
        $closingUser = $openingUser;

        $device = TechnicalSupportDevice::create([
            'device_key' => 'ticket_server_2',
            'name' => 'Branch Server',
            'os' => 'windows',
            'is_online' => false,
            'is_custom' => true,
            'created_by_user_id' => $openingUser->id,
        ]);

        $closedAt = now()->startOfSecond();
        $ticket = TechnicalSupportTicket::create([
            'device_key' => $device->device_key,
            'subject' => 'Database connection error',
            'description' => 'CRM cannot reach the branch database.',
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'opened_by_user_id' => $openingUser->id,
            'opened_at' => $closedAt->copy()->subMinutes(95),
        ]);

        $this->travelTo($closedAt);

        $response = $this->actingAs($closingUser)
            ->from(route('v2.technical-support.cards.show', $device->device_key))
            ->patch(route('v2.technical-support.tickets.close', [$device->device_key, $ticket]), [
                'resolution' => 'Restarted the database service and verified CRM connectivity.',
            ]);

        $response
            ->assertRedirect(route('v2.technical-support.cards.show', $device->device_key))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('technical_support_tickets', [
            'id' => $ticket->id,
            'status' => TechnicalSupportTicket::STATUS_CLOSED,
            'resolution' => 'Restarted the database service and verified CRM connectivity.',
            'closed_by_user_id' => $closingUser->id,
            'closed_at' => $closedAt,
        ]);

        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $this->actingAs($closingUser)
            ->get(route('v2.technical-support.cards.show', $device->device_key))
            ->assertOk()
            ->assertSeeText('Database connection error')
            ->assertSeeText('Restarted the database service and verified CRM connectivity.')
            ->assertSeeText('Opening Agent')
            ->assertSeeText('Closed at')
            ->assertSeeText('Time spent')
            ->assertSeeText('1 hour 35 minutes');
    }

    public function test_ticket_cards_are_colored_by_support_time(): void
    {
        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $device = TechnicalSupportDevice::create([
            'device_key' => 'ticket_time_bands',
            'name' => 'Timed Support Server',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);
        $closedAt = now()->startOfSecond();

        foreach ([
            ['subject' => 'Fast ticket', 'minutes' => 5],
            ['subject' => 'Warning ticket', 'minutes' => 15],
            ['subject' => 'Critical ticket', 'minutes' => 31],
        ] as $ticketData) {
            TechnicalSupportTicket::create([
                'device_key' => $device->device_key,
                'subject' => $ticketData['subject'],
                'status' => TechnicalSupportTicket::STATUS_CLOSED,
                'resolution' => 'Support work completed.',
                'opened_by_user_id' => $user->id,
                'closed_by_user_id' => $user->id,
                'opened_at' => $closedAt->copy()->subMinutes($ticketData['minutes']),
                'closed_at' => $closedAt,
            ]);
        }

        $this->actingAs($user)
            ->get(route('v2.technical-support.cards.show', $device->device_key))
            ->assertOk()
            ->assertSee('ticket-record closed time-fast', false)
            ->assertSee('ticket-record closed time-warning', false)
            ->assertSee('ticket-record closed time-critical', false)
            ->assertSeeText('Under 10 minutes')
            ->assertSeeText('10 to 30 minutes')
            ->assertSeeText('Over 30 minutes');
    }

    public function test_support_reports_group_completed_tickets_by_employee_and_server(): void
    {
        $viewer = $this->makeSuperAdmin(
            User::factory()->create(['is_active' => true]),
        );
        $firstAgent = User::factory()->create(['name' => 'First Support Agent', 'is_active' => true]);
        $secondAgent = User::factory()->create(['name' => 'Second Support Agent', 'is_active' => true]);
        $firstServer = TechnicalSupportDevice::create([
            'device_key' => 'report_server_alpha',
            'name' => 'Alpha Server',
            'company_name' => 'Alpha Company',
            'os' => 'linux',
            'is_custom' => true,
            'created_by_user_id' => $viewer->id,
        ]);
        $secondServer = TechnicalSupportDevice::create([
            'device_key' => 'report_server_beta',
            'name' => 'Beta Server',
            'company_name' => 'Beta Company',
            'os' => 'windows',
            'is_custom' => true,
            'created_by_user_id' => $viewer->id,
        ]);
        $closedAt = now()->startOfSecond();

        foreach ([
            [$firstAgent, $firstServer, 'Alpha phone issue', 5],
            [$firstAgent, $secondServer, 'Beta database issue', 20],
            [$secondAgent, $secondServer, 'Beta network issue', 40],
        ] as [$agent, $server, $subject, $minutes]) {
            TechnicalSupportTicket::create([
                'device_key' => $server->device_key,
                'subject' => $subject,
                'description' => 'Recorded issue details for '.$subject,
                'status' => TechnicalSupportTicket::STATUS_CLOSED,
                'resolution' => 'Support work completed.',
                'opened_by_user_id' => $viewer->id,
                'closed_by_user_id' => $agent->id,
                'opened_at' => $closedAt->copy()->subMinutes($minutes),
                'closed_at' => $closedAt,
            ]);
        }

        $this->actingAs($viewer)
            ->get(route('v2.technical-support.reports'))
            ->assertOk()
            ->assertSeeText('Technical Support Reports')
            ->assertSeeText('First Support Agent')
            ->assertSeeText('Second Support Agent')
            ->assertSeeText('Alpha Server')
            ->assertSeeText('Alpha Company')
            ->assertSeeText('Beta Server')
            ->assertSeeText('Beta Company')
            ->assertSeeText('25 minutes')
            ->assertSeeText('40 minutes')
            ->assertSeeText('Completed tickets by employee')
            ->assertSeeText('Support duration by employee')
            ->assertSeeText('Most completed tickets')
            ->assertSeeText('Highest support duration')
            ->assertViewHas('performanceCharts', static fn (array $charts): bool => $charts['tickets'][0]['employee_name'] === 'First Support Agent'
                && $charts['tickets'][0]['value'] === 2
                && $charts['duration'][0]['employee_name'] === 'Second Support Agent'
                && $charts['duration'][0]['value'] === 2400)
            ->assertSeeText('Alpha phone issue')
            ->assertSeeText('Beta database issue')
            ->assertSeeText('Beta network issue')
            ->assertSeeText('Ticket details')
            ->assertSeeText('Recorded issue details for Alpha phone issue')
            ->assertSeeText('Support work completed.')
            ->assertSee('Ticket follow-ups for First Support Agent')
            ->assertSee('data-ticket-details-open="employeeTicketDetails'.$firstAgent->id.'"', false)
            ->assertDontSeeText('Report metrics are based on closed tickets')
            ->assertSee(route('v2.technical-support.index'))
            ->assertSee(route('v2.technical-support.reports'));
    }

    public function test_support_report_filters_include_active_crm_users_and_all_visible_servers(): void
    {
        $viewer = $this->makeSuperAdmin(User::factory()->create([
            'name' => 'Report Viewer',
            'is_active' => true,
        ]));
        $activeUserWithoutTickets = User::factory()->create([
            'name' => 'Available Support User',
            'is_active' => true,
        ]);
        $inactiveUser = User::factory()->create([
            'name' => 'Inactive Support User',
            'is_active' => false,
        ]);

        $onlineServer = TechnicalSupportDevice::create([
            'device_key' => 'available_online_server',
            'name' => 'Available Online Server',
            'company_name' => 'Online Company',
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'created_by_user_id' => $viewer->id,
        ]);
        $offlineServer = TechnicalSupportDevice::create([
            'device_key' => 'available_offline_server',
            'name' => 'Available Offline Server',
            'company_name' => 'Offline Company',
            'os' => 'windows',
            'is_online' => false,
            'is_custom' => true,
            'created_by_user_id' => $viewer->id,
        ]);
        $hiddenServer = TechnicalSupportDevice::create([
            'device_key' => 'hidden_report_server',
            'name' => 'Hidden Report Server',
            'os' => 'linux',
            'is_online' => false,
            'is_custom' => true,
            'is_hidden' => true,
            'created_by_user_id' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('v2.technical-support.reports'))
            ->assertOk()
            ->assertViewHas('employees', static fn ($employees): bool => $employees->pluck('id')->contains($viewer->id)
                && $employees->pluck('id')->contains($activeUserWithoutTickets->id)
                && ! $employees->pluck('id')->contains($inactiveUser->id))
            ->assertViewHas('devices', static fn ($devices): bool => $devices->pluck('id')->contains($onlineServer->id)
                && $devices->pluck('id')->contains($offlineServer->id)
                && ! $devices->pluck('id')->contains($hiddenServer->id))
            ->assertViewHas('companies', static fn ($companies): bool => $companies->contains('Online Company')
                && $companies->contains('Offline Company'));
    }

    public function test_support_reports_can_be_filtered_by_employee_server_company_and_date(): void
    {
        $viewer = $this->makeSuperAdmin(
            User::factory()->create(['is_active' => true]),
        );
        $selectedAgent = User::factory()->create(['name' => 'Selected Agent', 'is_active' => true]);
        $otherAgent = User::factory()->create(['name' => 'Other Agent', 'is_active' => true]);
        $selectedServer = TechnicalSupportDevice::create([
            'device_key' => 'filtered_report_server',
            'name' => 'Filtered Server',
            'company_name' => 'Filtered Company',
            'os' => 'linux',
            'is_custom' => true,
            'created_by_user_id' => $viewer->id,
        ]);
        $otherServer = TechnicalSupportDevice::create([
            'device_key' => 'other_report_server',
            'name' => 'Other Server',
            'company_name' => 'Other Company',
            'os' => 'linux',
            'is_custom' => true,
            'created_by_user_id' => $viewer->id,
        ]);
        $closedAt = now()->startOfSecond();

        foreach ([
            [$selectedAgent, $selectedServer, 'Included report ticket', $closedAt],
            [$selectedAgent, $otherServer, 'Wrong server ticket', $closedAt],
            [$otherAgent, $selectedServer, 'Wrong employee ticket', $closedAt],
            [$selectedAgent, $selectedServer, 'Old report ticket', $closedAt->copy()->subDays(10)],
        ] as [$agent, $server, $subject, $ticketClosedAt]) {
            TechnicalSupportTicket::create([
                'device_key' => $server->device_key,
                'subject' => $subject,
                'status' => TechnicalSupportTicket::STATUS_CLOSED,
                'resolution' => 'Support work completed.',
                'opened_by_user_id' => $viewer->id,
                'closed_by_user_id' => $agent->id,
                'opened_at' => $ticketClosedAt->copy()->subMinutes(12),
                'closed_at' => $ticketClosedAt,
            ]);
        }

        $this->actingAs($viewer)
            ->get(route('v2.technical-support.reports', [
                'employee_id' => $selectedAgent->id,
                'device_key' => $selectedServer->device_key,
                'company_name' => $selectedServer->company_name,
                'from_date' => $closedAt->format('Y-m-d'),
                'to_date' => $closedAt->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSeeText('Included report ticket')
            ->assertSeeText('Selected Agent')
            ->assertDontSeeText('Wrong server ticket')
            ->assertDontSeeText('Wrong employee ticket')
            ->assertDontSeeText('Old report ticket');
    }

    public function test_opening_ticket_for_tailscale_device_creates_server_card(): void
    {
        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $user = $this->makeSuperAdmin(
            User::factory()->create(['is_active' => true]),
        );

        $this->actingAs($user)
            ->post(route('v2.technical-support.tickets.store', 'online-node'), [
                'subject' => 'Remote support requested',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('technical_support_devices', [
            'device_key' => 'online-node',
            'name' => 'sales-laptop',
            'is_custom' => false,
            'is_hidden' => false,
        ]);

        $this->assertDatabaseHas('technical_support_tickets', [
            'device_key' => 'online-node',
            'subject' => 'Remote support requested',
            'status' => TechnicalSupportTicket::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('technical_support_ips', [
            'device_key' => 'online-node',
            'ip_address' => '100.64.0.20',
            'label' => 'Tailscale',
        ]);
    }

    public function test_invalid_tailscale_ticket_does_not_create_server_card(): void
    {
        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('v2.technical-support.tickets.store', 'online-node'), [
                'subject' => '',
            ])
            ->assertSessionHasErrors(['subject']);

        $this->assertDatabaseMissing('technical_support_devices', [
            'device_key' => 'online-node',
        ]);

        $this->assertDatabaseMissing('technical_support_tickets', [
            'device_key' => 'online-node',
        ]);
    }

    public function test_server_card_with_ticket_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $device = TechnicalSupportDevice::create([
            'device_key' => 'ticket_server_protected',
            'name' => 'Protected Server',
            'os' => 'linux',
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportTicket::create([
            'device_key' => $device->device_key,
            'subject' => 'Historical issue',
            'description' => 'This support history must remain accessible.',
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'opened_by_user_id' => $user->id,
            'opened_at' => now(),
        ]);

        $this->actingAs($user)
            ->deleteJson(route('v2.technical-support.cards.destroy', $device->device_key))
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('technical_support_devices', [
            'id' => $device->id,
            'is_hidden' => false,
        ]);
    }

    public function test_user_cannot_close_ticket_from_another_server_card(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $firstDevice = TechnicalSupportDevice::create([
            'device_key' => 'ticket_server_3',
            'name' => 'First Server',
            'os' => 'linux',
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        $secondDevice = TechnicalSupportDevice::create([
            'device_key' => 'ticket_server_4',
            'name' => 'Second Server',
            'os' => 'linux',
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        $ticket = TechnicalSupportTicket::create([
            'device_key' => $firstDevice->device_key,
            'subject' => 'First server issue',
            'description' => 'This ticket belongs only to the first server.',
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'opened_by_user_id' => $user->id,
            'opened_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('v2.technical-support.tickets.close', [$secondDevice->device_key, $ticket]), [
                'resolution' => 'Should not be accepted.',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('technical_support_tickets', [
            'id' => $ticket->id,
            'status' => TechnicalSupportTicket::STATUS_OPEN,
            'closed_at' => null,
        ]);
    }

    public function test_card_online_status_is_determined_by_assigned_ip(): void
    {
        Process::fake([
            '*' => Process::result(
                output: json_encode($this->tailscaleStatus(), JSON_THROW_ON_ERROR),
            ),
        ]);

        $user = User::factory()->create(['is_active' => true]);

        // Create card linked to online Tailscale IP 100.64.0.20
        $deviceOnline = TechnicalSupportDevice::create([
            'device_key' => 'custom_online_card',
            'name' => 'Online Linked Card',
            'os' => 'linux',
            'is_online' => false, // Initial stored flag is false
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportIp::create([
            'device_key' => 'custom_online_card',
            'ip_address' => '100.64.0.20', // Online in Tailscale status map
            'created_by_user_id' => $user->id,
        ]);

        // Create card linked to offline Tailscale IP 100.64.0.30
        $deviceOffline = TechnicalSupportDevice::create([
            'device_key' => 'custom_offline_card',
            'name' => 'Offline Linked Card',
            'os' => 'linux',
            'is_online' => true, // Initial stored flag is true
            'is_custom' => true,
            'created_by_user_id' => $user->id,
        ]);

        TechnicalSupportIp::create([
            'device_key' => 'custom_offline_card',
            'ip_address' => '100.64.0.30', // Offline in Tailscale status map
            'created_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('v2.technical-support.index'));

        $response->assertOk();

        // Online Linked Card computed status must be online because 100.64.0.20 is online
        $this->actingAs($user)
            ->get(route('v2.technical-support.cards.show', 'custom_online_card'))
            ->assertOk()
            ->assertSeeText('Online (based on linked IP)');

        // Offline Linked Card computed status must be offline because 100.64.0.30 is offline
        $this->actingAs($user)
            ->get(route('v2.technical-support.cards.show', 'custom_offline_card'))
            ->assertOk()
            ->assertSeeText('Offline (based on linked IP)');
    }

    private function createOwnedDevice(User $user, string $deviceKey): TechnicalSupportDevice
    {
        return TechnicalSupportDevice::query()->create([
            'device_key' => $deviceKey,
            'name' => $deviceKey,
            'os' => 'linux',
            'is_custom' => true,
            'is_hidden' => false,
            'created_by_user_id' => $user->getKey(),
        ]);
    }

    private function makeSuperAdmin(User $user): User
    {
        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'مدير النظام',
                'is_system' => true,
            ],
        );
        $user->groups()->syncWithoutDetaching([$group->getKey()]);

        return $user->unsetRelation('groups');
    }

    /** @return array<string, mixed> */
    private function tailscaleStatus(): array
    {
        return [
            'BackendState' => 'Running',
            'CurrentTailnet' => ['Name' => 'support@example.test'],
            'Self' => [
                'ID' => 'self-node',
                'HostName' => 'crm-server',
                'DNSName' => 'crm-server.example.ts.net.',
                'OS' => 'linux',
                'TailscaleIPs' => [
                    '100.64.0.10',
                    'fd7a:115c:a1e0::10',
                ],
                'Online' => true,
                'Active' => false,
            ],
            'Peer' => [
                'online-node' => [
                    'ID' => 'online-node',
                    'HostName' => 'sales-laptop',
                    'DNSName' => 'sales-laptop.example.ts.net.',
                    'OS' => 'windows',
                    'TailscaleIPs' => ['100.64.0.20'],
                    'Online' => true,
                    'Active' => true,
                    'LastHandshake' => '2026-08-26T12:22:26Z',
                ],
                'offline-node' => [
                    'ID' => 'offline-node',
                    'HostName' => 'old-workstation',
                    'DNSName' => 'old-workstation.example.ts.net.',
                    'OS' => 'windows',
                    'TailscaleIPs' => ['100.64.0.30'],
                    'Online' => false,
                    'Active' => false,
                    'LastSeen' => '2026-08-20T10:00:00Z',
                ],
            ],
        ];
    }
}
