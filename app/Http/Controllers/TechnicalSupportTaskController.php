<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TechnicalSupportTask;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TechnicalSupportTaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $status = trim((string) $request->query('status', 'active'));
        if (! in_array($status, ['active', 'pending', 'in_progress', 'completed', 'all'], true)) {
            $status = 'active';
        }

        $priority = trim((string) $request->query('priority', 'all'));
        if (! in_array($priority, ['all', 'low', 'normal', 'high'], true)) {
            $priority = 'all';
        }

        $assigneeId = $request->filled('assignee_id')
            ? $request->integer('assignee_id')
            : null;
        $search = trim((string) $request->query('search', ''));

        $accessibleTasks = TechnicalSupportTask::query()->accessibleTo($user);
        $today = today();
        $metrics = [
            'active' => (clone $accessibleTasks)
                ->whereIn('status', [TechnicalSupportTask::STATUS_PENDING, TechnicalSupportTask::STATUS_IN_PROGRESS])
                ->count(),
            'due_today' => (clone $accessibleTasks)
                ->whereDate('due_date', $today)
                ->where('status', '!=', TechnicalSupportTask::STATUS_COMPLETED)
                ->count(),
            'overdue' => (clone $accessibleTasks)
                ->whereDate('due_date', '<', $today)
                ->where('status', '!=', TechnicalSupportTask::STATUS_COMPLETED)
                ->count(),
            'completed' => (clone $accessibleTasks)
                ->where('status', TechnicalSupportTask::STATUS_COMPLETED)
                ->count(),
        ];

        $tasksQuery = TechnicalSupportTask::query()
            ->accessibleTo($user)
            ->with(['assignee:id,name', 'creator:id,name', 'completedBy:id,name']);

        if ($status === 'active') {
            $tasksQuery->whereIn('status', [TechnicalSupportTask::STATUS_PENDING, TechnicalSupportTask::STATUS_IN_PROGRESS]);
        } elseif ($status !== 'all') {
            $tasksQuery->where('status', $status);
        }

        if ($priority !== 'all') {
            $tasksQuery->where('priority', $priority);
        }

        if ($assigneeId !== null && $assigneeId > 0) {
            $tasksQuery->where('assigned_to_user_id', $assigneeId);
        }

        if ($search !== '') {
            $tasksQuery->where(static function (Builder $query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $tasksQuery
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('technical-support.tasks.index', compact(
            'tasks',
            'users',
            'metrics',
            'status',
            'priority',
            'assigneeId',
            'search',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $data = $this->validateTask($request);
        $data['created_by_user_id'] = $user->getKey();
        $data['status'] = TechnicalSupportTask::STATUS_PENDING;

        TechnicalSupportTask::query()->create($data);

        return redirect()
            ->route('v2.technical-support.tasks.index')
            ->with('success', __('crm.support_task_created'));
    }

    public function update(Request $request, TechnicalSupportTask $task): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $task->isEditableBy($user), 403);

        $task->update($this->validateTask($request));

        return redirect()
            ->route('v2.technical-support.tasks.index', $request->only(['status', 'priority', 'assignee_id', 'search', 'page']))
            ->with('success', __('crm.support_task_updated'));
    }

    public function updateStatus(Request $request, TechnicalSupportTask $task): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $task->isStatusEditableBy($user), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                TechnicalSupportTask::STATUS_PENDING,
                TechnicalSupportTask::STATUS_IN_PROGRESS,
                TechnicalSupportTask::STATUS_COMPLETED,
            ])],
        ]);

        $isCompleted = $data['status'] === TechnicalSupportTask::STATUS_COMPLETED;
        $task->update([
            'status' => $data['status'],
            'completed_at' => $isCompleted ? now() : null,
            'completed_by_user_id' => $isCompleted ? $user->getKey() : null,
        ]);

        return back()->with('success', __('crm.support_task_status_updated'));
    }

    public function destroy(Request $request, TechnicalSupportTask $task): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $task->isEditableBy($user), 403);

        $task->delete();

        return redirect()
            ->route('v2.technical-support.tasks.index')
            ->with('success', __('crm.support_task_deleted'));
    }

    /**
     * @return array{title: string, description?: string|null, assigned_to_user_id: int, due_date?: string|null, priority: string}
     */
    private function validateTask(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'assigned_to_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(static fn ($query) => $query->where('is_active', true)),
            ],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in([
                TechnicalSupportTask::PRIORITY_LOW,
                TechnicalSupportTask::PRIORITY_NORMAL,
                TechnicalSupportTask::PRIORITY_HIGH,
            ])],
        ]);
    }
}
