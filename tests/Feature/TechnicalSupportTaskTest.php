<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\TechnicalSupportTask;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalSupportTaskTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_manager_can_assign_a_colored_task_and_employee_is_notified(): void
    {
        $manager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $employee = User::factory()->create(['is_active' => true, 'locale' => 'ar']);

        $this->actingAs($manager)
            ->post(route('v2.technical-support.tasks.store'), [
                'title' => 'Review branch server',
                'description' => 'Check the firewall and document the result.',
                'assigned_to_user_id' => $employee->getKey(),
                'due_date' => '2026-09-10',
                'priority' => TechnicalSupportTask::PRIORITY_HIGH,
                'color' => '#2563eb',
            ])
            ->assertRedirect(route('v2.technical-support.tasks.index'));

        $task = TechnicalSupportTask::query()->firstOrFail();
        $this->assertSame('#2563eb', $task->color);
        $this->assertSame(TechnicalSupportTask::PRIORITY_HIGH, $task->priority);
        $this->assertSame($employee->getKey(), $task->assigned_to_user_id);

        $notification = $employee->notifications()->firstOrFail();
        $this->assertSame('support_task.assigned', $notification->data['event_key']);
        $this->assertSame($task->getKey(), $notification->data['source_id']);
        $this->assertSame('urgent', $notification->data['priority']);
        $this->assertSame(
            route('v2.technical-support.tasks.index', [
                'status' => 'all',
                'task_id' => $task->getKey(),
            ], false).'#task-'.$task->getKey(),
            $notification->data['action_url'],
        );

        $this->actingAs($employee)
            ->delete(route('v2.notifications.destroy', $notification->id))
            ->assertOk();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_employee_only_sees_assigned_tasks_and_can_complete_them(): void
    {
        $manager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $employee = User::factory()->create(['is_active' => true]);
        $otherEmployee = User::factory()->create(['is_active' => true]);
        $ownTask = $this->createTask($manager, $employee, 'Assigned task');
        $otherTask = $this->createTask($manager, $otherEmployee, 'Hidden task');

        $this->actingAs($employee)
            ->get(route('v2.technical-support.tasks.index'))
            ->assertOk()
            ->assertSeeText($ownTask->title)
            ->assertDontSeeText($otherTask->title)
            ->assertSee('crmTechnicalSupportMenu', false)
            ->assertDontSeeText(__('crm.support_task_new'));

        $this->actingAs($employee)
            ->patch(route('v2.technical-support.tasks.status', $ownTask), [
                'status' => TechnicalSupportTask::STATUS_COMPLETED,
            ])
            ->assertRedirect();

        $ownTask->refresh();
        $this->assertSame(TechnicalSupportTask::STATUS_COMPLETED, $ownTask->status);
        $this->assertSame($employee->getKey(), $ownTask->completed_by_user_id);
        $this->assertNotNull($ownTask->completed_at);

        $this->actingAs($employee)
            ->patch(route('v2.technical-support.tasks.status', $otherTask), [
                'status' => TechnicalSupportTask::STATUS_COMPLETED,
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_create_tasks_and_manager_input_is_validated(): void
    {
        $manager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $employee = User::factory()->create(['is_active' => true]);
        $inactiveEmployee = User::factory()->create(['is_active' => false]);
        $payload = [
            'title' => 'Invalid task',
            'assigned_to_user_id' => $inactiveEmployee->getKey(),
            'priority' => TechnicalSupportTask::PRIORITY_NORMAL,
            'color' => 'red',
        ];

        $this->actingAs($employee)
            ->post(route('v2.technical-support.tasks.store'), $payload)
            ->assertForbidden();

        $this->actingAs($manager)
            ->from(route('v2.technical-support.tasks.index'))
            ->post(route('v2.technical-support.tasks.store'), $payload)
            ->assertRedirect(route('v2.technical-support.tasks.index'))
            ->assertSessionHasErrors(['assigned_to_user_id', 'color']);

        $this->assertDatabaseCount('technical_support_tasks', 0);
    }

    public function test_reassignment_notifies_the_new_employee(): void
    {
        $manager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $firstEmployee = User::factory()->create(['is_active' => true]);
        $newEmployee = User::factory()->create(['is_active' => true]);
        $task = $this->createTask($manager, $firstEmployee, 'Reassigned task');

        $this->actingAs($manager)
            ->patch(route('v2.technical-support.tasks.update', $task), [
                'title' => $task->title,
                'description' => null,
                'assigned_to_user_id' => $newEmployee->getKey(),
                'due_date' => null,
                'priority' => TechnicalSupportTask::PRIORITY_LOW,
                'color' => '#0f7440',
            ])
            ->assertRedirect();

        $this->assertSame(1, $newEmployee->notifications()->count());
        $this->assertSame(
            $task->getKey(),
            $newEmployee->notifications()->firstOrFail()->data['source_id'],
        );
    }

    public function test_revoked_manager_only_keeps_access_to_tasks_assigned_to_them(): void
    {
        $formerManager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $employee = User::factory()->create(['is_active' => true]);
        $task = $this->createTask($formerManager, $employee, 'Manager-only task');

        $formerManager->groups()->detach();
        $formerManager->unsetRelation('groups');

        $this->actingAs($formerManager)
            ->get(route('v2.technical-support.tasks.index'))
            ->assertOk()
            ->assertDontSeeText($task->title);
        $this->actingAs($formerManager)
            ->patch(route('v2.technical-support.tasks.status', $task), [
                'status' => TechnicalSupportTask::STATUS_COMPLETED,
            ])
            ->assertForbidden();
    }

    public function test_manager_can_oversee_all_tasks_and_due_filters_match_their_metrics(): void
    {
        $firstManager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $secondManager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $employee = User::factory()->create(['is_active' => true]);
        $todayTask = $this->createTask($firstManager, $employee, 'Due today task');
        $todayTask->update(['due_date' => today()]);
        $overdueTask = $this->createTask($firstManager, $employee, 'Overdue task');
        $overdueTask->update(['due_date' => today()->subDay()]);
        $futureTask = $this->createTask($firstManager, $employee, 'Future task');
        $futureTask->update(['due_date' => today()->addDay()]);

        $this->actingAs($secondManager)
            ->get(route('v2.technical-support.tasks.index', [
                'status' => 'active',
                'due' => 'today',
            ]))
            ->assertOk()
            ->assertSeeText($todayTask->title)
            ->assertDontSeeText($overdueTask->title)
            ->assertDontSeeText($futureTask->title)
            ->assertSee('data-confirm=', false)
            ->assertDontSee('onsubmit=', false);

        $this->actingAs($secondManager)
            ->delete(route('v2.technical-support.tasks.destroy', $overdueTask))
            ->assertRedirect(route('v2.technical-support.tasks.index'));
        $this->assertModelMissing($overdueTask);
    }

    public function test_manager_sees_cards_and_board_view_layout_and_kpis(): void
    {
        $manager = $this->createUserWithPermissions([
            CrmPermission::TECHNICAL_SUPPORT_TASKS_MANAGE->value,
        ]);
        $employee = User::factory()->create(['is_active' => true]);
        $task = $this->createTask($manager, $employee, 'UI Modern Task Card');
        $task->update(['status' => TechnicalSupportTask::STATUS_IN_PROGRESS, 'color' => '#2563eb']);

        $response = $this->actingAs($manager)->get(route('v2.technical-support.tasks.index'));

        $response->assertOk();
        $response->assertSee('btnOpenNewTaskModal');
        $response->assertSee('boardViewContainer');
        $response->assertSee('gridViewContainer');
        $response->assertSee('t-card');
        $response->assertSee('#2563eb');
        $response->assertSee('status-in_progress');
        $response->assertSee('active-kpi');
    }

    /** @param list<string> $permissionCodes */
    private function createUserWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => 'Task role '.uniqid(),
            'code' => 'task-role-'.uniqid(),
            'is_system' => false,
        ]);
        $group->permissions()->sync(
            Permission::query()->whereIn('code', $permissionCodes)->pluck('id'),
        );
        $user = User::factory()->create(['is_active' => true]);
        $user->groups()->attach($group);

        return $user;
    }

    private function createTask(User $manager, User $employee, string $title): TechnicalSupportTask
    {
        return TechnicalSupportTask::query()->create([
            'title' => $title,
            'status' => TechnicalSupportTask::STATUS_PENDING,
            'priority' => TechnicalSupportTask::PRIORITY_NORMAL,
            'color' => '#dc2637',
            'assigned_to_user_id' => $employee->getKey(),
            'created_by_user_id' => $manager->getKey(),
        ]);
    }
}
