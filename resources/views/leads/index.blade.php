<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.view_leads') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v=1.0.0">
<style>
:root {
  --red: #dc2637;
  --red-hover: #b81829;
  --primary: #4f46e5;
  --primary-hover: #4338ca;
  --dark: #182033;
  --muted: #64748b;
  --line: #e2e8f0;
  --bg: #f8fafc;
  --card: #ffffff;
  --shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
  --radius: 16px;
  --font-primary: var(--font-primary, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
}

html.dark-mode {
  --dark: #f1f5f9;
  --muted: #94a3b8;
  --line: #334155;
  --bg: #0f172a;
  --card: #1e293b;
  --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

* { box-sizing: border-box; }
body {
  margin: 0;
  min-width: 320px;
  background: var(--bg);
  color: var(--dark);
  font-family: var(--font-primary);
  font-size: 14px;
  line-height: 1.5;
}
button, input, select, textarea { font: inherit; }
a { color: inherit; text-decoration: none; }

.crm-app { display: flex; min-height: 100vh; }
.crm-main { flex: 1; min-width: 0; padding: 24px 32px 60px; }

/* Topbar */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.topbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}
.topbar h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 900;
  color: var(--dark);
}
.topbar p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 13px;
}
.top-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 44px;
  min-height: 44px;
  padding: 0 18px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--card);
  color: var(--dark);
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
  font-size: 13px;
  white-space: nowrap;
}

.btn:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateY(-1px);
}
html.dark-mode .btn {
  background: rgba(255, 255, 255, 0.05);
  border-color: var(--line);
  color: var(--dark);
}
html.dark-mode .btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: #475569;
}
.btn.primary {
  background: var(--red);
  border-color: var(--red);
  color: #fff;
  box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25);
}
.btn.primary:hover {
  background: var(--red-hover);
  border-color: var(--red-hover);
  color: #fff;
}
.btn.soft {
  background: #f1f5f9;
  border-color: transparent;
  color: #334155;
}
html.dark-mode .btn.soft {
  background: rgba(255, 255, 255, 0.08);
  color: #f1f5f9;
}
.btn.soft:hover {
  background: #e2e8f0;
}
html.dark-mode .btn.soft:hover {
  background: rgba(255, 255, 255, 0.15);
}
.btn.small {
  height: 38px;
  min-height: 38px;
  padding: 0 12px;
  font-size: 12px;
  border-radius: 9px;
}

/* Stats Summary Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}
.stat-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  padding: 14px 18px;
  box-shadow: var(--shadow);
  text-decoration: none;
  color: inherit;
  display: block;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.stat-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
}
.stat-card.selected {
  border-color: var(--status-color, var(--red));
  background: color-mix(in srgb, var(--status-color, var(--red)) 6%, var(--card));
}
.stat-card span {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--muted);
  font-weight: 700;
}
.stat-card b {
  display: block;
  font-size: 20px;
  font-weight: 900;
  margin-top: 5px;
  color: var(--dark);
}
.stat-card small {
  display: inline-block;
  margin-top: 4px;
  font-size: 10px;
  font-weight: 700;
  color: var(--muted);
}

/* Hero Pipeline Banner */
.hero-pipeline {
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  padding: 12px 16px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  margin-bottom: 20px;
  box-shadow: var(--shadow);
}
.hero-stage {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 12px;
  border: 1px solid var(--line);
  background: var(--bg);
  text-decoration: none;
  color: var(--dark);
  font-weight: 800;
  font-size: 13px;
  white-space: nowrap;
  transition: all 0.15s ease;
}
.hero-stage:hover {
  border-color: var(--stage-color, var(--red));
  background: color-mix(in srgb, var(--stage-color, var(--red)) 10%, var(--card));
}
.hero-stage.is-selected {
  border-color: var(--stage-color, var(--red));
  background: var(--stage-color, var(--red));
  color: #fff;
}
.hero-stage .stage-icon {
  font-size: 14px;
}
.hero-pipe-arrow {
  color: var(--muted);
  font-size: 12px;
  opacity: 0.6;
}

