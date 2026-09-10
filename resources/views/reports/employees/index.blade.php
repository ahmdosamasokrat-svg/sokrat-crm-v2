<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SokratCRM — {{ __('crm.employee_reports_title') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v=1.0.0">
<style>
/* ==========================================================================
   SOKRAT CRM EXECUTIVE DASHBOARD BASE & DESIGN TOKENS
   ========================================================================== */
:root {
  --red: #dc2637;
  --red-hover: #b81829;
  --red-soft: rgba(220, 38, 55, 0.08);
  --dark: #172033;
  --text: #334155;
  --muted: #64748b;
  --line: #e2e8f0;
  --bg: #f7f8fa;
  --card: #ffffff;
  --shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 6px 18px rgba(15, 23, 42, 0.03);
  --shadow-hover: 0 10px 25px -3px rgba(15, 23, 42, 0.07), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
  --radius-sm: 10px;
  --radius-md: 14px;
  --radius-lg: 18px;
  --transition: 180ms cubic-bezier(0.16, 1, 0.3, 1);
  --font-family: Tajawal, Cairo, Tahoma, Arial, sans-serif;
}

html.dark-mode,
html.dark,
[data-theme="dark"] {
  --dark: #f8fafc;
  --text: #cbd5e1;
  --muted: #94a3b8;
  --line: #1e293b;
  --bg: #0b0f19;
  --card: #111827;
  --shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
  --shadow-hover: 0 12px 30px rgba(0, 0, 0, 0.5);
}

* { box-sizing: border-box; }
html { overflow-x: clip; }
body {
  margin: 0;
  min-width: 320px;
  width: 100%;
  max-width: 100vw;
  background: var(--bg);
  color: var(--dark);
  font-family: var(--font-family);
  font-size: 14px;
  line-height: 1.5;
  overflow-x: clip;
}
button, input, select, textarea { font: inherit; }
a { color: inherit; text-decoration: none; }

.app {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  min-height: 100vh;
  width: 100%;
  max-width: 100vw;
  overflow-x: clip;
  min-width: 0;
  background: transparent;
}
.main {
  order: 1;
  flex: 1 1 auto;
  width: calc(100% - 288px);
  min-height: 100vh;
  min-width: 0;
  padding: 24px clamp(16px, 2.5vw, 36px) 48px;
}
.overlay {
  display: none;
  position: fixed;
  inset: 0;
  border: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 99990 !important;
  cursor: pointer;
}
@media (max-width: 900px) {
  .app { display: block; }
  .main { width: 100% !important; padding: 14px 12px 36px; min-height: 100vh; }
  .crm-side-open .overlay,
  .transfer-side-open .overlay {
    display: block !important;
    opacity: 1 !important;
    pointer-events: auto !important;
  }
}

/* ==========================================================================
   1. EXECUTIVE HEADER
   ========================================================================== */
.exec-header-panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  padding: 18px 22px;
  margin-bottom: 18px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  flex-wrap: wrap;
}
.exec-header-left {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 260px;
  flex: 1 1 auto;
}
.exec-header-title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.exec-header-title {
  margin: 0;
  font-size: 21px;
  font-weight: 900;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 10px;
  letter-spacing: -0.3px;
}
.exec-header-title i {
  color: var(--red);
  font-size: 21px;
}
.exec-scope-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 99px;
  background: var(--red-soft);
  color: var(--red);
  font-size: 12px;
  font-weight: 800;
  border: 1px solid rgba(220, 38, 55, 0.18);
}
.exec-header-desc {
  margin: 0;
  font-size: 13px;
  color: var(--muted);
  font-weight: 500;
}
.exec-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.btn-exec {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 38px;
  padding: 0 15px;
  border-radius: var(--radius-sm);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--transition);
  border: 1px solid transparent;
  white-space: nowrap;
}
.btn-exec-primary {
  background: linear-gradient(135deg, #e83243, #c91d2e);
  color: #fff;
  box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25);
}
.btn-exec-primary:hover {
  background: linear-gradient(135deg, #f03e4f, #d62537);
  transform: translateY(-1px);
}
.btn-exec-soft {
  background: var(--bg);
  border-color: var(--line);
  color: var(--dark);
}
.btn-exec-soft:hover {
  background: rgba(220, 38, 55, 0.05);
  border-color: rgba(220, 38, 55, 0.3);
  color: var(--red);
  transform: translateY(-1px);
}

/* ==========================================================================
   2. FILTER BAR (No Team/Group, Dynamic Time Filter with Custom Duration Picker)
   ========================================================================== */
.exec-filter-bar {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  margin-bottom: 18px;
  box-shadow: var(--shadow);
}
.exec-presets-strip {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding-bottom: 12px;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--line);
}
.exec-preset-tag {
  font-size: 12px;
  font-weight: 800;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 5px;
  margin-inline-end: 4px;
}
.exec-preset-pill {
  padding: 5px 13px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
  background: var(--bg);
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--transition);
}
.exec-preset-pill:hover {
  background: var(--card);
  color: var(--dark);
  border-color: #cbd5e1;
}
.exec-preset-pill.active {
  background: linear-gradient(135deg, #e83243, #c91d2e);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 4px 12px rgba(220, 38, 55, 0.25);
}

.exec-filter-controls-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.exec-filter-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 140px;
  flex: 1 1 150px;
}
.exec-filter-cell label {
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
  white-space: nowrap;
}
.exec-filter-cell select,
.exec-filter-cell input[type="date"] {
  width: 100%;
  height: 38px;
  padding: 0 10px;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--dark);
  font-size: 12px;
  font-weight: 600;
  outline: none;
  transition: all var(--transition);
}
.exec-filter-cell select:focus,
.exec-filter-cell input[type="date"]:focus {
  border-color: var(--red);
  background: var(--card);
  box-shadow: 0 0 0 3px rgba(220, 38, 55, 0.1);
}
.exec-filter-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 0 0 auto;
}

/* Custom Date Range Container */
.custom-date-picker-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 3px 10px;
  background: rgba(220, 38, 55, 0.04);
  border: 1px dashed rgba(220, 38, 55, 0.3);
  border-radius: var(--radius-sm);
}

/* ==========================================================================
   3. PIPELINE STAGES SHOWCASE (FIRST RIGHT AFTER FILTERS, SLEEK & COMPACT)
   ========================================================================== */
