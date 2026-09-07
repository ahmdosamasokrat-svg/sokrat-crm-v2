@php
 $taskColor = preg_match('/^#[0-9a-f]{6}$/i', (string) $task->color) ? $task->color : '#dc2637';
 $isOverdue = $task->status !== 'completed' && $task->due_date?->isPast() && ! $task->due_date?->isToday();
@endphp
<article class="t-card {{ $isOverdue ? 'is-overdue' : '' }} {{ $task->status === 'completed' ? 'is-completed' : '' }}" id="task-{{ $task->id }}" style="--task-color: {{ $taskColor }}">
 <div class="t-card-head">
  <div class="t-card-identity">
   <span class="t-card-color-dot" aria-hidden="true"></span>
   <h3>{{ $task->title }}</h3>
  </div>
  <div class="t-card-badges">
   <span class="t-badge status-{{ $task->status }}">
    @if($task->status === 'completed')
     <i class="bi bi-check2"></i>
    @endif
    {{ $statusLabels[$task->status] ?? $task->status }}
   </span>
   <span class="t-badge priority-{{ $task->priority }}">
    <i class="bi bi-flag-fill" aria-hidden="true"></i>
    {{ $priorityLabels[$task->priority] ?? $task->priority }}
   </span>
   @if($isOverdue)
    <span class="t-badge overdue-pill">
     <i class="bi bi-exclamation-triangle-fill"></i>
     {{ __('متأخرة') }}
    </span>
   @endif
  </div>
 </div>

 @if($task->description)
  <p class="t-card-desc">{{ $task->description }}</p>
 @endif

 <div class="t-card-meta">
  <span class="t-card-user" title="{{ __('crm.support_task_assigned_to') }}">
   <span class="t-card-user-avatar">{{ mb_substr($task->assignee?->name ?? '—', 0, 1) }}</span>
   <span>{{ $task->assignee?->name ?? __('crm.unassigned') }}</span>
  </span>

  <span class="t-card-due {{ $isOverdue ? 'is-overdue-text' : '' }}" title="{{ __('crm.support_task_due') }}">
   <i class="bi bi-calendar3"></i>
   <span>{{ $task->due_date?->format('Y-m-d') ?? __('crm.support_task_no_due_date') }}</span>
  </span>
 </div>

 <div class="t-card-foot">
  @if($task->isStatusEditableBy(auth()->user()))
   <form class="status-form" method="POST" action="{{ route('v2.technical-support.tasks.status', $task) }}">
    @csrf
    @method('PATCH')
    <label for="taskStatusSelect{{ $task->id }}" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">{{ __('crm.support_task_update_status') }}</label>
    <select id="taskStatusSelect{{ $task->id }}" name="status" onchange="this.form.submit()" title="{{ __('crm.support_task_update_status') }}">
     @foreach($statusLabels as $value => $label)
      <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
     @endforeach
    </select>
   </form>
  @else
   <span></span>
  @endif

  <div class="t-card-buttons">
   @if($task->isEditableBy(auth()->user()))
    <button
     type="button"
     class="btn-icon-action btn-edit-task"
     title="{{ __('crm.support_task_edit') }}"
     data-task-id="{{ $task->id }}"
     data-title="{{ $task->title }}"
     data-description="{{ $task->description }}"
     data-assignee-id="{{ $task->assigned_to_user_id }}"
     data-due-date="{{ $task->due_date?->format('Y-m-d') }}"
     data-priority="{{ $task->priority }}"
     data-color="{{ $taskColor }}"
     data-update-url="{{ route('v2.technical-support.tasks.update', $task) }}"
    >
     <i class="bi bi-pencil-square" aria-hidden="true"></i>
    </button>
   @endif

   @if($task->isEditableBy(auth()->user()))
    <form class="confirm-delete" method="POST" action="{{ route('v2.technical-support.tasks.destroy', $task) }}" data-confirm="{{ __('crm.support_task_delete_confirm') }}">
     @csrf
     @method('DELETE')
     <button class="btn-icon-action" type="submit" title="{{ __('crm.delete') }}" style="color:#dc2637">
      <i class="bi bi-trash3" aria-hidden="true"></i>
     </button>
    </form>
   @endif
  </div>
 </div>
</article>
