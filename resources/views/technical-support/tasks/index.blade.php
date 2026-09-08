@php
 $crmSidebarAssetsLoaded = true;
 $statusLabels = [
  'pending' => __('crm.support_task_status_pending'),
  'in_progress' => __('crm.support_task_status_in_progress'),
  'completed' => __('crm.support_task_status_completed'),
 ];
 $priorityLabels = [
  'low' => __('crm.support_task_priority_low'),
  'normal' => __('crm.support_task_priority_normal'),
  'high' => __('crm.support_task_priority_high'),
 ];
 $createHasErrors = old('form_context') === 'create';
 $createColorCandidate = $createHasErrors ? (string) old('color') : '#dc2637';
 $createColor = preg_match('/^#[0-9a-f]{6}$/i', $createColorCandidate) ? $createColorCandidate : '#dc2637';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <meta name="csrf-token" content="{{ csrf_token() }}">
 <title>{{ __('crm.support_tasks_title') }} - SokratCRM</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
 <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <style>
  *{box-sizing:border-box}
  :root{--task-red:#dc2637;--task-red-dark:#b81829;--task-bg:#f4f6fa;--task-panel:#fff;--task-soft:#f8fafc;--task-ink:#172033;--task-muted:#596579;--task-line:#e4e8ef;--task-green:#0f7440;--task-green-bg:#e9f8ef;--task-amber:#986800;--task-amber-bg:#fff6d8;--task-shadow:0 14px 38px rgba(17,24,39,.06);--font-primary:'Tajawal',Tahoma,Arial,sans-serif}
  html.dark-mode{--task-bg:#151922;--task-panel:#202631;--task-soft:#272e3a;--task-ink:#f3f5f8;--task-muted:#aeb7c6;--task-line:#333b49;--task-green:#57d58c;--task-green-bg:#173c2a;--task-amber:#f0c75e;--task-amber-bg:#3c3218;--task-shadow:0 18px 42px rgba(0,0,0,.24)}
  html{background:var(--task-bg)}body{margin:0;min-width:320px;background:var(--task-bg);color:var(--task-ink);font-family:var(--font-primary)}button,input,select,textarea{font:inherit}a{color:inherit}::selection{background:#dc26372a;color:var(--task-ink)}*{scrollbar-width:thin;scrollbar-color:#aeb6c4 transparent}:focus-visible{outline:3px solid rgba(220,38,55,.28);outline-offset:3px}
  .task-shell{display:flex;min-height:100vh;max-width:100vw;overflow-x:hidden}.task-main{min-width:0;flex:1;max-width:100%;padding:24px 30px 48px}.task-stack{display:grid;gap:18px}
  .task-alert{display:flex;align-items:flex-start;gap:10px;padding:13px 15px;border:1px solid var(--task-line);border-radius:14px;background:var(--task-panel);color:var(--task-muted);font-size:13px;font-weight:700;line-height:1.6}.task-alert i{margin-top:2px;color:var(--task-red)}.flash{color:var(--task-green);border-color:color-mix(in srgb,var(--task-green) 35%,var(--task-line));background:var(--task-green-bg)}.error{color:#9c1a29;border-color:#efbec4;background:#fff0f2}html.dark-mode .error{color:#ffb4bc;border-color:#71343d;background:#4b222a}
  .metric-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));overflow:hidden;border:1px solid var(--task-line);border-radius:16px;background:var(--task-panel);box-shadow:var(--task-shadow)}.metric-item{display:flex;align-items:center;gap:12px;min-width:0;padding:17px 19px;text-decoration:none}.metric-item+.metric-item{border-inline-start:1px solid var(--task-line)}.metric-icon{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;border-radius:11px;background:var(--task-soft);color:var(--task-muted)}.metric-item.attention .metric-icon{background:var(--task-amber-bg);color:var(--task-amber)}.metric-item.complete .metric-icon{background:var(--task-green-bg);color:var(--task-green)}.metric-copy{min-width:0}.metric-copy span,.metric-copy strong{display:block}.metric-copy span{color:var(--task-muted);font-size:11px;font-weight:800}.metric-copy strong{margin-top:3px;font-size:21px;font-weight:900;font-variant-numeric:tabular-nums}
  .task-layout{display:grid;grid-template-columns:minmax(0,1fr) 350px;gap:18px;align-items:start}.task-layout.employee{grid-template-columns:1fr}.panel{border:1px solid var(--task-line);border-radius:16px;background:var(--task-panel);box-shadow:var(--task-shadow);overflow:hidden}.panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--task-line)}.panel-title{display:flex;align-items:flex-start;gap:11px;min-width:0}.panel-title>i{width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;border-radius:11px;background:#fff0f2;color:var(--task-red)}html.dark-mode .panel-title>i{background:#4b222a;color:#ff9aa4}.panel-title h2{margin:0;font-size:16px;font-weight:900}.panel-title p{margin:4px 0 0;color:var(--task-muted);font-size:12px;line-height:1.55}
  .filters{display:grid;grid-template-columns:minmax(180px,1.4fr) repeat(3,minmax(130px,.8fr)) auto;gap:10px;padding:15px 18px;border-bottom:1px solid var(--task-line);background:var(--task-soft)}.filters.no-assignee{grid-template-columns:minmax(180px,1.4fr) repeat(2,minmax(130px,.8fr)) auto}.field{display:grid;gap:7px;min-width:0}.field label{color:var(--task-muted);font-size:11px;font-weight:900}.field input,.field select,.field textarea{width:100%;border:1px solid var(--task-line);border-radius:10px;background:var(--task-soft);color:var(--task-ink);outline:none}.field input,.field select{height:42px;padding:0 11px}.field textarea{min-height:100px;padding:10px 11px;line-height:1.65;resize:vertical}.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--task-red);box-shadow:0 0 0 3px rgba(220,38,55,.1);background:var(--task-panel)}.field input::placeholder,.field textarea::placeholder{color:var(--task-muted)}.filter-actions{display:flex;align-items:end;gap:7px}
  .btn{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 15px;border:1px solid var(--task-line);border-radius:10px;background:var(--task-panel);color:var(--task-ink);font-size:12px;font-weight:900;text-decoration:none;cursor:pointer}.btn:hover{border-color:var(--task-red);color:var(--task-red)}.btn.primary{border-color:var(--task-red);background:var(--task-red);color:#fff}.btn.primary:hover{background:var(--task-red-dark);color:#fff}.btn.danger{color:var(--task-red)}.btn.compact{min-height:36px;padding:0 11px;font-size:11px}
  .task-list{display:grid}.task-row{--task-color:#dc2637;position:relative;padding:19px 20px;border-bottom:1px solid var(--task-line);background:linear-gradient(90deg,color-mix(in srgb,var(--task-color) 7%,var(--task-panel)),var(--task-panel) 38%)}html[dir=rtl] .task-row{background:linear-gradient(270deg,color-mix(in srgb,var(--task-color) 7%,var(--task-panel)),var(--task-panel) 38%)}.task-row:last-child{border-bottom:0}.task-row-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}.task-identity{display:flex;align-items:flex-start;gap:11px;min-width:0}.task-swatch{width:12px;height:12px;flex:0 0 12px;margin-top:5px;border-radius:4px;background:var(--task-color);box-shadow:0 0 0 4px color-mix(in srgb,var(--task-color) 14%,transparent)}.task-identity h3{margin:0;font-size:15px;font-weight:900;line-height:1.5;overflow-wrap:anywhere}.task-badges{display:flex;justify-content:flex-end;gap:6px;flex-wrap:wrap}.badge{display:inline-flex;align-items:center;gap:5px;min-height:26px;padding:3px 8px;border-radius:8px;background:var(--task-soft);color:var(--task-muted);font-size:10px;font-weight:900}.badge.status-in_progress{background:var(--task-amber-bg);color:var(--task-amber)}.badge.status-completed{background:var(--task-green-bg);color:var(--task-green)}.badge.priority-high{background:#fff0f2;color:#a91d2d}html.dark-mode .badge.priority-high{background:#4b222a;color:#ffb4bc}.task-description{max-width:72ch;margin:10px 23px 0 0;color:var(--task-muted);font-size:13px;font-weight:600;line-height:1.75;white-space:pre-wrap;overflow-wrap:anywhere}html[dir=ltr] .task-description{margin:10px 0 0 23px}.task-meta{display:flex;align-items:center;gap:8px 18px;flex-wrap:wrap;margin-top:13px;color:var(--task-muted);font-size:11px;font-weight:800}.task-meta span{display:inline-flex;align-items:center;gap:6px}.task-meta i{color:var(--task-color)}.task-row.overdue .due-meta{color:var(--task-red)}
  .task-workflow{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-top:16px;padding-top:14px;border-top:1px dashed var(--task-line)}.status-form{display:flex;align-items:end;gap:8px}.status-form .field{min-width:150px}.task-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap}.task-edit{margin-top:14px;border-top:1px solid var(--task-line)}.task-edit summary{width:max-content;margin-top:12px;color:var(--task-red);font-size:11px;font-weight:900;cursor:pointer;list-style:none}.task-edit summary::-webkit-details-marker{display:none}.edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px;margin-top:13px;padding:15px;border-radius:12px;background:var(--task-soft)}.edit-grid .wide{grid-column:1/-1}.edit-actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px}
  .empty-state{display:grid;place-items:center;min-height:260px;padding:38px 20px;text-align:center}.empty-state i{width:52px;height:52px;display:grid;place-items:center;margin-bottom:12px;border-radius:14px;background:var(--task-soft);color:var(--task-muted);font-size:22px}.empty-state strong{font-size:16px}.empty-state p{max-width:48ch;margin:5px 0 0;color:var(--task-muted);font-size:12px;line-height:1.7}.pagination-wrap{padding:13px 18px;border-top:1px solid var(--task-line)}
  .create-panel{position:sticky;top:18px}.create-form{display:grid;gap:13px;padding:18px}.create-grid{display:grid;grid-template-columns:1fr 1fr;gap:11px}.create-grid .wide{grid-column:1/-1}.color-control{display:flex;align-items:center;gap:9px}.color-control input[type=color]{width:48px;padding:4px;cursor:pointer}.color-value{direction:ltr;color:var(--task-muted);font:800 11px ui-monospace,SFMono-Regular,Menlo,monospace}.create-form .btn{width:100%;margin-top:2px}
  @media(max-width:1180px){.task-layout{grid-template-columns:minmax(0,1fr) 310px}.filters{grid-template-columns:repeat(2,minmax(0,1fr))}.filter-actions{align-items:center}.filters.no-assignee{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(max-width:900px){.task-shell{display:block}.task-main{width:100%;padding:18px 16px 36px}.task-layout{grid-template-columns:1fr}.create-panel{position:static;order:-1}.metric-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.metric-item:nth-child(3){border-inline-start:0}.metric-item:nth-child(n+3){border-top:1px solid var(--task-line)}body.crm-side-open{overflow:hidden}}
  @media(max-width:620px){.task-main{padding:14px 10px 28px}.metric-item{padding:14px 12px}.metric-icon{width:34px;height:34px;flex-basis:34px}.metric-copy strong{font-size:18px}.filters,.filters.no-assignee{grid-template-columns:1fr;padding:14px}.filter-actions{display:grid;grid-template-columns:1fr auto}.task-row{padding:16px 14px}.task-row-head,.task-workflow{align-items:stretch;flex-direction:column}.task-badges{justify-content:flex-start}.task-description{margin-inline:0}.status-form{display:grid;grid-template-columns:1fr auto}.status-form .field{min-width:0}.task-actions{justify-content:flex-start}.edit-grid,.create-grid{grid-template-columns:1fr}.edit-grid .wide,.create-grid .wide,.edit-actions{grid-column:auto}.edit-actions{display:grid;grid-template-columns:1fr auto}.panel-head{padding:16px}.create-form{padding:15px}}
  @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
 </style>
</head>
<body>
<!-- THESIS: Support tasks are a shared operational ledger, not a generic dashboard of disconnected cards.
OWN-WORLD: SokratCRM neutral surfaces, Tajawal typography, red interaction emphasis, and employee-selected task color.
STORY: Managers assign clearly scoped work; employees scan urgency, open details, and move work to completion.
FIRST VIEWPORT: Shared navigation and topbar lead into one metric strip, then filters and the live task ledger beside manager creation controls.
FORM: Established SokratCRM operational surface extended for task assignment and status updates.
FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance. -->
 @include('partials.page-loader')
 <div class="task-shell">
  @include('partials.crm-sidebar')
  <main class="task-main">
   @php
    ob_start();
   @endphp
    @if($canManage)
     <a class="btn primary" href="#newTask"><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __('crm.support_task_new') }}</a>
    @endif
   @php
    $taskActions = ob_get_clean();
   @endphp
   @include('partials.topbar', [
    'title' => __('crm.support_tasks_title'),
    'subtitle' => __('crm.support_tasks_subtitle'),
    'icon' => 'bi-list-check',
    'actions' => $taskActions,
   ])

   <div class="task-stack">
    @if(session('success'))
     <div class="task-alert flash" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
     <div class="task-alert error" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><div><strong>{{ __('crm.support_task_validation_error') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif

    <section class="metric-strip" aria-label="{{ __('crm.support_tasks_title') }}">
     <a class="metric-item" href="{{ route('v2.technical-support.tasks.index', ['status' => 'active']) }}"><span class="metric-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span><span class="metric-copy"><span>{{ __('crm.support_tasks_active') }}</span><strong>{{ number_format($metrics['active']) }}</strong></span></a>
     <a class="metric-item attention" href="{{ route('v2.technical-support.tasks.index', ['status' => 'active', 'due' => 'today']) }}"><span class="metric-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span><span class="metric-copy"><span>{{ __('crm.support_tasks_due_today') }}</span><strong>{{ number_format($metrics['due_today']) }}</strong></span></a>
     <a class="metric-item attention" href="{{ route('v2.technical-support.tasks.index', ['status' => 'active', 'due' => 'overdue']) }}"><span class="metric-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span><span class="metric-copy"><span>{{ __('crm.support_tasks_overdue') }}</span><strong>{{ number_format($metrics['overdue']) }}</strong></span></a>
     <a class="metric-item complete" href="{{ route('v2.technical-support.tasks.index', ['status' => 'completed']) }}"><span class="metric-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span><span class="metric-copy"><span>{{ __('crm.support_tasks_completed') }}</span><strong>{{ number_format($metrics['completed']) }}</strong></span></a>
    </section>

    @unless($canManage)
     <div class="task-alert"><i class="bi bi-person-check" aria-hidden="true"></i><span>{{ __('crm.support_task_manager_note') }}</span></div>
    @endunless

    <div class="task-layout {{ $canManage ? '' : 'employee' }}">
     <section class="panel">
      <header class="panel-head"><div class="panel-title"><i class="bi bi-list-task" aria-hidden="true"></i><div><h2>{{ __('crm.support_tasks') }}</h2><p>{{ __('crm.support_tasks_subtitle') }}</p></div></div></header>
      <form class="filters {{ $canManage ? '' : 'no-assignee' }}" method="GET" action="{{ route('v2.technical-support.tasks.index') }}">
       @if($due !== 'all')<input type="hidden" name="due" value="{{ $due }}">@endif
       <div class="field"><label for="taskSearch">{{ __('crm.search') }}</label><input id="taskSearch" name="search" type="search" value="{{ $search }}" placeholder="{{ __('crm.support_task_search_placeholder') }}"></div>
       <div class="field"><label for="taskStatus">{{ __('crm.status') }}</label><select id="taskStatus" name="status"><option value="active" @selected($status === 'active')>{{ __('crm.support_task_status_active') }}</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach<option value="all" @selected($status === 'all')>{{ __('crm.support_task_all_statuses') }}</option></select></div>
       <div class="field"><label for="taskPriority">{{ __('crm.support_task_priority') }}</label><select id="taskPriority" name="priority"><option value="all">{{ __('crm.support_task_all_priorities') }}</option>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected($priority === $value)>{{ $label }}</option>@endforeach</select></div>
       @if($canManage)
        <div class="field"><label for="taskAssignee">{{ __('crm.support_task_assignee') }}</label><select id="taskAssignee" name="assignee_id"><option value="">{{ __('crm.support_task_all_assignees') }}</option>@foreach($users as $employee)<option value="{{ $employee->id }}" @selected($assigneeId === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
       @endif
       <div class="filter-actions"><button class="btn primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>{{ __('crm.support_task_filter') }}</button><a class="btn" href="{{ route('v2.technical-support.tasks.index') }}" aria-label="{{ __('crm.support_task_clear_filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></a></div>
      </form>

      @forelse($tasks as $task)
       @php
        $taskColor = preg_match('/^#[0-9a-f]{6}$/i', (string) $task->color) ? $task->color : '#dc2637';
        $isOverdue = $task->status !== 'completed' && $task->due_date?->isPast() && ! $task->due_date?->isToday();
        $editingThisTask = old('form_context') === 'edit:'.$task->id;
        $editColorCandidate = $editingThisTask ? (string) old('color') : $taskColor;
        $editColor = preg_match('/^#[0-9a-f]{6}$/i', $editColorCandidate) ? $editColorCandidate : $taskColor;
       @endphp
       <article class="task-row {{ $isOverdue ? 'overdue' : '' }}" id="task-{{ $task->id }}" style="--task-color:{{ $taskColor }}">
        <div class="task-row-head">
         <div class="task-identity"><span class="task-swatch" aria-hidden="true"></span><div><h3>{{ $task->title }}</h3></div></div>
         <div class="task-badges"><span class="badge status-{{ $task->status }}">{{ $statusLabels[$task->status] }}</span><span class="badge priority-{{ $task->priority }}"><i class="bi bi-flag-fill" aria-hidden="true"></i>{{ $priorityLabels[$task->priority] }}</span></div>
        </div>
        @if($task->description)<p class="task-description">{{ $task->description }}</p>@endif
        <div class="task-meta">
         <span><i class="bi bi-person-check" aria-hidden="true"></i>{{ __('crm.support_task_assigned_to') }}: {{ $task->assignee?->name }}</span>
         <span><i class="bi bi-person-plus" aria-hidden="true"></i>{{ __('crm.support_task_creator') }}: {{ $task->creator?->name ?? __('crm.not_specified') }}</span>
         <span class="due-meta"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ __('crm.support_task_due') }}: {{ $task->due_date?->format('Y-m-d') ?? __('crm.support_task_no_due_date') }}</span>
        </div>
        <div class="task-workflow">
         @if($task->isStatusEditableBy(auth()->user()))
          <form class="status-form" method="POST" action="{{ route('v2.technical-support.tasks.status', $task) }}">
           @csrf @method('PATCH')
           <div class="field"><label for="taskStatus{{ $task->id }}">{{ __('crm.support_task_update_status') }}</label><select id="taskStatus{{ $task->id }}" name="status">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>@endforeach</select></div>
           <button class="btn compact" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>{{ __('crm.support_task_save_status') }}</button>
          </form>
         @endif
         @if($task->isEditableBy(auth()->user()))
          <div class="task-actions"><form class="confirm-delete" method="POST" action="{{ route('v2.technical-support.tasks.destroy', $task) }}" data-confirm="{{ __('crm.support_task_delete_confirm') }}">@csrf @method('DELETE')<button class="btn compact danger" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i>{{ __('crm.delete') }}</button></form></div>
         @endif
        </div>
        @if($task->isEditableBy(auth()->user()))
         <details class="task-edit" @if($editingThisTask) open @endif>
          <summary><i class="bi bi-pencil-square" aria-hidden="true"></i> {{ __('crm.support_task_edit') }}</summary>
          <form class="edit-grid" method="POST" action="{{ route('v2.technical-support.tasks.update', $task) }}">
           @csrf @method('PATCH')
            <input type="hidden" name="form_context" value="edit:{{ $task->id }}">
            @foreach(request()->only(['status','priority','due','assignee_id','search','page']) as $queryKey => $queryValue)<input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">@endforeach
            <div class="field wide"><label for="editTitle{{ $task->id }}">{{ __('crm.support_task_title') }}</label><input id="editTitle{{ $task->id }}" name="title" required maxlength="255" value="{{ $editingThisTask ? old('title') : $task->title }}"></div>
            <div class="field wide"><label for="editDescription{{ $task->id }}">{{ __('crm.support_task_description') }}</label><textarea id="editDescription{{ $task->id }}" name="description" maxlength="3000">{{ $editingThisTask ? old('description') : $task->description }}</textarea></div>
            <div class="field"><label for="editAssignee{{ $task->id }}">{{ __('crm.support_task_assignee') }}</label><select id="editAssignee{{ $task->id }}" name="assigned_to_user_id" required>@foreach($users as $employee)<option value="{{ $employee->id }}" @selected(($editingThisTask ? (int) old('assigned_to_user_id') : $task->assigned_to_user_id) === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
            <div class="field"><label for="editDue{{ $task->id }}">{{ __('crm.support_task_due_date') }}</label><input id="editDue{{ $task->id }}" name="due_date" type="date" value="{{ $editingThisTask ? old('due_date') : $task->due_date?->format('Y-m-d') }}"></div>
            <div class="field"><label for="editPriority{{ $task->id }}">{{ __('crm.support_task_priority') }}</label><select id="editPriority{{ $task->id }}" name="priority" required>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(($editingThisTask ? old('priority') : $task->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="editColor{{ $task->id }}">{{ __('crm.support_task_color') }}</label><input id="editColor{{ $task->id }}" name="color" type="color" value="{{ $editColor }}" required></div>
           <div class="edit-actions"><button class="btn primary" type="submit"><i class="bi bi-check2" aria-hidden="true"></i>{{ __('crm.support_task_save_changes') }}</button></div>
          </form>
         </details>
        @endif
       </article>
      @empty
       <div class="empty-state"><i class="bi bi-clipboard-check" aria-hidden="true"></i><strong>{{ __('crm.support_task_no_tasks') }}</strong><p>{{ __('crm.support_task_no_tasks_description') }}</p></div>
      @endforelse
      @if($tasks->hasPages())<div class="pagination-wrap">{{ $tasks->links() }}</div>@endif
     </section>

     @if($canManage)
      <aside class="panel create-panel" id="newTask">
       <header class="panel-head"><div class="panel-title"><i class="bi bi-plus-square" aria-hidden="true"></i><div><h2>{{ __('crm.support_task_new') }}</h2><p>{{ __('crm.support_task_new_description') }}</p></div></div></header>
       <form class="create-form" method="POST" action="{{ route('v2.technical-support.tasks.store') }}">
        @csrf
        <input type="hidden" name="form_context" value="create">
        <div class="field"><label for="newTaskTitle">{{ __('crm.support_task_title') }}</label><input id="newTaskTitle" name="title" required maxlength="255" value="{{ $createHasErrors ? old('title') : '' }}" placeholder="{{ __('crm.support_task_title_placeholder') }}"></div>
        <div class="field"><label for="newTaskDescription">{{ __('crm.support_task_description') }}</label><textarea id="newTaskDescription" name="description" maxlength="3000" placeholder="{{ __('crm.support_task_description_placeholder') }}">{{ $createHasErrors ? old('description') : '' }}</textarea></div>
        <div class="field"><label for="newTaskAssignee">{{ __('crm.support_task_assignee') }}</label><select id="newTaskAssignee" name="assigned_to_user_id" required><option value="">{{ __('crm.support_task_choose_assignee') }}</option>@foreach($users as $employee)<option value="{{ $employee->id }}" @selected($createHasErrors && (int) old('assigned_to_user_id') === $employee->id)>{{ $employee->name }}</option>@endforeach</select></div>
        <div class="create-grid">
         <div class="field"><label for="newTaskDue">{{ __('crm.support_task_due_date') }}</label><input id="newTaskDue" name="due_date" type="date" value="{{ $createHasErrors ? old('due_date') : '' }}"></div>
         <div class="field"><label for="newTaskPriority">{{ __('crm.support_task_priority') }}</label><select id="newTaskPriority" name="priority" required>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(($createHasErrors ? old('priority') : 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
         <div class="field wide"><label for="newTaskColor">{{ __('crm.support_task_color') }}</label><div class="color-control"><input id="newTaskColor" name="color" type="color" value="{{ $createColor }}" required><output class="color-value" for="newTaskColor" id="newTaskColorValue">{{ $createColor }}</output></div></div>
        </div>
        <button class="btn primary" type="submit"><i class="bi bi-send-check" aria-hidden="true"></i>{{ __('crm.support_task_create') }}</button>
       </form>
      </aside>
     @endif
    </div>
   </div>
  </main>
 </div>
 <script>
 (() => {
  const input = document.getElementById('newTaskColor');
  const output = document.getElementById('newTaskColorValue');
  if (input && output) input.addEventListener('input', () => { output.value = input.value; });
  document.querySelectorAll('.confirm-delete').forEach(form => {
   form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm || '')) event.preventDefault();
   });
  });
 })();
 </script>
</body>
</html>