.exec-pipeline-showcase {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  padding: 14px 18px;
  margin-bottom: 20px;
  box-shadow: var(--shadow);
}
.exec-pipeline-showcase-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--line);
}
.exec-pipeline-showcase-title {
  font-size: 13.5px;
  font-weight: 800;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 8px;
}
.exec-stages-strip {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding: 4px 2px 8px;
  scrollbar-width: thin;
  -webkit-overflow-scrolling: touch;
}
.exec-stage-pill {
  flex: 0 0 auto;
  min-width: 145px;
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 8px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  overflow: hidden;
  transition: all var(--transition);
}
.exec-stage-pill:hover {
  background: var(--card);
  border-color: var(--stage-theme, #3b82f6);
  transform: translateY(-2px);
  box-shadow: var(--shadow-hover);
}
.exec-stage-pill-icon {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-size: 15px;
  background: var(--stage-bg, rgba(59, 130, 246, 0.1));
  color: var(--stage-theme, #3b82f6);
}
.exec-stage-pill-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.exec-stage-pill-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}
.exec-stage-pill-name {
  font-size: 12px;
  font-weight: 800;
  color: var(--dark);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.exec-stage-pill-pct {
  font-size: 10px;
  font-weight: 800;
  color: var(--muted);
  font-family: Arial, sans-serif;
}
.exec-stage-pill-count {
  font-size: 15px;
  font-weight: 900;
  color: var(--dark);
  font-family: Arial, sans-serif;
  line-height: 1.2;
}
.exec-stage-pill-flows {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 9.5px;
  font-weight: 700;
  color: var(--muted);
  margin-top: 1px;
}
.exec-stage-bottom-accent {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--stage-theme, #3b82f6);
}

/* ==========================================================================
   4. ROW 1: 4 KPI CARDS (Summary, Overdue, Today, Upcoming)
   ========================================================================== */
.exec-kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 22px;
}
@media (max-width: 1100px) {
  .exec-kpi-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 580px) {
  .exec-kpi-row { grid-template-columns: 1fr; }
}

.exec-kpi-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all var(--transition);
  position: relative;
  overflow: hidden;
}
.exec-kpi-card.is-clickable {
  cursor: pointer;
  user-select: none;
}
.exec-kpi-card.is-clickable:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
  border-color: color-mix(in srgb, var(--card-accent, #3b82f6) 45%, var(--line));
}
.exec-kpi-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.exec-kpi-title {
  margin: 0;
  font-size: 13.5px;
  font-weight: 800;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 8px;
}
.exec-kpi-icon-pill {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  display: grid;
  place-items: center;
  font-size: 17px;
  background: var(--icon-bg, rgba(59, 130, 246, 0.1));
  color: var(--card-accent, #3b82f6);
}
.kpi-main-number {
  font-size: 28px;
  font-weight: 900;
  color: var(--dark);
  font-family: Arial, Tahoma, sans-serif;
  line-height: 1;
  letter-spacing: -0.5px;
  margin-bottom: 8px;
}
.kpi-card-subtext {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--card-accent, var(--muted));
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 5px;
}
.kpi-click-hint {
  font-size: 10.5px;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.04);
  color: var(--muted);
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.kpi-summary-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--line);
}
.kpi-split-box {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.kpi-split-label {
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
}
.kpi-split-val {
  font-size: 15px;
  font-weight: 800;
  color: var(--dark);
  font-family: Arial, sans-serif;
}

/* ==========================================================================
   5. ROW 2: STAGES TIMELINE (LINE) + CUSTOMER DISTRIBUTION (DONUT)
   ========================================================================== */
.exec-grid-2col {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-bottom: 22px;
}
@media (min-width: 1024px) {
  .exec-grid-2col { grid-template-columns: 1.45fr 1fr; }
}

.exec-card-panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  padding: 22px 24px;
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
  transition: all var(--transition);
}
.exec-card-panel:hover { box-shadow: var(--shadow-hover); }
.exec-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 14px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--line);
  gap: 12px;
  flex-wrap: wrap;
}
.exec-card-title-group h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 8px;
}
.exec-card-title-group p {
  margin: 3px 0 0;
  font-size: 12px;
  color: var(--muted);
}
.exec-chart-wrap {
  position: relative;
  width: 100%;
  height: 270px;
}

/* Donut Box Layout */
.donut-center-container {
  position: relative;
  width: 100%;
  height: 190px;
  display: grid;
  place-items: center;
}
.donut-center-metric {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}
.donut-center-metric strong {
  display: block;
  font-size: 22px;
  font-weight: 900;
  color: var(--dark);
  font-family: Arial, sans-serif;
  line-height: 1;
}
.donut-center-metric small {
  display: block;
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  margin-top: 3px;
}
.donut-legend-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  padding-top: 14px;
  margin-top: 12px;
  border-top: 1px solid var(--line);
}
.donut-legend-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text);
}
.donut-legend-tag span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

/* ==========================================================================
   6. ROW 3: CUSTOMER FUNNEL + UPCOMING FOLLOW-UPS
   ========================================================================== */
.exec-funnel-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.funnel-stage-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.funnel-stage-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
  font-weight: 700;
}
.funnel-stage-name {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--dark);
}
.funnel-stage-metrics {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--muted);
}
.funnel-stage-metrics strong {
  color: var(--dark);
  font-family: Arial, sans-serif;
  font-size: 13px;
}
.funnel-bar-track {
  width: 100%;
  height: 10px;
  background: var(--bg);
  border-radius: 99px;
  overflow: hidden;
  border: 1px solid var(--line);
}
.funnel-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.4s ease;
}

