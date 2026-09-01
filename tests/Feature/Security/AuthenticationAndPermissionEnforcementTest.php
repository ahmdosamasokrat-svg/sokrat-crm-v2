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

class AuthenticationAndPermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $unprivilegedUser;
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
            'username' => 'super_admin_sec',
            'is_active' => true,
        ]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        $this->unprivilegedUser = User::factory()->create([
            'name' => 'Unprivileged User',
            'username' => 'unprivileged_sec',
            'is_active' => true,
        ]);

        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'start'],
            [
                'name_ar' => 'البداية',
                'position' => 1,
                'color' => '#3478f6',
                'is_primary' => true,
                'is_active' => true,
            ]
        );
        $status = $stage->statuses()->first();
        $status->update(['code' => 'new', 'name_ar' => 'جديد']);

        $this->lead = Lead::query()->create([
            'lead_status_id' => $status->id,
            'name' => 'Test Security Lead',
            'phone' => '0509998877',
            'assigned_user_id' => $this->superAdmin->id,
            'source' => 'web',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_all_protected_routes(): void
    {
        $protectedRoutes = [
            route('dashboard'),
            route('v2.leads'),
            route('v2.leads.kanban'),
            route('v2.leads.create'),
            route('v2.leads.show', $this->lead),
            route('v2.campaigns.index'),
            route('v2.calendar.index'),
            route('v2.tasks.daily'),
            route('v2.quotations.index'),
            route('v2.settings'),
            route('v2.settings.users.index'),
            route('v2.settings.groups.index'),
            route('v2.settings.permissions.index'),
            route('v2.settings.stages.index'),
        ];

        foreach ($protectedRoutes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_inactive_user_is_denied_access(): void
    {
        $inactiveUser = User::factory()->create([
            'is_active' => false,
        ]);
        $inactiveUser->groups()->attach(Group::where('code', Group::SUPER_ADMIN_CODE)->first());

        $response = $this->actingAs($inactiveUser)->get(route('dashboard'));
        $this->assertTrue(
            $response->isRedirect() || $response->isForbidden(),
            'Inactive user must not access dashboard'
        );
    }

    public function test_user_without_leads_view_cannot_access_leads_or_kanban(): void
    {
        $response = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads'));
        $response->assertForbidden();

        $kanbanResponse = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads.kanban'));
        $kanbanResponse->assertForbidden();

        $showResponse = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads.show', $this->lead));
        $showResponse->assertForbidden();
    }

    public function test_user_without_leads_create_cannot_access_create_form_or_store(): void
    {
        $response = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads.create'));
        $response->assertForbidden();

        $postResponse = $this->actingAs($this->unprivilegedUser)->post(route('v2.leads.store'), [
            'name' => 'Malicious Lead',
            'phone' => '0501112233',
        ]);
        $postResponse->assertForbidden();
    }

    public function test_user_without_leads_update_cannot_edit_or_patch(): void
    {
        $response = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads.edit', $this->lead));
        $response->assertForbidden();

        $patchResponse = $this->actingAs($this->unprivilegedUser)->patch(route('v2.leads.update', $this->lead), [
            'name' => 'Updated Name',
        ]);
        $patchResponse->assertForbidden();
    }

    public function test_user_without_leads_delete_cannot_destroy_lead(): void
    {
        $response = $this->actingAs($this->unprivilegedUser)->delete(route('v2.leads.destroy', $this->lead));
        $response->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $this->lead->id]);
    }

    public function test_user_without_leads_export_cannot_download_leads(): void
    {
        $response = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads.export'));
        $response->assertForbidden();

        $exportPostResponse = $this->actingAs($this->unprivilegedUser)->post(route('v2.leads.export.download'), [
            'format' => 'csv',
        ]);
        $exportPostResponse->assertForbidden();
    }

    public function test_user_without_leads_import_cannot_import_leads(): void
    {
        $response = $this->actingAs($this->unprivilegedUser)->get(route('v2.leads.import'));
        $response->assertForbidden();
    }

    public function test_user_without_campaigns_permissions_cannot_access_campaigns(): void
    {
        $this->actingAs($this->unprivilegedUser)->get(route('v2.campaigns.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.campaigns.create'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.campaigns.reports'))->assertForbidden();
    }

    public function test_user_without_calendar_permissions_cannot_access_calendar(): void
    {
        $this->actingAs($this->unprivilegedUser)->get(route('v2.calendar.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.calendar.events'))->assertForbidden();
    }

    public function test_user_without_quotations_permissions_cannot_access_quotations(): void
    {
        $this->actingAs($this->unprivilegedUser)->get(route('v2.quotations.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.quotations.create'))->assertForbidden();
    }

    public function test_user_without_tasks_permission_cannot_access_tasks(): void
    {
        $this->actingAs($this->unprivilegedUser)->get(route('v2.tasks.daily'))->assertForbidden();
    }

    public function test_user_without_reports_permission_cannot_access_reports(): void
    {
        $this->actingAs($this->unprivilegedUser)->get(route('v2.reports.leads'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.reports.tasks'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.reports.employees'))->assertForbidden();
    }

    public function test_user_without_settings_permission_cannot_access_admin_settings(): void
    {
        $this->actingAs($this->unprivilegedUser)->get(route('v2.settings'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.settings.users.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.settings.groups.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.settings.permissions.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.settings.stages.index'))->assertForbidden();
        $this->actingAs($this->unprivilegedUser)->get(route('v2.settings.voip'))->assertForbidden();
    }

    public function test_super_admin_has_full_access(): void
    {
        $this->actingAs($this->superAdmin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('v2.leads'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('v2.campaigns.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('v2.calendar.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('v2.tasks.daily'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('v2.settings'))->assertOk();
    }
}
