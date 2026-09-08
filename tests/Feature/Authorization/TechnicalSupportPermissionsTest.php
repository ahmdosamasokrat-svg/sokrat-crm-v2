<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\Group;
use App\Models\Permission;
use App\Models\TechnicalSupportDevice;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class TechnicalSupportPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Process::fake([
            '*' => Process::result(
                errorOutput: 'tailscaled is unavailable',
                exitCode: 1,
            ),
        ]);

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

        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->groups()->attach($superAdminGroup);
    }

    public function test_sidebar_and_routes_follow_support_tab_permissions(): void
    {
        $dashboardOnly = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
        ]);

        $sidebar = $this->actingAs($dashboardOnly)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('crmTechnicalSupportMenu', $sidebar);

        $this->actingAs($dashboardOnly)
            ->get(route('v2.technical-support.index'))
            ->assertForbidden();
        $this->actingAs($dashboardOnly)
            ->get(route('v2.technical-support.reports'))
            ->assertForbidden();

        $reportsOnly = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::TECHNICAL_SUPPORT_REPORTS->value,
        ]);

        $reportsSidebar = $this->actingAs($reportsOnly)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'href="'.route('v2.technical-support.reports').'"',
            $reportsSidebar,
        );
        $this->assertStringNotContainsString(
            'href="'.route('v2.technical-support.index').'"',
            $reportsSidebar,
        );

        $this->actingAs($reportsOnly)
            ->get(route('v2.technical-support.reports'))
            ->assertOk();
        $this->actingAs($reportsOnly)
            ->get(route('v2.technical-support.index'))
            ->assertForbidden();
    }

    public function test_view_only_employee_cannot_mutate_support_data(): void
    {
        $employee = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_VIEW->value,
        ]);
        $device = $this->createDevice($employee, 'read-only-device', 'Read only device');

        $this->actingAs($employee)
            ->get(route('v2.technical-support.index'))
            ->assertOk()
            ->assertSeeText($device->name)
            ->assertDontSee('id="btnOpenAddCardModal"', false);

        $this->actingAs($employee)
            ->post(route('v2.technical-support.devices.store'), [
                'name' => 'Forbidden device',
                'os' => 'linux',
            ])
            ->assertForbidden();
    }

    public function test_authorized_employee_can_view_all_support_servers_and_admin_sees_all(): void
    {
        $employee = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_VIEW->value,
            CrmPermission::TECHNICAL_SUPPORT_MANAGE->value,
            CrmPermission::TECHNICAL_SUPPORT_REPORTS->value,
        ]);
        $otherEmployee = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_VIEW->value,
            CrmPermission::TECHNICAL_SUPPORT_MANAGE->value,
            CrmPermission::TECHNICAL_SUPPORT_REPORTS->value,
        ]);

        $ownDevice = $this->createDevice($employee, 'employee-device', 'Employee server');
        $otherDevice = $this->createDevice($otherEmployee, 'other-device', 'Other server');
        $ownTicket = $this->createClosedTicket($employee, $ownDevice, 'Employee ticket');
        $otherTicket = $this->createClosedTicket($otherEmployee, $otherDevice, 'Other ticket');

        $this->actingAs($employee)
            ->get(route('v2.technical-support.index'))
            ->assertOk()
            ->assertSeeText($ownDevice->name)
            ->assertSeeText($otherDevice->name);

        $this->actingAs($employee)
            ->get(route('v2.technical-support.cards.show', $otherDevice->device_key))
            ->assertOk()
            ->assertSeeText($otherDevice->name);

        $this->actingAs($employee)
            ->put(route('v2.technical-support.cards.update', $otherDevice->device_key), [
                'name' => 'Updated server',
                'os' => 'linux',
            ])
            ->assertRedirect(route('v2.technical-support.cards.show', $otherDevice->device_key));
        $this->actingAs($employee)
            ->get(route('v2.technical-support.reports'))
            ->assertOk()
            ->assertSeeText($ownTicket->subject)
            ->assertDontSeeText($otherTicket->subject);

        $this->actingAs($this->superAdmin)
            ->get(route('v2.technical-support.index'))
            ->assertOk()
            ->assertSeeText($ownDevice->name)
            ->assertSeeText('Updated server');

        $this->actingAs($this->superAdmin)
            ->get(route('v2.technical-support.reports'))
            ->assertOk()
            ->assertSeeText($ownTicket->subject)
            ->assertSeeText($otherTicket->subject);
    }

    /**
     * @param  list<string>  $permissionCodes
     */
    private function createUserWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Custom support role '.uniqid(),
            'code' => 'support-role-'.uniqid(),
            'is_system' => false,
        ]);
        $group->permissions()->sync(
            Permission::query()
                ->whereIn('code', $permissionCodes)
                ->pluck('id'),
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group);

        return $user;
    }

    private function createDevice(User $creator, string $key, string $name): TechnicalSupportDevice
    {
        return TechnicalSupportDevice::query()->create([
            'device_key' => $key,
            'name' => $name,
            'os' => 'linux',
            'is_online' => true,
            'is_custom' => true,
            'is_hidden' => false,
            'created_by_user_id' => $creator->getKey(),
        ]);
    }

    private function createClosedTicket(
        User $employee,
        TechnicalSupportDevice $device,
        string $subject,
    ): TechnicalSupportTicket {
        return TechnicalSupportTicket::query()->create([
            'device_key' => $device->device_key,
            'subject' => $subject,
            'status' => TechnicalSupportTicket::STATUS_CLOSED,
            'resolution' => 'Resolved',
            'opened_by_user_id' => $employee->getKey(),
            'closed_by_user_id' => $employee->getKey(),
            'opened_at' => now()->subMinutes(5),
            'closed_at' => now(),
        ]);
    }
}
