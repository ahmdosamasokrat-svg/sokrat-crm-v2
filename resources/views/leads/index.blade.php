@php
    $selectedStageFieldIds = $selectedStageFieldIds ?? [];
    $allStageFieldsGrouped = $allStageFieldsGrouped ?? collect();
    $availableStageFields = $availableStageFields ?? collect();
    $stageFieldFilters = $stageFieldFilters ?? [];
    $serverTheme = request('theme');
    $isDarkServer = $serverTheme === 'dark';
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="{{ $isDarkServer ? 'dark-mode' : '' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
(() => {
    try {
        const root = document.documentElement;
        if (window.self !== window.top && window.parent?.document?.documentElement) {
            const parentHtml = window.parent.document.documentElement;
            const isDark = parentHtml.classList.contains('dark-mode');
            root.classList.toggle('dark-mode', isDark);
            root.dataset.theme = parentHtml.dataset.theme || (isDark ? 'dark' : 'light');
            root.classList.add('is-embedded-popup');
            return;
        }
        const storageKey = 'sokrat.crm.theme';
        const saved = localStorage.getItem(storageKey);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = saved === 'dark' || (saved !== 'light' && prefersDark);
        root.classList.toggle('dark-mode', isDark);
        root.dataset.theme = saved || (prefersDark ? 'dark' : 'light');
    } catch (e) {}
})();
</script>
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
/* Filters Panel - Two-Level Clean Enterprise CRM Design */
.filter-panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 8px 12px;
  box-shadow: 0 3px 12px rgba(15, 23, 42, 0.03);
  margin-bottom: 16px;
}
/* Row 1: Primary Filter Bar (Desktop >= 1280px: Strictly One Horizontal Line) */
.filter-bar-primary {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  flex-wrap: nowrap;
}
.filter-field {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.filter-field.col-search {
  flex: 1 1 180px;
  min-width: 140px;
}
.filter-field.col-status {
  flex: 0 0 130px;
  width: 130px;
}
.filter-field.col-source {
  flex: 0 0 115px;
  width: 115px;
}
.filter-field.col-employee {
  flex: 0 0 130px;
  width: 130px;
}
.filter-field.col-followup {
  flex: 0 0 120px;
  width: 120px;
}
.filter-field.col-sort {
  flex: 0 0 115px;
  width: 115px;
}
.filter-field.col-fields {
  flex: 0 0 auto;
}
.filter-actions-col {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}
/* Secondary / Ghost Reset Button */
.reset-filters-btn {
  height: 40px;
  min-height: 40px;
  padding: 0 12px;
  border-radius: 9px;
  background: transparent;
  border: 1px solid var(--line);
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}
.reset-filters-btn:hover {
  background: var(--bg);
  color: var(--dark);
  border-color: var(--muted);
}
.filter-control.compact-select {
  height: 40px;
  min-height: 40px;
  font-size: 12px;
  font-weight: 700;
  padding: 0 8px;
  border-radius: 9px;
  cursor: pointer;
}
/* Row 2: Selected Dynamic Fields Strip (Strictly One Row with Native Scroll) */
.dynamic-filter-fields-bar {
  display: flex;
  flex-wrap: nowrap;
  gap: 8px;
  align-items: center;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed var(--line);
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
  padding-bottom: 3px;
}
.dynamic-filter-fields-bar[hidden] {
  display: none !important;
}
.dynamic-filter-fields-bar::-webkit-scrollbar {
  height: 4px;
}
.dynamic-filter-fields-bar::-webkit-scrollbar-thumb {
  background: var(--line);
  border-radius: 4px;
}
/* Compact Labeled Filter Pill */
.dynamic-filter-item {
  display: inline-flex;
  align-items: center;
  flex: 0 0 auto;
  gap: 6px;
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 3px 6px 3px 8px;
  height: 36px;
  box-sizing: border-box;
  white-space: nowrap;
}
.dynamic-filter-item label {
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.dynamic-filter-item input,
.dynamic-filter-item select {
  height: 28px;
  min-height: 28px;
  border: 1px solid var(--line);
  border-radius: 6px;
  padding: 0 8px;
  font-size: 12px;
  font-weight: 600;
  background: var(--card);
  color: var(--dark);
  outline: none;
  transition: border-color 0.15s ease;
}
.dynamic-filter-item input:focus,
.dynamic-filter-item select:focus {
  border-color: var(--primary, #3478f6);
}
.dynamic-input-text {
  width: 140px;
}
.dynamic-input-select {
  width: 125px;
}
.dynamic-input-bool {
  width: 100px;
}
.dynamic-input-date,
.dynamic-input-datetime {
  width: 130px;
}
.dynamic-input-number {
  width: 100px;
}
/* Choose Fields Popover Section Hierarchy */
.dynamic-picker-section-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 800;
  color: var(--dark);
  padding: 6px 4px 4px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  border-bottom: 1px solid var(--line);
  margin-bottom: 6px;
}
.dynamic-picker-section-title i {
  color: var(--primary, #3478f6);
  font-size: 12px;
}
.dynamic-picker-stage-title {
  font-size: 10px;
  font-weight: 800;
  color: var(--muted);
  padding: 3px 6px;
  border-radius: 5px;
  background: rgba(0, 0, 0, 0.02);
  margin: 6px 0 3px;
}
html.dark-mode .dynamic-picker-stage-title {
  background: rgba(255, 255, 255, 0.04);
}
@media (max-width: 1279px) {
  .filter-bar-primary {
    flex-wrap: wrap;
    gap: 8px;
  }
  .filter-field.col-search {
    flex: 1 1 200px;
  }
  .filter-field.col-status,
  .filter-field.col-source,
  .filter-field.col-employee,
  .filter-field.col-followup,
  .filter-field.col-sort {
    flex: 1 1 120px;
    width: auto;
  }
}
@media (max-width: 768px) {
  .filter-bar-primary {
    flex-wrap: wrap;
    gap: 8px;
  }
  .filter-field.col-search {
    width: 100% !important;
    flex: 1 1 100%;
  }
  .filter-field.col-status,
  .filter-field.col-source,
  .filter-field.col-employee,
  .filter-field.col-followup,
  .filter-field.col-sort {
    flex: 1 1 calc(50% - 4px);
    width: auto;
  }
  .filter-field.col-fields {
    flex: 1 1 auto;
  }
  .filter-actions-col {
    flex: 0 0 auto;
  }
}
@media (max-width: 430px) {
  .filter-field.col-fields {
    width: 100% !important;
  }
  .filter-actions-col {
    width: 100% !important;
  }
  .reset-filters-btn {
    width: 100%;
    justify-content: center;
  }
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
.filter-input-wrap.is-searching .filter-input-icon {
  animation: filter-spin 0.6s linear infinite;
  color: var(--red, #dc2637);
}
@keyframes filter-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.table-card {
  transition: opacity 0.2s ease;
}
.table-card.is-loading {
  opacity: 0.55;
  pointer-events: none;
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

/* Popup Mode */
body.kanban-leads-popup {
  background: transparent !important;
}
body.kanban-leads-popup .crm-app {
  display: block;
  min-height: auto;
}
body.kanban-leads-popup .crm-main {
  padding: 16px;
}
body.kanban-leads-popup .hero-pipeline {
  display: none !important;
}
</style>
</head>
<body class="{{ request()->boolean('kanban_popup') ? 'kanban-leads-popup' : '' }}">
<div class="crm-app leads-page">
@unless (request()->boolean('kanban_popup'))
    @include('partials.crm-sidebar')
@endunless

    <main class="crm-main">
        @php
            $leadTopActions = '';
            if (auth()->user()->can('leads.create')) {
                $leadTopActions .= '<a href="' . route('v2.leads.create') . '" class="btn primary"><i class="bi bi-plus-lg"></i> ' . __('crm.create_lead') . '</a>';
            }
        @endphp

@if (request()->boolean('kanban_popup'))
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--line);">
            <div>
                <h2 style="margin:0; font-size:18px; font-weight:900; color:var(--dark)"><i class="bi bi-people-fill" style="color:var(--red)"></i> {{ __('crm.view_leads') }}</h2>
                <p style="margin:4px 0 0; font-size:12px; color:var(--muted)"><span>{{ __('crm.total_leads') }}: {{ number_format($totalLeads) }}</span></p>
            </div>
            <button type="button" class="btn soft small" onclick="closePopupUtilityModal()" aria-label="{{ __('crm.close') }}">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
@else
        @include('partials.topbar', [
            'title' => __('crm.view_leads'),
            'subtitle' => '<span>' . __('crm.total_leads') . ': ' . number_format($totalLeads) . '</span>',
            'icon' => 'bi-people-fill',
            'actions' => $leadTopActions,
        ])
@endif

        @if (session('success'))
            <div style="padding:12px 16px;background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:16px;font-weight:700">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif

        <div id="statsAndHeroContainer">
            @include('leads.partials.stats-and-hero')
        </div>
        <!-- FILTERS PANEL -->
        <section class="filter-panel">
            <form method="GET" action="{{ route('v2.leads') }}" id="leadFilters">
                <input
                    type="hidden"
                    name="field_ids"
                    value="{{ implode(',', $selectedStageFieldIds) }}"
                    data-selected-dynamic-field-ids
                >
                <input
                    type="hidden"
                    name="columns"
                    id="activeColumnsInput"
                    value="{{ implode(',', $visibleColumnKeys ?? []) }}"
                >
                @if (!empty($filters['stage']))
                    <input type="hidden" name="stage" value="{{ $filters['stage'] }}">
                @endif

                <!-- PRIMARY ONE-ROW FILTER BAR (Desktop >= 1280px: Exactly 1 Row) -->
                <div class="filter-bar-primary">
                    <!-- Quick Search Input (flex-grow) -->
                    <div class="filter-field col-search">
                        <div class="filter-input-wrap">
                            <i class="bi bi-search filter-input-icon"></i>
                            <input
                                type="text"
                                id="searchQuery"
                                name="q"
                                value="{{ $filters['q'] }}"
                                class="filter-control with-icon"
                                placeholder="{{ __('crm.lead_search_placeholder') }}"
                                aria-label="{{ __('crm.search_query_label') }}"
                            >
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-field col-status">
                        <select id="leadStatus" name="status" class="filter-control compact-select" aria-label="{{ __('crm.status') }}">
                            <option value="">{{ __('crm.all_states') }}</option>
                            @foreach ($statuses as $status)
                                <option
                                    value="{{ $status->code }}"
                                    data-stage-id="{{ $status->pipeline_stage_id }}"
                                    @selected($filters['status'] === $status->code)
                                >
                                    {{ $status->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Source Filter -->
                    <div class="filter-field col-source">
                        <select id="leadSource" name="source" class="filter-control compact-select" aria-label="{{ __('crm.source') }}">
                            <option value="">{{ __('crm.all_sources') }}</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source }}" @selected($filters['source'] === $source)>
                                    {{ $source }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    <div class="filter-field col-employee">
                        <select id="leadEmployee" name="employee" class="filter-control compact-select" aria-label="{{ __('crm.assigned_employee') }}">
                            <option value="">{{ __('كل الموظفين') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee }}" @selected($filters['employee'] === $employee)>
                                    {{ $employee }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Follow Up Filter -->
                    <div class="filter-field col-followup">
                        <select id="leadFollowUp" name="follow_up" class="filter-control compact-select" aria-label="{{ __('crm.next_followup') }}">
                            <option value="">{{ __('crm.all_appointments') }}</option>
                            <option value="today" @selected($filters['follow_up'] === 'today')>{{ __('اليوم') }}</option>
                            <option value="upcoming" @selected($filters['follow_up'] === 'upcoming')>{{ __('crm.upcoming') }}</option>
                            <option value="overdue" @selected($filters['follow_up'] === 'overdue')>{{ __('crm.overdue') }}</option>
                            <option value="none" @selected($filters['follow_up'] === 'none')>{{ __('crm.no_date') }}</option>
                        </select>
                    </div>

                    <!-- Sort Filter -->
                    <div class="filter-field col-sort">
                        <select id="leadSort" name="sort" class="filter-control compact-select" aria-label="{{ __('crm.sort') }}">
                            <option value="latest" @selected($filters['sort'] === 'latest')>{{ __('crm.newest_first') }}</option>
                            <option value="oldest" @selected($filters['sort'] === 'oldest')>{{ __('crm.oldest_first') }}</option>
                            <option value="name" @selected($filters['sort'] === 'name')>{{ __('crm.by_name') }}</option>
                            <option value="followup" @selected($filters['sort'] === 'followup')>{{ __('crm.by_followup') }}</option>
                        </select>
                    </div>

                    <!-- Choose Fields Dropdown / Popover -->
                    <div class="filter-field col-fields">
                        <div class="dynamic-field-picker" data-dynamic-field-picker>
                            <button
                                class="btn small dynamic-field-picker-toggle"
                                type="button"
                                data-dynamic-field-picker-toggle
                                aria-expanded="false"
                                aria-controls="dynamicFieldPickerMenu"
                                title="{{ __('crm.choose_filter_fields') }}"
                            >
                                <i class="bi bi-sliders"></i>
                                <span>{{ __('crm.choose_filter_fields') }}</span>
                                <span class="dynamic-field-picker-count" data-dynamic-field-count aria-live="polite" aria-atomic="true">
                                    {{ count($selectedStageFieldIds) }}
                                </span>
                            </button>

                            <div
                                class="dynamic-field-picker-menu"
                                id="dynamicFieldPickerMenu"
                                data-dynamic-field-picker-menu
                                role="group"
                                aria-labelledby="dynamicFieldPickerLabel"
                                hidden
                            >
                                <div class="dynamic-picker-scroll">
                                    <!-- SECTION 1: STANDARD COLUMNS -->
                                    <div class="dynamic-picker-stage-group" data-picker-standard-group style="border-bottom:1px solid var(--line);padding-bottom:10px;margin-bottom:10px;">
                                        <div class="dynamic-picker-section-title">
                                            <i class="bi bi-layout-three-columns"></i>
                                            <span>{{ __('crm.standard_columns') ?? 'الأعمدة الأساسية' }}</span>
                                        </div>

                                        {{-- Customer (Mandatory) --}}
                                        <label class="dynamic-field-option is-mandatory" title="{{ __('crm.mandatory_column') ?? 'عمود إجباري' }}" style="opacity:0.85;cursor:default;">
                                            <input type="checkbox" checked disabled data-mandatory-column="core:customer">
                                            <i class="bi bi-person-badge" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.client') }}</span>
                                                <small style="color:var(--muted);font-size:10px">({{ __('crm.mandatory') ?? 'إجباري' }})</small>
                                            </span>
                                        </label>

                                        {{-- Contact Data (Phone / Email) --}}
                                        <label class="dynamic-field-option">
                                            <input
                                                type="checkbox"
                                                value="core:contact"
                                                data-standard-column-option
                                                @checked(!empty($standardColumnsMeta['core:contact']['visible']))
                                            >
                                            <i class="bi bi-telephone" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.contact_data') }}</span>
                                            </span>
                                        </label>

                                        {{-- Company / Source --}}
                                        <label class="dynamic-field-option">
                                            <input
                                                type="checkbox"
                                                value="core:company_source"
                                                data-standard-column-option
                                                @checked(!empty($standardColumnsMeta['core:company_source']['visible']))
                                            >
                                            <i class="bi bi-building" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.company_source') }}</span>
                                            </span>
                                        </label>

                                        {{-- Status (Mandatory) --}}
                                        <label class="dynamic-field-option is-mandatory" title="{{ __('crm.mandatory_column') ?? 'عمود إجباري' }}" style="opacity:0.85;cursor:default;">
                                            <input type="checkbox" checked disabled data-mandatory-column="core:status">
                                            <i class="bi bi-tag" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.current_status') }}</span>
                                                <small style="color:var(--muted);font-size:10px">({{ __('crm.mandatory') ?? 'إجباري' }})</small>
                                            </span>
                                        </label>

                                        {{-- Assigned Employee --}}
                                        <label class="dynamic-field-option">
                                            <input
                                                type="checkbox"
                                                value="core:employee"
                                                data-standard-column-option
                                                @checked(!empty($standardColumnsMeta['core:employee']['visible']))
                                            >
                                            <i class="bi bi-person-check" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.responsible_employee') }}</span>
                                            </span>
                                        </label>

                                        {{-- Next Follow-up --}}
                                        <label class="dynamic-field-option">
                                            <input
                                                type="checkbox"
                                                value="core:next_followup"
                                                data-standard-column-option
                                                @checked(!empty($standardColumnsMeta['core:next_followup']['visible']))
                                            >
                                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.next_followup') }}</span>
                                            </span>
                                        </label>

                                        {{-- Created Date --}}
                                        <label class="dynamic-field-option">
                                            <input
                                                type="checkbox"
                                                value="core:created_at"
                                                data-standard-column-option
                                                @checked(!empty($standardColumnsMeta['core:created_at']['visible']))
                                            >
                                            <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                            <span class="dynamic-field-option-copy">
                                                <span>{{ __('crm.created_date') }}</span>
                                            </span>
                                        </label>
                                    </div>

                                    <!-- SECTION 2: STAGE QUESTIONS / FIELDS -->
                                    <div class="dynamic-picker-stage-group" data-picker-stage-fields-root>
                                        <div class="dynamic-picker-section-title" id="stageQuestionsSectionTitle">
                                            <i class="bi bi-diagram-3"></i>
                                            <span data-stage-questions-title>
                                                @if (!empty($contextStage))
                                                    {{ __('crm.stage_questions_for', ['stage' => $contextStage->localizedName()]) }}
                                                @else
                                                    {{ __('crm.stage_fields') }}
                                                @endif
                                            </span>
                                        </div>

                                        <div class="dynamic-picker-stage-groups-list" data-stage-groups-container>
                                            @foreach ($allStageFieldsGrouped as $stageGroupName => $stageFieldsInGroup)
                                                @php
                                                    $firstField = $stageFieldsInGroup->first();
                                                    $stageIdForGroup = $firstField?->pipeline_stage_id;
                                                    $isContextMatch = !empty($contextStage) && (int) $contextStage->id === (int) $stageIdForGroup;
                                                @endphp
                                                <div
                                                    class="dynamic-picker-stage-subgroup"
                                                    data-picker-stage-group
                                                    data-group-stage-id="{{ $stageIdForGroup }}"
                                                    data-stage-name="{{ $stageGroupName }}"
                                                    style="margin-bottom:8px; {{ (!empty($contextStage) && !$isContextMatch) ? 'display:none;' : '' }}"
                                                >
                                                    <div
                                                        class="dynamic-picker-stage-title"
                                                        data-stage-subgroup-title
                                                        style="{{ !empty($contextStage) ? 'display:none;' : '' }}"
                                                    >
                                                        {{ app()->getLocale() === 'en' ? 'Stage: ' . $stageGroupName : 'مرحلة ' . $stageGroupName }}
                                                    </div>
                                                    @foreach ($stageFieldsInGroup as $field)
                                                        @php
                                                            $fieldIcon = match ($field->type) {
                                                                 'number' => 'bi-123',
                                                                 'date', 'datetime' => 'bi-calendar3',
                                                                 'select', 'multiselect' => 'bi-list-check',
                                                                 'checkbox' => 'bi-check2-square',
                                                                 'email' => 'bi-envelope',
                                                                 'tel' => 'bi-telephone',
                                                                 default => 'bi-input-cursor-text',
                                                             };
                                                        @endphp
                                                        <label class="dynamic-field-option" data-field-stage-id="{{ $field->pipeline_stage_id }}">
                                                            <input
                                                                type="checkbox"
                                                                value="{{ $field->id }}"
                                                                data-field-stage-id="{{ $field->pipeline_stage_id }}"
                                                                data-dynamic-field-option
                                                                @checked(in_array((int) $field->id, $selectedStageFieldIds, true))
                                                            >
                                                            <i class="bi {{ $fieldIcon }}" aria-hidden="true"></i>
                                                            <span class="dynamic-field-option-copy">
                                                                <span>{{ $field->localizedLabel() }}</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                <div class="dynamic-field-picker-footer">
                                    <div class="dynamic-field-picker-actions" style="width:100%; display:flex; justify-content:space-between; align-items:center; gap:6px; flex-wrap:wrap;">
                                        <button class="dynamic-field-clear" type="button" data-restore-default-columns style="color:var(--primary);font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span>{{ __('crm.restore_default_columns') ?? 'الأعمدة الافتراضية' }}</span>
                                        </button>
                                        <div style="display:inline-flex; gap:6px;">
                                            <button class="dynamic-field-clear dynamic-field-select-all" type="button" data-select-all-dynamic-fields>
                                                {{ __('crm.select_all_fields') }}
                                            </button>
                                            <button class="dynamic-field-clear" type="button" data-clear-dynamic-fields>
                                                {{ __('crm.clear_field_selection') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <!-- Reset Action Button (Secondary / Ghost Button at End of Row 1) -->
                    <div class="filter-actions-col">
                        <button
                            type="button"
                            class="reset-filters-btn"
                            id="resetFiltersBtn"
                            title="{{ __('crm.reset') }}"
                            style="{{ empty($activeQuery) ? 'display:none;' : '' }}"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>{{ __('crm.reset') }}</span>
                        </button>
                    </div>
                </div>

                <!-- DYNAMIC STAGE FIELD CONTROLS (Row 2: Compact Single-Row Strip with Native Scroll) -->
                <div
                    class="dynamic-filter-fields-bar"
                    id="dynamicFilterFieldsBar"
                    data-dynamic-filter-fields
                    style="{{ count($selectedStageFieldIds) === 0 ? 'display:none;' : '' }}"
                >
                    @foreach ($availableStageFields as $field)
                        @php
                            $fieldId = (int) $field->id;
                            $fieldSelected = in_array($fieldId, $selectedStageFieldIds, true);
                            $fieldValue = (string) ($stageFieldFilters[$fieldId] ?? '');
                            $fieldInputId = 'stageFieldFilter_' . $fieldId;
                            $isTextarea = $field->type === 'textarea';
                        @endphp
                        <div
                            class="dynamic-filter-item"
                            data-dynamic-filter-field="{{ $fieldId }}"
                            data-field-stage-id="{{ $field->pipeline_stage_id }}"
                            style="{{ ! $fieldSelected ? 'display:none;' : '' }}"
                        >
                            <label for="{{ $fieldInputId }}">
                                {{ $field->localizedLabel() }}
                            </label>

                            @if (in_array($field->type, ['select', 'multiselect'], true))
                                <select
                                    id="{{ $fieldInputId }}"
                                    name="field_filters[{{ $fieldId }}]"
                                    class="filter-control compact-select dynamic-input-select"
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
                                    class="filter-control compact-select dynamic-input-bool"
                                    @disabled(! $fieldSelected)
                                >
                                    <option value="">{{ __('crm.all_field_values') }}</option>
                                    <option value="1" @selected($fieldValue === '1')>{{ __('crm.yes') }}</option>
                                    <option value="0" @selected($fieldValue === '0')>{{ __('crm.no') }}</option>
                                </select>
                            @elseif ($isTextarea)
                                <input
                                    id="{{ $fieldInputId }}"
                                    name="field_filters[{{ $fieldId }}]"
                                    type="text"
                                    value="{{ $fieldValue }}"
                                    class="filter-control dynamic-input-text"
                                    placeholder="{{ __('crm.search_in') ?? 'بحث في' }} {{ $field->localizedLabel() }}"
                                    @disabled(! $fieldSelected)
                                >
                            @else
                                @php
                                    $filterInputType = match ($field->type) {
                                        'number' => 'number',
                                        'date' => 'date',
                                        'datetime' => 'datetime-local',
                                        default => 'text',
                                    };
                                @endphp
                                <input
                                    id="{{ $fieldInputId }}"
                                    name="field_filters[{{ $fieldId }}]"
                                    type="{{ $filterInputType }}"
                                    @if ($field->type === 'number') step="any" @endif
                                    value="{{ $fieldValue }}"
                                    class="filter-control dynamic-input-{{ $field->type }}"
                                    placeholder="{{ $field->localizedPlaceholder() ?: __('crm.enter_filter_value') }}"
                                    @disabled(! $fieldSelected)
                                >
                            @endif
                        </div>
                    @endforeach
                </div>
        </section>

        <!-- CUSTOMERS TABLE -->
        <section class="table-card" id="leadsTableCard">
            @include('leads.partials.table')
        </section>
    </main>
</div>

<script>
(() => {
    // Dynamic field picker & Context-Aware Stage Fields
    const fieldPicker = document.querySelector('[data-dynamic-field-picker]');
    const fieldPickerToggle = fieldPicker?.querySelector('[data-dynamic-field-picker-toggle]');
    const fieldPickerMenu = fieldPicker?.querySelector('[data-dynamic-field-picker-menu]');
    const fieldCount = fieldPicker?.querySelector('[data-dynamic-field-count]');
    const fieldOptions = Array.from(document.querySelectorAll('[data-dynamic-field-option]'));
    const dynamicFieldsBar = document.getElementById('dynamicFilterFieldsBar');
    const dynamicFieldItems = Array.from(document.querySelectorAll('.dynamic-filter-item'));
    const selectedFieldIdsInput = document.querySelector('[data-selected-dynamic-field-ids]');
    const clearDynamicFields = fieldPicker?.querySelector('[data-clear-dynamic-fields]');
    const selectAllDynamicFields = fieldPicker?.querySelector('[data-select-all-dynamic-fields]');
    const statusSelect = document.getElementById('leadStatus');

    const closeFieldPicker = () => {
        if (!fieldPickerMenu || !fieldPickerToggle) return;
        fieldPickerMenu.hidden = true;
        fieldPickerToggle.setAttribute('aria-expanded', 'false');
    };

    const getFocusedStageId = () => {
        if (!statusSelect || statusSelect.selectedIndex < 0) return null;
        const opt = statusSelect.options[statusSelect.selectedIndex];
        return (opt && opt.dataset.stageId) ? String(opt.dataset.stageId) : null;
    };

    const updateContextAwareFields = (isStatusSwitch = false) => {
        const focusedStageId = getFocusedStageId();
        const stageQuestionsTitle = document.querySelector('[data-stage-questions-title]');
        const stageSubgroupTitles = document.querySelectorAll('[data-stage-subgroup-title]');

        if (focusedStageId) {
            // SINGLE STAGE MODE:
            // 1. Update section title to "أسئلة مرحلة {stageName}" or "{stageName} Questions"
            let currentStageName = '';
            if (statusSelect && statusSelect.selectedIndex >= 0) {
                const opt = statusSelect.options[statusSelect.selectedIndex];
                currentStageName = opt?.text?.trim() || '';
            }

            if (stageQuestionsTitle) {
                const isEn = document.documentElement.lang === 'en';
                stageQuestionsTitle.textContent = isEn
                    ? (currentStageName ? currentStageName + ' Questions' : 'Stage Questions')
                    : (currentStageName ? 'أسئلة مرحلة ' + currentStageName : 'أسئلة المرحلة');
            }

            // Hide subgroup titles so stage name is not repeated
            stageSubgroupTitles.forEach(t => { t.style.display = 'none'; });

            // Show only the subgroup matching focusedStageId
            document.querySelectorAll('[data-picker-stage-group]').forEach(group => {
                const groupStageId = String(group.dataset.groupStageId || '');
                const hasStageField = Array.from(group.querySelectorAll('[data-field-stage-id]'))
                    .some(el => String(el.dataset.fieldStageId) === focusedStageId);
                const isMatch = (groupStageId === focusedStageId) || hasStageField;
                group.style.display = isMatch ? 'block' : 'none';
            });

            fieldOptions.forEach(opt => {
                const isMatch = String(opt.dataset.fieldStageId) === focusedStageId;
                const label = opt.closest('.dynamic-field-option');
                if (label) label.style.display = isMatch ? 'flex' : 'none';
                if (!isMatch && isStatusSwitch) {
                    opt.checked = false; // Prune stale stage fields from previous stage!
                }
            });

            const selectedStageIds = new Set(
                fieldOptions.filter(opt => opt.checked && String(opt.dataset.fieldStageId) === focusedStageId)
                    .map(opt => opt.value)
            );

            // 2. In dynamicFilterFieldsBar:
            dynamicFieldItems.forEach(item => {
                const itemStageId = String(item.dataset.fieldStageId || '');
                const isMatch = itemStageId === focusedStageId && selectedStageIds.has(item.dataset.dynamicFilterField || '');
                item.style.display = isMatch ? 'inline-flex' : 'none';
                item.querySelectorAll('input, select, textarea').forEach(c => {
                    c.disabled = !isMatch;
                    if (!isMatch && itemStageId !== focusedStageId) {
                        c.value = ''; // Discard stale values from previous stage
                    }
                });
            });

            const activeCount = selectedStageIds.size;
            if (fieldCount) fieldCount.textContent = String(activeCount);
            if (selectedFieldIdsInput) selectedFieldIdsInput.value = Array.from(selectedStageIds).join(',');
            if (dynamicFieldsBar) dynamicFieldsBar.style.display = activeCount > 0 ? 'flex' : 'none';
        } else {
            // ALL STATUSES MODE:
            if (stageQuestionsTitle) {
                const isEn = document.documentElement.lang === 'en';
                stageQuestionsTitle.textContent = isEn ? 'Stage Questions' : 'أسئلة المرحلة';
            }

            // Show subgroup titles (مرحلة جديد, مرحلة لم يرد, etc.)
            stageSubgroupTitles.forEach(t => { t.style.display = 'block'; });

            // Show all stage groups
            document.querySelectorAll('[data-picker-stage-group]').forEach(group => {
                group.style.display = 'block';
            });

            fieldOptions.forEach(opt => {
                const label = opt.closest('.dynamic-field-option');
                if (label) label.style.display = 'flex';
            });

            const selectedIds = new Set(
                fieldOptions.filter(opt => opt.checked).map(opt => opt.value)
            );

            dynamicFieldItems.forEach(item => {
                const selected = selectedIds.has(item.dataset.dynamicFilterField || '');
                item.style.display = selected ? 'inline-flex' : 'none';
                item.querySelectorAll('input, select, textarea').forEach(c => {
                    c.disabled = !selected;
                    if (!selected) c.value = '';
                });
            });

            const activeCount = selectedIds.size;
            if (fieldCount) fieldCount.textContent = String(activeCount);
            if (selectedFieldIdsInput) selectedFieldIdsInput.value = Array.from(selectedIds).join(',');
            if (dynamicFieldsBar) dynamicFieldsBar.style.display = activeCount > 0 ? 'flex' : 'none';
        }
    };

    fieldPickerToggle?.addEventListener('click', () => {
        if (!fieldPickerMenu) return;
        const willOpen = fieldPickerMenu.hidden;
        fieldPickerMenu.hidden = !willOpen;
        fieldPickerToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    const activeColumnsInput = document.getElementById('activeColumnsInput');
    const standardColumnOptions = Array.from(document.querySelectorAll('[data-standard-column-option]'));
    const restoreDefaultsBtn = document.querySelector('[data-restore-default-columns]');

    const serializeActiveColumns = () => {
        if (!activeColumnsInput) return;
        const cols = [];
        cols.push('core:customer');
        standardColumnOptions.forEach(opt => {
            if (opt.checked) cols.push(opt.value);
        });
        if (!cols.includes('core:status')) {
            const idx = cols.indexOf('core:company_source');
            if (idx !== -1) {
                cols.splice(idx + 1, 0, 'core:status');
            } else {
                cols.push('core:status');
            }
        }
        const focusedStageId = getFocusedStageId();
        fieldOptions.forEach(opt => {
            if (opt.checked) {
                if (!focusedStageId || String(opt.dataset.fieldStageId) === focusedStageId) {
                    cols.push('stage_field:' + opt.value);
                }
            }
        });
        if (!cols.includes('core:actions')) {
            cols.push('core:actions');
        }
        activeColumnsInput.value = cols.join(',');
    };

    standardColumnOptions.forEach(opt => {
        opt.addEventListener('change', () => {
            serializeActiveColumns();
            performLiveFilter(null, { resetPage: false });
        });
    });

    fieldOptions.forEach(option => option.addEventListener('change', () => {
        updateContextAwareFields(false);
        serializeActiveColumns();
        performLiveFilter(null, { resetPage: false });
    }));

    selectAllDynamicFields?.addEventListener('click', () => {
        const focusedStageId = getFocusedStageId();
        fieldOptions.forEach(option => {
            const label = option.closest('.dynamic-field-option');
            const isMatch = !focusedStageId || String(option.dataset.fieldStageId) === focusedStageId;
            if (isMatch && (!label || label.style.display !== 'none')) {
                option.checked = true;
            }
        });
        updateContextAwareFields(false);
        serializeActiveColumns();
        performLiveFilter(null, { resetPage: false });
    });

    clearDynamicFields?.addEventListener('click', () => {
        const focusedStageId = getFocusedStageId();
        fieldOptions.forEach(option => {
            const isMatch = !focusedStageId || String(option.dataset.fieldStageId) === focusedStageId;
            if (isMatch) {
                option.checked = false;
            }
        });
        dynamicFieldItems.forEach(field => {
            const itemStageId = String(field.dataset.fieldStageId || '');
            if (!focusedStageId || itemStageId === focusedStageId) {
                field.querySelectorAll('input, select, textarea').forEach(inp => { inp.value = ''; });
            }
        });
        updateContextAwareFields(false);
        serializeActiveColumns();
        performLiveFilter(null, { resetPage: false });
    });

    restoreDefaultsBtn?.addEventListener('click', () => {
        standardColumnOptions.forEach(opt => { opt.checked = true; });
        fieldOptions.forEach(opt => { opt.checked = false; });
        if (activeColumnsInput) activeColumnsInput.value = 'default';
        updateContextAwareFields(false);
        serializeActiveColumns();
        performLiveFilter(null, { resetPage: false });
    });

    document.addEventListener('click', event => {
        if (fieldPicker && !fieldPicker.contains(event.target)) closeFieldPicker();
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || fieldPickerMenu?.hidden) return;
        closeFieldPicker();
        fieldPickerToggle?.focus();
    });

    updateContextAwareFields(false);

    // Reactive Live Filter Engine
    const filterForm = document.getElementById('leadFilters');
    const searchInput = document.getElementById('searchQuery');
    const resetBtn = document.getElementById('resetFiltersBtn');
    const tableCard = document.getElementById('leadsTableCard');
    const searchWrap = document.querySelector('.filter-input-wrap');
    let currentAbortController = null;
    let debounceTimer = null;

    const buildCleanUrl = (customUrl = null) => {
        let url;
        if (customUrl) {
            url = new URL(customUrl, window.location.origin);
        } else {
            url = new URL(filterForm ? filterForm.action : window.location.href, window.location.origin);
            if (filterForm) {
                const formData = new FormData(filterForm);
                for (const [key, value] of formData.entries()) {
                    const valStr = typeof value === 'string' ? value.trim() : '';
                    if (valStr !== '' && !(key === 'sort' && valStr === 'latest')) {
                        url.searchParams.append(key, valStr);
                    }
                }
            }
        }
        return url;
    };

    const performLiveFilter = async (customUrl = null, options = {}) => {
        if (!filterForm || !tableCard) return;

        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();

        const targetUrl = buildCleanUrl(customUrl);
        if (options.resetPage) {
            targetUrl.searchParams.delete('page');
        }

        // Loading feedback: opacity & aria-busy (no blocking modal)
        tableCard.classList.add('is-loading');
        tableCard.setAttribute('aria-busy', 'true');
        if (searchWrap) searchWrap.classList.add('is-searching');

        try {
            const reqUrl = new URL(targetUrl.toString());
            reqUrl.searchParams.set('partial', 'table');

            const response = await fetch(reqUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: currentAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const data = await response.json();

            if (data.table_html !== undefined) {
                tableCard.innerHTML = data.table_html;
            }

            if (data.stats_html !== undefined) {
                const statsContainer = document.getElementById('statsAndHeroContainer');
                if (statsContainer) {
                    statsContainer.innerHTML = data.stats_html;
                }
            }

            // Sync total leads counter in topbar subtitle
            const totalLeadsCounter = document.querySelector('.topbar p span, .crm-topbar-title p span');
            if (totalLeadsCounter && data.total_leads !== undefined) {
                totalLeadsCounter.innerHTML = '<span>{{ __("crm.total_leads") }}: ' + Number(data.total_leads).toLocaleString() + '</span>';
            }

            // Reset button visibility: only show when filters differ from default
            const filterParamKeys = ['q', 'status', 'source', 'employee', 'follow_up', 'stage'];
            const hasPrimaryFilter = filterParamKeys.some(k => targetUrl.searchParams.has(k) && targetUrl.searchParams.get(k) !== '');
            const hasSortFilter = targetUrl.searchParams.has('sort') && targetUrl.searchParams.get('sort') !== 'latest';
            const hasDynamicFilter = Array.from(targetUrl.searchParams.keys()).some(k => k.startsWith('field_filters[') && targetUrl.searchParams.get(k) !== '');

            const hasActiveFilters = hasPrimaryFilter || hasSortFilter || hasDynamicFilter;
            if (resetBtn) {
                resetBtn.style.display = hasActiveFilters ? 'inline-flex' : 'none';
            }

            // URL update (clean params)
            const cleanBrowserUrl = new URL(targetUrl.toString());
            cleanBrowserUrl.searchParams.delete('partial');
            if (!options.isPopstate) {
                if (options.replaceState) {
                    window.history.replaceState(null, '', cleanBrowserUrl.toString());
                } else {
                    window.history.pushState(null, '', cleanBrowserUrl.toString());
                }
            }

            bindTableEvents();
            bindStageLinks();
        } catch (err) {
            if (err.name === 'AbortError') {
                return;
            }
            console.error('Live filter error:', err);
            showFilterError();
        } finally {
            tableCard.classList.remove('is-loading');
            tableCard.removeAttribute('aria-busy');
            if (searchWrap) searchWrap.classList.remove('is-searching');
        }
    };

    const showFilterError = () => {
        const errorMsg = '{{ app()->getLocale() === "en" ? "Unable to update results. Please try again." : "تعذر تحديث النتائج. حاول مرة أخرى." }}';
        let banner = document.getElementById('filterErrorBanner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'filterErrorBanner';
            banner.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#ef4444;color:#fff;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;box-shadow:0 8px 24px rgba(0,0,0,0.25);z-index:9999;transition:opacity 0.2s ease;';
            document.body.appendChild(banner);
        }
        banner.textContent = errorMsg;
        banner.style.opacity = '1';
        clearTimeout(banner._timer);
        banner._timer = setTimeout(() => {
            banner.style.opacity = '0';
            setTimeout(() => banner.remove(), 200);
        }, 3500);
    };

    const scheduleLiveFilter = (delay = 350, options = {}) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performLiveFilter(null, options);
        }, delay);
    };

    // Select & Date inputs: immediate refresh
    filterForm?.addEventListener('change', (e) => {
        const target = e.target;
        if (target.hasAttribute('data-dynamic-field-option')) return;
        if (target === statusSelect) {
            updateContextAwareFields(true);
            serializeActiveColumns();
            clearTimeout(debounceTimer);
            performLiveFilter(null, { resetPage: true });
            return;
        }
        if (target.matches('select, input[type="date"], input[type="datetime-local"], input[type="checkbox"]')) {
            clearTimeout(debounceTimer);
            performLiveFilter(null, { resetPage: true });
        }
    });

    // Text inputs & search: debounced 350ms
    filterForm?.addEventListener('input', (e) => {
        const target = e.target;
        if (target === searchInput || target.matches('[name^="field_filters["]')) {
            scheduleLiveFilter(350, { resetPage: true, replaceState: true });
        }
    });

    searchInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            performLiveFilter(null, { resetPage: true });
        }
    });

    // Prevent normal form submission
    filterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        clearTimeout(debounceTimer);
        performLiveFilter(null, { resetPage: true });
    });

    // Reset Button
    resetBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (searchInput) searchInput.value = '';
        filterForm?.querySelectorAll('select').forEach(sel => {
            sel.value = sel.id === 'leadSort' ? 'latest' : '';
        });
        filterForm?.querySelectorAll('input[name="stage"]').forEach(el => el.remove());
        dynamicFieldItems.forEach(item => {
            item.querySelectorAll('input, select, textarea').forEach(inp => { inp.value = ''; });
        });
        updateContextAwareFields(false);
        const baseUrl = new URL(filterForm?.action || window.location.pathname, window.location.origin);
        if (activeColumnsInput && activeColumnsInput.value && activeColumnsInput.value !== 'default') {
            baseUrl.searchParams.set('columns', activeColumnsInput.value);
        }
        performLiveFilter(baseUrl.toString(), { resetPage: true });
    });

    const bindTableEvents = () => {

        // Intercept pagination clicks for smooth AJAX pagination
        document.querySelectorAll('#leadsTableCard nav a, #leadsTableCard .pagination a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                performLiveFilter(link.href, { resetPage: false });
                tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        document.querySelectorAll('.js-call-followup').forEach(link => {
            link.addEventListener('click', () => {
                const callHref = link.dataset.callHref;
                if (!callHref || link.target !== '_blank') return;
                window.setTimeout(() => { window.location.href = callHref; }, 120);
            });
        });

        // Bulk Actions Selection & Forms
        const selectAll = document.getElementById('selectAllLeads');
        const checkboxes = document.querySelectorAll('.lead-select-checkbox');
        const bulkBar = document.getElementById('bulkActionsBar');
        const countEl = document.getElementById('bulkSelectedCount');
        const clearBtn = document.getElementById('bulkClearSelection');
        const assignInputs = document.getElementById('bulkAssignHiddenInputs');
        const deleteInputs = document.getElementById('bulkDeleteHiddenInputs');

        const updateBulkState = () => {
            const checkedBoxes = Array.from(document.querySelectorAll('.lead-select-checkbox:checked'));
            const count = checkedBoxes.length;
            const total = document.querySelectorAll('.lead-select-checkbox').length;

            if (selectAll) {
                selectAll.checked = total > 0 && count === total;
                selectAll.indeterminate = count > 0 && count < total;
            }

            if (countEl) {
                countEl.textContent = count;
            }

            if (bulkBar) {
                bulkBar.style.display = count > 0 ? 'flex' : 'none';
            }

            if (assignInputs) {
                assignInputs.innerHTML = '';
                checkedBoxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'lead_ids[]';
                    input.value = cb.value;
                    assignInputs.appendChild(input);
                });
            }

            if (deleteInputs) {
                deleteInputs.innerHTML = '';
                checkedBoxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'lead_ids[]';
                    input.value = cb.value;
                    deleteInputs.appendChild(input);
                });
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.lead-select-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                });
                updateBulkState();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkState);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                document.querySelectorAll('.lead-select-checkbox').forEach(cb => {
                    cb.checked = false;
                });
                if (selectAll) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                }
                updateBulkState();
            });
        }

        updateBulkState();
    };

    const bindStageLinks = () => {
        document.querySelectorAll('.hero-stage, .stats-grid .stat-card').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const linkUrl = new URL(link.href, window.location.origin);
                const stageParam = linkUrl.searchParams.get('stage') || '';
                let stageInput = filterForm ? filterForm.querySelector('input[name="stage"]') : null;
                if (!stageInput && stageParam !== '' && filterForm) {
                    stageInput = document.createElement('input');
                    stageInput.type = 'hidden';
                    stageInput.name = 'stage';
                    filterForm.appendChild(stageInput);
                }
                if (stageInput) {
                    stageInput.value = stageParam;
                }
                performLiveFilter(link.href, { resetPage: true });
            });
        });
    };

    const restoreFormFromUrl = () => {
        const params = new URLSearchParams(window.location.search);
        if (searchInput) searchInput.value = params.get('q') || '';
        const statusSelect = document.getElementById('leadStatus');
        if (statusSelect) statusSelect.value = params.get('status') || '';
        const sourceSelect = document.getElementById('leadSource');
        if (sourceSelect) sourceSelect.value = params.get('source') || '';
        const employeeSelect = document.getElementById('leadEmployee');
        if (employeeSelect) employeeSelect.value = params.get('employee') || '';
        const followUpSelect = document.getElementById('leadFollowUp');
        if (followUpSelect) followUpSelect.value = params.get('follow_up') || '';
        const sortSelect = document.getElementById('leadSort');
        if (sortSelect) sortSelect.value = params.get('sort') || 'latest';

        const stageParam = params.get('stage');
        let stageInput = filterForm?.querySelector('input[name="stage"]');
        if (stageParam) {
            if (!stageInput) {
                stageInput = document.createElement('input');
                stageInput.type = 'hidden';
                stageInput.name = 'stage';
                filterForm.appendChild(stageInput);
            }
            stageInput.value = stageParam;
        } else if (stageInput) {
            stageInput.remove();
        }

        const columnsParam = params.get('columns');
        if (columnsParam) {
            if (columnsParam === 'default') {
                standardColumnOptions.forEach(opt => { opt.checked = true; });
            } else {
                const colKeys = new Set(columnsParam.split(',').map(s => s.trim()));
                standardColumnOptions.forEach(opt => {
                    opt.checked = colKeys.has(opt.value);
                });
            }
            if (activeColumnsInput) activeColumnsInput.value = columnsParam;
        }

        // Restore dynamic fields
        const fieldIdsStr = params.get('field_ids') || '';
        const activeIds = new Set(fieldIdsStr ? fieldIdsStr.split(',').map(s => s.trim()) : []);
        fieldOptions.forEach(opt => {
            opt.checked = activeIds.has(opt.value);
        });
        updateContextAwareFields(false);
        params.forEach((value, key) => {
            const match = key.match(/^field_filters\[(\d+)\]$/);
            if (match) {
                const fieldInput = document.getElementById('stageFieldFilter_' + match[1]);
                if (fieldInput) fieldInput.value = value;
            }
        });
    };

    window.addEventListener('popstate', () => {
        restoreFormFromUrl();
        performLiveFilter(window.location.href, { isPopstate: true });
    });

    bindTableEvents();
    bindStageLinks();
})();
</script>
<script>
function closePopupUtilityModal() {
    try {
        if (window.parent && typeof window.parent.closeUtilityPopup === 'function' && window.self !== window.top) {
            window.parent.closeUtilityPopup();
            return;
        }
    } catch(e) {}
    try {
        const modal = window.parent?.document?.getElementById('crmKanbanUtilityModal');
        if (modal && window.self !== window.top) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            window.parent.document.body.classList.remove('kanban-modal-open');
            return;
        }
    } catch(e) {}
    window.location.href = "{{ route('v2.leads') }}";
}
</script>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
</body>
</html>
