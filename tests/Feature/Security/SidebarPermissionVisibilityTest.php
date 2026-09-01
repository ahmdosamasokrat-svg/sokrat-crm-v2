<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarPermissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

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
            'username' => 'super_admin_sb_vis',
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->attach($superAdminGroup);
    }

    private function createUserWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Custom Role ' . uniqid(),
            'code' => 'custom-role-' . uniqid(),
            'is_system' => false,
        ]);

        $permIds = Permission::whereIn('code', $permissionCodes)->pluck('id');
        $group->permissions()->sync($permIds);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->groups()->attach($group);

        return $user;
    }

    private function getSidebarHtml($response): string
    {
        $html = $response->getContent();
        if (preg_match('/<aside[^>]*id="crmSidebar"[^>]*>(.*?)<\/aside>/s', $html, $matches)) {
            return $matches[1];
        }

        return $html;
    }

    public function test_super_admin_sees_all_permitted_sidebar_modules(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringContainsString(route('dashboard'), $sidebar);
        $this->assertStringContainsString(route('v2.leads.kanban'), $sidebar);
        $this->assertStringContainsString(route('v2.leads'), $sidebar);
        $this->assertStringContainsString(route('v2.leads.create'), $sidebar);
        $this->assertStringContainsString(route('v2.leads.import'), $sidebar);
        $this->assertStringContainsString(route('v2.leads.export'), $sidebar);
        $this->assertStringContainsString(route('v2.tasks.daily'), $sidebar);
        $this->assertStringContainsString(route('v2.campaigns.index'), $sidebar);
        $this->assertStringContainsString(route('v2.campaigns.create'), $sidebar);
        $this->assertStringContainsString(route('v2.campaigns.reports'), $sidebar);
        $this->assertStringContainsString(route('v2.quotations.create'), $sidebar);
        $this->assertStringContainsString(route('v2.quotations.index'), $sidebar);
        $this->assertStringContainsString(route('v2.calendar.index'), $sidebar);
        $this->assertStringContainsString(route('v2.settings'), $sidebar);
    }

    public function test_user_without_settings_access_does_not_see_settings_and_direct_url_is_403(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        // 1. Sidebar HTML must NOT contain settings link
        $this->assertStringNotContainsString(route('v2.settings'), $sidebar);

        // 2. Direct URL access must return 403 Forbidden
        $directSettings = $this->actingAs($user)->get(route('v2.settings'));
        $directSettings->assertForbidden();
    }

    public function test_read_only_leads_user_sees_view_and_kanban_but_not_create_import_export(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        // Allowed
        $this->assertStringContainsString(route('v2.leads'), $sidebar);
        $this->assertStringContainsString(route('v2.leads.kanban'), $sidebar);

        // Hidden
        $this->assertStringNotContainsString(route('v2.leads.create'), $sidebar);
        $this->assertStringNotContainsString(route('v2.leads.import'), $sidebar);
        $this->assertStringNotContainsString(route('v2.leads.export'), $sidebar);
    }

    public function test_user_without_leads_permissions_does_not_see_leads_menu_or_kanban(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CALENDAR_VIEW->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringNotContainsString('crmLeadsMenu', $sidebar);
        $this->assertStringNotContainsString(route('v2.leads'), $sidebar);
        $this->assertStringNotContainsString(route('v2.leads.kanban'), $sidebar);
    }

    public function test_campaign_reports_only_user_sees_campaigns_parent_with_only_reports_link(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CAMPAIGNS_REPORTS->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        // Parent and reports link visible
        $this->assertStringContainsString('crmCampaignsMenu', $sidebar);
        $this->assertStringContainsString('href="' . route('v2.campaigns.reports') . '"', $sidebar);

        // Other campaign links hidden
        $this->assertStringNotContainsString('href="' . route('v2.campaigns.index') . '"', $sidebar);
        $this->assertStringNotContainsString('href="' . route('v2.campaigns.create') . '"', $sidebar);
    }

    public function test_user_without_campaign_permissions_does_not_see_campaigns_menu(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringNotContainsString('crmCampaignsMenu', $sidebar);
        $this->assertStringNotContainsString('href="' . route('v2.campaigns.index') . '"', $sidebar);
    }

    public function test_user_without_tasks_permission_does_not_see_tasks_menu(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringNotContainsString('crmTasksMenu', $sidebar);
        $this->assertStringNotContainsString(route('v2.tasks.daily'), $sidebar);
    }

    public function test_quotations_create_only_user_sees_only_create_quotation_link(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::QUOTATIONS_CREATE->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringContainsString('crmQuotationsMenu', $sidebar);
        $this->assertStringContainsString('href="' . route('v2.quotations.create') . '"', $sidebar);
        $this->assertStringNotContainsString('href="' . route('v2.quotations.index') . '"', $sidebar);
    }

    public function test_user_without_quotations_permissions_does_not_see_quotations_menu(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::LEADS_VIEW->value,
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringNotContainsString('crmQuotationsMenu', $sidebar);
        $this->assertStringNotContainsString(route('v2.quotations.create'), $sidebar);
        $this->assertStringNotContainsString(route('v2.quotations.index'), $sidebar);
    }

    public function test_calendar_only_user_sees_calendar_and_unauthorized_modules_are_hidden(): void
    {
        $user = $this->createUserWithPermissions([
            CrmPermission::DASHBOARD_VIEW->value,
            CrmPermission::CALENDAR_VIEW->value,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $sidebar = $this->getSidebarHtml($response);

        $this->assertStringContainsString(route('v2.calendar.index'), $sidebar);
        $this->assertStringNotContainsString(route('v2.settings'), $sidebar);
        $this->assertStringNotContainsString('crmCampaignsMenu', $sidebar);
        $this->assertStringNotContainsString('crmQuotationsMenu', $sidebar);
        $this->assertStringNotContainsString('crmTasksMenu', $sidebar);
        $this->assertStringNotContainsString('crmLeadsMenu', $sidebar);
    }
}