/* Filters Panel */
.filter-panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 14px 16px;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
  margin-bottom: 20px;
}
.filter-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 10px 12px;
  align-items: end;
}
.filter-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.filter-field.col-search {
  grid-column: span 2;
  min-width: 220px;
}
.filter-actions-col {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 44px;
  min-height: 44px;
  justify-self: start;
  align-self: end;
  white-space: nowrap;
}
.filter-field label {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 800;
  color: var(--muted);
  white-space: nowrap;
}
.filter-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}
.filter-input-icon {
  position: absolute;
  inset-inline-start: 10px;
  color: var(--muted);
  pointer-events: none;
  font-size: 12px;
}
.filter-control {
  width: 100%;
  height: 44px;
  min-height: 44px;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 0 12px;
  background: var(--card);
  color: var(--dark);
  font-size: 13px;
  font-weight: 600;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.filter-control.with-icon {
  padding-inline-start: 28px;
}
.filter-control:focus {
  border-color: var(--red);
  box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.12);
}
html.dark-mode .filter-control {
  background: rgba(30, 41, 59, 0.7);
  border-color: var(--line);
  color: var(--dark);
}

/* Bulk Actions Bar */
.bulk-actions-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  margin-bottom: 16px;
  color: #991b1b;
  font-weight: 800;
  font-size: 13px;
}
html.dark-mode .bulk-actions-bar {
  background: rgba(153, 27, 27, 0.2);
  border-color: rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}
.bulk-actions-bar[hidden] { display: none !important; }
.bulk-actions-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}
.bulk-clear-button {
  background: transparent;
  border: 1px solid #fca5a5;
  color: #991b1b;
  padding: 5px 12px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
  font-size: 12px;
}
html.dark-mode .bulk-clear-button {
  color: #fca5a5;
  border-color: rgba(239, 68, 68, 0.4);
}
.bulk-export-button {
  background: var(--red);
  border: 0;
  color: #fff;
  padding: 6px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 800;
  font-size: 12px;
}

/* Table Card */
.table-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
}
.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  width: 100%;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}
th, td {
  text-align: start;
  padding: 12px 16px;
  border-bottom: 1px solid var(--line);
  vertical-align: middle;
}
th {
  background: #f8fafc;
  color: var(--muted);
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}
html.dark-mode th {
  background: rgba(255, 255, 255, 0.03);
  color: var(--muted);
}
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }
html.dark-mode tr:hover td { background: rgba(255, 255, 255, 0.02); }

