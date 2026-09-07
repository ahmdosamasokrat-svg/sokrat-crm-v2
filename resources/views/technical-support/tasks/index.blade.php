@php
    $canManage = $canManage ?? (auth()->user()?->can('technical_support.manage') ?? false);
    $due = $due ?? request('due', 'all');
@endphp
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
 $editContext = old('form_context', '');
 $editingTaskId = str_starts_with($editContext, 'edit:') ? (int) substr($editContext, 5) : null;
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
  :root{
   --task-red:#dc2637;
   --task-red-dark:#b81829;
   --task-bg:#f6f8fb;
   --task-panel:#ffffff;
   --task-soft:#f8fafc;
   --task-ink:#172033;
   --task-muted:#64748b;
   --task-line:#e2e8f0;
   --task-shadow:0 6px 20px rgba(15,23,42,.04);
   --task-shadow-hover:0 12px 30px rgba(15,23,42,.08);
   --font-primary:'Tajawal',Tahoma,Arial,sans-serif;
  }
  html.dark-mode{
   --task-bg:#0b0f19;
   --task-panel:#111827;
   --task-soft:#161f30;
   --task-ink:#f3f5f8;
   --task-muted:#94a3b8;
   --task-line:#1f293d;
   --task-shadow:0 8px 24px rgba(0,0,0,.28);
   --task-shadow-hover:0 16px 36px rgba(0,0,0,.4);
  }
  body{margin:0;min-width:320px;background:var(--task-bg);color:var(--task-ink);font-family:var(--font-primary);overflow-x:hidden}
  button,input,select,textarea{font:inherit}a{color:inherit;text-decoration:none}
  *{scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
  :focus-visible{outline:2px solid rgba(220,38,55,.35);outline-offset:2px}

  .task-shell{display:flex;min-height:100vh;max-width:100vw;overflow-x:hidden}
  .task-main{min-width:0;flex:1;max-width:100%;padding:24px 30px 48px}
  .task-stack{display:flex;flex-direction:column;gap:18px}

  /* Alerts */
  .task-alert{display:flex;align-items:center;gap:10px;padding:12px 16px;border:1px solid var(--task-line);border-radius:12px;background:var(--task-panel);color:var(--task-muted);font-size:13px;font-weight:700}
  .task-alert i{font-size:16px;flex-shrink:0}
  .flash{color:#059669;border-color:#a7f3d0;background:#ecfdf5}
  html.dark-mode .flash{color:#34d399;border-color:rgba(16,185,129,.3);background:rgba(16,185,129,.12)}
  .error{color:#dc2637;border-color:#fecaca;background:#fef2f2}
  html.dark-mode .error{color:#f87171;border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.12)}

  /* 1. BALANCED KPI METRIC STRIP */
  .metric-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
  .metric-item{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border:1px solid var(--task-line);border-radius:14px;background:var(--task-panel);box-shadow:var(--task-shadow);text-decoration:none;transition:all .18s ease;position:relative;overflow:hidden}
  .metric-item:hover{transform:translateY(-2px);box-shadow:var(--task-shadow-hover);border-color:#cbd5e1}
  .metric-copy{display:flex;flex-direction:column;gap:3px;min-width:0}
  .metric-copy span{font-size:12px;font-weight:800;color:var(--task-muted);white-space:nowrap}
  .metric-copy strong{font-size:24px;font-weight:900;color:var(--task-ink);font-variant-numeric:tabular-nums;line-height:1}
  .metric-icon{width:42px;height:42px;display:grid;place-items:center;flex:0 0 42px;border-radius:12px;font-size:19px}
  .metric-item.active-kpi .metric-icon{background:rgba(37,99,235,.1);color:#2563eb}
  .metric-item.today-kpi .metric-icon{background:rgba(245,158,11,.1);color:#d97706}
  .metric-item.overdue-kpi .metric-icon{background:rgba(239,68,68,.1);color:#dc2637}
  .metric-item.completed-kpi .metric-icon{background:rgba(16,185,129,.1);color:#059669}

  /* 2. COMPACT HORIZONTAL TOOLBAR & FILTERS */
  .task-toolbar-card{background:var(--task-panel);border:1px solid var(--task-line);border-radius:14px;padding:12px 16px;box-shadow:var(--task-shadow);display:flex;flex-direction:column;gap:12px}
  .task-toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .task-toolbar-title{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--task-ink)}
  .task-toolbar-title i{color:var(--task-red);font-size:16px}
  .view-mode-toggle{display:inline-flex;align-items:center;background:var(--task-soft);border:1px solid var(--task-line);border-radius:8px;padding:2px;gap:2px}
  .btn-view-toggle{border:0;background:transparent;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:800;color:var(--task-muted);cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .15s ease}
  .btn-view-toggle.active{background:var(--task-panel);color:var(--task-red);box-shadow:0 1px 3px rgba(0,0,0,.08)}

  .filters-horizontal{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
  .filters-horizontal .field{display:flex;align-items:center;gap:6px;flex:1 1 130px;min-width:120px}
  .filters-horizontal .field.search-field{flex:2 1 180px;min-width:160px}
  .filters-horizontal .field label{font-size:11px;font-weight:800;color:var(--task-muted);white-space:nowrap;display:inline-flex;align-items:center;gap:3px;flex-shrink:0}
  .filters-horizontal input,
  .filters-horizontal select{width:100%;height:36px;min-height:36px;padding:0 9px;border:1px solid var(--task-line);border-radius:8px;background:var(--task-soft);color:var(--task-ink);font-size:12px;font-weight:600;outline:0;transition:border-color .15s ease,box-shadow .15s ease}
  .filters-horizontal input:focus,
  .filters-horizontal select:focus{border-color:var(--task-red);box-shadow:0 0 0 2px rgba(220,38,55,.12);background:var(--task-panel)}
  .filter-actions-inline{display:inline-flex;align-items:center;gap:6px;margin-inline-start:auto;flex-shrink:0}

  /* Buttons */
  .btn{min-height:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 13px;border:1px solid var(--task-line);border-radius:8px;background:var(--task-panel);color:var(--task-ink);font-size:12px;font-weight:800;cursor:pointer;transition:all .15s ease;white-space:nowrap}
  .btn:hover{border-color:var(--task-red);color:var(--task-red)}
  .btn.primary{border-color:var(--task-red);background:var(--task-red);color:#fff}
  .btn.primary:hover{background:var(--task-red-dark);color:#fff}
  .btn.soft{background:var(--task-soft);border-color:var(--task-line);color:var(--task-ink)}
  .btn.danger{color:var(--task-red);border-color:transparent;background:transparent}
  .btn.danger:hover{background:rgba(220,38,55,.08);color:var(--task-red)}
  .btn.compact{min-height:30px;height:30px;padding:0 9px;font-size:11px}

  /* 3. KANBAN BOARD VIEW */
  .kanban-board-layout{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:start}
  .kanban-col{display:flex;flex-direction:column;gap:12px;background:var(--task-soft);border:1px solid var(--task-line);border-radius:14px;padding:12px}
  .kanban-col-head{display:flex;align-items:center;justify-content:space-between;padding:4px 6px 8px;border-bottom:2px solid transparent}
  .kanban-col.pending .kanban-col-head{border-bottom-color:#64748b}
  .kanban-col.in_progress .kanban-col-head{border-bottom-color:#2563eb}
  .kanban-col.completed .kanban-col-head{border-bottom-color:#10b981}
  .col-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:900;color:var(--task-ink)}
  .col-pill{width:8px;height:8px;border-radius:50%}
  .col-count{font-size:11px;font-weight:800;padding:2px 7px;border-radius:99px;background:var(--task-panel);color:var(--task-muted);border:1px solid var(--task-line)}
  .kanban-col-cards{display:flex;flex-direction:column;gap:12px;min-height:100px}

  /* 4. GRID CARDS VIEW */
  .grid-cards-layout{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}

  /* 5. MODERN TASK CARD DESIGN */
  .t-card{background:var(--task-panel);border:1px solid var(--task-line);border-radius:12px;box-shadow:0 2px 6px rgba(15,23,42,.03);display:flex;flex-direction:column;justify-content:space-between;gap:10px;padding:14px 15px;position:relative;overflow:hidden;transition:all .18s ease;border-inline-start:4px solid var(--task-color,#dc2637)}
  .t-card:hover{transform:translateY(-2px);box-shadow:var(--task-shadow-hover);border-color:#cbd5e1;border-inline-start-color:var(--task-color,#dc2637)}
  .t-card.is-overdue{border-inline-start-color:#dc2637;background:linear-gradient(to bottom,var(--task-panel),rgba(220,38,55,.02))}
  .t-card.is-completed{border-inline-start-color:#10b981;opacity:.92}
  .t-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
  .t-card-identity{display:flex;align-items:flex-start;gap:8px;min-width:0}
  .t-card-color-dot{width:9px;height:9px;border-radius:50%;background:var(--task-color,#dc2637);flex-shrink:0;margin-top:4px}
  .t-card h3{margin:0;font-size:13px;font-weight:800;color:var(--task-ink);line-height:1.35;word-break:break-word}
  .t-card-badges{display:flex;align-items:center;gap:5px;flex-shrink:0;flex-wrap:wrap}
  .t-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:5px;font-size:10px;font-weight:800;white-space:nowrap}
  .t-badge.status-pending{background:#f1f5f9;color:#475569}
  .t-badge.status-in_progress{background:#eff6ff;color:#1d4ed8}
  .t-badge.status-completed{background:#ecfdf5;color:#047857}
  .t-badge.priority-high{background:#fef2f2;color:#dc2637}
  .t-badge.priority-normal{background:#f0f9ff;color:#0284c7}
  .t-badge.priority-low{background:#f8fafc;color:#64748b;border:1px solid #e2e8f0}
  .t-badge.overdue-pill{background:#fef2f2;color:#dc2637;border:1px solid #fca5a5}
  html.dark-mode .t-badge.status-pending{background:#1e293b;color:#cbd5e1}
  html.dark-mode .t-badge.status-in_progress{background:rgba(37,99,235,.2);color:#93c5fd}
  html.dark-mode .t-badge.status-completed{background:rgba(16,185,129,.2);color:#6ee7b7}
  html.dark-mode .t-badge.priority-high{background:rgba(220,38,55,.2);color:#fca5a5}

  .t-card-desc{margin:0;font-size:12px;color:var(--task-muted);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word}
  .t-card-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:11px;color:var(--task-muted);padding-top:8px;border-top:1px solid var(--task-line);flex-wrap:wrap}
  .t-card-user{display:inline-flex;align-items:center;gap:5px;font-weight:700;color:var(--task-ink)}
  .t-card-user-avatar{width:20px;height:20px;border-radius:50%;background:rgba(220,38,55,.1);color:var(--task-red);font-size:10px;font-weight:900;display:grid;place-items:center}
  .t-card-due{display:inline-flex;align-items:center;gap:4px;font-weight:700}
  .t-card-due.is-overdue-text{color:#dc2637}

  /* Task Card Actions Row */
  .t-card-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:8px;border-top:1px dashed var(--task-line)}
  .status-form{display:inline-flex;align-items:center;gap:5px}
  .status-form select{height:28px;min-height:28px;padding:0 6px;border:1px solid var(--task-line);border-radius:6px;background:var(--task-soft);color:var(--task-ink);font-size:11px;font-weight:800;outline:0;cursor:pointer}
  .t-card-buttons{display:inline-flex;align-items:center;gap:4px}
  .btn-icon-action{width:28px;height:28px;padding:0;display:grid;place-items:center;border-radius:6px;background:transparent;border:1px solid transparent;color:var(--task-muted);cursor:pointer;font-size:13px;transition:all .15s ease}
  .btn-icon-action:hover{color:var(--task-red);background:rgba(220,38,55,.06);border-color:rgba(220,38,55,.2)}

  /* Empty state */
  .empty-box{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:32px 20px;text-align:center;color:var(--task-muted)}
  .empty-box i{font-size:32px;color:#cbd5e1}
  .empty-box strong{font-size:14px;font-weight:800;color:var(--task-ink)}
  .empty-box p{margin:0;font-size:12px;line-height:1.5;max-width:32ch}

  /* 6. MODAL / SLIDE DRAWER FOR TASK CREATE & EDIT */
  .task-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);z-index:999;opacity:0;visibility:hidden;transition:all .2s ease}
  .task-modal-backdrop.open{opacity:1;visibility:visible}
  .task-modal-card{position:fixed;top:0;inset-inline-end:-440px;width:min(440px,94vw);height:100vh;background:var(--task-panel);box-shadow:-8px 0 30px rgba(0,0,0,.2);z-index:1000;display:flex;flex-direction:column;transition:inset-inline-end .25s cubic-bezier(0.4,0,0.2,1);box-sizing:border-box}
  .task-modal-card.open{inset-inline-end:0}
  .task-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--task-line);background:var(--task-soft)}
  .task-modal-head h3{margin:0;font-size:16px;font-weight:900;color:var(--task-ink);display:flex;align-items:center;gap:8px}
  .task-modal-head h3 i{color:var(--task-red)}
  .btn-modal-close{width:32px;height:32px;border-radius:8px;border:1px solid var(--task-line);background:var(--task-panel);color:var(--task-muted);cursor:pointer;display:grid;place-items:center;font-size:14px}
  .btn-modal-close:hover{color:var(--task-red);border-color:var(--task-red)}
  .task-modal-body{flex:1;overflow-y:auto;padding:22px;display:flex;flex-direction:column;gap:14px}
  .task-modal-foot{padding:16px 22px;border-top:1px solid var(--task-line);background:var(--task-soft);display:flex;align-items:center;gap:10px}
  .task-modal-foot .btn{flex:1;height:42px}

  /* Preset color chips */
  .color-presets-row{display:flex;align-items:center;gap:8px;margin-top:6px}
  .color-chip-btn{width:26px;height:26px;border-radius:50%;border:2px solid transparent;cursor:pointer;padding:0;transition:transform .15s ease}
  .color-chip-btn:hover{transform:scale(1.15)}
  .color-chip-btn.active{border-color:var(--task-ink);box-shadow:0 0 0 2px #fff}

  /* Responsive */
  @media(max-width:960px){
   .kanban-board-layout{grid-template-columns:1fr}
   .metric-strip{grid-template-columns:repeat(2,minmax(0,1fr))}
  }
  @media(max-width:640px){
   .task-main{padding:16px 14px 36px}
   .filters-horizontal{flex-direction:column;align-items:stretch}
   .filters-horizontal .field{width:100%}
   .filter-actions-inline{width:100%;justify-content:stretch}
   .filter-actions-inline .btn{flex:1}
   .metric-strip{grid-template-columns:1fr}
  }
 </style>
</head>
<body>
 @include('partials.page-loader')
 <div class="task-shell">
  @include('partials.crm-sidebar')
  <main class="task-main">
   @php
    ob_start();
   @endphp
    @if($canManage)
     <button class="btn primary" id="btnOpenNewTaskModal" type="button">
      <i class="bi bi-plus-lg" aria-hidden="true"></i>
      <span>{{ __('crm.support_task_new') }}</span>
     </button>
     <a href="#newTask" id="newTaskAnchor" style="display:none">{{ __('crm.support_task_new') }}</a>
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
     <div class="task-alert flash" role="status">
      <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
      <span>{{ session('success') }}</span>
     </div>
    @endif
    @if($errors->any())
     <div class="task-alert error" role="alert">
      <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
      <div>
       <strong>{{ __('crm.support_task_validation_error') }}</strong>
       <ul style="margin:4px 0 0;padding-inline-start:18px">
        @foreach($errors->all() as $error)
         <li>{{ $error }}</li>
        @endforeach
       </ul>
      </div>
     </div>
    @endif

    <!-- 1. BALANCED KPI METRIC STRIP -->
    <section class="metric-strip" aria-label="{{ __('crm.support_tasks_title') }}">
     <a class="metric-item active-kpi" href="{{ route('v2.technical-support.tasks.index', ['status' => 'active']) }}">
      <div class="metric-copy">
       <span>{{ __('crm.support_tasks_active') }}</span>
       <strong>{{ number_format($metrics['active']) }}</strong>
      </div>
      <div class="metric-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
     </a>

     <a class="metric-item today-kpi" href="{{ route('v2.technical-support.tasks.index', ['status' => 'active', 'due' => 'today']) }}">
      <div class="metric-copy">
       <span>{{ __('crm.support_tasks_due_today') }}</span>
       <strong>{{ number_format($metrics['due_today']) }}</strong>
      </div>
      <div class="metric-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></div>
     </a>

     <a class="metric-item overdue-kpi" href="{{ route('v2.technical-support.tasks.index', ['status' => 'active', 'due' => 'overdue']) }}">
      <div class="metric-copy">
       <span>{{ __('crm.support_tasks_overdue') }}</span>
       <strong>{{ number_format($metrics['overdue']) }}</strong>
      </div>
      <div class="metric-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></div>
     </a>

     <a class="metric-item completed-kpi" href="{{ route('v2.technical-support.tasks.index', ['status' => 'completed']) }}">
      <div class="metric-copy">
       <span>{{ __('crm.support_tasks_completed') }}</span>
       <strong>{{ number_format($metrics['completed']) }}</strong>
      </div>
      <div class="metric-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></div>
     </a>
    </section>

    @unless($canManage)
     <div class="task-alert">
      <i class="bi bi-person-check" aria-hidden="true"></i>
      <span>{{ __('crm.support_task_manager_note') }}</span>
     </div>
    @endunless

    <!-- 2. TOOLBAR & COMPACT HORIZONTAL FILTERS -->
    <section class="task-toolbar-card">
     <div class="task-toolbar-top">
      <div class="task-toolbar-title">
       <i class="bi bi-kanban-fill"></i>
       <span>{{ __('إدارة ومتابعة المهام') }}</span>
      </div>

      <div class="view-mode-toggle">
       <button type="button" class="btn-view-toggle active" id="btnBoardView" title="{{ __('عرض لوحة المهام (Kanban)') }}">
        <i class="bi bi-kanban"></i>
        <span>{{ __('لوحة المهام') }}</span>
       </button>
       <button type="button" class="btn-view-toggle" id="btnGridView" title="{{ __('عرض شبكة الكروت (Grid)') }}">
        <i class="bi bi-grid-fill"></i>
        <span>{{ __('شبكة الكروت') }}</span>
       </button>
      </div>
     </div>

     <form class="filters-horizontal" method="GET" action="{{ route('v2.technical-support.tasks.index') }}" id="taskFiltersForm">
      @if($due !== 'all')
       <input type="hidden" name="due" value="{{ $due }}">
      @endif

      <!-- Search Query -->
      <div class="field search-field">
       <label for="taskSearch"><i class="bi bi-search"></i> {{ __('crm.search') }}:</label>
       <input id="taskSearch" name="search" type="search" value="{{ $search }}" placeholder="{{ __('crm.support_task_search_placeholder') }}">
      </div>

      <!-- Status Filter -->
      <div class="field">
       <label for="taskStatus"><i class="bi bi-tag"></i> {{ __('crm.status') }}:</label>
       <select id="taskStatus" name="status">
        <option value="active" @selected($status === 'active')>{{ __('crm.support_task_status_active') }}</option>
        @foreach($statusLabels as $value => $label)
         <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
        @endforeach
        <option value="all" @selected($status === 'all')>{{ __('crm.support_task_all_statuses') }}</option>
       </select>
      </div>

      <!-- Priority Filter -->
      <div class="field">
       <label for="taskPriority"><i class="bi bi-flag"></i> {{ __('crm.support_task_priority') }}:</label>
       <select id="taskPriority" name="priority">
        <option value="all">{{ __('crm.support_task_all_priorities') }}</option>
        @foreach($priorityLabels as $value => $label)
         <option value="{{ $value }}" @selected($priority === $value)>{{ $label }}</option>
        @endforeach
       </select>
      </div>

      <!-- Assignee Filter (for managers) -->
      @if($canManage)
       <div class="field">
        <label for="taskAssignee"><i class="bi bi-person"></i> {{ __('crm.support_task_assignee') }}:</label>
        <select id="taskAssignee" name="assignee_id">
         <option value="">{{ __('crm.support_task_all_assignees') }}</option>
         @foreach($users as $employee)
          <option value="{{ $employee->id }}" @selected($assigneeId === $employee->id)>{{ $employee->name }}</option>
         @endforeach
        </select>
       </div>
      @endif

      <div class="filter-actions-inline">
       <button class="btn primary" type="submit">
        <i class="bi bi-funnel-fill" aria-hidden="true"></i>
        <span>{{ __('crm.support_task_filter') }}</span>
       </button>
       <a class="btn soft" href="{{ route('v2.technical-support.tasks.index') }}" title="{{ __('crm.support_task_clear_filters') }}">
        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
       </a>
      </div>
     </form>
    </section>

    <!-- 3. MAIN TASKS CONTAINER -->
    @php
     $pendingTasks = $tasks->filter(static fn ($t) => $t->status === 'pending');
     $inProgressTasks = $tasks->filter(static fn ($t) => $t->status === 'in_progress');
     $completedTasks = $tasks->filter(static fn ($t) => $t->status === 'completed');
    @endphp

    <!-- VIEW A: KANBAN BOARD (Columns per Status) -->
    <div class="kanban-board-layout" id="boardViewContainer">
     <!-- Column 1: Pending (قيد الانتظار) -->
     <div class="kanban-col pending">
      <div class="kanban-col-head">
       <div class="col-title">
        <span class="col-pill" style="background:#64748b;"></span>
        <span>{{ __('crm.support_task_status_pending') }}</span>
       </div>
       <span class="col-count">{{ number_format($pendingTasks->count()) }}</span>
      </div>

      <div class="kanban-col-cards">
       @forelse($pendingTasks as $task)
        @include('technical-support.tasks._card', ['task' => $task])
       @empty
        <div class="empty-box">
         <i class="bi bi-inbox"></i>
         <p>{{ __('لا توجد مهام قيد الانتظار حالياً.') }}</p>
        </div>
       @endforelse
      </div>
     </div>

     <!-- Column 2: In Progress (تحت التنفيذ) -->
     <div class="kanban-col in_progress">
      <div class="kanban-col-head">
       <div class="col-title">
        <span class="col-pill" style="background:#2563eb;"></span>
        <span>{{ __('crm.support_task_status_in_progress') }}</span>
       </div>
       <span class="col-count">{{ number_format($inProgressTasks->count()) }}</span>
      </div>

      <div class="kanban-col-cards">
       @forelse($inProgressTasks as $task)
        @include('technical-support.tasks._card', ['task' => $task])
       @empty
        <div class="empty-box">
         <i class="bi bi-play-circle"></i>
         <p>{{ __('لا توجد مهام تحت التنفيذ حالياً.') }}</p>
        </div>
       @endforelse
      </div>
     </div>

     <!-- Column 3: Completed (مكتملة) -->
     <div class="kanban-col completed">
      <div class="kanban-col-head">
       <div class="col-title">
        <span class="col-pill" style="background:#10b981;"></span>
        <span>{{ __('crm.support_task_status_completed') }}</span>
       </div>
       <span class="col-count">{{ number_format($completedTasks->count()) }}</span>
      </div>

      <div class="kanban-col-cards">
       @forelse($completedTasks as $task)
        @include('technical-support.tasks._card', ['task' => $task])
       @empty
        <div class="empty-box">
         <i class="bi bi-check2-all"></i>
         <p>{{ __('لا توجد مهام مكتملة في هذا العرض.') }}</p>
        </div>
       @endforelse
      </div>
     </div>
    </div>

    <!-- VIEW B: GRID CARDS VIEW (Full Responsive Grid) -->
    <div class="grid-cards-layout" id="gridViewContainer" style="display:none;">
     @forelse($tasks as $task)
      @include('technical-support.tasks._card', ['task' => $task])
     @empty
      <div class="empty-box" style="grid-column:1/-1;padding:48px 20px;">
       <i class="bi bi-clipboard-check" aria-hidden="true" style="font-size:42px;"></i>
       <strong>{{ __('crm.support_task_no_tasks') }}</strong>
       <p>{{ __('crm.support_task_no_tasks_description') }}</p>
      </div>
     @endforelse
    </div>

    @if($tasks->hasPages())
     <div style="background:var(--task-panel);border:1px solid var(--task-line);border-radius:12px;padding:12px 18px;margin-top:14px">
      {{ $tasks->links() }}
     </div>
    @endif
   </div>
  </main>
 </div>

 <!-- 6. MODERN SLIDE-OVER DRAWER / MODAL FOR CREATING TASK -->
 @if($canManage)
  <div class="task-modal-backdrop {{ $createHasErrors ? 'open' : '' }}" id="taskModalBackdrop"></div>
  <aside class="task-modal-card {{ $createHasErrors ? 'open' : '' }}" id="newTask">
   <header class="task-modal-head">
    <h3><i class="bi bi-plus-square-fill"></i> {{ __('crm.support_task_new') }}</h3>
    <button class="btn-modal-close" type="button" id="btnCloseNewTaskModal" aria-label="Close">
     <i class="bi bi-x-lg"></i>
    </button>
   </header>

   <form method="POST" action="{{ route('v2.technical-support.tasks.store') }}" id="createTaskForm" style="display:flex;flex-direction:column;flex:1;overflow:hidden">
    @csrf
    <input type="hidden" name="form_context" value="create">

    <div class="task-modal-body">
     <div class="field">
      <label for="newTaskTitle"><i class="bi bi-card-heading"></i> {{ __('crm.support_task_title') }} <span style="color:var(--task-red)">*</span></label>
      <input id="newTaskTitle" name="title" required maxlength="255" value="{{ $createHasErrors ? old('title') : '' }}" placeholder="{{ __('crm.support_task_title_placeholder') }}">
     </div>

     <div class="field">
      <label for="newTaskDescription"><i class="bi bi-text-paragraph"></i> {{ __('crm.support_task_description') }}</label>
      <textarea id="newTaskDescription" name="description" maxlength="3000" rows="4" placeholder="{{ __('crm.support_task_description_placeholder') }}">{{ $createHasErrors ? old('description') : '' }}</textarea>
     </div>

     <div class="field">
      <label for="newTaskAssignee"><i class="bi bi-person-check"></i> {{ __('crm.support_task_assignee') }} <span style="color:var(--task-red)">*</span></label>
      <select id="newTaskAssignee" name="assigned_to_user_id" required>
       <option value="">{{ __('crm.support_task_choose_assignee') }}</option>
       @foreach($users as $employee)
        <option value="{{ $employee->id }}" @selected($createHasErrors && (int) old('assigned_to_user_id') === $employee->id)>{{ $employee->name }}</option>
       @endforeach
      </select>
     </div>

     <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="field">
       <label for="newTaskDue"><i class="bi bi-calendar3"></i> {{ __('crm.support_task_due_date') }}</label>
       <input id="newTaskDue" name="due_date" type="date" value="{{ $createHasErrors ? old('due_date') : '' }}">
      </div>

      <div class="field">
       <label for="newTaskPriority"><i class="bi bi-flag"></i> {{ __('crm.support_task_priority') }}</label>
       <select id="newTaskPriority" name="priority" required>
        @foreach($priorityLabels as $value => $label)
         <option value="{{ $value }}" @selected(($createHasErrors ? old('priority') : 'normal') === $value)>{{ $label }}</option>
        @endforeach
       </select>
      </div>
     </div>

     <div class="field">
      <label for="newTaskColor"><i class="bi bi-palette"></i> {{ __('crm.support_task_color') }}</label>
      <div style="display:flex;align-items:center;gap:10px">
       <input id="newTaskColor" name="color" type="color" value="{{ $createColor }}" required style="width:48px;height:38px;padding:3px;border-radius:8px;cursor:pointer;border:1px solid var(--task-line)">
       <output class="color-value" for="newTaskColor" id="newTaskColorValue" style="font-family:monospace;font-weight:800;font-size:12px;direction:ltr">{{ $createColor }}</output>
      </div>
      <div class="color-presets-row">
       @foreach(['#dc2637', '#2563eb', '#10b981', '#8b5cf6', '#f59e0b', '#06b6d4'] as $presetHex)
        <button type="button" class="color-chip-btn" style="background:{{ $presetHex }};" onclick="document.getElementById('newTaskColor').value='{{ $presetHex }}'; document.getElementById('newTaskColorValue').value='{{ $presetHex }}';"></button>
       @endforeach
      </div>
     </div>
    </div>

    <footer class="task-modal-foot">
     <button class="btn primary" type="submit">
      <i class="bi bi-check-lg" aria-hidden="true"></i>
      <span>{{ __('crm.support_task_create') }}</span>
     </button>
     <button class="btn soft" type="button" onclick="document.getElementById('newTask').classList.remove('open'); document.getElementById('taskModalBackdrop').classList.remove('open');">
      <span>{{ __('crm.cancel') ?? 'إلغاء' }}</span>
     </button>
    </footer>
   </form>
  </aside>
 @endif

 <!-- 7. EDIT TASK MODAL / DRAWER -->
 @if($canManage)
  <aside class="task-modal-card {{ $editingTaskId ? 'open' : '' }}" id="editTaskModal">
   <header class="task-modal-head">
    <h3><i class="bi bi-pencil-square"></i> {{ __('crm.support_task_edit') }}</h3>
    <button class="btn-modal-close" type="button" id="btnCloseEditTaskModal" aria-label="Close">
     <i class="bi bi-x-lg"></i>
    </button>
   </header>

   <form method="POST" action="" id="editTaskForm" style="display:flex;flex-direction:column;flex:1;overflow:hidden">
    @csrf
    @method('PATCH')
    <input type="hidden" name="form_context" id="editFormContext" value="{{ $editContext }}">
    @foreach(request()->only(['status','priority','due','assignee_id','search','page']) as $queryKey => $queryValue)
     <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
    @endforeach

    <div class="task-modal-body">
     <div class="field">
      <label for="editTaskTitle">{{ __('crm.support_task_title') }} <span style="color:var(--task-red)">*</span></label>
      <input id="editTaskTitle" name="title" required maxlength="255" value="">
     </div>

     <div class="field">
      <label for="editTaskDescription">{{ __('crm.support_task_description') }}</label>
      <textarea id="editTaskDescription" name="description" maxlength="3000" rows="4"></textarea>
     </div>

     <div class="field">
      <label for="editTaskAssignee">{{ __('crm.support_task_assignee') }} <span style="color:var(--task-red)">*</span></label>
      <select id="editTaskAssignee" name="assigned_to_user_id" required>
       @foreach($users as $employee)
        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
       @endforeach
      </select>
     </div>

     <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="field">
       <label for="editTaskDue">{{ __('crm.support_task_due_date') }}</label>
       <input id="editTaskDue" name="due_date" type="date" value="">
      </div>

      <div class="field">
       <label for="editTaskPriority">{{ __('crm.support_task_priority') }}</label>
       <select id="editTaskPriority" name="priority" required>
        @foreach($priorityLabels as $value => $label)
         <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
       </select>
      </div>
     </div>

     <div class="field">
      <label for="editTaskColor">{{ __('crm.support_task_color') }}</label>
      <div style="display:flex;align-items:center;gap:10px">
       <input id="editTaskColor" name="color" type="color" value="#dc2637" required style="width:48px;height:38px;padding:3px;border-radius:8px;cursor:pointer;border:1px solid var(--task-line)">
       <output class="color-value" for="editTaskColor" id="editTaskColorValue" style="font-family:monospace;font-weight:800;font-size:12px;direction:ltr">#dc2637</output>
      </div>
     </div>
    </div>

    <footer class="task-modal-foot">
     <button class="btn primary" type="submit">
      <i class="bi bi-check2" aria-hidden="true"></i>
      <span>{{ __('crm.support_task_save_changes') }}</span>
     </button>
     <button class="btn soft" type="button" onclick="document.getElementById('editTaskModal').classList.remove('open'); document.getElementById('taskModalBackdrop').classList.remove('open');">
      <span>{{ __('crm.cancel') ?? 'إلغاء' }}</span>
     </button>
    </footer>
   </form>
  </aside>
 @endif

 <script>
 (() => {
  // 1. Color Picker Synchronization
  const colorInput = document.getElementById('newTaskColor');
  const colorOutput = document.getElementById('newTaskColorValue');
  if (colorInput && colorOutput) {
   colorInput.addEventListener('input', () => { colorOutput.value = colorInput.value; });
  }

  const editColorInput = document.getElementById('editTaskColor');
  const editColorOutput = document.getElementById('editTaskColorValue');
  if (editColorInput && editColorOutput) {
   editColorInput.addEventListener('input', () => { editColorOutput.value = editColorInput.value; });
  }

  // 2. Delete Confirmation Handling
  document.querySelectorAll('.confirm-delete').forEach(form => {
   form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm || '')) event.preventDefault();
   });
  });

  // 3. New Task Modal Open / Close
  const btnOpenNew = document.getElementById('btnOpenNewTaskModal');
  const btnCloseNew = document.getElementById('btnCloseNewTaskModal');
  const modalBackdrop = document.getElementById('taskModalBackdrop');
  const newTaskModal = document.getElementById('newTask');

  const openNewModal = () => {
   if (newTaskModal && modalBackdrop) {
    newTaskModal.classList.add('open');
    modalBackdrop.classList.add('open');
   }
  };

  const closeModals = () => {
   if (newTaskModal) newTaskModal.classList.remove('open');
   const editModal = document.getElementById('editTaskModal');
   if (editModal) editModal.classList.remove('open');
   if (modalBackdrop) modalBackdrop.classList.remove('open');
  };

  btnOpenNew?.addEventListener('click', openNewModal);
  btnCloseNew?.addEventListener('click', closeModals);
  modalBackdrop?.addEventListener('click', closeModals);

  // 4. Edit Task Modal Setup
  const editModal = document.getElementById('editTaskModal');
  const btnCloseEdit = document.getElementById('btnCloseEditTaskModal');
  btnCloseEdit?.addEventListener('click', closeModals);

  document.querySelectorAll('.btn-edit-task').forEach(btn => {
   btn.addEventListener('click', () => {
    if (!editModal || !modalBackdrop) return;
    const form = document.getElementById('editTaskForm');
    if (form) form.action = btn.dataset.updateUrl;

    const titleInput = document.getElementById('editTaskTitle');
    const descInput = document.getElementById('editTaskDescription');
    const assigneeSelect = document.getElementById('editTaskAssignee');
    const dueInput = document.getElementById('editTaskDue');
    const prioritySelect = document.getElementById('editTaskPriority');
    const colorInp = document.getElementById('editTaskColor');
    const colorOut = document.getElementById('editTaskColorValue');
    const contextInput = document.getElementById('editFormContext');

    if (titleInput) titleInput.value = btn.dataset.title || '';
    if (descInput) descInput.value = btn.dataset.description || '';
    if (assigneeSelect) assigneeSelect.value = btn.dataset.assigneeId || '';
    if (dueInput) dueInput.value = btn.dataset.dueDate || '';
    if (prioritySelect) prioritySelect.value = btn.dataset.priority || 'normal';
    if (colorInp) colorInp.value = btn.dataset.color || '#dc2637';
    if (colorOut) colorOut.value = btn.dataset.color || '#dc2637';
    if (contextInput) contextInput.value = 'edit:' + btn.dataset.taskId;

    editModal.classList.add('open');
    modalBackdrop.classList.add('open');
   });
  });

  // 5. Board View vs Grid Cards View Switcher
  const btnBoard = document.getElementById('btnBoardView');
  const btnGrid = document.getElementById('btnGridView');
  const boardContainer = document.getElementById('boardViewContainer');
  const gridContainer = document.getElementById('gridViewContainer');

  const setViewMode = (mode) => {
   if (mode === 'grid') {
    btnGrid?.classList.add('active');
    btnBoard?.classList.remove('active');
    if (boardContainer) boardContainer.style.display = 'none';
    if (gridContainer) gridContainer.style.display = 'grid';
    localStorage.setItem('crm_task_view_mode', 'grid');
   } else {
    btnBoard?.classList.add('active');
    btnGrid?.classList.remove('active');
    if (gridContainer) gridContainer.style.display = 'none';
    if (boardContainer) boardContainer.style.display = 'grid';
    localStorage.setItem('crm_task_view_mode', 'board');
   }
  };

  btnBoard?.addEventListener('click', () => setViewMode('board'));
  btnGrid?.addEventListener('click', () => setViewMode('grid'));

  const savedViewMode = localStorage.getItem('crm_task_view_mode');
  if (savedViewMode === 'grid') {
   setViewMode('grid');
  }
 })();
 </script>
</body>
</html>
