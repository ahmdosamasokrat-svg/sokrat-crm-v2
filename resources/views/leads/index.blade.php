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
  flex-wrap: nowrap;
  overflow-x: auto;
  min-width: 0;
  max-width: 100%;
  padding: 12px 16px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  margin-bottom: 20px;
  box-shadow: var(--shadow);
  scrollbar-width: thin;
  scrollbar-color: var(--muted) var(--bg);
  scroll-snap-type: inline proximity;
  overscroll-behavior-inline: contain;
  -webkit-overflow-scrolling: touch;
  touch-action: pan-x;
  cursor: grab;
}
.hero-pipeline:active {
  cursor: grabbing;
}
.hero-pipeline:hover,
.hero-pipeline:focus-within {
  scrollbar-color: var(--red) var(--bg);
}
.hero-pipeline::-webkit-scrollbar {
  height: 5px;
}
.hero-pipeline::-webkit-scrollbar-track {
  background: var(--bg);
  border-radius: 99px;
}
.hero-pipeline::-webkit-scrollbar-thumb {
  background: var(--muted);
  border-radius: 99px;
}
.hero-pipeline:hover::-webkit-scrollbar-thumb,
.hero-pipeline:focus-within::-webkit-scrollbar-thumb {
  background: var(--red);
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
  flex: 0 0 auto;
  scroll-snap-align: start;
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
  flex: 0 0 auto;
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
.dynamic-filter-builder {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding-top: 12px;
  border-top: 1px solid var(--line);
}
.dynamic-filter-context {
  min-width: 0;
}
.dynamic-filter-context strong {
  display: flex;
  align-items: center;
  gap: 7px;
  color: var(--dark);
  font-size: 13px;
}
.dynamic-filter-context small {
  display: block;
  margin-top: 3px;
  color: var(--muted);
  font-size: 11px;
}
.dynamic-field-picker {
  position: relative;
  flex: 0 0 auto;
}
.dynamic-field-picker-toggle {
  min-width: 190px;
  justify-content: space-between;
}
.dynamic-field-picker-count {
  min-width: 24px;
  height: 24px;
  display: inline-grid;
  place-items: center;
  padding: 0 6px;
  border-radius: 8px;
  background: #fef2f2;
  color: var(--red);
  font-size: 11px;
  font-weight: 900;
}
html.dark-mode .dynamic-field-picker-count {
  background: rgba(220, 38, 55, 0.16);
}
.dynamic-field-picker-menu {
  position: absolute;
  inset-block-start: calc(100% + 7px);
  inset-inline-end: 0;
  z-index: 30;
  width: min(360px, calc(100vw - 40px));
  max-height: 340px;
  overflow-y: auto;
  padding: 8px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--card);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
}
.dynamic-field-picker-menu[hidden] { display: none; }
.dynamic-field-option {
  display: flex;
  align-items: center;
  gap: 9px;
  min-height: 42px;
  padding: 8px 9px;
  border-radius: 9px;
  color: var(--dark);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
.dynamic-field-option:hover {
  background: #f8fafc;
}
html.dark-mode .dynamic-field-option:hover {
  background: rgba(255, 255, 255, 0.06);
}
.dynamic-field-option input {
  width: 18px;
  height: 18px;
  flex: 0 0 18px;
  accent-color: var(--red);
}
.dynamic-field-option i {
  color: var(--muted);
}
.dynamic-field-option-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.dynamic-field-option-copy span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.dynamic-field-option-copy small {
  color: var(--muted);
  font-size: 10px;
  font-weight: 600;
}
.dynamic-field-picker-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 9px 2px;
  border-top: 1px solid var(--line);
}
.dynamic-field-picker-footer small {
  color: var(--muted);
  font-size: 10px;
}
.dynamic-field-picker-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
}
.dynamic-field-clear {
  padding: 0;
  border: 0;
  background: transparent;
  color: var(--red);
  font: inherit;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}