/* Customer Cell */
.customer-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}
.customer-avatar {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  background: #fef2f2;
  color: var(--red);
  display: grid;
  place-items: center;
  font-weight: 900;
  font-size: 14px;
  flex-shrink: 0;
}
.customer-info strong {
  display: block;
  font-size: 13px;
  font-weight: 900;
  color: var(--dark);
}
.customer-info small {
  display: block;
  color: var(--muted);
  font-size: 11px;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  background: #f1f5f9;
  color: #475569;
  white-space: nowrap;
}
html.dark-mode .badge {
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
}
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  background: color-mix(in srgb, var(--status-color, #64748b) 12%, transparent);
  color: var(--status-color, #64748b);
  border: 1px solid color-mix(in srgb, var(--status-color, #64748b) 25%, transparent);
  white-space: nowrap;
}
.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--status-color, #64748b);
}
.stage-name {
  display: block;
  font-size: 11px;
  color: var(--muted);
  margin-top: 3px;
}

/* Actions Cell */
.actions-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}
.btn-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  min-width: 38px;
  min-height: 38px;
  border-radius: 9px;
  background: #f1f5f9;
  border: 1px solid var(--line);
  color: var(--dark);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-action:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}
html.dark-mode .btn-action {
  background: rgba(255, 255, 255, 0.06);
  border-color: var(--line);
  color: var(--dark);
}
.btn-action.call { color: #2563eb; }
.btn-action.call:hover { background: #eff6ff; border-color: #bfdbfe; }
.btn-action.whatsapp { color: #15803d; }
.btn-action.whatsapp:hover { background: #dcfce7; border-color: #bbf7d0; }

/* Pagination */
.pagination-wrap {
  padding: 16px 20px;
  border-top: 1px solid var(--line);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  background: var(--card);
}
.pagination-info {
  font-size: 13px;
  font-weight: 700;
  color: var(--muted);
}
.crm-pagination-nav {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0;
  padding: 0;
  list-style: none;
}
.crm-page-btn, .crm-page-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-width: 44px;
  height: 44px;
  min-height: 44px;
  padding: 0 12px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--card);
  color: var(--dark);
  font-weight: 700;
  font-size: 13px;
  text-decoration: none;
  transition: all 0.15s ease;
  cursor: pointer;
}
.crm-page-btn:hover:not(.disabled), .crm-page-num:hover:not(.active) {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateY(-1px);
}
.crm-page-num.active {
  background: var(--red) !important;
  border-color: var(--red) !important;
  color: #ffffff !important;
}

@media(max-width: 1200px) {
  .stats-grid { grid-template-columns: repeat(3, 1fr); }
  .filter-form-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
  .filter-field.col-search { grid-column: span 2; }
}
@media(max-width: 900px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .filter-form-grid { grid-template-columns: repeat(2, 1fr); }
  .topbar { flex-direction: column; align-items: stretch; gap: 14px; }
  .top-actions { width: 100%; flex-wrap: wrap; gap: 8px; }
  .top-actions .btn { flex: 1 1 auto; min-height: 44px; }
}
@media(max-width: 600px) {
  .crm-main { padding: 16px 12px 60px; min-width: 0; width: 100%; max-width: 100%; }
  .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .filter-form-grid { grid-template-columns: 1fr; }
  .filter-field.col-search { grid-column: span 1; }
  .filter-actions-col { width: 100%; justify-self: stretch; }
  .filter-actions-col .btn { flex: 1; height: 44px; min-height: 44px; }
  .pagination-wrap { flex-direction: column; align-items: center; text-align: center; gap: 12px; }
  .crm-pagination-nav { flex-wrap: wrap; justify-content: center; }
  .btn-action { width: 44px; height: 44px; min-width: 44px; min-height: 44px; font-size: 16px; }
}
@media(max-width: 380px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="crm-app leads-page">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        @php
            $leadTopActions = '';
            if (auth()->user()->can('leads.create')) {
                $leadTopActions .= '<a href="' . route('v2.leads.create') . '" class="btn primary"><i class="bi bi-plus-lg"></i> ' . __('crm.create_lead') . '</a>';
            }
            if (auth()->user()->can('leads.import')) {
                $leadTopActions .= '<a href="' . route('v2.leads.import') . '" class="btn soft"><i class="bi bi-file-earmark-arrow-up"></i> ' . __('crm.import') . '</a>';
            }
            if (auth()->user()->can('leads.export')) {
                $leadTopActions .= '<a href="' . route('v2.leads.export') . '" class="btn soft"><i class="bi bi-file-earmark-arrow-down"></i> ' . __('crm.export') . '</a>';
            }
        @endphp

        @include('partials.topbar', [
            'title' => __('crm.view_leads'),
            'subtitle' => '<span>' . __('crm.total_leads') . ': ' . number_format($totalLeads) . '</span>',
            'icon' => 'bi-people-fill',
            'actions' => $leadTopActions,
        ])

        @if (session('success'))
            <div style="padding:12px 16px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:16px;font-weight:700">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif

        <!-- HERO PIPELINE -->
        <section class="hero-pipeline" id="heroPipelineSection">
            @foreach ($pipelineStages as $index => $hStage)
                @php
                    $hColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hStage->color) ? $hStage->color : '#3478f6';
                    $hIcon = !empty($hStage->icon) ? $hStage->icon : match ($hStage->code) {
                        'start', 'new' => 'bi-person-plus-fill',
                        'no_answer', 'no-answer' => 'bi-telephone-x-fill',
                        'interest', 'interested' => 'bi-hand-thumbs-up-fill',
                        'not_interested', 'not-interested' => 'bi-hand-thumbs-down-fill',
                        'negotiation', 'meeting' => 'bi-calendar-event-fill',
                        'quotation' => 'bi-file-earmark-text-fill',
                        'discussion' => 'bi-chat-dots-fill',
                        'closing_execution', 'contract_closed', 'contract' => 'bi-check-circle-fill',
                        'execution' => 'bi-gear-fill',
                        'donor' => 'bi-heart-fill',
                        default => 'bi-diagram-3-fill',
                    };
                    $isHeroSelected = ((string) ($filters['stage'] ?? '') === (string) $hStage->id)
                        || (isset($selectedStage) && $selectedStage?->id === $hStage->id);
                    $heroStageQuery = $isHeroSelected
                        ? $queryWithoutStatus
                        : array_merge($queryWithoutStatus, ['stage' => $hStage->id]);
                @endphp

                @if ($index > 0)
                    <span class="hero-pipe-arrow" aria-hidden="true"><i class="bi bi-chevron-right rtl-flip"></i></span>
                @endif

                <a
                    class="hero-stage {{ $isHeroSelected ? 'is-selected' : '' }}"
                    href="{{ route('v2.leads', $heroStageQuery) }}"
                    style="--stage-color:{{ $hColor }};"
                    data-stage-id="{{ $hStage->id }}"
                    title="{{ $hStage->localizedName() }}"
                >
                    <span class="stage-icon"><i class="bi {{ $hIcon }}"></i></span>
                    <span class="stage-label">{{ $hStage->localizedName() }}</span>
                </a>
            @endforeach
        </section>

        <!-- STAGES STATS CARDS -->
        <section class="stats-grid">
            <a href="{{ route('v2.leads', $queryWithoutStatus) }}" class="stat-card {{ empty($filters['stage']) && empty($filters['status']) ? 'selected' : '' }}">
                <span><i class="bi bi-people-fill"></i> {{ __('crm.total_leads') }}</span>
                <b>{{ number_format($totalLeads) }}</b>
            </a>
            @foreach ($pipelineStages as $stg)
                @php
                    $stgColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $stg->color) ? $stg->color : '#3478f6';
                    $isStgSelected = ((string) ($filters['stage'] ?? '') === (string) $stg->id)
                        || (isset($selectedStage) && $selectedStage?->id === $stg->id)
                        || (!empty($filters['status']) && in_array($filters['status'], $stg->statuses->pluck('code')->all(), true));
                    $stgQuery = $isStgSelected
                        ? $queryWithoutStatus
                        : array_merge($queryWithoutStatus, ['stage' => $stg->id]);
                @endphp
                <a href="{{ route('v2.leads', $stgQuery) }}" class="stat-card {{ $isStgSelected ? 'selected' : '' }}" style="--status-color:{{ $stgColor }}">
                    <span>
                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $stgColor }};flex-shrink:0;"></span>
                        {{ $stg->localizedName() }}
                    </span>
                    <b>{{ number_format($stg->leads_count ?? ($stg->scoped_leads_count ?? 0)) }}</b>
                    <small>{{ $stg->isPrimary() ? __('crm.primary_stage_badge') : __('crm.additional_stage_badge') }}</small>
                </a>
            @endforeach
        </section>

        <!-- FILTERS PANEL -->
        <section class="filter-panel">
            <form method="GET" action="{{ route('v2.leads') }}" class="filter-form-grid" id="leadFilters">
                @if (!empty($filters['stage']))
                    <input type="hidden" name="stage" value="{{ $filters['stage'] }}">
                @endif

                <!-- Search Input -->
                <div class="filter-field col-search">
                    <label for="searchQuery"><i class="bi bi-search"></i> {{ __('crm.search_query_label') }}</label>
                    <div class="filter-input-wrap">
                        <i class="bi bi-search filter-input-icon"></i>
                        <input type="text" id="searchQuery" name="q" value="{{ $filters['q'] }}" class="filter-control with-icon" placeholder="{{ __('crm.lead_search_placeholder') }}">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="filter-field">
                    <label for="leadStatus"><i class="bi bi-tag"></i> {{ __('crm.status') }}</label>
                    <select id="leadStatus" name="status" class="filter-control">
                        <option value="">{{ __('crm.all_states') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->code }}" @selected($filters['status'] === $status->code)>
                                {{ $status->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Filter -->
                <div class="filter-field">
                    <label for="leadSource"><i class="bi bi-diagram-2"></i> {{ __('crm.source') }}</label>
                    <select id="leadSource" name="source" class="filter-control">
                        <option value="">{{ __('crm.all_sources') }}</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source }}" @selected($filters['source'] === $source)>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Employee Filter -->
                <div class="filter-field">
                    <label for="leadEmployee"><i class="bi bi-person-check"></i> {{ __('crm.assigned_employee') }}</label>
                    <select id="leadEmployee" name="employee" class="filter-control">
                        <option value="">{{ __('كل الموظفين') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee }}" @selected($filters['employee'] === $employee)>
                                {{ $employee }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Follow Up Filter -->
                <div class="filter-field">
                    <label for="leadFollowUp"><i class="bi bi-calendar-event"></i> {{ __('crm.next_followup') }}</label>
                    <select id="leadFollowUp" name="follow_up" class="filter-control">
                        <option value="">{{ __('crm.all_appointments') }}</option>
                        <option value="today" @selected($filters['follow_up'] === 'today')>{{ __('اليوم') }}</option>
                        <option value="upcoming" @selected($filters['follow_up'] === 'upcoming')>{{ __('crm.upcoming') }}</option>
                        <option value="overdue" @selected($filters['follow_up'] === 'overdue')>{{ __('crm.overdue') }}</option>
                        <option value="none" @selected($filters['follow_up'] === 'none')>{{ __('crm.no_date') }}</option>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div class="filter-field">
                    <label for="leadSort"><i class="bi bi-sort-down"></i> {{ __('crm.sort') }}</label>
                    <select id="leadSort" name="sort" class="filter-control">
                        <option value="latest" @selected($filters['sort'] === 'latest')>{{ __('crm.newest_first') }}</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>{{ __('crm.oldest_first') }}</option>
                        <option value="name" @selected($filters['sort'] === 'name')>{{ __('crm.by_name') }}</option>
                        <option value="followup" @selected($filters['sort'] === 'followup')>{{ __('crm.by_followup') }}</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="filter-actions-col">
                    <button type="submit" class="btn primary small" title="{{ __('crm.apply_filter') }}">
                        <i class="bi bi-funnel-fill"></i> {{ __('crm.apply') }}
                    </button>
                    @if ($activeQuery !== [])
                        <a href="{{ route('v2.leads') }}" class="btn soft small" title="{{ __('crm.reset') }}">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- BULK ACTIONS FORM -->
        @can('leads.export')
        <form
            class="bulk-actions-bar"
            id="bulkActionsBar"
            method="POST"
            action="{{ route('v2.leads.export-selected') }}"
            hidden
        >
            @csrf
            <div>
                <i class="bi bi-check2-square"></i>
                {{ __('crm.selected') }}
                <strong id="selectedLeadsCount">0</strong>
                {{ __('crm.lead_unit') }}
            </div>
            <div class="bulk-actions-buttons">
                <button class="bulk-clear-button" id="bulkClearSelection" type="button">
                    {{ __('crm.deselect') }}
                </button>
                <button class="bulk-export-button" id="bulkExportButton" type="submit" disabled>
                    <i class="bi bi-file-earmark-excel"></i> {{ __('crm.export_excel') }}
                </button>
            </div>
        </form>
        @endcan

        <!-- CUSTOMERS TABLE -->
        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            @can('leads.export')
                            <th style="width:40px;text-align:center">
                                <input
                                    id="selectAllLeads"
                                    type="checkbox"
                                    class="lead-select-all"
                                    title="{{ __('crm.select_all_current_page') }}"
                                >
                            </th>
                            @endcan
                            <th style="min-width:200px">{{ __('crm.client') }}</th>
                            <th style="min-width:140px">{{ __('crm.contact_data') }}</th>
                            <th style="min-width:140px">{{ __('crm.company_source') }}</th>
                            <th style="min-width:140px">{{ __('crm.current_status') }}</th>
                            <th style="min-width:130px">{{ __('crm.responsible_employee') }}</th>
                            <th style="min-width:140px">{{ __('crm.next_followup') }}</th>
                            <th style="min-width:110px">{{ __('crm.created_date') }}</th>
                            <th style="min-width:140px;text-align:center">{{ __('crm.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            @php
                                $leadStatusColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $lead->status?->color) ? $lead->status->color : '#64748b';
                                $leadPhoneRaw = trim((string) $lead->phone);
                                $leadPhoneDigits = preg_replace('/\D+/', '', $leadPhoneRaw) ?? '';
                                $callPhone = preg_match('/^[0-9]{2,20}$/', $leadPhoneDigits) === 1 ? $leadPhoneDigits : null;
                                $whatsappPhone = null;

                                if (str_starts_with($leadPhoneDigits, '0020')) {
                                    $whatsappPhone = substr($leadPhoneDigits, 2);
                                } elseif (preg_match('/^01[0125][0-9]{8}$/', $leadPhoneDigits) === 1) {
                                    $whatsappPhone = '20'.substr($leadPhoneDigits, 1);
                                } elseif (preg_match('/^20[0-9]{10}$/', $leadPhoneDigits) === 1 || preg_match('/^[1-9][0-9]{7,14}$/', $leadPhoneDigits) === 1) {
                                    $whatsappPhone = $leadPhoneDigits;
                                }
                            @endphp
                            <tr class="lead-row">
                                @can('leads.export')
                                <td style="text-align:center">
                                    <input
                                        class="lead-select-checkbox"
                                        type="checkbox"
                                        name="lead_ids[]"
                                        value="{{ $lead->id }}"
                                        form="bulkActionsBar"
                                        autocomplete="off"
                                    >
                                </td>
                                @endcan

                                <td>
                                    <div class="customer-name-cell">
                                        <div class="customer-avatar">
                                            {{ mb_substr((string) $lead->name, 0, 1) }}
                                        </div>
                                        <div class="customer-info">
                                            <a href="{{ route('v2.leads.show', array_merge(request()->query(), ['lead' => $lead->id])) }}">
                                                <strong>{{ $lead->name }}</strong>
                                            </a>
                                            <small>{{ $lead->email ?: __('crm.no_email') }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if ($lead->phone)
                                        @can('leads.followups.view')
                                            @if ($callPhone)
                                                <a
                                                    class="js-call-followup"
                                                    href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
                                                    data-call-href="tel:{{ $callPhone }}"
                                                    title="{{ __('crm.open_microsip_followup') }}"
                                                    style="font-weight:700;color:inherit"
                                                    dir="ltr"
                                                >
                                                    {{ $lead->phone }}
                                                </a>
                                            @else
                                                <a href="tel:{{ $lead->phone }}" style="font-weight:700;color:inherit" dir="ltr">
                                                    {{ $lead->phone }}
                                                </a>
                                            @endif
                                        @else
                                            <a href="tel:{{ $lead->phone }}" style="font-weight:700;color:inherit" dir="ltr">
                                                {{ $lead->phone }}
                                            </a>
                                        @endcan
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>

                                <td>
                                    <strong>{{ $lead->company_name ?: __('crm.no_company') }}</strong>
                                    <span class="stage-name">{{ $lead->source ? __($lead->source) : __('غير محدد') }}</span>
                                </td>

                                <td>
                                    <span class="status-badge" style="--status-color:{{ $leadStatusColor }}">
                                        <i class="status-dot"></i>
                                        {{ $lead->status?->name_ar ? __($lead->status->name_ar) : __('crm.no_status') }}
                                    </span>
                                    <span class="stage-name">
                                        {{ $lead->status?->stage?->name_ar ? __($lead->status->stage->name_ar) : __('بدون مرحلة') }}
                                    </span>
                                </td>

                                <td>
                                    {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
                                </td>

                                <td>
                                    @if ($lead->next_follow_up_at)
                                        <span class="badge {{ $lead->next_follow_up_at->isPast() ? 'overdue' : ($lead->next_follow_up_at->isToday() ? 'today' : '') }}">
                                            <i class="bi bi-clock"></i> {{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}
                                        </span>
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $lead->created_at?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td>
                                    <div class="actions-cell" style="justify-content:center">
                                        <a
                                            class="btn-action"
                                            href="{{ route('v2.leads.show', array_merge(request()->query(), ['lead' => $lead->id])) }}"
                                            title="{{ __('crm.view_lead') }}"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($callPhone)
                                            @can('leads.followups.view')
                                                <a
                                                    class="btn-action call js-call-followup"
                                                    href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
                                                    data-call-href="tel:{{ $callPhone }}"
                                                    title="{{ __('crm.call_action') }}"
                                                >
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                            @else
                                                <a class="btn-action call" href="tel:{{ $callPhone }}" title="{{ __('crm.call_action') }}">
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                            @endcan
                                        @endif

                                        @can('leads.followups.view')
                                            <a
                                                class="btn-action"
                                                href="{{ route('v2.leads.followups.index', $lead) }}"
                                                title="{{ __('crm.log_new_followup') }}"
                                            >
                                                <i class="bi bi-clock-history"></i>
                                            </a>
                                        @endcan

                                        @if ($whatsappPhone)
                                            <a
                                                class="btn-action whatsapp"
                                                href="https://wa.me/{{ $whatsappPhone }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="{{ __('crm.whatsapp') }}"
                                            >
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        @endif

                                        @can('leads.update')
                                            <a
                                                class="btn-action"
                                                href="{{ route('v2.leads.edit', $lead) }}"
                                                title="{{ __('crm.edit_data') }}"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="text-align:center;padding:40px 20px;color:var(--muted)">
                                    <i class="bi bi-people" style="font-size:32px;display:block;margin-bottom:8px"></i>
                                    <strong>{{ $activeQuery === [] ? __('لا يوجد عملاء حتى الآن') : __('لا توجد نتائج مطابقة') }}</strong>
                                    <p style="margin:4px 0 0;font-size:12px">
                                        @if ($activeQuery === [])
                                            لم تتم إضافة أي عميل إلى قاعدة CRM حتى الآن.
                                        @else
                                            جرّب تغيير كلمات البحث أو إزالة بعض الفلاتر الحالية.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if ($leads->hasPages())
                {{ $leads->links() }}
            @endif
        </section>
    </main>
</div>

<script>
(() => {
    // Bulk actions handling
    const selectAll = document.getElementById('selectAllLeads');
    const bulkBar = document.getElementById('bulkActionsBar');
    const counter = document.getElementById('selectedLeadsCount');
    const exportBtn = document.getElementById('bulkExportButton');
    const clearBtn = document.getElementById('bulkClearSelection');
    const checkboxes = document.querySelectorAll('.lead-select-checkbox');

    const updateBulkState = () => {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        const count = checked.length;
        if (counter) counter.textContent = String(count);
        if (exportBtn) exportBtn.disabled = count === 0;
        if (bulkBar) {
            if (count > 0) {
                bulkBar.removeAttribute('hidden');
            } else {
                bulkBar.setAttribute('hidden', '');
            }
        }
        if (selectAll) {
            selectAll.checked = count > 0 && count === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
        updateBulkState();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkState);
    });

    clearBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => { cb.checked = false; });
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        updateBulkState();
    });

    // Same-tab Call handler
    document.querySelectorAll('.js-call-followup').forEach(link => {
        link.addEventListener('click', () => {
            const callHref = link.dataset.callHref;
            if (!callHref || link.target !== '_blank') {
                return;
            }
            window.setTimeout(() => {
                window.location.href = callHref;
            }, 120);
        });
    });
})();
</script>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
</body>
</html>