/* Upcoming Follow-ups List */
.followups-timeline {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.followup-item-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  background: var(--bg);
  border: 1px solid var(--line);
  gap: 12px;
  transition: all var(--transition);
}
.followup-item-card:hover {
  background: var(--card);
  border-color: #cbd5e1;
  transform: translateY(-1px);
}
.followup-item-lead {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.followup-lead-name {
  font-size: 13.5px;
  font-weight: 800;
  color: var(--dark);
}
.followup-lead-sub {
  font-size: 11px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 6px;
}
.followup-status-badge {
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}
.badge-overdue { background: rgba(220, 38, 55, 0.12); color: #dc2637; border: 1px solid rgba(220, 38, 55, 0.2); }
.badge-today { background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.badge-tomorrow { background: rgba(59, 130, 246, 0.12); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
.badge-upcoming { background: var(--card); color: var(--muted); border: 1px solid var(--line); }

/* ==========================================================================
   7. ROW 4: DETAILED PERFORMANCE TABLE
   ========================================================================== */
.table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
}
.exec-table {
  width: 100%;
  border-collapse: collapse;
  text-align: right;
  font-size: 13px;
}
[dir="ltr"] .exec-table { text-align: left; }
.exec-table th {
  padding: 12px 14px;
  border-bottom: 1px solid var(--line);
  color: var(--muted);
  font-size: 12px;
  font-weight: 800;
  background: var(--bg);
  white-space: nowrap;
  user-select: none;
}
.exec-table th.sortable { cursor: pointer; transition: color var(--transition); }
.exec-table th.sortable:hover { color: var(--red); }
.exec-table td {
  padding: 13px 14px;
  border-bottom: 1px solid var(--line);
  color: var(--dark);
  font-size: 13px;
  vertical-align: middle;
  white-space: nowrap;
}
.exec-table tbody tr { transition: background-color var(--transition); }
.exec-table tbody tr:hover { background-color: var(--bg); }
.exec-table tbody tr:last-child td { border-bottom: none; }

.emp-avatar-circle {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: var(--red-soft);
  color: var(--red);
  display: grid;
  place-items: center;
  font-weight: 800;
  font-size: 12.5px;
  flex: 0 0 34px;
}
.emp-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 800;
}
.emp-status-badge.excellent { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.emp-status-badge.good { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
.emp-status-badge.needs-followup { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.emp-status-badge.low { background: rgba(220, 38, 55, 0.12); color: #dc2637; }

/* ==========================================================================
   8. FOLLOW-UPS POPUP MODAL
   ========================================================================== */
.followups-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 99992;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease, visibility 0.2s ease;
  display: grid;
  place-items: center;
  padding: 20px;
}
.followups-modal-backdrop.active {
  opacity: 1;
  visibility: visible;
}
.followups-modal-card {
  width: 100%;
  max-width: 820px;
  max-height: 85vh;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-lg);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transform: scale(0.96);
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.followups-modal-backdrop.active .followups-modal-card {
  transform: scale(1);
}
.followups-modal-head {
  padding: 18px 24px;
  border-bottom: 1px solid var(--line);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: var(--card);
}
.followups-modal-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 17px;
  font-weight: 900;
  color: var(--dark);
  margin: 0;
}
.followups-modal-search {
  padding: 12px 24px;
  background: var(--bg);
  border-bottom: 1px solid var(--line);
  display: flex;
  align-items: center;
  gap: 10px;
}
.followups-modal-search input {
  width: 100%;
  height: 38px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--line);
  background: var(--card);
  padding: 0 12px;
  font-size: 13px;
  outline: none;
}
.followups-modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 0;
}

/* Slide Drawer */
.crm-drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 99990;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.25s ease, visibility 0.25s ease;
}
.crm-drawer-backdrop.active { opacity: 1; visibility: visible; }
.crm-drawer {
  position: fixed;
  top: 0;
  bottom: 0;
  width: 92%;
  max-width: 760px;
  background: var(--card);
  box-shadow: -10px 0 40px rgba(0, 0, 0, 0.25);
  z-index: 99995;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
}
html[dir="rtl"] .crm-drawer { right: 0; left: auto; transform: translateX(100%); }
html[dir="ltr"] .crm-drawer { right: 0; left: auto; transform: translateX(100%); }
.crm-drawer.active,
html[dir="rtl"] .crm-drawer.active,
html[dir="ltr"] .crm-drawer.active { transform: translateX(0) !important; }
.drawer-header {
  padding: 18px 24px;
  border-bottom: 1px solid var(--line);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: var(--card);
}
.drawer-header h2 { margin: 0; font-size: 18px; font-weight: 900; color: var(--dark); }
.drawer-close-btn {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  background: var(--bg);
  border: 1px solid var(--line);
  color: var(--muted);
  font-size: 16px;
  cursor: pointer;
  display: grid;
  place-items: center;
}
.drawer-close-btn:hover { background: var(--line); color: var(--dark); }
.drawer-body { flex: 1; overflow-y: auto; padding: 24px; }
</style>
</head>
<body>
<div class="app">
    @include('partials.crm-sidebar')
    <button class="overlay" id="overlay" type="button" aria-label="{{ __('crm.close') ?? 'إغلاق' }}"></button>

    <main class="main" id="employeeReportsMain">
        @php
            $selectedEmployee = null;
            if (!empty($filters['user_id'])) {
                $selectedEmployee = $authorizedEmployees->firstWhere('id', (int) $filters['user_id']);
            }

            // Safe Trend Data Fallback
            $trendData = $performanceTrend ?? ['labels' => [], 'series' => []];

            // Safe Distribution Fallback
            $distData = $customerDistribution ?? [
                'type' => 'campaign',
                'labels' => [__('crm.all_records')],
                'data' => [max(1, $kpis['total_leads'])],
                'colors' => ['#dc2637'],
                'total' => $kpis['total_leads'],
            ];

            // Upcoming Items Fallback
            $upcomingItems = $upcomingFollowups ?? collect();
            $upcomingTotalCount = $followupPerformance['upcoming_total'] ?? $upcomingItems->count();
        @endphp

        <!-- 1. EXECUTIVE HEADER -->
        <section class="exec-header-panel" aria-label="{{ __('crm.employee_reports_title') }}">
            <div class="exec-header-left">
                <div class="exec-header-title-row">
                    <h1 class="exec-header-title">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        {{ __('crm.employee_reports_title') }}
                    </h1>
                    <span class="exec-scope-badge">
                        <i class="bi bi-person-badge"></i>
                        {{ $selectedEmployee ? $selectedEmployee->name : (__('crm.all_employees') ?? 'كل الموظفين') }}
                    </span>
                </div>
                <p class="exec-header-desc">
                    {{ __('crm.employee_reports_subtitle') }} — {{ __('تحليل أداء الموظفين ومعدلات التحويل والمتابعات والحملات خلال الفترة المحددة') }}
                </p>
            </div>

            <div class="exec-header-actions">
                <a href="{{ route('v2.reports.employees.export', request()->query()) }}" class="btn-exec btn-exec-soft">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <span>{{ __('crm.export_csv') }}</span>
                </a>
                <button type="button" class="btn-exec btn-exec-soft" onclick="resetFilters()">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>{{ __('crm.refresh') ?? 'تحديث البيانات' }}</span>
                </button>
            </div>
        </section>

        <!-- 2. COMPACT FILTER BAR (No Team/Group, Dynamic Time Filter) -->
        <section class="exec-filter-bar" aria-label="{{ __('crm.filter_results') }}">
            <form id="reportFilterForm" method="GET" action="{{ route('v2.reports.employees.index') }}">
                <input type="hidden" name="sort" id="inputSort" value="{{ $filters['sort'] ?? 'name' }}">
                <input type="hidden" name="direction" id="inputDirection" value="{{ $filters['direction'] ?? 'asc' }}">

                <!-- Quick Date Presets Row -->
                <div class="exec-presets-strip">
                    <span class="exec-preset-tag">
                        <i class="bi bi-clock-history"></i> {{ __('فلترة الوقت') }}:
                    </span>
                    @php
                        $presets = [
                            'today' => __('crm.preset_today'),
                            'yesterday' => __('crm.preset_yesterday'),
                            'this_week' => __('crm.preset_this_week'),
                            'this_month' => __('crm.preset_this_month'),
                            'last_month' => __('crm.preset_last_month'),
                            'custom' => __('crm.preset_custom'),
                        ];
                    @endphp
                    @foreach ($presets as $pKey => $pLabel)
                        <button
                            type="button"
                            class="exec-preset-pill {{ ($filters['preset'] ?? 'this_month') === $pKey ? 'active' : '' }}"
                            onclick="handlePresetClick('{{ $pKey }}')"
                        >
                            {{ $pLabel }}
                        </button>
                    @endforeach
                </div>

                <!-- Single Row Filter Controls -->
                <div class="exec-filter-controls-row">
                    <!-- Period / Preset Selector Dropdown -->
                    <div class="exec-filter-cell" style="max-width: 170px;">
                        <label for="selectPresetDropdown"><i class="bi bi-calendar3"></i> {{ __('الفترة') }}:</label>
                        <select name="preset" id="selectPresetDropdown" onchange="handlePresetChange(this.value)">
                            <option value="today" {{ ($filters['preset'] ?? 'this_month') === 'today' ? 'selected' : '' }}>{{ __('crm.preset_today') }}</option>
                            <option value="yesterday" {{ ($filters['preset'] ?? 'this_month') === 'yesterday' ? 'selected' : '' }}>{{ __('crm.preset_yesterday') }}</option>
                            <option value="this_week" {{ ($filters['preset'] ?? 'this_month') === 'this_week' ? 'selected' : '' }}>{{ __('crm.preset_this_week') }}</option>
                            <option value="this_month" {{ ($filters['preset'] ?? 'this_month') === 'this_month' ? 'selected' : '' }}>{{ __('crm.preset_this_month') }}</option>
                            <option value="last_month" {{ ($filters['preset'] ?? 'this_month') === 'last_month' ? 'selected' : '' }}>{{ __('crm.preset_last_month') }}</option>
                            <option value="custom" {{ ($filters['preset'] ?? 'this_month') === 'custom' ? 'selected' : '' }}>{{ __('crm.preset_custom') }}</option>
                        </select>
                    </div>

                    <!-- Custom Duration Range Picker (Appears when Custom is chosen) -->
                    <div class="custom-date-picker-box" id="customDatePickerBox" style="{{ ($filters['preset'] ?? 'this_month') === 'custom' ? 'display:flex;' : 'display:none;' }}">
                        <div class="exec-filter-cell" style="min-width: 130px;">
                            <label for="inputFrom">{{ __('من') }}:</label>
                            <input type="date" name="from" id="inputFrom" value="{{ $dateRange['from']->format('Y-m-d') }}" onchange="handleDateChange()">
                        </div>
                        <div class="exec-filter-cell" style="min-width: 130px;">
                            <label for="inputTo">{{ __('إلى') }}:</label>
                            <input type="date" name="to" id="inputTo" value="{{ $dateRange['to']->format('Y-m-d') }}" onchange="handleDateChange()">
                        </div>
                    </div>

                    <!-- Employee Select -->
                    <div class="exec-filter-cell">
                        <label for="selectEmployee"><i class="bi bi-person-badge"></i> {{ __('crm.employee') }}:</label>
                        <select name="user_id" id="selectEmployee">
                            <option value="">{{ __('crm.all_employees') }}</option>
                            @foreach ($authorizedEmployees as $emp)
                                <option value="{{ $emp->id }}" {{ (string)($filters['user_id'] ?? '') === (string)$emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Campaign Select -->
                    <div class="exec-filter-cell">
                        <label for="selectCampaign"><i class="bi bi-megaphone"></i> {{ __('crm.campaign') }}:</label>
                        <select name="campaign_id" id="selectCampaign">
                            <option value="">{{ __('crm.all_campaigns') }}</option>
                            @foreach ($visibleCampaigns as $camp)
                                <option value="{{ $camp->id }}" {{ (string)($filters['campaign_id'] ?? '') === (string)$camp->id ? 'selected' : '' }}>
                                    {{ $camp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pipeline Stage Select -->
                    <div class="exec-filter-cell">
                        <label for="selectStage"><i class="bi bi-funnel"></i> {{ __('crm.pipeline_stage') }}:</label>
                        <select name="stage_id" id="selectStage">
                            <option value="">{{ __('crm.all_stages') }}</option>
                            @foreach ($stages as $stage)
                                <option value="{{ $stage->id }}" {{ (string)($filters['stage_id'] ?? '') === (string)$stage->id ? 'selected' : '' }}>
                                    {{ $stage->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Action Buttons -->
                    <div class="exec-filter-buttons">
                        <button type="submit" class="btn-exec btn-exec-primary">
                            <i class="bi bi-funnel-fill"></i>
                            <span>{{ __('crm.filter') ?? 'تطبيق' }}</span>
                        </button>
                        <a href="{{ route('v2.reports.employees.index') }}" class="btn-exec btn-exec-soft">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>{{ __('crm.reset') ?? 'إعادة تعيين' }}</span>
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <!-- 3. PIPELINE STAGES SHOWCASE (FIRST RIGHT AFTER FILTERS, SLEEK & COMPACT) -->
        <section class="exec-pipeline-showcase" aria-label="{{ __('مراحل مسار المبيعات') }}">
            <div class="exec-pipeline-showcase-head">
                <div class="exec-pipeline-showcase-title">
                    <i class="bi bi-diagram-3-fill" style="color:var(--red);"></i>
                    <span>{{ __('مراحل مسار المبيعات') }}</span>
                </div>
                <span style="font-size:12px;font-weight:800;color:var(--muted);">
                    {{ count($pipelinePerformance) }} {{ __('مراحل') }}
                </span>
            </div>

            <div class="exec-stages-strip">
                @foreach ($pipelinePerformance as $stMetric)
                    @php
                        $stColor = $stMetric['color'] ?: '#3b82f6';
                        $pctOfTotal = $kpis['total_leads'] > 0 ? round(($stMetric['current_count'] / $kpis['total_leads']) * 100, 1) : 0;
                    @endphp
                    <div class="exec-stage-pill" style="--stage-theme: {{ $stColor }}; --stage-bg: {{ $stColor }}18;">
                        <div class="exec-stage-pill-icon">
                            <i class="bi {{ $stMetric['icon'] }}"></i>
                        </div>
                        <div class="exec-stage-pill-content">
                            <div class="exec-stage-pill-top">
                                <span class="exec-stage-pill-name" title="{{ $stMetric['stage_name'] }}">{{ $stMetric['stage_name'] }}</span>
                                <span class="exec-stage-pill-pct">{{ $pctOfTotal }}%</span>
                            </div>
                            <div class="exec-stage-pill-count">{{ number_format($stMetric['current_count']) }}</div>
                            <div class="exec-stage-pill-flows">
                                <span style="color:#10b981;">+{{ $stMetric['entered_count'] }}</span>
                                <span style="color:var(--muted);">-{{ $stMetric['exited_count'] }}</span>
                            </div>
                        </div>
                        <div class="exec-stage-bottom-accent"></div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- 4. ROW 1: 4 KPI CARDS (Summary, Overdue, Today, Upcoming) -->
        <section class="exec-kpi-row" aria-label="{{ __('المؤشرات الرئيسية') }}">
            <!-- Card 1: ملخص العملاء -->
            <div class="exec-kpi-card" style="--card-accent:#3b82f6;--icon-bg:rgba(59,130,246,0.1);">
                <div class="exec-kpi-header">
                    <h3 class="exec-kpi-title">
                        <i class="bi bi-people-fill" style="color:#3b82f6;"></i>
                        {{ __('ملخص العملاء') }}
                    </h3>
                    <div class="exec-kpi-icon-pill">
                        <i class="bi bi-database"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-main-number">
                        {{ number_format($kpis['total_leads']) }}
                        <span style="font-size:14px;color:var(--muted);font-weight:700;">{{ __('عميل') }}</span>
                    </div>
                    <div class="kpi-summary-split">
                        <div class="kpi-split-box">
                            <span class="kpi-split-label">{{ __('النشطين') }}</span>
                            <span class="kpi-split-val">{{ number_format($kpis['active_leads']) }}</span>
                        </div>
                        <div class="kpi-split-box">
                            <span class="kpi-split-label">{{ __('المحولين') }} ({{ $kpis['conversion_rate'] }}%)</span>
                            <span class="kpi-split-val" style="color:#10b981;">{{ number_format($kpis['converted_leads']) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: المتابعات المتأخرة (Clickable Popup Trigger) -->
            <div
                class="exec-kpi-card is-clickable"
                style="--card-accent:#dc2637;--icon-bg:rgba(220,38,55,0.1);border-top:3px solid #dc2637;"
                onclick="openFollowupsModal('overdue', '{{ __('المتابعات المتأخرة') }}')"
                title="{{ __('انقر لعرض المتابعات المتأخرة') }}"
            >
                <div class="exec-kpi-header">
                    <h3 class="exec-kpi-title">
                        <i class="bi bi-exclamation-triangle-fill" style="color:#dc2637;"></i>
                        {{ __('المتابعات المتأخرة') }}
                    </h3>
                    <div class="exec-kpi-icon-pill">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-main-number" style="color:#dc2637;">
                        {{ number_format($kpis['overdue_followups']) }}
                        <span style="font-size:14px;color:var(--muted);font-weight:700;">{{ __('متابعة') }}</span>
                    </div>
                    <div class="kpi-card-subtext">
                        <span><i class="bi bi-shield-exclamation"></i> {{ __('حالات حرجة متأخرة') }}</span>
                        <span class="kpi-click-hint"><i class="bi bi-box-arrow-up-right"></i> {{ __('عرض القائمة') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: متابعات اليوم (Clickable Popup Trigger) -->
            <div
                class="exec-kpi-card is-clickable"
                style="--card-accent:#10b981;--icon-bg:rgba(16,185,129,0.1);border-top:3px solid #10b981;"
                onclick="openFollowupsModal('today', '{{ __('متابعات اليوم') }}')"
                title="{{ __('انقر لعرض متابعات اليوم') }}"
            >
                <div class="exec-kpi-header">
                    <h3 class="exec-kpi-title">
                        <i class="bi bi-calendar-check-fill" style="color:#10b981;"></i>
                        {{ __('متابعات اليوم') }}
                    </h3>
                    <div class="exec-kpi-icon-pill">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-main-number" style="color:#10b981;">
                        {{ number_format($followupPerformance['upcoming_today'] ?? 0) }}
                        <span style="font-size:14px;color:var(--muted);font-weight:700;">{{ __('مستحقة') }}</span>
                    </div>
                    <div class="kpi-card-subtext">
                        <span><i class="bi bi-calendar-day"></i> {{ __('مستحقة الإنجاز والتواصل اليوم') }}</span>
                        <span class="kpi-click-hint"><i class="bi bi-box-arrow-up-right"></i> {{ __('عرض القائمة') }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: المتابعات القادمة (Clickable Popup Trigger) -->
            <div
                class="exec-kpi-card is-clickable"
                style="--card-accent:#6366f1;--icon-bg:rgba(99,102,241,0.1);border-top:3px solid #6366f1;"
                onclick="openFollowupsModal('upcoming', '{{ __('المتابعات القادمة') }}')"
                title="{{ __('انقر لعرض المتابعات القادمة') }}"
            >
                <div class="exec-kpi-header">
                    <h3 class="exec-kpi-title">
                        <i class="bi bi-calendar-plus-fill" style="color:#6366f1;"></i>
                        {{ __('المتابعات القادمة') }}
                    </h3>
                    <div class="exec-kpi-icon-pill">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                </div>
                <div>
                    <div class="kpi-main-number" style="color:#6366f1;">
                        {{ number_format($upcomingTotalCount) }}
                        <span style="font-size:14px;color:var(--muted);font-weight:700;">{{ __('مجدولة') }}</span>
                    </div>
                    <div class="kpi-card-subtext">
                        <span><i class="bi bi-arrow-left-circle"></i> {{ __('مجدولة للفترات القادمة') }}</span>
                        <span class="kpi-click-hint"><i class="bi bi-box-arrow-up-right"></i> {{ __('عرض القائمة') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. ROW 2: STAGES TIMELINE (LINE) + CUSTOMER DISTRIBUTION (DONUT) -->
        <div class="exec-grid-2col">
            <!-- Line Chart: Pipeline Stages Trend -->
            <div class="exec-card-panel">
                <div class="exec-card-header">
                    <div class="exec-card-title-group">
                        <h3>
                            <i class="bi bi-graph-up-arrow" style="color:var(--red);"></i>
                            {{ __('حركة وتطور المراحل') }}
                        </h3>
                        <p>{{ __('متابعة حركة العملاء وتدفقهم بين المراحل خلال الفترة المحددة') }}</p>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        @foreach(array_slice($trendData['series'] ?? [], 0, 5) as $sItem)
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;">
                                <span style="width:9px;height:9px;border-radius:50%;background:{{ $sItem['color'] }};"></span>
                                {{ $sItem['name'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="exec-chart-wrap">
                    <canvas id="stagesTrendChart"></canvas>
                </div>
            </div>

            <!-- Donut Chart: Customer Distribution -->
            <div class="exec-card-panel">
                <div class="exec-card-header">
                    <div class="exec-card-title-group">
                        <h3>
                            <i class="bi bi-pie-chart-fill" style="color:#8b5cf6;"></i>
                            {{ __('توزيع عملاء الموظف') }}
                        </h3>
                        <p>{{ __('تقسيم العملاء حسب الحملات والمصادر النشطة') }}</p>
                    </div>
                    <span class="exec-scope-badge" style="background:var(--bg);color:var(--muted);border-color:var(--line);">
                        {{ count($distData['labels'] ?? []) }} {{ __('مجموعات') }}
                    </span>
                </div>
                <div class="donut-center-container">
                    <canvas id="customerDistributionChart"></canvas>
                    <div class="donut-center-metric">
                        <strong>{{ number_format($kpis['total_leads']) }}</strong>
                        <small>{{ __('إجمالي العملاء') }}</small>
                    </div>
                </div>
                <div class="donut-legend-bar">
                    @foreach(($distData['labels'] ?? []) as $i => $dLabel)
                        <span class="donut-legend-tag">
                            <span style="background: {{ $distData['colors'][$i] ?? '#3b82f6' }};"></span>
                            {{ $dLabel }}: <strong>{{ number_format($distData['data'][$i] ?? 0) }}</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- 6. ROW 3: CUSTOMER FUNNEL + UPCOMING FOLLOW-UPS -->
        <div class="exec-grid-2col" style="grid-template-columns: 1fr 1fr;">
            <!-- Horizontal Funnel -->
            <div class="exec-card-panel">
                <div class="exec-card-header">
                    <div class="exec-card-title-group">
                        <h3>
                            <i class="bi bi-funnel-fill" style="color:#f59e0b;"></i>
                            {{ __('مسار العملاء') }}
                        </h3>
                        <p>{{ __('توزيع العملاء ومعدل الانتقال بين مراحل مسار المبيعات') }}</p>
                    </div>
                </div>
                <div class="exec-funnel-list">
                    @php
                        $maxStageCount = 1;
                        foreach ($pipelinePerformance as $st) {
                            if ($st['current_count'] > $maxStageCount) $maxStageCount = $st['current_count'];
                        }
                        $prevCount = null;
                    @endphp
                    @forelse ($pipelinePerformance as $i => $stageRow)
                        @php
                            $currCount = $stageRow['current_count'];
                            $transRate = $prevCount !== null && $prevCount > 0 ? round(($currCount / $prevCount) * 100, 1) : null;
                            $prevCount = $currCount;
                            $barWidth = max(5, round(($currCount / $maxStageCount) * 100));
                        @endphp
                        <div class="funnel-stage-item">
                            <div class="funnel-stage-top">
                                <span class="funnel-stage-name">
                                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $stageRow['color'] ?: '#3b82f6' }};"></span>
                                    {{ $stageRow['stage_name'] }}
                                </span>
                                <div class="funnel-stage-metrics">
                                    @if ($transRate !== null)
                                        <span title="{{ __('نسبة الانتقال من المرحلة السابقة') }}">
                                            <i class="bi bi-arrow-left-short"></i> {{ $transRate }}%
                                        </span>
                                    @endif
                                    <strong>{{ number_format($currCount) }} {{ __('عميل') }}</strong>
                                </div>
                            </div>
                            <div class="funnel-bar-track">
                                <div class="funnel-bar-fill" style="width: {{ $barWidth }}%; background: {{ $stageRow['color'] ?: '#3b82f6' }};"></div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:30px;color:var(--muted);">
                            <i class="bi bi-inbox" style="font-size:24px;display:block;margin-bottom:6px;"></i>
                            {{ __('crm.no_employee_reports_data') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Upcoming Follow-ups List -->
            <div class="exec-card-panel">
                <div class="exec-card-header">
                    <div class="exec-card-title-group">
                        <h3>
                            <i class="bi bi-calendar-check-fill" style="color:#0284c7;"></i>
                            {{ __('المتابعات القادمة') }}
                        </h3>
                        <p>{{ __('قائمة المتابعات المجدولة القادمة والمتأخرة للعملاء') }}</p>
                    </div>
                    @can('tasks.view')
                        <a href="{{ route('v2.followups') }}" class="exec-scope-badge" style="text-decoration:none;">
                            {{ __('عرض كل المتابعات') }} <i class="bi bi-arrow-left"></i>
                        </a>
                    @endcan
                </div>
                <div class="followups-timeline">
                    @forelse ($upcomingItems as $fLead)
                        @php
                            $dt = $fLead->next_follow_up_at ? \Carbon\CarbonImmutable::parse($fLead->next_follow_up_at) : null;
                            $badgeClass = 'badge-upcoming';
                            $badgeText = __('crm.upcoming') ?? 'قادمة';
                            if ($dt) {
                                if ($dt->isPast()) {
                                    $badgeClass = 'badge-overdue';
                                    $badgeText = __('crm.overdue') ?? 'متأخرة';
                                } elseif ($dt->isToday()) {
                                    $badgeClass = 'badge-today';
                                    $badgeText = __('crm.today') ?? 'اليوم';
                                } elseif ($dt->isTomorrow()) {
                                    $badgeClass = 'badge-tomorrow';
                                    $badgeText = __('crm.tomorrow') ?? 'غداً';
                                }
                            }
                        @endphp
                        <div class="followup-item-card">
                            <div class="followup-item-lead">
                                <a href="/leads/{{ $fLead->id }}" target="_blank" class="followup-lead-name">
                                    {{ $fLead->name }}
                                </a>
                                <div class="followup-lead-sub">
                                    @if($fLead->company_name)
                                        <span><i class="bi bi-building"></i> {{ $fLead->company_name }}</span>
                                    @endif
                                    @if($fLead->assignedUser)
                                        <span><i class="bi bi-person"></i> {{ $fLead->assignedUser->name }}</span>
                                    @endif
                                    @if($dt)
                                        <span><i class="bi bi-clock"></i> {{ $dt->format('Y-m-d H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="followup-status-badge {{ $badgeClass }}">
                                {{ $badgeText }}
                            </span>
                        </div>
                    @empty
                        <div style="text-align:center;padding:30px;color:var(--muted);">
                            <i class="bi bi-check2-circle" style="font-size:26px;color:#10b981;display:block;margin-bottom:6px;"></i>
                            {{ __('لا توجد متابعات مجدولة حاليًا') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 7. ROW 4: DETAILED PERFORMANCE TABLE -->
        <section class="exec-card-panel" aria-label="{{ __('تفاصيل أداء الموظفين') }}">
            <div class="exec-card-header">
                <div class="exec-card-title-group">
                    <h3>
                        <i class="bi bi-table" style="color:var(--red);"></i>
                        {{ __('تفاصيل أداء الموظف') }}
                    </h3>
                    <p>{{ __('التقرير المالي والتنفيذي الكامل للموظفين وتوزيع العملاء ومؤشرات الكفاءة') }}</p>
                </div>
                <div style="position:relative;width:260px;">
                    <i class="bi bi-search" style="position:absolute;top:50%;right:12px;transform:translateY(-50%);color:var(--muted);pointer-events:none;"></i>
                    <input
                        type="text"
                        class="table-search-input"
                        id="tableSearch"
                        placeholder="{{ __('crm.search_placeholder') }}"
                        value="{{ $filters['search'] ?? '' }}"
                        oninput="debounceSearch(this.value)"
                        style="width:100%;height:38px;padding:0 36px 0 12px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--bg);font-size:12px;outline:none;"
                    >
                </div>
            </div>

            <div class="table-wrap">
                <table class="exec-table">
                    <thead>
                        <tr>
                            <th class="sortable" onclick="sortTable('name')">
                                {{ __('crm.employee') }}
                                @if (($filters['sort'] ?? '') === 'name')
                                    <i class="bi bi-arrow-{{ ($filters['direction'] ?? '') === 'desc' ? 'down' : 'up' }}"></i>
                                @endif
                            </th>
                            <th>{{ __('crm.team') }}</th>
                            <th class="sortable" onclick="sortTable('assigned_leads')">
                                {{ __('crm.assigned_leads') }}
                                @if (($filters['sort'] ?? '') === 'assigned_leads')
                                    <i class="bi bi-arrow-{{ ($filters['direction'] ?? '') === 'desc' ? 'down' : 'up' }}"></i>
                                @endif
                            </th>
                            <th class="sortable" onclick="sortTable('new_leads')">
                                {{ __('عملاء جدد') }}
                            </th>
                            <th class="sortable" onclick="sortTable('total_followups')">
                                {{ __('المتابعات') }}
                            </th>
                            <th class="sortable" onclick="sortTable('overdue_followups')">
                                {{ __('المتأخرة') }}
                            </th>
                            <th class="sortable" onclick="sortTable('conversion_rate')">
                                {{ __('معدل التحويل') }}
                            </th>
                            <!-- Dynamic Stages -->
                            @foreach ($stages as $stage)
                                <th>
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $stage->color ?: '#3478f6' }};"></span>
                                        {{ $stage->localizedName() }}
                                    </span>
                                </th>
                            @endforeach
                            <th>{{ __('الحالة') }}</th>
                            <th>{{ __('crm.actions_th') }}</th>
                        </tr>
                    </thead>
                    <tbody id="empTableBody">
                        @forelse ($performance->items() as $empRow)
                            @php
                                $rate = $empRow['conversion_rate'];
                                $badgeStyle = 'low';
                                $badgeLabel = __('منخفض الأداء');
                                if ($rate >= 20) {
                                    $badgeStyle = 'excellent';
                                    $badgeLabel = __('ممتاز');
                                } elseif ($rate >= 10) {
                                    $badgeStyle = 'good';
                                    $badgeLabel = __('جيد');
                                } elseif ($empRow['total_followups'] > 0) {
                                    $badgeStyle = 'needs-followup';
                                    $badgeLabel = __('يحتاج متابعة');
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="emp-avatar-circle">
                                            {{ mb_substr($empRow['name'], 0, 2) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:800;color:var(--dark);cursor:pointer;" onclick="openDrilldown({{ $empRow['id'] }})">
                                                {{ $empRow['name'] }}
                                            </div>
                                            <div style="font-size:11px;color:var(--muted);">{{ $empRow['username'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if (!empty($empRow['groups']))
                                        @foreach ($empRow['groups'] as $grpName)
                                            <span style="display:inline-block;padding:2px 7px;border-radius:5px;background:var(--bg);border:1px solid var(--line);font-size:11px;color:var(--muted);">{{ $grpName }}</span>
                                        @endforeach
                                    @else
                                        <span style="color:var(--muted);">—</span>
                                    @endif
                                </td>
                                <td><strong style="font-family:Arial,sans-serif;">{{ number_format($empRow['assigned_leads']) }}</strong></td>
                                <td style="font-family:Arial,sans-serif;">{{ number_format($empRow['new_leads']) }}</td>
                                <td style="font-family:Arial,sans-serif;">{{ number_format($empRow['total_followups']) }}</td>
                                <td style="font-family:Arial,sans-serif;">
                                    @if ($empRow['overdue_followups'] > 0)
                                        <span style="color:#dc2637;font-weight:800;">{{ number_format($empRow['overdue_followups']) }}</span>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    <strong style="color:#10b981;font-family:Arial,sans-serif;">{{ $empRow['conversion_rate'] }}%</strong>
                                </td>
                                @foreach ($stages as $stage)
                                    <td style="font-family:Arial,sans-serif;">
                                        {{ number_format($empRow['stage_counts'][$stage->id] ?? 0) }}
                                    </td>
                                @endforeach
                                <td>
                                    <span class="emp-status-badge {{ $badgeStyle }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn-exec btn-exec-soft" style="height:32px;padding:0 10px;font-size:12px;" onclick="openDrilldown({{ $empRow['id'] }})">
                                        <i class="bi bi-eye"></i> {{ __('التفاصيل') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 10 + count($stages) }}" style="text-align:center;padding:48px 20px;color:var(--muted);">
                                    <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                                    {{ __('crm.no_employee_reports_data') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;gap:12px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:700;color:var(--muted);">
                    {{ __('crm.all_records') }}: {{ number_format($performance->total()) }}
                </span>
                <div>
                    {{ $performance->links('partials.pagination') }}
                </div>
            </div>
        </section>

    </main>
</div>

<!-- 8. FOLLOW-UPS POPUP MODAL -->
<div class="followups-modal-backdrop" id="followupsModalBackdrop" onclick="closeFollowupsModal(event)">
    <div class="followups-modal-card" id="followupsModalCard" onclick="event.stopPropagation()">
        <div class="followups-modal-head">
            <h3 class="followups-modal-title">
                <i class="bi bi-clock-history" id="followupsModalIcon" style="color:var(--red);"></i>
                <span id="followupsModalTitle">{{ __('المتابعات') }}</span>
                <span class="exec-scope-badge" id="followupsModalCount">0</span>
            </h3>
            <button type="button" class="drawer-close-btn" onclick="closeFollowupsModal()" aria-label="{{ __('crm.close') ?? 'إغلاق' }}">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="followups-modal-search">
            <i class="bi bi-search" style="color:var(--muted);"></i>
            <input type="text" id="followupsSearchInput" placeholder="{{ __('بحث في المتابعات...') }}" oninput="filterFollowupsModal(this.value)">
        </div>
        <div class="followups-modal-body" id="followupsModalBody">
            <div style="text-align:center;padding:50px;color:var(--muted);">
                <i class="bi bi-arrow-repeat spin" style="font-size:32px;display:inline-block;animation:spin 1s linear infinite;"></i>
                <p style="margin-top:10px;">{{ __('crm.loading_data') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- EMPLOYEE DRILLDOWN DRAWER -->
<div class="crm-drawer-backdrop" id="drawerBackdrop" onclick="closeDrilldown()"></div>
<aside class="crm-drawer" id="employeeDrawer" aria-label="{{ __('crm.employee_drilldown') }}">
    <div class="drawer-header">
        <div>
            <h2 id="drawerEmployeeName">{{ __('crm.employee_drilldown') }}</h2>
            <p id="drawerEmployeeGroups" style="margin:2px 0 0;font-size:12px;color:var(--muted);"></p>
        </div>
        <button type="button" class="drawer-close-btn" onclick="closeDrilldown()" aria-label="{{ __('crm.close_drawer') }}">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="drawer-body" id="drawerBody">
        <div style="text-align:center;padding:40px;color:var(--muted);">
            <i class="bi bi-hourglass-split" style="font-size:32px;"></i>
            <p>{{ __('crm.loading_data') }}</p>
        </div>
    </div>
</aside>

<!-- VENDOR CHART.JS -->
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
(() => {
    const isDark = document.documentElement.classList.contains('dark-mode') || document.documentElement.classList.contains('dark');

    // 1. Pipeline Stages Trend Chart (Multi-Line Chart for Stages)
    const trendCtx = document.getElementById('stagesTrendChart');
    const trendLabels = @json($trendData['labels'] ?? []);
    const stageSeries = @json($trendData['series'] ?? []);

    if (trendCtx && typeof Chart !== 'undefined') {
        const datasets = stageSeries.map(s => ({
            label: s.name,
            data: s.data,
            borderColor: s.color,
            backgroundColor: s.color + '15',
            tension: 0.35,
            fill: false,
            pointRadius: 3,
            pointHoverRadius: 6,
            borderWidth: 2.2
        }));

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels.length ? trendLabels : ['Day 1', 'Day 2', 'Day 3', 'Day 4'],
                datasets: datasets.length ? datasets : [{
                    label: @json(__('المراحل')),
                    data: [0, 0, 0, 0],
                    borderColor: '#3b82f6',
                    tension: 0.35,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#0f172a',
                        bodyColor: isDark ? '#94a3b8' : '#64748b',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        usePointStyle: true,
                        bodyFont: { family: 'Tajawal, sans-serif' },
                        titleFont: { family: 'Tajawal, sans-serif', weight: 'bold' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { family: 'Tajawal, sans-serif', size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)' },
                        ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { family: 'Arial, sans-serif', size: 11 }, precision: 0 }
                    }
                }
            }
        });
    }

    // 2. Customer Distribution Donut Chart
    const distCtx = document.getElementById('customerDistributionChart');
    const distLabels = @json($distData['labels'] ?? []);
    const distValues = @json($distData['data'] ?? []);
    const distColors = @json($distData['colors'] ?? []);

    if (distCtx && typeof Chart !== 'undefined') {
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: distLabels.length ? distLabels : [@json(__('لا توجد بيانات'))],
                datasets: [{
                    data: distValues.length ? distValues : [1],
                    backgroundColor: distColors.length ? distColors : ['#e2e8f0'],
                    borderWidth: 2,
                    borderColor: isDark ? '#111827' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f8fafc' : '#0f172a',
                        bodyColor: isDark ? '#94a3b8' : '#64748b',
                        borderColor: isDark ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        bodyFont: { family: 'Tajawal, sans-serif' }
                    }
                },
                cutout: '72%'
            }
        });
    }
})();

// Filter Functions
function handlePresetClick(preset) {
    const dropdown = document.getElementById('selectPresetDropdown');
    if (dropdown) dropdown.value = preset;
    handlePresetChange(preset);
}

function handlePresetChange(preset) {
    const customBox = document.getElementById('customDatePickerBox');
    if (preset === 'custom') {
        if (customBox) customBox.style.display = 'flex';
    } else {
        if (customBox) customBox.style.display = 'none';
        document.getElementById('reportFilterForm').submit();
    }
}

function handleDateChange() {
    const from = document.getElementById('inputFrom').value;
    const to = document.getElementById('inputTo').value;
    if (from && to) {
        document.getElementById('reportFilterForm').submit();
    }
}

function resetFilters() {
    window.location.href = @json(route('v2.reports.employees.index'));
}

['selectEmployee', 'selectCampaign', 'selectStage'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('change', () => {
            document.getElementById('reportFilterForm').submit();
        });
    }
});

let searchTimeout;
function debounceSearch(val) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const form = document.getElementById('reportFilterForm');
        let searchInput = form.querySelector('input[name="search"]');
        if (!searchInput) {
            searchInput = document.createElement('input');
            searchInput.type = 'hidden';
            searchInput.name = 'search';
            form.appendChild(searchInput);
        }
        searchInput.value = val;
        form.submit();
    }, 400);
}

function sortTable(column) {
    const currentSort = document.getElementById('inputSort').value;
    const currentDir = document.getElementById('inputDirection').value;
    let newDir = 'asc';
    if (currentSort === column) {
        newDir = currentDir === 'asc' ? 'desc' : 'asc';
    } else {
        newDir = column === 'name' ? 'asc' : 'desc';
    }
    document.getElementById('inputSort').value = column;
    document.getElementById('inputDirection').value = newDir;
    document.getElementById('reportFilterForm').submit();
}

// Follow-ups Popup Modal Logic
let modalFollowupItems = [];
async function openFollowupsModal(type, title) {
    const backdrop = document.getElementById('followupsModalBackdrop');
    const modalTitle = document.getElementById('followupsModalTitle');
    const modalCount = document.getElementById('followupsModalCount');
    const modalIcon = document.getElementById('followupsModalIcon');
    const modalBody = document.getElementById('followupsModalBody');
    const searchInput = document.getElementById('followupsSearchInput');

    if (searchInput) searchInput.value = '';
    modalTitle.textContent = title;
    modalCount.textContent = '...';

    if (type === 'overdue') {
        modalIcon.className = 'bi bi-clock-history';
        modalIcon.style.color = '#dc2637';
    } else if (type === 'today') {
        modalIcon.className = 'bi bi-calendar-check-fill';
        modalIcon.style.color = '#10b981';
    } else {
        modalIcon.className = 'bi bi-calendar-plus-fill';
        modalIcon.style.color = '#6366f1';
    }

    backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';

    modalBody.innerHTML = `
        <div style="text-align:center;padding:50px;color:var(--muted);">
            <i class="bi bi-arrow-repeat spin" style="font-size:32px;display:inline-block;animation:spin 1s linear infinite;"></i>
            <p style="margin-top:10px;">${@json(__('crm.loading_data'))}</p>
        </div>
        <style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
    `;

    try {
        const queryParams = new URLSearchParams(window.location.search);
        queryParams.set('followups_type', type);

        const res = await fetch(`${@json(route('v2.reports.employees.index'))}?${queryParams.toString()}`);
        if (!res.ok) throw new Error('فشل جلب قائمة المتابعات');
        const data = await res.json();

        modalFollowupItems = data.items || [];
        modalCount.textContent = modalFollowupItems.length;

        renderFollowupsModalList(modalFollowupItems);
    } catch (err) {
        modalBody.innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--red);">
                <i class="bi bi-exclamation-circle" style="font-size:32px;"></i>
                <p style="margin-top:10px;">${err.message}</p>
            </div>
        `;
    }
}

function renderFollowupsModalList(items) {
    const modalBody = document.getElementById('followupsModalBody');
    if (!items.length) {
        modalBody.innerHTML = `
            <div style="text-align:center;padding:48px 20px;color:var(--muted);">
                <i class="bi bi-check2-circle" style="font-size:32px;color:#10b981;display:block;margin-bottom:8px;"></i>
                <p style="margin:0;font-weight:700;">${@json(__('لا توجد متابعات مسجلة في هذه الفئة'))}</p>
            </div>
        `;
        return;
    }

    let rows = items.map(item => `
        <tr>
            <td>
                <strong style="color:var(--dark);">
                    <a href="${item.lead_url}" target="_blank" style="color:var(--dark);font-weight:800;transition:color .15s;" onmouseover="this.style.color='var(--red)'" onmouseout="this.style.color='var(--dark)'">
                        ${item.name}
                    </a>
                </strong>
                ${item.company_name ? `<div style="font-size:11px;color:var(--muted);">${item.company_name}</div>` : ''}
            </td>
            <td>
                ${item.phone ? `<a href="tel:${item.phone}" style="color:var(--muted);font-family:Arial,sans-serif;">${item.phone}</a>` : '<span style="color:var(--muted);">—</span>'}
            </td>
            <td>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;">
                    <span style="width:7px;height:7px;border-radius:50%;background:${item.stage_color};"></span>
                    ${item.stage_name}
                </span>
            </td>
            <td>
                <span style="font-size:12px;color:var(--dark);font-weight:600;">${item.assigned_user || '—'}</span>
            </td>
            <td>
                <span style="font-size:12px;font-family:Arial,sans-serif;color:var(--dark);font-weight:700;">${item.next_follow_up_at || '—'}</span>
                ${item.time_diff ? `<div style="font-size:10.5px;color:var(--muted);">${item.time_diff}</div>` : ''}
            </td>
            <td>
                <a href="${item.lead_url}" target="_blank" class="btn-exec btn-exec-soft" style="height:30px;padding:0 10px;font-size:11px;">
                    <i class="bi bi-box-arrow-up-right"></i> ${@json(__('فتح العميل'))}
                </a>
            </td>
        </tr>
    `).join('');

    modalBody.innerHTML = `
        <div class="table-wrap" style="border:none;border-radius:0;">
            <table class="exec-table">
                <thead>
                    <tr>
                        <th>${@json(__('العميل'))}</th>
                        <th>${@json(__('الهاتف'))}</th>
                        <th>${@json(__('المرحلة'))}</th>
                        <th>${@json(__('الموظف'))}</th>
                        <th>${@json(__('موعد المتابعة'))}</th>
                        <th>${@json(__('الإجراء'))}</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>
        </div>
    `;
}

function filterFollowupsModal(query) {
    const q = (query || '').trim().toLowerCase();
    if (!q) {
        renderFollowupsModalList(modalFollowupItems);
        return;
    }
    const filtered = modalFollowupItems.filter(i => {
        return (i.name && i.name.toLowerCase().includes(q))
            || (i.phone && i.phone.includes(q))
            || (i.company_name && i.company_name.toLowerCase().includes(q))
            || (i.assigned_user && i.assigned_user.toLowerCase().includes(q));
    });
    renderFollowupsModalList(filtered);
}

function closeFollowupsModal(e) {
    if (e && e.target && e.target !== document.getElementById('followupsModalBackdrop')) return;
    const backdrop = document.getElementById('followupsModalBackdrop');
    backdrop.classList.remove('active');
    document.body.style.overflow = '';
}

// Drilldown Drawer Logic
async function openDrilldown(userId) {
    const drawer = document.getElementById('employeeDrawer');
    const backdrop = document.getElementById('drawerBackdrop');
    const body = document.getElementById('drawerBody');
    const nameEl = document.getElementById('drawerEmployeeName');
    const grpEl = document.getElementById('drawerEmployeeGroups');

    drawer.classList.add('active');
    backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';

    body.innerHTML = `
        <div style="text-align:center;padding:50px;color:var(--muted);">
            <i class="bi bi-arrow-repeat spin" style="font-size:32px;display:inline-block;animation:spin 1s linear infinite;"></i>
            <p style="margin-top:10px;">${@json(__('crm.loading_data'))}</p>
        </div>
        <style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
    `;

    try {
        const res = await fetch(`${@json(url('reports/employees'))}/${userId}?ajax=1`);
        if (!res.ok) throw new Error('Failed to load drilldown data');
        const data = await res.json();
        const d = data.drilldown;

        nameEl.textContent = d.employee.name + ' (' + d.employee.username + ')';
        grpEl.textContent = d.employee.groups.join(', ') || '—';

        let html = `
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:20px;">
                <div style="padding:14px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--bg);text-align:center;">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);display:block;">${@json(__('crm.assigned_leads'))}</span>
                    <strong style="font-size:20px;font-weight:900;color:var(--dark);font-family:Arial,sans-serif;">${d.metrics.total_leads}</strong>
                </div>
                <div style="padding:14px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--bg);text-align:center;">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);display:block;">${@json(__('crm.kpi_total_followups'))}</span>
                    <strong style="font-size:20px;font-weight:900;color:#3b82f6;font-family:Arial,sans-serif;">${d.metrics.total_followups}</strong>
                </div>
                <div style="padding:14px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--bg);text-align:center;">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);display:block;">${@json(__('crm.kpi_overdue_followups'))}</span>
                    <strong style="font-size:20px;font-weight:900;color:var(--red);font-family:Arial,sans-serif;">${d.metrics.overdue_count}</strong>
                </div>
                <div style="padding:14px;border:1px solid var(--line);border-radius:var(--radius-sm);background:var(--bg);text-align:center;">
                    <span style="font-size:11px;font-weight:700;color:var(--muted);display:block;">${@json(__('crm.kpi_conversion_rate'))}</span>
                    <strong style="font-size:20px;font-weight:900;color:#10b981;font-family:Arial,sans-serif;">${d.metrics.conversion_rate}%</strong>
                </div>
            </div>

            <div style="margin-bottom:24px;">
                <h4 style="font-size:13px;font-weight:800;color:var(--dark);margin:0 0 10px;">${@json(__('crm.current_pipeline_distribution'))}</h4>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    ${d.stage_distribution.map(st => `
                        <div style="padding:8px 12px;border-radius:var(--radius-sm);border:1px solid var(--line);background:var(--bg);font-size:12px;display:flex;align-items:center;gap:6px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:${st.color};display:inline-block;"></span>
                            <span style="color:var(--dark);font-weight:600;">${st.stage_name}:</span>
                            <strong style="color:var(--dark);font-weight:800;">${st.count}</strong>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div style="margin-bottom:24px;">
                <h4 style="font-size:13px;font-weight:800;color:var(--dark);margin:0 0 10px;">${@json(__('crm.drilldown_leads'))}</h4>
                <div class="table-wrap">
                    <table class="exec-table">
                        <thead>
                            <tr>
                                <th>${@json(__('crm.lead_name'))}</th>
                                <th>${@json(__('crm.phone'))}</th>
                                <th>${@json(__('crm.status'))}</th>
                                <th>${@json(__('crm.next_followup'))}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${d.recent_leads.length ? d.recent_leads.map(l => `
                                <tr>
                                    <td><strong><a href="/leads/${l.id}" target="_blank" style="color:var(--red);">${l.name}</a></strong></td>
                                    <td>${l.phone || '—'}</td>
                                    <td><span style="display:inline-block;padding:2px 7px;border-radius:5px;background:var(--bg);border:1px solid var(--line);font-size:11px;">${l.status ? l.status.name_ar : '—'}</span></td>
                                    <td>${l.next_follow_up_at ? new Date(l.next_follow_up_at).toLocaleDateString() : '—'}</td>
                                </tr>
                            `).join('') : `<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:18px;">${@json(__('crm.no_employee_reports_data'))}</td></tr>`}
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h4 style="font-size:13px;font-weight:800;color:var(--dark);margin:0 0 10px;">${@json(__('crm.drilldown_followups'))}</h4>
                <div class="table-wrap">
                    <table class="exec-table">
                        <thead>
                            <tr>
                                <th>${@json(__('crm.lead_name'))}</th>
                                <th>${@json(__('crm.communication_channel'))}</th>
                                <th>${@json(__('crm.date'))}</th>
                                <th>${@json(__('crm.notes'))}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${d.recent_followups.length ? d.recent_followups.map(f => `
                                <tr>
                                    <td><strong>${f.lead ? f.lead.name : '—'}</strong></td>
                                    <td><span style="display:inline-block;padding:2px 7px;border-radius:5px;background:var(--bg);border:1px solid var(--line);font-size:11px;">${f.communication_type}</span></td>
                                    <td>${f.followed_up_at ? new Date(f.followed_up_at).toLocaleDateString() : '—'}</td>
                                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.outcome || '—'}</td>
                                </tr>
                            `).join('') : `<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:18px;">${@json(__('crm.no_employee_reports_data'))}</td></tr>`}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        body.innerHTML = html;
    } catch (err) {
        body.innerHTML = `
            <div style="text-align:center;padding:40px;color:var(--red);">
                <i class="bi bi-exclamation-circle" style="font-size:32px;"></i>
                <p style="margin-top:10px;">${err.message}</p>
            </div>
        `;
    }
}

function closeDrilldown() {
    document.getElementById('employeeDrawer').classList.remove('active');
    document.getElementById('drawerBackdrop').classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeFollowupsModal();
        closeDrilldown();
    }
});
</script>
</body>
</html>