.dynamic-field-select-all {
  color: var(--dark);
}
.dynamic-filter-field-stage {
  color: var(--muted);
  font-size: 9px;
  font-weight: 700;
}
.dynamic-filter-fields {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 10px 12px;
}
.dynamic-filter-field[hidden] { display: none; }
.dynamic-filter-empty {
  grid-column: 1 / -1;
  margin: 0;
  padding: 9px 11px;
  border-radius: 9px;
  background: #f8fafc;
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
}
html.dark-mode .dynamic-filter-empty {
  background: rgba(255, 255, 255, 0.04);
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
  .dynamic-filter-builder { align-items: stretch; flex-direction: column; }
  .dynamic-field-picker, .dynamic-field-picker-toggle { width: 100%; }
  .dynamic-field-picker-menu { inset-inline: 0; width: 100%; }
  .dynamic-filter-fields { grid-template-columns: 1fr; }
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
                <input
                    type="hidden"
                    name="field_ids"
                    value="{{ implode(',', $selectedStageFieldIds) }}"
                    data-selected-dynamic-field-ids
                >
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
                            <option value="{{ $status->code }}" data-stage-id="{{ $status->pipeline_stage_id }}" @selected($filters['status'] === $status->code)>
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

                <!-- Choose Fields / Column Chooser Dropdown -->
                <div class="filter-field col-choose-fields" data-dynamic-field-picker style="position:relative;">
                    <label for="chooseFieldsToggle" style="font-size:12px;"><i class="bi bi-layout-three-columns"></i> {{ __('تحديد الأعمدة والفلاتر') }}</label>
                    <button
                        id="chooseFieldsToggle"
                        class="filter-control btn small soft dynamic-field-picker-toggle"
                        type="button"
                        data-dynamic-field-picker-toggle
                        aria-expanded="false"
                        aria-controls="dynamicFieldPickerMenu"
                        style="width:100%; display:flex; align-items:center; justify-content:space-between; padding:0 12px; font-weight:700; background:var(--card);"
                    >
                        <span style="pointer-events:none;"><i class="bi bi-ui-checks-grid" style="margin-inline-end:4px;"></i> {{ __('الأعمدة والحقول') }}</span>
                        <span class="dynamic-field-picker-count badge" data-dynamic-field-count style="pointer-events:none; background:#6366f1; color:#fff; font-size:10px; padding:2px 6px;">{{ count($selectedStageFieldIds) }}</span>
                    </button>

                    <div
                        class="dynamic-field-picker-menu"
                        id="dynamicFieldPickerMenu"
                        data-dynamic-field-picker-menu
                        role="group"
                        aria-labelledby="chooseFieldsToggle"
                        hidden
                        style="position:absolute; top:calc(100% + 6px); inset-inline-end:0; z-index:150; max-height:480px; overflow-y:auto; width:340px; background:var(--card); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow-dropdown); box-sizing:border-box;"
                    >
                        <div style="padding: 10px 12px 6px; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; border-bottom:1px solid var(--line);">
                            الأعمدة الأساسية (إلزامية واختيارية)
                        </div>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="client" checked disabled>
                            <i class="bi bi-person-badge"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.client') }}</span>
                                <small style="color:var(--muted)">إلزامي</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="status" checked disabled>
                            <i class="bi bi-tag"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.current_status') }}</span>
                                <small style="color:var(--muted)">إلزامي</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="actions" checked disabled>
                            <i class="bi bi-gear"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.actions') }}</span>
                                <small style="color:var(--muted)">إلزامي</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="contact" checked>
                            <i class="bi bi-telephone"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.contact_data') }}</span>
                                <small>الهاتف / البريد</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="company_source" checked>
                            <i class="bi bi-building"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.company_source') }}</span>
                                <small>الشركة والمصدر</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="employee" checked>
                            <i class="bi bi-person-check"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.responsible_employee') }}</span>
                                <small>الموظف المسؤول</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="followup" checked>
                            <i class="bi bi-calendar-event"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.next_followup') }}</span>
                                <small>الموعد القادم</small>
                            </span>
                        </label>
                        <label class="dynamic-field-option">
                            <input type="checkbox" data-col-toggle="created_date" checked>
                            <i class="bi bi-clock-history"></i>
                            <span class="dynamic-field-option-copy">
                                <span>{{ __('crm.created_date') }}</span>
                                <small>تاريخ الإضافة</small>
                            </span>
                        </label>

                        <div style="padding: 12px 12px 6px; font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; border-top: 1px solid var(--line); border-bottom:1px solid var(--line);">
                            حقول وأسئلة المراحل (أعمدة وفلاتر ديناميكية)
                        </div>

                        @php
                            $activeStageModel = $selectedStage ?: ($selectedStatus?->stage);
                            $fieldsToDisplay = $activeStageModel
                                ? $availableStageFields->where('pipeline_stage_id', $activeStageModel->id)
                                : $availableStageFields;

                            $seenTargets = [];
                            $dedupedFields = $fieldsToDisplay->filter(function ($f) use (&$seenTargets) {
                                if ($f->isCanonical() && !empty($f->binding_target)) {
                                    if (in_array($f->binding_target, $seenTargets, true)) {
                                        return false;
                                    }
                                    $seenTargets[] = $f->binding_target;
                                }
                                return true;
                            });

                            $groupedFields = $dedupedFields->groupBy(fn ($f) => $f->stage?->localizedName() ?: 'المراحل');
                        @endphp

                        @foreach ($groupedFields as $stgName => $fList)
                            <div class="picker-stage-group-header" data-stage-name="{{ $stgName }}" style="padding: 8px 12px 2px; font-size: 11px; font-weight: 700; color: #4f46e5; background:rgba(79,70,229,0.04);">
                                • {{ $stgName }}
                            </div>
                            @foreach ($fList as $field)
                                @php
                                    $fieldIcon = match ($field->type) {
                                        'number', 'currency' => 'bi-123',
                                        'date', 'datetime' => 'bi-calendar3',
                                        'select', 'multiselect' => 'bi-list-check',
                                        'checkbox', 'boolean' => 'bi-check2-square',
                                        'email' => 'bi-envelope',
                                        'tel' => 'bi-telephone',
                                        'file', 'image', 'pdf' => 'bi-paperclip',
                                        default => 'bi-input-cursor-text',
                                    };
                                    $isFieldChecked = in_array((int) $field->id, $selectedStageFieldIds, true);
                                @endphp
                                <label class="dynamic-field-option" data-stage-id="{{ $field->pipeline_stage_id }}">
                                    <input
                                        type="checkbox"
                                        value="{{ $field->id }}"
                                        data-col-toggle="stage_field_{{ $field->id }}"
                                        data-dynamic-field-option
                                        @checked($isFieldChecked)
                                    >
                                    <i class="bi {{ $fieldIcon }}" aria-hidden="true"></i>
                                    <span class="dynamic-field-option-copy">
                                        <span>{{ $field->localizedLabel() }}</span>
                                        <small>{{ $field->stage?->localizedName() }}</small>
                                    </span>
                                </label>
                            @endforeach
                        @endforeach

                        <div class="dynamic-field-picker-footer" style="position: sticky; bottom: 0; background: var(--card); border-top: 1px solid var(--line); padding: 8px 12px;">
                            <div class="dynamic-field-picker-actions" style="display: flex; gap: 6px; flex-wrap: wrap; width: 100%;">
                                <button class="btn small soft" type="button" id="restoreDefaultColsBtn" style="flex:1; color: #4f46e5; font-weight: 700; font-size:11px;">
                                    استعادة الأعمدة الافتراضية
                                </button>
                                <button class="btn small soft" type="button" id="selectAllColsBtn" style="font-size:11px;">
                                    تحديد الكل
                                </button>
                                <button class="btn small soft" type="button" id="clearOptionalColsBtn" style="font-size:11px;">
                                    مسح الاختياري
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reset Filters Button -->
                <div class="filter-actions-col">
                    <label style="font-size:12px; visibility:hidden;">Reset</label>
                    <a href="{{ route('v2.leads') }}" class="btn soft small" id="resetFiltersBtn" title="{{ __('crm.reset') }}" style="height:40px; display:inline-flex; align-items:center; gap:6px; font-weight:700;">
                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('crm.reset') }}
                    </a>
                </div>

                @if ($availableStageFields->isNotEmpty())
                    <div class="dynamic-filter-fields" data-dynamic-filter-fields style="grid-column: 1 / -1; display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:8px;">
                        @foreach ($availableStageFields as $field)
                            @php
                                $fieldId = (int) $field->id;
                                $fieldSelected = in_array($fieldId, $selectedStageFieldIds, true);
                                $fieldValue = (string) ($stageFieldFilters[$fieldId] ?? '');
                                $fieldInputId = 'stageFieldFilter_' . $fieldId;
                            @endphp
                            <div
                                class="filter-field dynamic-filter-field"
                                data-dynamic-filter-field="{{ $fieldId }}"
                                data-stage-id="{{ $field->pipeline_stage_id }}"
                                @if (! $fieldSelected) hidden @endif
                            >
                                <label for="{{ $fieldInputId }}">
                                    <i class="bi bi-funnel"></i>
                                    {{ $field->localizedLabel() }}
                                    <span class="dynamic-filter-field-stage">· {{ $field->stage?->localizedName() }}</span>
                                </label>

                                @if (in_array($field->type, ['select', 'multiselect'], true))
                                    <select
                                        id="{{ $fieldInputId }}"
                                        name="field_filters[{{ $fieldId }}]"
                                        class="filter-control"
                                        @disabled(! $fieldSelected)
                                    >
                                        <option value="">{{ __('crm.all_field_values') }}</option>
                                        @foreach ($field->normalizedOptions() as $option)
                                            <option value="{{ $option['value'] }}" @selected($fieldValue === (string) $option['value'])>
                                                {{ app()->getLocale() === 'en' ? $option['label_en'] : $option['label_ar'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($field->type === 'checkbox')
                                    <select
                                        id="{{ $fieldInputId }}"
                                        name="field_filters[{{ $fieldId }}]"
                                        class="filter-control"
                                        @disabled(! $fieldSelected)
                                    >
                                        <option value="">{{ __('crm.all_field_values') }}</option>
                                        <option value="1" @selected($fieldValue === '1')>{{ __('crm.yes') }}</option>
                                        <option value="0" @selected($fieldValue === '0')>{{ __('crm.no') }}</option>
                                    </select>
                                @else
                                    @php
                                        $filterInputType = match ($field->type) {
                                            'number', 'currency' => 'number',
                                            'date' => 'date',
                                            'datetime' => 'datetime-local',
                                            default => 'text',
                                        };
                                    @endphp
                                    <input
                                        id="{{ $fieldInputId }}"
                                        name="field_filters[{{ $fieldId }}]"
                                        type="{{ $filterInputType }}"
                                        @if (in_array($field->type, ['number', 'currency'], true)) step="any" @endif
                                        value="{{ $fieldValue }}"
                                        class="filter-control"
                                        placeholder="{{ $field->localizedPlaceholder() ?: __('crm.enter_filter_value') }}"
                                        @disabled(! $fieldSelected)
                                    >
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </form>
        </section>

        <!-- BULK SELECTION TOOLBAR (DIRECTLY BELOW FILTERS / ABOVE TABLE) -->
        <div class="bulk-toolbar" id="leadsBulkToolbar" style="display:none; margin-bottom:16px; padding:12px 20px; background:var(--card); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow-card); align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="badge" style="background:#eef2ff; color:#4f46e5; font-size:12px; padding:4px 10px; font-weight:800;">
                    <span id="selectedLeadsCounter">0</span> {{ __('عملاء محددين') }}
                </span>
                <button type="button" class="btn small soft" id="clearLeadsSelectionBtn" style="font-size:12px; padding:0 12px;">
                    {{ __('إلغاء التحديد') }}
                </button>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                @can('leads.export')
                    <button type="button" class="btn small soft" id="bulkExportBtn" onclick="submitBulkExport()" style="font-size:12px; color:#16a34a; border-color:#bbf7d0; background:#f0fdf4;">
                        <i class="bi bi-file-earmark-excel"></i> {{ __('تصدير') }}
                    </button>
                @endcan
                @can('leads.delete')
                    <button type="button" class="btn small danger" id="bulkDeleteBtn" onclick="confirmBulkDelete()" style="font-size:12px;">
                        <i class="bi bi-trash"></i> {{ __('حذف') }}
                    </button>
                @endcan
            </div>
        </div>

        <!-- HIDDEN FORM FOR BULK EXPORT -->
        <form id="bulkExportForm" method="POST" action="{{ route('v2.leads.export-selected') }}" style="display:none;">
            @csrf
            <div id="bulkExportInputs"></div>
        </form>

        <!-- HIDDEN FORM FOR BULK DELETE (TRASH) -->
        <form id="bulkDeleteForm" method="POST" action="{{ route('v2.leads.bulk-delete') }}" style="display:none;">
            @csrf
            <div id="bulkDeleteInputs"></div>
        </form>

        <!-- CUSTOMERS TABLE -->
        <section class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th data-col="select" style="width:40px; text-align:center; padding:0 8px;">
                                <input type="checkbox" id="selectAllLeads" title="تحديد الكل في هذه الصفحة" style="width:17px; height:17px; cursor:pointer;">
                            </th>
                            <th data-col="client" style="min-width:200px">{{ __('crm.client') }}</th>
                            <th data-col="contact" style="min-width:140px">{{ __('crm.contact_data') }}</th>
                            <th data-col="company_source" style="min-width:140px">{{ __('crm.company_source') }}</th>
                            <th data-col="status" style="min-width:140px">{{ __('crm.current_status') }}</th>
                            <th data-col="employee" style="min-width:130px">{{ __('crm.responsible_employee') }}</th>
                            <th data-col="followup" style="min-width:140px">{{ __('crm.next_followup') }}</th>
                            <th data-col="created_date" style="min-width:110px">{{ __('crm.created_date') }}</th>
                            @foreach ($availableStageFields as $sField)
                                <th data-col="stage_field_{{ $sField->id }}" data-dynamic-stage-col="{{ $sField->id }}" style="min-width:140px;" @if(!in_array((int)$sField->id, $selectedStageFieldIds, true)) hidden @endif>
                                    {{ $sField->localizedLabel() }}
                                    <small style="display:block;font-size:10px;color:var(--muted)">{{ $sField->stage?->localizedName() }}</small>
                                </th>
                            @endforeach
                            <th data-col="actions" style="min-width:140px;text-align:center">{{ __('crm.actions') }}</th>
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
                                <td data-col="select" style="width:40px; text-align:center; padding:0 8px;">
                                    <input type="checkbox" class="lead-select-checkbox" value="{{ $lead->id }}" style="width:17px; height:17px; cursor:pointer;" onchange="handleRowSelectionChange()">
                                </td>

                                <td data-col="client">
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

                                <td data-col="contact">
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

                                <td data-col="company_source">
                                    <strong>{{ $lead->company_name ?: __('crm.no_company') }}</strong>
                                    <span class="stage-name">{{ $lead->source ? __($lead->source) : __('غير محدد') }}</span>
                                </td>

                                <td data-col="status">
                                    <span class="status-badge" style="--status-color:{{ $leadStatusColor }}">
                                        <i class="status-dot"></i>
                                        {{ $lead->status?->name_ar ? __($lead->status->name_ar) : __('crm.no_status') }}
                                    </span>
                                    <span class="stage-name">
                                        {{ $lead->status?->stage?->name_ar ? __($lead->status->stage->name_ar) : __('بدون مرحلة') }}
                                    </span>
                                </td>

                                <td data-col="employee">
                                    {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
                                </td>

                                <td data-col="followup">
                                    @if ($lead->next_follow_up_at)
                                        <span class="badge {{ $lead->next_follow_up_at->isPast() ? 'overdue' : ($lead->next_follow_up_at->isToday() ? 'today' : '') }}">
                                            <i class="bi bi-clock"></i> {{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}
                                        </span>
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>

                                <td data-col="created_date">
                                    {{ $lead->created_at?->format('d/m/Y') ?? '—' }}
                                </td>

                                @foreach ($availableStageFields as $sField)
                                    @php
                                        $sVal = $lead->stageValues->firstWhere('pipeline_stage_field_id', $sField->id)?->value;
                                    @endphp
                                    <td data-col="stage_field_{{ $sField->id }}" data-dynamic-stage-col="{{ $sField->id }}" @if(!in_array((int)$sField->id, $selectedStageFieldIds, true)) hidden @endif>
                                        @if ($sVal !== null && $sVal !== '')
                                            @if (in_array($sField->type, ['file', 'image', 'pdf'], true))
                                                <a href="{{ Storage::disk('local')->url($sVal) }}" target="_blank" class="badge" style="background:#e0f2fe; color:#0369a1;">
                                                    <i class="bi bi-paperclip"></i> ملف
                                                </a>
                                            @else
                                                <strong>{{ $sVal }}</strong>
                                            @endif
                                        @else
                                            <span style="color:var(--muted)">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td data-col="actions">
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
                                <td colspan="25" style="text-align:center;padding:40px 20px;color:var(--muted)">
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
    const form = document.getElementById('leadFilters');
    const searchInput = document.getElementById('searchQuery');
    const tableWrap = document.querySelector('.table-wrap');
    const summaryCard = document.querySelector('.summary');
    const fieldPickerToggle = document.getElementById('chooseFieldsToggle') || document.querySelector('[data-dynamic-field-picker-toggle]');
    const fieldPickerMenu = document.getElementById('dynamicFieldPickerMenu') || document.querySelector('[data-dynamic-field-picker-menu]');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const restoreDefaultColsBtn = document.getElementById('restoreDefaultColsBtn');
    const selectAllColsBtn = document.getElementById('selectAllColsBtn');
    const clearOptionalColsBtn = document.getElementById('clearOptionalColsBtn');

    let abortController = null;
    let searchDebounce = null;

    // 1. Column Management (Mandatory + Optional + Dynamic)
    const MANDATORY_COLS = ['select', 'client', 'status', 'actions'];
    const DEFAULT_COLS = ['client', 'contact', 'company_source', 'status', 'employee', 'followup', 'created_date', 'actions'];

    const getStoredColumns = () => {
        try {
            const saved = localStorage.getItem('sokrat.crm.leads.columns');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed;
                }
            }
        } catch (e) {}
        return DEFAULT_COLS;
    };

    const saveColumns = (cols) => {
        try {
            localStorage.setItem('sokrat.crm.leads.columns', JSON.stringify(cols));
        } catch (e) {}
    };

    const applyColumnVisibility = () => {
        const activeCols = new Set(getStoredColumns());
        MANDATORY_COLS.forEach(c => activeCols.add(c));

        // Sync checkboxes in column chooser
        document.querySelectorAll('[data-col-toggle]').forEach(cb => {
            const col = cb.dataset.colToggle;
            if (MANDATORY_COLS.includes(col)) {
                cb.checked = true;
                cb.disabled = true;
            } else {
                cb.checked = activeCols.has(col);
            }
        });

        // Toggle table headers and cells
        document.querySelectorAll('th[data-col], td[data-col]').forEach(el => {
            const col = el.dataset.col;
            if (col === 'select') {
                el.hidden = false;
                return;
            }
            el.hidden = !activeCols.has(col);
        });

        // Sync dynamic filter inputs above the table
        document.querySelectorAll('.dynamic-filter-field').forEach(field => {
            const fieldId = field.dataset.dynamicFilterField;
            const colKey = `stage_field_${fieldId}`;
            const isVisible = activeCols.has(colKey);
            field.hidden = !isVisible;
            field.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = !isVisible;
            });
        });

        const dynamicEmpty = document.querySelector('[data-dynamic-filter-empty]');
        const dynamicCountEl = document.querySelector('[data-dynamic-field-count]');
        const dynamicActiveCount = Array.from(activeCols).filter(c => c.startsWith('stage_field_')).length;
        if (dynamicCountEl) dynamicCountEl.textContent = String(dynamicActiveCount);
        if (dynamicEmpty) dynamicEmpty.hidden = dynamicActiveCount > 0;

        const selectedIdsInput = document.querySelector('[data-selected-dynamic-field-ids]');
        if (selectedIdsInput) {
            const stageFieldIds = Array.from(activeCols)
                .filter(c => c.startsWith('stage_field_'))
                .map(c => c.replace('stage_field_', ''));
            selectedIdsInput.value = stageFieldIds.join(',');
        }
    };

    // 2. Reactive Fetching Architecture (No Apply Button, URL remains state)
    const fetchFilteredLeads = (targetUrl, pushState = true) => {
        if (abortController) {
            abortController.abort(); // Cancel previous in-flight request
        }
        abortController = new AbortController();

        if (tableWrap) {
            tableWrap.style.opacity = '0.5';
            tableWrap.style.pointerEvents = 'none';
        }

        fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: abortController.signal
        })
        .then(res => res.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newTable = doc.querySelector('.table-card');
            const currentTable = document.querySelector('.table-card');
            if (newTable && currentTable) {
                currentTable.innerHTML = newTable.innerHTML;
            }

            const newSummary = doc.querySelector('.summary');
            if (newSummary && summaryCard) {
                summaryCard.innerHTML = newSummary.innerHTML;
            }

            if (pushState) {
                window.history.pushState(null, '', targetUrl);
            }

            applyColumnVisibility();
            wirePaginationAndCallLinks();
            updateBulkToolbar();
        })
        .catch(err => {
            if (err.name !== 'AbortError') {
                console.error('Filtering failed:', err);
            }
        })
        .finally(() => {
            if (tableWrap) {
                tableWrap.style.opacity = '';
                tableWrap.style.pointerEvents = '';
            }
        });
    };

    const buildFilterUrl = () => {
        if (!form) return window.location.href;
        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value !== '' && value !== null) {
                params.set(key, value);
            }
        }

        const base = form.action || window.location.pathname;
        const qs = params.toString();
        return qs ? `${base}?${qs}` : base;
    };

    const triggerReactiveFilter = () => {
        const url = buildFilterUrl();
        fetchFilteredLeads(url, true);
    };

    // Listeners for reactive filter inputs
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(triggerReactiveFilter, 280);
    });

    ['leadStatus', 'leadSource', 'leadEmployee', 'leadFollowUp', 'leadSort'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', triggerReactiveFilter);
    });
    const syncStatusSpecificFields = () => {
        const statusSelect = document.getElementById('leadStatus');
        const selectedOpt = statusSelect?.selectedOptions?.[0];
        const statusVal = statusSelect?.value || '';
        const stageId = selectedOpt?.dataset?.stageId || '';

        document.querySelectorAll('.dynamic-field-picker-menu .dynamic-field-option[data-stage-id]').forEach(opt => {
            if (!statusVal || !stageId) {
                opt.style.display = '';
            } else {
                const optStageId = opt.dataset.stageId;
                opt.style.display = (optStageId === stageId) ? '' : 'none';
            }
        });
        document.querySelectorAll('.picker-stage-group-header').forEach(hdr => {
            hdr.style.display = (statusVal && stageId) ? 'none' : '';
        });
    };

    document.getElementById('leadStatus')?.addEventListener('change', syncStatusSpecificFields);
    syncStatusSpecificFields();


    // Dynamic field filter inputs
    document.querySelector('[data-dynamic-filter-fields]')?.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(triggerReactiveFilter, 300);
    });
    document.querySelector('[data-dynamic-filter-fields]')?.addEventListener('change', triggerReactiveFilter);

    // Browser History (Back / Forward)
    window.addEventListener('popstate', () => {
        fetchFilteredLeads(window.location.href, false);
    });

    // Intercept Pagination Links
    const wirePaginationAndCallLinks = () => {
        document.querySelectorAll('.pagination a, .table-card .pagination a').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                fetchFilteredLeads(a.href, true);
                window.scrollTo({ top: document.querySelector('.filter-panel')?.offsetTop || 0, behavior: 'smooth' });
            });
        });

        // Wire softphone links
        document.querySelectorAll('.js-call-followup').forEach(link => {
            link.addEventListener('click', () => {
                const callHref = link.dataset.callHref;
                if (!callHref || link.target !== '_blank') return;
                window.setTimeout(() => { window.location.href = callHref; }, 120);
            });
        });
    };

    // Column Picker Toggle and Actions
    const toggleFieldPicker = (e) => {
        if (e) e.stopPropagation();
        if (!fieldPickerMenu) return;
        const isHidden = fieldPickerMenu.hidden || fieldPickerMenu.style.display === 'none';
        if (isHidden) {
            fieldPickerMenu.hidden = false;
            fieldPickerMenu.style.display = 'block';
            fieldPickerToggle?.setAttribute('aria-expanded', 'true');
        } else {
            fieldPickerMenu.hidden = true;
            fieldPickerMenu.style.display = 'none';
            fieldPickerToggle?.setAttribute('aria-expanded', 'false');
        }
    };
    fieldPickerToggle?.addEventListener('click', toggleFieldPicker);

    document.addEventListener('click', (e) => {
        if (fieldPickerMenu && !fieldPickerMenu.hidden && fieldPickerMenu.style.display !== 'none') {
            if (!fieldPickerToggle?.contains(e.target) && !fieldPickerMenu.contains(e.target)) {
                fieldPickerMenu.hidden = true;
                fieldPickerMenu.style.display = 'none';
                fieldPickerToggle?.setAttribute('aria-expanded', 'false');
            }
        }
    });

    // Bulk Selection Handling
    const getSelectedLeadIds = () => {
        return Array.from(document.querySelectorAll('.lead-select-checkbox:checked')).map(cb => cb.value);
    };

    const updateBulkToolbar = () => {
        const selectedIds = getSelectedLeadIds();
        const toolbar = document.getElementById('leadsBulkToolbar');
        const counter = document.getElementById('selectedLeadsCounter');
        const selectAll = document.getElementById('selectAllLeads');
        const checkboxes = Array.from(document.querySelectorAll('.lead-select-checkbox'));
        const total = checkboxes.length;

        if (selectAll) {
            selectAll.checked = total > 0 && selectedIds.length === total;
            selectAll.indeterminate = selectedIds.length > 0 && selectedIds.length < total;
        }

        if (counter) counter.textContent = String(selectedIds.length);
        if (toolbar) {
            toolbar.style.display = selectedIds.length > 0 ? 'flex' : 'none';
        }
    };

    window.handleRowSelectionChange = () => {
        updateBulkToolbar();
    };

    window.submitBulkExport = () => {
        const ids = getSelectedLeadIds();
        if (ids.length === 0) return;
        const container = document.getElementById('bulkExportInputs');
        if (!container) return;
        container.innerHTML = '';
        ids.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'lead_ids[]';
            inp.value = id;
            container.appendChild(inp);
        });
        document.getElementById('bulkExportForm')?.submit();
    };

    window.confirmBulkDelete = () => {
        const ids = getSelectedLeadIds();
        if (ids.length === 0) return;
        const msg = `سيتم نقل ${ids.length} عملاء إلى سلة المهملات. هل أنت متأكد من الحذف؟`;
        if (!confirm(msg)) return;
        const container = document.getElementById('bulkDeleteInputs');
        if (!container) return;
        container.innerHTML = '';
        ids.forEach(id => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'lead_ids[]';
            inp.value = id;
            container.appendChild(inp);
        });
        document.getElementById('bulkDeleteForm')?.submit();
    };

    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'selectAllLeads') {
            document.querySelectorAll('.lead-select-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateBulkToolbar();
        } else if (e.target && e.target.classList.contains('lead-select-checkbox')) {
            updateBulkToolbar();
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target && (e.target.id === 'clearLeadsSelectionBtn' || e.target.closest('#clearLeadsSelectionBtn'))) {
            document.querySelectorAll('.lead-select-checkbox').forEach(cb => { cb.checked = false; });
            updateBulkToolbar();
        }
    });
    // Column Checkbox Changed
    document.querySelectorAll('[data-col-toggle]').forEach(cb => {
        cb.addEventListener('change', () => {
            const checked = Array.from(document.querySelectorAll('[data-col-toggle]:checked')).map(el => el.dataset.colToggle);
            saveColumns(checked);
            applyColumnVisibility();
            triggerReactiveFilter();
        });
    });

    // Select All Columns
    selectAllColsBtn?.addEventListener('click', () => {
        const allCols = Array.from(document.querySelectorAll('[data-col-toggle]')).map(el => el.dataset.colToggle);
        saveColumns(allCols);
        applyColumnVisibility();
        triggerReactiveFilter();
    });

    // Clear Optional Columns
    clearOptionalColsBtn?.addEventListener('click', () => {
        saveColumns(MANDATORY_COLS);
        applyColumnVisibility();
        triggerReactiveFilter();
    });

    // Restore Default Columns
    restoreDefaultColsBtn?.addEventListener('click', () => {
        saveColumns(DEFAULT_COLS);
        applyColumnVisibility();
        triggerReactiveFilter();
    });

    // Reset Filters button (resets filter values without clearing column choices!)
    resetFiltersBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (!form) return;
        form.querySelectorAll('input:not([type="hidden"]), select').forEach(el => {
            el.value = '';
        });
        document.querySelectorAll('[name^="field_filters["]').forEach(el => {
            el.value = '';
        });
        triggerReactiveFilter();
    });

    // Prevent native form submission
    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        triggerReactiveFilter();
    });

    // Initial setup
    applyColumnVisibility();
    wirePaginationAndCallLinks();
    updateBulkToolbar();
})();
</script>
<script>
(() => {
  document.querySelectorAll('.dash-pipeline-strip, .hero-pipeline').forEach((strip) => {
    strip.addEventListener('wheel', (event) => {
      if (
        event.defaultPrevented
        || event.shiftKey
        || Math.abs(event.deltaY) <= Math.abs(event.deltaX)
        || strip.scrollWidth <= strip.clientWidth
      ) {
        return;
      }

      const beforeScrollLeft = strip.scrollLeft;
      const direction = getComputedStyle(strip).direction === 'rtl' ? -1 : 1;

      strip.scrollBy({
        left: event.deltaY * direction,
        behavior: 'auto',
      });

      if (strip.scrollLeft === beforeScrollLeft) {
        return;
      }

      event.preventDefault();
    }, { passive: false });
  });
})();
</script>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
</body>
</html>
