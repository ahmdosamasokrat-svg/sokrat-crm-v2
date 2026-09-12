<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Group;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PipelineStageUserAccessTest extends TestCase
{
    use RefreshDatabase;

    private Group $regularGroup;

    private User $user;

    private PipelineStage $firstStage;

    private PipelineStage $secondStage;

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

        $this->regularGroup = Group::query()->create([
            'name' => 'Stage Restricted Users',
            'code' => 'stage-restricted-users',
        ]);
        $this->regularGroup->permissions()->sync(
            Permission::query()
                ->whereIn('code', [
                    CrmPermission::LEADS_VIEW->value,
                    CrmPermission::LEADS_SCOPE_ALL->value,
                ])
                ->pluck('id'),
        );

        $this->user = User::factory()->create();
        $this->user->groups()->attach($this->regularGroup);

        $this->firstStage = $this->createStage('stage-one', 'المرحلة الأولى', 1);
        $this->secondStage = $this->createStage('stage-two', 'المرحلة الثانية', 2);
    }

    public function test_all_mode_includes_existing_and_new_stages(): void
    {
        $newStage = $this->createStage('stage-three', 'المرحلة الثالثة', 3);

        $this->assertSame(
            [$this->firstStage->id, $this->secondStage->id, $newStage->id],
            PipelineStage::query()
                ->visibleTo($this->user)
                ->orderBy('position')
                ->pluck('id')
                ->all(),
        );
    }

    public function test_selected_mode_limits_stages_statuses_and_leads_even_with_all_leads_scope(): void
    {
        $this->user->update(['pipeline_stage_access_mode' => 'selected']);
        $this->user->pipelineStages()->sync([$this->firstStage->id]);

        $allowedLead = $this->createLead($this->firstStage, 'Allowed Lead');
        $blockedLead = $this->createLead($this->secondStage, 'Blocked Lead');

        $this->assertSame(
            [$this->firstStage->id],
            PipelineStage::query()->visibleTo($this->user)->pluck('id')->all(),
        );
        $this->assertSame(
            [$this->firstStage->statuses()->firstOrFail()->id],
            \App\Models\LeadStatus::query()->visibleTo($this->user)->pluck('id')->all(),
        );
        $this->assertSame(
            [$allowedLead->id],
            Lead::query()->accessibleTo($this->user)->pluck('id')->all(),
        );
        $this->assertTrue($allowedLead->isAccessibleTo($this->user));
        $this->assertFalse($blockedLead->isAccessibleTo($this->user));
    }

    public function test_transition_to_a_blocked_stage_is_rejected(): void
    {
        $this->user->update(['pipeline_stage_access_mode' => 'selected']);
        $this->user->pipelineStages()->sync([$this->firstStage->id]);
        $lead = $this->createLead($this->firstStage, 'Transition Lead');

        $this->expectException(ValidationException::class);

        app(LeadTransitionService::class)->transition(
            $lead,
            $this->secondStage->statuses()->firstOrFail(),
            $this->user,
        );
    }

    public function test_super_admin_bypasses_selected_stage_restrictions(): void
    {
        $superAdminGroup = Group::query()->create([
            'name' => 'Super Admin',
            'code' => Group::SUPER_ADMIN_CODE,
            'is_system' => true,
        ]);
        $superAdmin = User::factory()->create([
            'pipeline_stage_access_mode' => 'selected',
        ]);
        $superAdmin->groups()->attach($superAdminGroup);
        $superAdmin->pipelineStages()->attach($this->firstStage);

        $this->assertCount(2, PipelineStage::query()->visibleTo($superAdmin)->get());
    }

    public function test_user_settings_persist_selected_stages_and_show_new_stages_dynamically(): void
    {
        $superAdminGroup = Group::query()->create([
            'name' => 'Super Admin',
            'code' => Group::SUPER_ADMIN_CODE,
            'is_system' => true,
        ]);
        $superAdmin = User::factory()->create();
        $superAdmin->groups()->attach($superAdminGroup);

        $this->actingAs($superAdmin)
            ->patch(route('v2.settings.users.update', $this->user), [
                'name' => $this->user->name,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'group_ids' => [$this->regularGroup->id],
                'pipeline_stage_access_mode' => 'selected',
                'pipeline_stage_ids' => [$this->secondStage->id],
            ])
            ->assertRedirect();

        $this->assertSame('selected', $this->user->refresh()->pipeline_stage_access_mode);
        $this->assertSame(
            [$this->secondStage->id],
            $this->user->pipelineStages()->pluck('pipeline_stages.id')->all(),
        );

        $newStage = $this->createStage('dynamic-stage', 'مرحلة ديناميكية', 3);

        $this->actingAs($superAdmin)
            ->get(route('v2.settings.users.edit', $this->user))
            ->assertOk()
            ->assertSee($newStage->name_ar);
    }

    private function createStage(string $code, string $name, int $position): PipelineStage
    {
        return PipelineStage::query()->create([
            'code' => $code,
            'name_ar' => $name,
            'position' => $position,
            'is_active' => true,
        ]);
    }

    private function createLead(PipelineStage $stage, string $name): Lead
    {
        return Lead::query()->create([
            'lead_status_id' => $stage->statuses()->firstOrFail()->id,
            'name' => $name,
            'assigned_user_id' => $this->user->id,
            'created_by_user_id' => $this->user->id,
        ]);
    }
}
