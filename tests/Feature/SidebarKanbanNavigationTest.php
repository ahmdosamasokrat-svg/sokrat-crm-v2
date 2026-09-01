<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarKanbanNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_standalone_kanban_in_sidebar_in_arabic(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'ar'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('v2.leads.kanban'));
        $response->assertSee('الكانبان');
    }

    public function test_super_admin_sees_standalone_kanban_in_sidebar_in_english(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('v2.leads.kanban'));
        $response->assertSee('Kanban');
    }

    public function test_kanban_is_not_inside_leads_submenu(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $html = $response->getContent();

        // Extract #crmLeadsMenu content
        preg_match('/<div[^>]*id="crmLeadsMenu"[^>]*>(.*?)<\/div>\s*<\/div>/s', $html, $matches);
        $leadsMenuHtml = $matches[1] ?? '';

        $this->assertStringNotContainsString(route('v2.leads.kanban'), $leadsMenuHtml);
        $this->assertStringNotContainsString('/leads/kanban', $leadsMenuHtml);
    }

    public function test_kanban_appears_as_standalone_top_level_link(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $html = $response->getContent();

        // Assert it has the top-level crm-link class with bi-kanban icon
        $this->assertMatchesRegularExpression(
            '/<a\s+class="crm-link link[^"]*"\s+href="[^"]*\/leads\/kanban">\s*<span class="crm-ico ico"><i class="bi bi-kanban"><\/i><\/span>/',
            $html
        );
    }

    public function test_unauthorized_user_cannot_access_kanban_route(): void
    {
        $userWithoutPerms = User::factory()->create();

        $this->actingAs($userWithoutPerms)
            ->get(route('v2.leads.kanban'))
            ->assertForbidden();
    }

    private function superAdmin(): User
    {
        $group = Group::query()->firstOrCreate(
            ['code' => Group::SUPER_ADMIN_CODE],
            [
                'name' => 'Super Admin',
                'is_system' => true,
            ]
        );
        $user = User::factory()->create();
        $user->groups()->attach($group);

        return $user;
    }
}
