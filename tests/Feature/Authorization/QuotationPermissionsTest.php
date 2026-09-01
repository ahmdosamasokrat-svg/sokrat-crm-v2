<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $salesAgent;

    private User $readOnlyUser;

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
        $superAdminGroup->permissions()->sync(Permission::pluck('id'));

        $salesAgentGroup = Group::query()->firstOrCreate(
            ['code' => 'sales-agent'],
            [
                'name' => 'موظف المبيعات',
                'is_system' => false,
            ]
        );
        $salesAgentGroup->permissions()->sync(
            Permission::whereIn('code', ['dashboard.view', 'quotations.view', 'quotations.create'])->pluck('id')
        );

        $readOnlyGroup = Group::query()->firstOrCreate(
            ['code' => 'read-only'],
            [
                'name' => 'مشاهدة فقط',
                'is_system' => false,
            ]
        );
        $readOnlyGroup->permissions()->sync(
            Permission::whereIn('code', ['dashboard.view', 'quotations.view'])->pluck('id')
        );
        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->groups()->attach($superAdminGroup);

        $this->salesAgent = User::factory()->create(['is_active' => true]);
        $this->salesAgent->groups()->attach($salesAgentGroup);

        $this->readOnlyUser = User::factory()->create(['is_active' => true]);
        $this->readOnlyUser->groups()->attach($readOnlyGroup);
    }

    public function test_read_only_user_can_view_quotations_but_cannot_create(): void
    {
        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.quotations.index'))
            ->assertOk();

        $this->actingAs($this->readOnlyUser)
            ->get(route('v2.quotations.create'))
            ->assertForbidden();

        $this->actingAs($this->readOnlyUser)
            ->post(route('v2.quotations.store'), [
                'title' => 'Test Quotation',
            ])
            ->assertForbidden();
    }

    public function test_sales_agent_can_view_and_create_quotation_form(): void
    {
        $this->actingAs($this->salesAgent)
            ->get(route('v2.quotations.index'))
            ->assertOk();

        $this->actingAs($this->salesAgent)
            ->get(route('v2.quotations.create'))
            ->assertOk();
    }

    public function test_employee_only_sees_and_opens_own_quotations(): void
    {
        $ownQuotation = $this->createQuotation(
            $this->salesAgent,
            'Employee quotation',
        );
        $otherQuotation = $this->createQuotation(
            $this->superAdmin,
            'Administrator quotation',
        );

        $this->actingAs($this->salesAgent)
            ->get(route('v2.quotations.index'))
            ->assertOk()
            ->assertSeeText($ownQuotation->client_name)
            ->assertDontSeeText($otherQuotation->client_name);

        $this->actingAs($this->salesAgent)
            ->get(route('v2.quotations.show', $otherQuotation))
            ->assertNotFound();
    }

    public function test_super_admin_sees_and_opens_every_quotation(): void
    {
        $employeeQuotation = $this->createQuotation(
            $this->salesAgent,
            'Employee quotation visible to admin',
        );
        $adminQuotation = $this->createQuotation(
            $this->superAdmin,
            'Administrator quotation visible to admin',
        );

        $this->actingAs($this->superAdmin)
            ->get(route('v2.quotations.index'))
            ->assertOk()
            ->assertSeeText($employeeQuotation->client_name)
            ->assertSeeText($adminQuotation->client_name);

        $this->actingAs($this->superAdmin)
            ->get(route('v2.quotations.show', $employeeQuotation))
            ->assertOk();
    }

    private function createQuotation(User $creator, string $clientName): Quotation
    {
        return Quotation::query()->create([
            'quotation_no' => 'Q-'.uniqid(),
            'client_name' => $clientName,
            'quote_date' => now()->toDateString(),
            'grand_total' => 100,
            'payload' => ['items' => []],
            'created_by' => $creator->name,
            'created_by_user_id' => $creator->getKey(),
        ]);
    }
}
