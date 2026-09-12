<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SokratCRM — {{ __('crm.dashboard') }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
/* ==========================================================================
   SOKRAT CRM BASE & LAYOUT STRUCTURE (Sidebar untouched, layout preserved)
   ========================================================================== */
:root {
  --red: #dc2637;
  --dark: #182033;
  --text: #4b5568;
  --muted: #8b94a5;
  --line: #e7e9ef;
  --bg: #f6f8fb;
  --card: #fff;
  --shadow: 0 12px 35px #1720330d;
}

* {
  box-sizing: border-box;
}

html {
  overflow-x: clip;
}
body {
  margin: 0;
  min-width: 320px;
  width: 100%;
  max-width: 100vw;
  background: var(--bg);
  color: var(--dark);
  font-family: Tajawal, Cairo, Tahoma, Arial, sans-serif;
  font-size: 15px;
  overflow-x: clip;
}
button, input, select {
  font: inherit;
}

a {
  color: inherit;
}

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
.side {
  grid-column: auto;
  order: 0;
  flex: 0 0 288px;
  width: 288px;
  min-width: 288px;
  max-width: 288px;
  position: sticky;
  top: 0;
  height: 100vh;
  max-height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  align-self: flex-start;
  box-sizing: border-box;
  padding: 24px 17px;
  background: var(--bg-card, #fff);
  border-inline-end: 1px solid var(--line);
  z-index: 80;
}

.main {
  grid-column: auto;
  order: 1;
  flex: 1 1 auto;
  width: calc(100% - 288px);
  min-height: 100vh;
  min-width: 0;
  padding: 24px clamp(16px, 2.5vw, 36px) 48px;
}
/* Sidebar original styles (preserved strictly for layout parity) */
.brand { display: flex; align-items: center; gap: 11px; padding: 4px 8px 20px; margin-bottom: 17px; border-bottom: 1px solid var(--line); text-decoration: none; }
.logo { width: 58px; height: 58px; display: block; flex: 0 0 58px; object-fit: contain; }
.brand strong { display: block; color: var(--red); font: 900 22px Arial; }
.brand small { display: block; margin-top: 5px; color: var(--muted); font-size: 12px; }
.caption { margin: 0 12px 9px; color: #a0a7b4; font-size: 12px; font-weight: bold; }
.nav { display: grid; gap: 6px; }
.link, .toggle { width: 100%; min-height: 49px; display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid transparent; border-radius: 13px; background: transparent; color: #566175; text-decoration: none; text-align: right; cursor: pointer; transition: .2s; }
.link:hover, .toggle:hover { color: var(--red); background: #fff5f6; transform: translateX(-2px); }
.link.active { color: #fff; background: linear-gradient(135deg, #e83243, #c91d2e); box-shadow: 0 11px 25px #dc263737; }
.ico { width: 32px; height: 32px; flex: 0 0 32px; display: grid; place-items: center; border-radius: 10px; background: #f0f2f6; font-size: 17px; }
.active .ico { background: #ffffff2b; }
.label { flex: 1; font-size: 15px; font-weight: 800; }
.count { min-width: 24px; height: 24px; display: grid; place-items: center; padding: 0 6px; border-radius: 99px; background: #eef0f4; color: #7e8796; font: 800 10px Arial; }
.active .count { color: #fff; background: #ffffff2b; }
.arrow { font-size: 11px; color: #a2a9b5; transition: .2s; }
.toggle[aria-expanded=true] .arrow { transform: rotate(180deg); }
.sub { display: grid; grid-template-rows: 0fr; transition: .22s; }
.sub.open { grid-template-rows: 1fr; }
.sub > div { min-height: 0; overflow: hidden; }
.sub nav { display: grid; gap: 2px; margin: 3px 28px 7px 0; padding-inline-end: 14px; border-inline-end: 1px solid var(--line); }
.sub a { padding: 8px 10px; border-radius: 8px; color: #788294; text-decoration: none; font-size: 13px; font-weight: bold; }
.sub a:hover { color: var(--red); background: #fff2f4; }
.overlay { display: none; position: fixed; inset: 0; border: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 99990 !important; cursor: pointer; }

@media(max-width: 900px) {
  .app { display: block; }
  .side {
    position: fixed !important;
    top: 0 !important;
    bottom: 0 !important;
    height: 100vh !important;
    max-height: 100vh !important;
    inset-inline-end: 0 !important;
    width: min(288px, calc(100vw - 45px)) !important;
    min-width: 0 !important;
    max-width: none !important;
    transform: translateX(105%) !important;
    transition: transform .25s ease !important;
    z-index: 80 !important;
    border-inline-end: none !important;
  }
  [dir="ltr"] .side {
    inset-inline-end: auto !important;
    inset-inline-start: 0 !important;
    transform: translateX(-105%) !important;
  }
  .side-open,
  .crm-side-open,
  .transfer-side-open {
    overflow: hidden;
    touch-action: none;
  }
  .side-open .side,
  .crm-side-open .side,
  .transfer-side-open .side {
    transform: none !important;
  }
  .side-open .overlay,
  .crm-side-open .overlay,
  .transfer-side-open .overlay {
    display: block !important;
    opacity: 1 !important;
    pointer-events: auto !important;
  }
  .main { width: 100% !important; padding: 14px 12px 36px; min-height: 100vh; }
}

/* ==========================================================================
   CRM V2 MODERN DASHBOARD SCOPED DESIGN TOKENS
   ========================================================================== */
.crm-dashboard-v2 {
  --d-bg: #f6f8fb;
  --d-surface: #ffffff;
  --d-surface-alt: #f8fafc;
  --d-surface-hover: #f1f5f9;
  --d-border: #e5e9f2;
  --d-border-subtle: #f1f5f9;
  --d-border-strong: #cbd5e1;
  --d-text: #0f172a;
  --d-text-muted: #64748b;
  --d-text-subtle: #94a3b8;
  --d-primary: #dc2637;
  --d-primary-subtle: rgba(220, 38, 55, 0.08);
  --d-primary-glow: rgba(220, 38, 55, 0.25);
  --d-blue: #3b82f6;
  --d-purple: #8b5cf6;
  --d-emerald: #10b981;
  --d-amber: #f59e0b;
  --d-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 6px 16px rgba(15, 23, 42, 0.03);
  --d-shadow-hover: 0 8px 24px -4px rgba(15, 23, 42, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04);
  --d-chart-grid: rgba(148, 163, 184, 0.14);
  --d-chart-text: #64748b;
  --d-radius-sm: 10px;
  --d-radius-md: 14px;
  --d-radius-lg: 18px;
  --d-radius-xl: 22px;
  --d-transition: 200ms cubic-bezier(0.16, 1, 0.3, 1);
  background-color: var(--d-bg);
  color: var(--d-text);
  transition: background-color var(--d-transition), color var(--d-transition);
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow-x: clip;
}
/* DARK THEME SCOPED OVERRIDES */
.crm-dashboard-v2[data-theme="dark"],
.dark-mode .crm-dashboard-v2,
html.dark .crm-dashboard-v2,
html.dark-mode .crm-dashboard-v2 {
  --d-bg: #0b0f19;
  --d-surface: #111827;
  --d-surface-alt: #161f30;
  --d-surface-hover: #1e293b;
  --d-border: #1f293d;
  --d-border-subtle: #172033;
  --d-border-strong: #334155;
  --d-text: #f8fafc;
  --d-text-muted: #94a3b8;
  --d-text-subtle: #64748b;
  --d-primary: #ef4444;
  --d-primary-subtle: rgba(239, 68, 68, 0.15);
  --d-primary-glow: rgba(239, 68, 68, 0.3);
  --d-shadow: 0 4px 20px rgba(0, 0, 0, 0.35), 0 1px 3px rgba(0, 0, 0, 0.2);
  --d-shadow-hover: 0 10px 30px rgba(0, 0, 0, 0.5), 0 2px 8px rgba(0, 0, 0, 0.3);
  --d-chart-grid: rgba(255, 255, 255, 0.08);
  --d-chart-text: #94a3b8;
}

/* ==========================================================================
   TOP HEADER (Clean, no standalone duplicate theme toggle)
   ========================================================================== */
.crm-dashboard-v2 .dash-top-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 20px;
  margin-bottom: 18px;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  background: var(--d-surface);
  box-shadow: var(--d-shadow);
  transition: all var(--d-transition);
}

.crm-dashboard-v2 .dash-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.crm-dashboard-v2 .dash-menu-toggle {
  display: none;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  color: var(--d-text);
  font-size: 20px;
  cursor: pointer;
  place-items: center;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .dash-menu-toggle:hover {
  background: var(--d-surface-hover);
  border-color: var(--d-primary);
  color: var(--d-primary);
}
@media(max-width: 900px) {
  .crm-dashboard-v2 .dash-menu-toggle { display: grid; }
}

.crm-dashboard-v2 .dash-header-title {
  min-width: 0;
}
.crm-dashboard-v2 .dash-header-title h1 {
  margin: 0;
  font-size: 22px;
  font-weight: 900;
  color: var(--d-text);
  letter-spacing: -0.3px;
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.crm-dashboard-v2 .dash-header-title p {
  margin: 4px 0 0;
  color: var(--d-text-muted);
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.crm-dashboard-v2 .dash-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

/* Grid Items and Container Bounds */
.crm-dashboard-v2 .dash-top-analytics-grid,
.crm-dashboard-v2 .dash-performance-grid,
.crm-dashboard-v2 .dash-activity-grid,
.crm-dashboard-v2 .kpi-block-2x2,
.crm-dashboard-v2 .donut-analytics-side,
.crm-dashboard-v2 .dash-stacked-metrics,
.crm-dashboard-v2 .dash-pipeline-strip-wrap,
.crm-dashboard-v2 .dash-filter-bar,
.crm-dashboard-v2 .dash-shortcuts-section {
  min-width: 0;
  max-width: 100%;
}
.crm-dashboard-v2 .dash-top-analytics-grid > *,
.crm-dashboard-v2 .dash-performance-grid > *,
.crm-dashboard-v2 .dash-activity-grid > *,
.crm-dashboard-v2 .kpi-block-2x2 > *,
.crm-dashboard-v2 .donut-analytics-side > *,
.crm-dashboard-v2 .dash-stacked-metrics > * {
  min-width: 0;
  max-width: 100%;
}
.crm-dashboard-v2 .donut-chart-wrap canvas,
.crm-dashboard-v2 .chart-container-relative canvas {
  max-width: 100% !important;
}

/* ==========================================================================
   COMPACT FILTER TOOLBAR
   ========================================================================== */
.crm-dashboard-v2 .dash-filter-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  padding: 12px 18px;
  margin-bottom: 16px;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-md);
  background: var(--d-surface);
  box-shadow: var(--d-shadow);
}
.crm-dashboard-v2 .dash-filter-item {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 140px;
  flex: 1 1 150px;
}
.crm-dashboard-v2 .dash-filter-item label {
  font-size: 12px;
  font-weight: 700;
  color: var(--d-text-muted);
  white-space: nowrap;
}
.crm-dashboard-v2 .dash-filter-item select,
.crm-dashboard-v2 .dash-filter-item input {
  width: 100%;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  color: var(--d-text);
  font-size: 12px;
  font-weight: 600;
  outline: none;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .dash-filter-item select:focus,
.crm-dashboard-v2 .dash-filter-item input:focus {
  border-color: var(--d-primary);
  background: var(--d-surface);
  box-shadow: 0 0 0 3px var(--d-primary-subtle);
}
.crm-dashboard-v2 .dash-filter-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 0 0 auto;
}
.crm-dashboard-v2 .btn-filter-submit {
  height: 40px;
  padding: 0 16px;
  border: 0;
  border-radius: var(--d-radius-sm);
  background: var(--red);
  color: #fff;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .btn-filter-submit:hover {
  background: #b91c1c;
  transform: translateY(-1px);
}
.crm-dashboard-v2 .btn-filter-clear {
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  color: var(--d-text-muted);
  text-decoration: none;
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .btn-filter-clear:hover {
  background: var(--d-surface-hover);
  color: var(--d-text);
}

/* ==========================================================================
   CHANGE 2: DYNAMIC PIPELINE STAGES STRIP DIRECTLY UNDER FILTERS
   ========================================================================== */
.crm-dashboard-v2 .dash-pipeline-strip-wrap {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 14px 18px;
  margin-bottom: 22px;
  box-shadow: var(--d-shadow);
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}
.crm-dashboard-v2 .dash-pipeline-strip-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.crm-dashboard-v2 .dash-pipeline-strip-title {
  font-size: 13.5px;
  font-weight: 800;
  color: var(--d-text);
  display: flex;
  align-items: center;
  gap: 7px;
}
.crm-dashboard-v2 .dash-kanban-link {
  font-size: 12.5px;
  font-weight: 800;
  color: var(--d-primary);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  border: 1px solid var(--d-border);
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .dash-kanban-link:hover {
  color: #b91c1c;
  background: var(--d-surface-hover);
  border-color: var(--d-primary);
  transform: translateY(-1px);
}
.crm-dashboard-v2 .dash-pipeline-strip {
  display: flex;
  flex-wrap: nowrap;
  gap: 10px;
  overflow-x: auto;
  min-width: 0;
  max-width: 100%;
  scrollbar-width: thin;
  scrollbar-color: var(--d-border-strong) var(--d-surface-alt);
  scroll-snap-type: inline proximity;
  overscroll-behavior-inline: contain;
  -webkit-overflow-scrolling: touch;
  touch-action: pan-x;
  cursor: grab;
  padding: 4px 2px 8px;
}
.crm-dashboard-v2 .dash-pipeline-strip:active {
  cursor: grabbing;
}
.crm-dashboard-v2 .dash-pipeline-strip:hover,
.crm-dashboard-v2 .dash-pipeline-strip:focus-within {
  scrollbar-color: var(--d-primary) var(--d-surface-alt);
}
.crm-dashboard-v2 .dash-pipeline-strip::-webkit-scrollbar {
  height: 5px;
}
.crm-dashboard-v2 .dash-pipeline-strip::-webkit-scrollbar-track {
  background: var(--d-surface-alt);
  border-radius: 99px;
}
.crm-dashboard-v2 .dash-pipeline-strip::-webkit-scrollbar-thumb {
  background: var(--d-border-strong);
  border-radius: 99px;
}
.crm-dashboard-v2 .dash-pipeline-strip:hover::-webkit-scrollbar-thumb,
.crm-dashboard-v2 .dash-pipeline-strip:focus-within::-webkit-scrollbar-thumb {
  background: var(--d-primary);
}
.crm-dashboard-v2 .pipeline-flow-pill {
  flex: 0 0 auto;
  min-width: 148px;
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 10px 14px;
  border-radius: 11px;
  background: var(--d-surface-alt);
  border: 1px solid var(--d-border);
  text-decoration: none;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  scroll-snap-align: start;
  position: relative;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}
.crm-dashboard-v2 .pipeline-flow-pill:hover {
  transform: translateY(-2px);
  border-color: var(--pill-color, var(--d-primary));
  background: var(--d-surface);
  box-shadow: 0 4px 14px color-mix(in srgb, var(--pill-color, var(--d-primary)) 14%, transparent), 0 2px 5px rgba(0, 0, 0, 0.03);
}
.crm-dashboard-v2 .pipeline-flow-icon {
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  font-size: 18px;
  background: var(--pill-bg, rgba(59, 130, 246, 0.1));
  color: var(--pill-color, #3b82f6);
  transition: transform 0.2s ease;
}
.crm-dashboard-v2 .pipeline-flow-pill:hover .pipeline-flow-icon {
  transform: scale(1.05);
}
.crm-dashboard-v2 .pipeline-flow-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.crm-dashboard-v2 .pipeline-flow-info strong {
  font-size: 12.5px;
  font-weight: 800;
  color: var(--d-text);
  white-space: nowrap;
  letter-spacing: -0.01em;
}
.crm-dashboard-v2 .pipeline-flow-info small {
  font-size: 15px;
  font-weight: 900;
  color: var(--d-text);
  font-family: Arial, sans-serif;
  line-height: 1.15;
}

/* ==========================================================================
   TOP ANALYTICS GRID (2x2 KPI Block + 2 Donut Cards)
   ========================================================================== */
.crm-dashboard-v2 .dash-top-analytics-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-bottom: 22px;
}
@media (min-width: 1100px) {
  .crm-dashboard-v2 .dash-top-analytics-grid {
    grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  }
}

/* 2x2 KPI Cards Container */
.crm-dashboard-v2 .kpi-block-2x2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}
.crm-dashboard-v2 .kpi-card-modern {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 18px 20px;
  box-shadow: var(--d-shadow);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-decoration: none;
  transition: all var(--d-transition);
  position: relative;
  overflow: hidden;
}
.crm-dashboard-v2 .kpi-card-modern:hover {
  transform: translateY(-3px);
  box-shadow: var(--d-shadow-hover);
  border-color: color-mix(in srgb, var(--card-accent, #3b82f6) 40%, var(--d-border));
}
.crm-dashboard-v2 .kpi-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.crm-dashboard-v2 .kpi-card-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--d-text-muted);
}
.crm-dashboard-v2 .kpi-card-icon {
  width: 40px;
  height: 40px;
  border-radius: var(--d-radius-sm);
  display: grid;
  place-items: center;
  font-size: 19px;
  background: var(--icon-bg, rgba(59, 130, 246, 0.1));
  color: var(--card-accent, #3b82f6);
}
.crm-dashboard-v2 .kpi-card-value {
  font-size: 28px;
  font-weight: 900;
  color: var(--d-text);
  font-family: Arial, Tahoma, sans-serif;
  line-height: 1;
  margin-bottom: 6px;
  letter-spacing: -0.5px;
}
.crm-dashboard-v2 .kpi-card-sub {
  font-size: 11px;
  font-weight: 700;
  color: var(--d-text-subtle);
  display: flex;
  align-items: center;
  gap: 4px;
}
.crm-dashboard-v2 .kpi-head-title-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}
.crm-dashboard-v2 .kpi-stage-selectors-pair {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.crm-dashboard-v2 .kpi-stage-select-item {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.crm-dashboard-v2 .kpi-stage-select-tag {
  font-size: 10px;
  font-weight: 800;
  color: var(--d-text-subtle);
  white-space: nowrap;
}
.crm-dashboard-v2 .kpi-stage-select {
  height: 24px;
  line-height: 22px;
  padding: 0 6px;
  font-size: 11px;
  font-weight: 700;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  color: var(--d-text);
  cursor: pointer;
  outline: none;
  max-width: 130px;
  width: fit-content;
  white-space: nowrap;
  text-overflow: ellipsis;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .kpi-stage-select-item .kpi-stage-select {
  max-width: 100px;
  font-size: 10.5px;
  padding: 0 4px;
}
.crm-dashboard-v2 .kpi-stage-select:hover,
.crm-dashboard-v2 .kpi-stage-select:focus {
  border-color: var(--d-primary);
  background: var(--d-surface);
}
.crm-dashboard-v2 .dash-chart-controls {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.crm-dashboard-v2 .dash-chart-stage-dropdown {
  position: relative;
  display: inline-block;
}
.crm-dashboard-v2 .dash-chart-dropdown-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 30px;
  padding: 0 10px;
  border-radius: var(--d-radius-sm);
  border: 1px solid var(--d-border);
  background: var(--d-surface-alt);
  color: var(--d-text);
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  outline: none;
  transition: all var(--d-transition);
  white-space: nowrap;
}
.crm-dashboard-v2 .dash-chart-dropdown-toggle:hover,
.crm-dashboard-v2 .dash-chart-dropdown-toggle:focus-visible,
.crm-dashboard-v2 .dash-chart-stage-dropdown.is-open .dash-chart-dropdown-toggle {
  border-color: var(--d-primary);
  background: var(--d-surface);
  box-shadow: 0 0 0 2px var(--d-primary-subtle);
}
.crm-dashboard-v2 .dash-chart-dropdown-toggle .toggle-arrow {
  font-size: 10px;
  color: var(--d-text-subtle);
  transition: transform var(--d-transition);
}
.crm-dashboard-v2 .dash-chart-stage-dropdown.is-open .toggle-arrow {
  transform: rotate(180deg);
}
.crm-dashboard-v2 .dash-chart-dropdown-menu {
  display: none;
  position: absolute;
  top: calc(100% + 6px);
  inset-inline-end: 0;
  z-index: 100;
  min-width: 210px;
  max-width: 280px;
  padding: 8px;
  border-radius: var(--d-radius-md);
  border: 1px solid var(--d-border);
  background: var(--d-surface, #ffffff);
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12), 0 2px 6px rgba(0, 0, 0, 0.04);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.crm-dashboard-v2[data-theme="dark"] .dash-chart-dropdown-menu,
.dark-mode .crm-dashboard-v2 .dash-chart-dropdown-menu,
html.dark-mode .dash-chart-dropdown-menu {
  background: var(--d-surface, #111827) !important;
  border-color: var(--d-border, #1f293d) !important;
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.55), 0 2px 8px rgba(0, 0, 0, 0.3) !important;
}
.crm-dashboard-v2 .dash-chart-stage-dropdown.is-open .dash-chart-dropdown-menu {
  display: block;
}
.crm-dashboard-v2 .dash-chart-dropdown-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  padding-bottom: 6px;
  margin-bottom: 6px;
  border-bottom: 1px solid var(--d-border-subtle);
}
.crm-dashboard-v2 .btn-dropdown-action {
  background: transparent;
  border: none;
  padding: 3px 6px;
  font-size: 10px;
  font-weight: 800;
  color: var(--d-primary);
  cursor: pointer;
  border-radius: 4px;
  transition: background var(--d-transition);
}
.crm-dashboard-v2 .btn-dropdown-action:hover {
  background: var(--d-primary-subtle);
}
.crm-dashboard-v2 .dash-chart-dropdown-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-height: 200px;
  overflow-y: auto;
}
.crm-dashboard-v2 .dash-chart-stage-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 5px 8px;
  border-radius: 6px;
  cursor: pointer;
  user-select: none;
  font-size: 11.5px;
  font-weight: 700;
  color: var(--d-text);
  transition: background var(--d-transition);
}
.crm-dashboard-v2 .dash-chart-stage-item:hover {
  background: var(--d-surface-hover);
}
.crm-dashboard-v2 .dash-chart-stage-item input[type="checkbox"] {
  width: 14px;
  height: 14px;
  accent-color: var(--d-primary);
  cursor: pointer;
}
.crm-dashboard-v2 .chart-stage-item-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.crm-dashboard-v2 .chart-stage-item-name {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.crm-dashboard-v2 .dash-chart-report-link {
  font-size: 12px;
  font-weight: 800;
  color: var(--d-primary);
  text-decoration: none;
  white-space: nowrap;
}
@media (max-width: 900px) {
  .crm-dashboard-v2 .dash-chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
@media (max-width: 580px) {
  .crm-dashboard-v2 .kpi-stage-selectors-pair {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
/* Donut Cards Side */
.crm-dashboard-v2 .donut-analytics-side {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}
@media (max-width: 580px) {
  .crm-dashboard-v2 .donut-analytics-side {
    grid-template-columns: 1fr;
  }
}
.crm-dashboard-v2 .donut-card-modern {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 18px 20px;
  box-shadow: var(--d-shadow);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all var(--d-transition);
  min-height: 220px;
}
.crm-dashboard-v2 .donut-card-modern:hover {
  box-shadow: var(--d-shadow-hover);
}
.crm-dashboard-v2 .donut-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.crm-dashboard-v2 .donut-card-title {
  margin: 0;
  font-size: 14px;
  font-weight: 800;
  color: var(--d-text);
}
.crm-dashboard-v2 .donut-card-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 6px;
  background: var(--d-surface-alt);
  color: var(--d-text-muted);
}
.crm-dashboard-v2 .donut-chart-wrap {
  position: relative;
  width: 100%;
  height: 120px;
  display: grid;
  place-items: center;
}
.crm-dashboard-v2 .donut-center-stat {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  pointer-events: none;
}
.crm-dashboard-v2 .donut-center-stat strong {
  display: block;
  font-size: 18px;
  font-weight: 900;
  color: var(--d-text);
  font-family: Arial, sans-serif;
  line-height: 1;
}
.crm-dashboard-v2 .donut-center-stat small {
  display: block;
  font-size: 10px;
  font-weight: 700;
  color: var(--d-text-muted);
  margin-top: 2px;
}
.crm-dashboard-v2 .donut-card-footer {
  margin-top: 8px;
  font-size: 11px;
  font-weight: 700;
  color: var(--d-text-muted);
  text-align: center;
}

/* ==========================================================================
   SECOND ROW: PRIMARY PERFORMANCE CHART + STACKED OPERATIONAL METRICS
   ========================================================================== */
.crm-dashboard-v2 .dash-performance-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-bottom: 22px;
}
@media (min-width: 1100px) {
  .crm-dashboard-v2 .dash-performance-grid {
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 0.95fr);
  }
}

.crm-dashboard-v2 .dash-chart-card {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 22px 24px;
  box-shadow: var(--d-shadow);
  display: flex;
  flex-direction: column;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .dash-chart-card:hover {
  box-shadow: var(--d-shadow-hover);
}
.crm-dashboard-v2 .dash-chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 14px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--d-border-subtle);
}
.crm-dashboard-v2 .dash-chart-title-group h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--d-text);
}
.crm-dashboard-v2 .dash-chart-title-group p {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--d-text-muted);
}
.crm-dashboard-v2 .chart-container-relative {
  position: relative;
  width: 100%;
  height: 290px;
}

/* Chart Empty Container (keeps tests valid) */
.crm-dashboard-v2 .chart-empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 32px 20px;
  min-height: 260px;
  border-radius: var(--d-radius-md);
  background: var(--d-surface-alt);
  border: 1px dashed var(--d-border);
}
.crm-dashboard-v2 .chart-empty-icon-wrap {
  width: 46px;
  height: 46px;
  border-radius: var(--d-radius-md);
  background: rgba(52, 120, 246, 0.1);
  color: #3b82f6;
  display: grid;
  place-items: center;
  font-size: 20px;
  margin-bottom: 10px;
}
.crm-dashboard-v2 .chart-empty-title {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 800;
  color: var(--d-text);
}
.crm-dashboard-v2 .chart-empty-desc {
  margin: 0 0 12px;
  font-size: 12px;
  color: var(--d-text-muted);
}
.crm-dashboard-v2 .btn-chart-empty-action {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: var(--d-radius-sm);
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  color: var(--d-text);
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
}

/* Stacked Operational Metric Cards */
.crm-dashboard-v2 .dash-stacked-metrics {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.crm-dashboard-v2 .metric-card-box {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 18px 20px;
  box-shadow: var(--d-shadow);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  flex: 1;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .metric-card-box:hover {
  box-shadow: var(--d-shadow-hover);
}
.crm-dashboard-v2 .metric-box-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.crm-dashboard-v2 .metric-box-head h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 800;
  color: var(--d-text);
  display: flex;
  align-items: center;
  gap: 6px;
}
.crm-dashboard-v2 .metric-items-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  text-align: center;
}
.crm-dashboard-v2 .metric-sub-item {
  padding: 8px 6px;
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
}
.crm-dashboard-v2 .metric-sub-item strong {
  display: block;
  font-size: 16px;
  font-weight: 900;
  color: var(--d-text);
  font-family: Arial, sans-serif;
  line-height: 1;
}
.crm-dashboard-v2 .metric-sub-item small {
  display: block;
  font-size: 10px;
  font-weight: 700;
  color: var(--d-text-muted);
  margin-top: 4px;
}
.crm-dashboard-v2 .stage-activity-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.crm-dashboard-v2 .stage-activity-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.crm-dashboard-v2 .stage-activity-select {
  height: 26px;
  padding: 0 8px;
  font-size: 12px;
  font-weight: 700;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  color: var(--d-text);
  cursor: pointer;
  outline: none;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .stage-activity-select:hover,
.crm-dashboard-v2 .stage-activity-select:focus {
  border-color: var(--d-primary);
  background: var(--d-surface);
}
.crm-dashboard-v2 .stage-activity-total-badge {
  font-size: 11px;
  font-weight: 800;
  color: var(--d-text-muted);
  padding: 2px 8px;
  border-radius: 99px;
  background: var(--d-surface-alt);
}
.crm-dashboard-v2 .stage-activity-counts-row {
  grid-template-columns: repeat(3, 1fr) !important;
}
.crm-dashboard-v2 .stage-activity-details {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 4px;
  max-height: 180px;
  overflow-y: auto;
  scrollbar-width: thin;
}
.crm-dashboard-v2 .stage-activity-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 10px;
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  font-size: 12px;
}
.crm-dashboard-v2 .stage-activity-lead-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  flex: 1;
}
.crm-dashboard-v2 .stage-activity-lead-link {
  font-weight: 700;
  color: var(--d-text);
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.crm-dashboard-v2 .stage-activity-lead-link:hover {
  color: var(--d-primary);
}
.crm-dashboard-v2 .stage-activity-lead-name {
  font-weight: 700;
  color: var(--d-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.crm-dashboard-v2 .stage-activity-lead-emp {
  font-size: 10px;
  color: var(--d-text-muted);
}
.crm-dashboard-v2 .stage-activity-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}
.crm-dashboard-v2 .stage-activity-badge {
  font-size: 10px;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 4px;
}
.crm-dashboard-v2 .badge-today {
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
}
.crm-dashboard-v2 .badge-overdue {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
}
.crm-dashboard-v2 .badge-upcoming {
  background: rgba(16, 185, 129, 0.15);
  color: #10b981;
}
.crm-dashboard-v2 .stage-activity-time {
  font-size: 10px;
  color: var(--d-text-muted);
  font-family: Arial, sans-serif;
}
.crm-dashboard-v2 .stage-activity-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 14px 8px;
  color: var(--d-text-muted);
  font-size: 12px;
  font-weight: 700;
  background: var(--d-surface-alt);
  border-radius: var(--d-radius-sm);
}

/* Interactive Stage Activity Metrics */
.crm-dashboard-v2 button.stage-activity-clickable-tile {
  font: inherit;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  cursor: pointer;
  padding: 8px 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  transition: all var(--d-transition);
  text-align: center;
  outline: none;
  width: 100%;
}
.crm-dashboard-v2 button.stage-activity-clickable-tile:hover {
  border-color: var(--d-primary);
  background: var(--d-surface-hover, rgba(0,0,0,0.03));
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.crm-dashboard-v2 button.stage-activity-clickable-tile:focus-visible {
  outline: 2px solid var(--d-primary);
  outline-offset: 2px;
}
.crm-dashboard-v2 button.stage-activity-total-badge.stage-activity-clickable-tile {
  display: inline-flex;
  flex-direction: row;
  align-items: center;
  width: auto;
  padding: 3px 10px;
  border-radius: 99px;
  font-size: 11px;
  font-weight: 800;
  color: var(--d-text-muted);
}
.crm-dashboard-v2 button.stage-activity-total-badge.stage-activity-clickable-tile:hover {
  color: var(--d-primary);
  border-color: var(--d-primary);
  transform: none;
}

/* Stage Activity Modal */
.stage-activity-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  opacity: 1;
  transition: opacity 0.2s ease;
}
.stage-activity-modal-backdrop[hidden] {
  display: none !important;
}
.stage-activity-modal {
  width: 100%;
  max-width: 680px;
  max-height: 85vh;
  max-height: 85dvh;
  background: var(--d-surface, #fff);
  color: var(--d-text, #182033);
  border: 1px solid var(--d-border, #e2e8f0);
  border-radius: var(--d-radius, 14px);
  box-shadow: 0 20px 45px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  outline: none;
}
@keyframes modalScaleIn {
  from { opacity: 0; transform: scale(0.96) translateY(8px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.stage-activity-modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px 14px;
  border-bottom: 1px solid var(--d-border, #e2e8f0);
  background: var(--d-surface-alt, #fafbfc);
}
.stage-activity-modal-title-wrap h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 900;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--d-text, #182033);
}
.stage-activity-modal-title-wrap p {
  margin: 4px 0 0;
  font-size: 12px;
  font-weight: 700;
  color: var(--d-text-muted, #64748b);
}
.stage-activity-modal-close {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 1px solid var(--d-border, #e2e8f0);
  border-radius: 9px;
  background: var(--d-surface, #fff);
  color: var(--d-text-muted, #64748b);
  cursor: pointer;
  font-size: 14px;
  transition: all var(--d-transition);
}
.stage-activity-modal-close:hover {
  border-color: #ef4444;
  color: #ef4444;
  background: rgba(239, 68, 68, 0.08);
}
.stage-activity-modal-tabs {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 6px;
  padding: 10px 16px;
  border-bottom: 1px solid var(--d-border, #e2e8f0);
  background: var(--d-surface, #fff);
}
.stage-activity-modal-tab {
  padding: 7px 4px;
  font-size: 12px;
  font-weight: 800;
  border: 1px solid var(--d-border, #e2e8f0);
  border-radius: 8px;
  background: var(--d-surface-alt, #f8fafc);
  color: var(--d-text-muted, #64748b);
  cursor: pointer;
  transition: all var(--d-transition);
  text-align: center;
}
.stage-activity-modal-tab:hover {
  border-color: var(--d-primary);
  color: var(--d-primary);
}
.stage-activity-modal-tab.active {
  background: var(--d-primary, #182033);
  color: #fff !important;
  border-color: var(--d-primary, #182033);
}
.stage-activity-modal-body {
  flex: 1 1 auto;
  min-height: 220px;
  max-height: 52vh;
  overflow-y: auto;
  padding: 8px 12px;
  background: var(--d-surface, #fff);
}
.stage-activity-lead-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 14px;
  margin-bottom: 6px;
  border: 1px solid var(--d-border, #e2e8f0);
  border-radius: var(--d-radius-sm, 8px);
  background: var(--d-surface-alt, #f8fafc);
  transition: all var(--d-transition);
}
.stage-activity-lead-card:hover {
  border-color: var(--d-primary);
  background: var(--d-surface-hover, rgba(0,0,0,0.02));
}
.stage-activity-lead-main {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
  flex: 1;
}
.stage-activity-lead-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.stage-activity-lead-name-link {
  font-size: 13px;
  font-weight: 800;
  color: var(--d-text, #182033);
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.stage-activity-lead-name-link:hover {
  color: var(--d-primary);
}
.stage-activity-lead-status-pill {
  font-size: 10px;
  font-weight: 800;
  padding: 1px 7px;
  border-radius: 99px;
  border: 1px solid currentColor;
}
.stage-activity-lead-sub-row {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 11px;
  color: var(--d-text-muted, #64748b);
  flex-wrap: wrap;
}
.stage-activity-lead-sub-row span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.stage-activity-lead-timing-col {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}
.stage-activity-lead-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
.stage-activity-quick-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 9px;
  border: 1px solid var(--d-border, #e2e8f0);
  border-radius: 6px;
  background: var(--d-surface, #fff);
  color: var(--d-text, #182033);
  font-size: 11px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
  transition: all var(--d-transition);
}
.stage-activity-quick-btn:hover {
  border-color: var(--d-primary);
  color: var(--d-primary);
  background: rgba(0,0,0,0.02);
}
.stage-activity-quick-btn.primary {
  background: var(--d-primary, #dc2637);
  color: #fff;
  border-color: var(--d-primary, #dc2637);
}
.stage-activity-modal-empty {
  min-height: 180px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px 16px;
  text-align: center;
  color: var(--d-text-muted, #64748b);
}
.stage-activity-modal-empty i {
  font-size: 28px;
  margin-bottom: 8px;
  color: #94a3b8;
}
.stage-activity-modal-empty h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 800;
  color: var(--d-text, #182033);
}
.stage-activity-modal-empty p {
  margin: 4px 0 0;
  font-size: 12px;
}
.stage-activity-modal-loading {
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--d-text-muted, #64748b);
  font-size: 13px;
  font-weight: 700;
}
.stage-activity-modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 18px;
  border-top: 1px solid var(--d-border, #e2e8f0);
  background: var(--d-surface-alt, #fafbfc);
  font-size: 12px;
}
.stage-activity-modal-info {
  font-weight: 700;
  color: var(--d-text-muted, #64748b);
}
.stage-activity-modal-pagination {
  display: flex;
  align-items: center;
  gap: 6px;
}
.stage-activity-page-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 10px;
  border: 1px solid var(--d-border, #e2e8f0);
  border-radius: 6px;
  background: var(--d-surface, #fff);
  color: var(--d-text, #182033);
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  transition: all var(--d-transition);
}
.stage-activity-page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.stage-activity-page-btn:not(:disabled):hover {
  border-color: var(--d-primary);
  color: var(--d-primary);
}
.stage-activity-page-num {
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  padding: 0 4px;
  color: var(--d-text, #182033);
}

/* Dark mode overrides for modal */
html.dark-mode .stage-activity-modal {
  background: #1e293b !important;
  color: #f8fafc !important;
  border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .stage-activity-modal-header {
  background: #0f172a !important;
  border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .stage-activity-modal-title-wrap h3 {
  color: #f8fafc !important;
}
html.dark-mode .stage-activity-modal-close {
  background: #1e293b !important;
  border-color: rgba(255,255,255,0.1) !important;
  color: #94a3b8 !important;
}
html.dark-mode .stage-activity-modal-tabs {
  background: #1e293b !important;
  border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .stage-activity-modal-tab {
  background: #0f172a !important;
  border-color: rgba(255,255,255,0.1) !important;
  color: #94a3b8 !important;
}
html.dark-mode .stage-activity-modal-tab.active {
  background: #3b82f6 !important;
  border-color: #3b82f6 !important;
  color: #fff !important;
}
html.dark-mode .stage-activity-modal-body {
  background: #1e293b !important;
}
html.dark-mode .stage-activity-lead-card {
  background: #0f172a !important;
  border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .stage-activity-lead-name-link {
  color: #f1f5f9 !important;
}
html.dark-mode .stage-activity-quick-btn {
  background: #1e293b !important;
  border-color: rgba(255,255,255,0.1) !important;
  color: #cbd5e1 !important;
}
html.dark-mode .stage-activity-modal-footer {
  background: #0f172a !important;
  border-color: rgba(255,255,255,0.08) !important;
}
html.dark-mode .stage-activity-page-btn {
  background: #1e293b !important;
  border-color: rgba(255,255,255,0.1) !important;
  color: #cbd5e1 !important;
}
html.dark-mode .stage-activity-page-num {
  color: #f8fafc !important;
}

/* Active Campaigns Horizontal Strip */
.crm-dashboard-v2 .dash-campaigns-strip {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  gap: 10px;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 2px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
  scrollbar-color: var(--d-border) transparent;
}
.crm-dashboard-v2 .dash-campaigns-strip::-webkit-scrollbar {
  height: 4px;
}
.crm-dashboard-v2 .dash-campaigns-strip::-webkit-scrollbar-track {
  background: transparent;
}
.crm-dashboard-v2 .dash-campaigns-strip::-webkit-scrollbar-thumb {
  background: var(--d-border);
  border-radius: 99px;
}
.crm-dashboard-v2 .dash-campaign-chip {
  flex: 1 0 auto;
  min-width: 170px;
  max-width: 220px;
  min-height: 54px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  padding: 8px 12px;
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-sm);
  background: var(--d-surface-alt);
  color: inherit;
  text-decoration: none;
  transition: all var(--d-transition);
  cursor: pointer;
}
.crm-dashboard-v2 .dash-campaign-chip:hover {
  border-color: var(--d-primary);
  background: var(--d-surface-hover);
  transform: translateY(-2px);
  box-shadow: var(--d-shadow);
}
.crm-dashboard-v2 .dash-campaign-chip-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
}
.crm-dashboard-v2 .dash-campaign-chip-name {
  font-size: 13px;
  font-weight: 800;
  color: var(--d-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.2;
}
.crm-dashboard-v2 .dash-campaign-chip-bottom {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 6px;
  font-size: 11px;
  color: var(--d-text-muted);
}
.crm-dashboard-v2 .dash-campaign-chip-count {
  font-weight: 700;
  color: var(--d-text-muted);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.crm-dashboard-v2 .dash-campaign-chip-count i {
  font-size: 11px;
  color: #ec4899;
}
.crm-dashboard-v2 .dash-campaigns-view-all {
  font-size: 12px;
  font-weight: 800;
  color: var(--d-primary);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: color var(--d-transition);
}
.crm-dashboard-v2 .dash-campaigns-view-all:hover {
  text-decoration: underline;
}
.crm-dashboard-v2 .dash-campaigns-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 12px;
  color: var(--d-text-muted);
  font-size: 13px;
  font-weight: 700;
  background: var(--d-surface-alt);
  border-radius: var(--d-radius-sm);
  border: 1px dashed var(--d-border);
  width: 100%;
  min-height: 60px;
}
.crm-dashboard-v2 .dash-campaigns-empty i {
  font-size: 16px;
  color: var(--d-text-subtle);
}

/* ==========================================================================
   THIRD ROW: RECENT ACTIVITY TABLE + MINI CALENDAR CARD
   ========================================================================== */
.crm-dashboard-v2 .dash-activity-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-bottom: 22px;
}
@media (min-width: 1100px) {
  .crm-dashboard-v2 .dash-activity-grid {
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
  }
}

.crm-dashboard-v2 .dash-panel-card {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 22px 24px;
  box-shadow: var(--d-shadow);
  display: flex;
  flex-direction: column;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .dash-panel-card:hover {
  box-shadow: var(--d-shadow-hover);
}
.crm-dashboard-v2 .dash-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 14px;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--d-border-subtle);
}
.crm-dashboard-v2 .dash-panel-head h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--d-text);
}
.crm-dashboard-v2 .dash-panel-head p {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--d-text-muted);
}

/* Activity Table */
.crm-dashboard-v2 .table-wrap {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
.crm-dashboard-v2 .dash-activity-table {
  width: 100%;
  border-collapse: collapse;
  text-align: right;
}
[dir="ltr"] .crm-dashboard-v2 .dash-activity-table {
  text-align: left;
}
.crm-dashboard-v2 .dash-activity-table th {
  padding: 10px 12px;
  border-bottom: 1px solid var(--d-border);
  color: var(--d-text-muted);
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  background: var(--d-surface-alt);
}
.crm-dashboard-v2 .dash-activity-table td {
  padding: 12px 12px;
  border-bottom: 1px solid var(--d-border-subtle);
  color: var(--d-text);
  font-size: 13px;
  vertical-align: middle;
}
.crm-dashboard-v2 .dash-activity-table tbody tr:hover {
  background-color: var(--d-surface-alt);
}
.crm-dashboard-v2 .lead-name-link {
  color: var(--d-text);
  font-weight: 800;
  text-decoration: none;
  transition: color var(--d-transition);
}
.crm-dashboard-v2 .lead-name-link:hover {
  color: var(--d-primary);
}
.crm-dashboard-v2 .status-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 6px;
  background: var(--d-surface-alt);
  border: 1px solid var(--d-border);
  font-size: 11px;
  font-weight: 700;
  color: var(--d-text);
}

/* Mini Calendar Card Styles (Preserves all test hooks) */
.crm-dashboard-v2 .mini-calendar-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.crm-dashboard-v2 .mini-calendar-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.crm-dashboard-v2 .calendar-title-group h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: var(--d-text);
}
.crm-dashboard-v2 .calendar-subtext {
  margin: 4px 0 0;
  color: var(--d-text-muted);
  font-size: 12px;
  font-weight: 700;
}
.crm-dashboard-v2 .mini-cal-nav-btns {
  display: flex;
  align-items: center;
  gap: 6px;
}
.crm-dashboard-v2 .mini-cal-nav-btn {
  width: 32px;
  height: 32px;
  border-radius: var(--d-radius-sm);
  border: 1px solid var(--d-border);
  background: var(--d-surface-alt);
  color: var(--d-text);
  display: grid;
  place-items: center;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .mini-cal-nav-btn:hover {
  background: var(--d-surface-hover);
  border-color: #3b82f6;
  color: #3b82f6;
}
.crm-dashboard-v2 .mini-cal-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  text-align: center;
  font-size: 11px;
  font-weight: 800;
  color: var(--d-text-muted);
  margin-bottom: 6px;
}
.crm-dashboard-v2 .mini-cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}
.crm-dashboard-v2 .mini-cal-day {
  min-height: 36px;
  padding: 4px;
  border-radius: var(--d-radius-sm);
  border: 1px solid transparent;
  background: transparent;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .mini-cal-day:hover {
  background: var(--d-surface-hover);
}
.crm-dashboard-v2 .mini-cal-day-num {
  font-size: 12px;
  font-weight: 700;
  color: var(--d-text);
  line-height: 1;
  font-family: Arial, sans-serif;
}
.crm-dashboard-v2 .mini-cal-day.other-month { opacity: 0.3; }
.crm-dashboard-v2 .mini-cal-day.today {
  border-color: #3b82f6;
  background: rgba(59, 130, 246, 0.08);
}
.crm-dashboard-v2 .mini-cal-day.today .mini-cal-day-num { color: #3b82f6; font-weight: 900; }
.crm-dashboard-v2 .mini-cal-day.selected {
  background: #3b82f6 !important;
  color: #fff !important;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
}
.crm-dashboard-v2 .mini-cal-day.selected .mini-cal-day-num { color: #fff !important; }
.crm-dashboard-v2 .mini-cal-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--red);
  margin-top: 2px;
}
.crm-dashboard-v2 .mini-calendar-preview {
  padding: 12px 14px;
  border-radius: var(--d-radius-md);
  background: var(--d-surface-alt);
  border: 1px solid var(--d-border);
}
.crm-dashboard-v2 .mini-cal-preview-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--d-border-subtle);
}
.crm-dashboard-v2 .preview-date-label { font-size: 12px; font-weight: 800; color: var(--d-text); }
.crm-dashboard-v2 .preview-count-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 6px;
  background: rgba(59, 130, 246, 0.12);
  color: #3b82f6;
}
.crm-dashboard-v2 .mini-cal-events-list {
  display: grid;
  gap: 6px;
  max-height: 180px;
  overflow-y: auto;
}
.crm-dashboard-v2 .mini-cal-event-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px 10px;
  border-radius: var(--d-radius-sm);
  background: var(--d-surface);
  border: 1px solid var(--d-border-subtle);
  font-size: 11px;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .mini-cal-event-item:hover {
  border-color: var(--d-border);
  box-shadow: var(--d-shadow);
}
.crm-dashboard-v2 .event-item-header {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.crm-dashboard-v2 .event-time-badge {
  font-weight: 800;
  color: #3b82f6;
  font-size: 11px;
  font-family: Arial, sans-serif;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.crm-dashboard-v2 .event-type-badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
}
.crm-dashboard-v2 .event-type-meeting { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
.crm-dashboard-v2 .event-type-call { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.crm-dashboard-v2 .event-type-task { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.crm-dashboard-v2 .event-type-reminder { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }

.crm-dashboard-v2 .event-status-badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  margin-inline-start: auto;
}
.crm-dashboard-v2 .event-status-scheduled { background: rgba(100, 116, 139, 0.1); color: var(--d-text-muted); }
.crm-dashboard-v2 .event-status-completed { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.crm-dashboard-v2 .event-status-canceled { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

.crm-dashboard-v2 .event-title-link {
  font-weight: 700;
  color: var(--d-text);
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  display: block;
}
.crm-dashboard-v2 .event-title-link:hover {
  color: var(--d-primary);
  text-decoration: underline;
}
.crm-dashboard-v2 .event-lead-meta {
  font-size: 10px;
  color: var(--d-text-muted);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.crm-dashboard-v2 .mini-cal-empty {
  padding: 12px 4px;
  text-align: center;
  color: var(--d-text-muted);
  font-size: 11px;
}
.crm-dashboard-v2 .btn-mini-cal-full {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 10px 14px;
  border-radius: var(--d-radius-md);
  background: var(--d-surface-alt);
  border: 1px solid var(--d-border);
  color: var(--d-text);
  font-weight: 700;
  font-size: 12px;
  text-decoration: none;
  transition: all var(--d-transition);
}
.crm-dashboard-v2 .btn-mini-cal-full:hover {
  background: #3b82f6;
  color: #fff;
  border-color: #3b82f6;
}

/* ==========================================================================
   FOURTH ROW: QUICK SHORTCUTS
   ========================================================================== */
.crm-dashboard-v2 .dash-shortcuts-section {
  background: var(--d-surface);
  border: 1px solid var(--d-border);
  border-radius: var(--d-radius-lg);
  padding: 18px 20px;
  box-shadow: var(--d-shadow);
  margin-bottom: 24px;
}
.crm-dashboard-v2 .dash-shortcuts-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.crm-dashboard-v2 .dash-shortcuts-title {
  margin: 0;
  font-size: 14px;
  font-weight: 800;
  color: var(--d-text);
  display: flex;
  align-items: center;
  gap: 6px;
}
.crm-dashboard-v2 .quick-action-grid-wrap {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 10px;
}
.crm-dashboard-v2 .quick-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  padding: 10px 14px;
  border-radius: var(--d-radius-md);
  background: var(--d-surface-alt);
  border: 1px solid var(--d-border);
  color: var(--d-text);
  text-decoration: none;
  font-size: 12px;
  font-weight: 800;
  transition: all var(--d-transition);
  text-align: center;
}
.crm-dashboard-v2 .quick-action-btn:hover {
  color: var(--d-primary);
  border-color: var(--d-primary);
  background: var(--d-surface-hover);
  transform: translateY(-1px);
}
.crm-dashboard-v2 .quick-action-btn i {
  font-size: 16px;
  color: var(--d-primary);
}

/* Empty State Table */
.crm-dashboard-v2 .empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 24px 16px;
}
.crm-dashboard-v2 .empty i {
  font-size: 24px;
  color: var(--d-text-muted);
  margin-bottom: 6px;
}
.crm-dashboard-v2 .empty strong {
  font-size: 13px;
  font-weight: 800;
  color: var(--d-text);
}
.crm-dashboard-v2 .empty p {
  margin: 4px 0 0;
  font-size: 11px;
  color: var(--d-text-muted);
}

/* ==========================================================================
   RESPONSIVE BREAKPOINTS (1600, 1440, 1366, 1280, 1024, 768, 430, 390, 375)
   ========================================================================== */
@media (max-width: 1200px) {
  .crm-dashboard-v2 .dash-top-analytics-grid {
    grid-template-columns: 1fr;
  }
  .crm-dashboard-v2 .dash-performance-grid {
    grid-template-columns: 1fr;
  }
  .crm-dashboard-v2 .dash-activity-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .crm-dashboard-v2 .dash-top-header {
    padding: 12px 14px;
  }
  .crm-dashboard-v2 .dash-header-title h1 {
    font-size: 18px;
  }
  .crm-dashboard-v2 .dash-filter-bar {
    padding: 12px 14px;
    gap: 8px;
  }
  .crm-dashboard-v2 .dash-filter-item {
    min-width: 100%;
    flex: 1 1 100%;
  }
  .crm-dashboard-v2 .dash-filter-actions {
    width: 100%;
    margin-top: 4px;
  }
  .crm-dashboard-v2 .btn-filter-submit,
  .crm-dashboard-v2 .btn-filter-clear {
    flex: 1;
    justify-content: center;
  }
  .crm-dashboard-v2 .chart-container-relative {
    height: 250px;
  }
}

@media (max-width: 480px) {
  .crm-dashboard-v2 .kpi-block-2x2 {
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }
  .crm-dashboard-v2 .kpi-card-modern {
    padding: 12px 14px;
  }
  .crm-dashboard-v2 .kpi-card-value {
    font-size: 22px;
  }
  .crm-dashboard-v2 .kpi-card-label {
    font-size: 11px;
  }
  .crm-dashboard-v2 .kpi-card-icon {
    width: 32px;
    height: 32px;
    font-size: 15px;
  }
  .crm-dashboard-v2 .metric-items-row {
    grid-template-columns: repeat(2, 1fr);
  }
  .crm-dashboard-v2 .quick-action-grid-wrap {
    grid-template-columns: 1fr 1fr;
  }
}

/* Toast Notification */
.toast {
  position: fixed;
  inset-inline-start: 24px;
  bottom: 24px;
  z-index: 50;
  padding: 12px 18px;
  border: 1px solid #10b98144;
  border-radius: var(--d-radius-md);
  background: var(--d-surface, #ffffff);
  color: #065f46;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(12px);
  transition: all 0.25s ease;
}
.dark-mode .toast,
html.dark .toast,
html.dark-mode .toast {
  background: #111827;
  color: #34d399;
  border-color: #05966955;
}
.toast.show {
  opacity: 1;
  visibility: visible;
  transform: none;
}
.toast .dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
}

@media(prefers-reduced-motion: reduce) {
  * { transition: none !important; animation: none !important; }
}
</style>
</head>
<body>
@include('partials.page-loader')
<div class="app">
@include('partials.crm-sidebar')
<button class="overlay" id="overlay" type="button" aria-label="{{ __('إغلاق القائمة') }}"></button>

<main class="main crm-dashboard-v2" id="crmDashboardV2">
  <!-- TOP HEADER (Unified Shared Topbar Partial) -->
  @include('partials.topbar', [
    'title' => __('crm.dashboard'),
    'subtitle' => __('نظرة عامة على أداء فريق المبيعات'),
    'icon' => 'bi-speedometer2',
  ])

  <!-- COMPACT FILTER TOOLBAR -->
  <form class="dash-filter-bar" id="filters" method="GET" action="{{ route('dashboard') }}">
    <div class="dash-filter-item">
      <label><i class="bi bi-person-badge"></i> {{ __('الموظف') }}:</label>
      <select name="employee">
        <option value="">{{ __('جميع الموظفين') }}</option>
        @foreach ($employees as $employee)
          <option value="{{ $employee }}" @selected($filters['employee'] === $employee)>
            {{ $employee }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="dash-filter-item">
      <label><i class="bi bi-calendar-range"></i> {{ __('الفترة') }}:</label>
      <select name="period">
        <option value="all" @selected($filters['period'] === 'all')>{{ __('كل الفترات') }}</option>
        <option value="today" @selected($filters['period'] === 'today')>{{ __('اليوم') }}</option>
        <option value="week" @selected($filters['period'] === 'week')>{{ __('هذا الأسبوع') }}</option>
        <option value="month" @selected($filters['period'] === 'month')>{{ __('هذا الشهر') }}</option>
      </select>
    </div>

    <div class="dash-filter-item">
      <label><i class="bi bi-calendar-event"></i> {{ __('من') }}:</label>
      <input type="date" name="from" value="{{ $filters['from'] }}">
    </div>

    <div class="dash-filter-item">
      <label><i class="bi bi-calendar-check"></i> {{ __('إلى') }}:</label>
      <input type="date" name="to" value="{{ $filters['to'] }}">
    </div>

    <div class="dash-filter-actions">
      <button class="btn-filter-submit" type="submit">
        <i class="bi bi-funnel-fill"></i>
        <span>{{ __('تطبيق') }}</span>
      </button>
      <a class="btn-filter-clear" href="{{ route('dashboard') }}">
        <i class="bi bi-arrow-counterclockwise"></i>
        <span>{{ __('إعادة ضبط') }}</span>
      </a>
    </div>
  </form>

  <!-- ======================================================================
       CHANGE 2: DYNAMIC PIPELINE STAGES STRIP (Directly under filters)
       ====================================================================== -->
  <section class="dash-pipeline-strip-wrap" aria-label="{{ __('مراحل مسار المبيعات النشطة') }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <div class="dash-pipeline-strip-header">
      <span class="dash-pipeline-strip-title">
        <i class="bi bi-diagram-3-fill" style="color: var(--d-primary);"></i>
        {{ __('مراحل مسار المبيعات النشطة') }}
      </span>
      @can('leads.view')
        <a href="{{ route('v2.leads.kanban') }}" class="dash-kanban-link">
          <i class="bi bi-kanban"></i> {{ __('crm.kanban') }}
        </a>
      @endcan
    </div>

    <div class="dash-pipeline-strip">
      @foreach (($activePipelineStages ?? []) as $pStage)
        @can('leads.view')
          <a href="{{ $pStage['filter_url'] }}" class="pipeline-flow-pill" style="--pill-color: {{ $pStage['color'] }}; --pill-bg: {{ $pStage['color'] }}1a;">
            <div class="pipeline-flow-icon"><i class="{{ $pStage['icon'] }}"></i></div>
            <div class="pipeline-flow-info">
              <strong>{{ $pStage['name'] }}</strong>
              <small class="counter-num" data-target="{{ $pStage['count'] }}">{{ number_format($pStage['count']) }}</small>
            </div>
          </a>
        @else
          <div class="pipeline-flow-pill" style="--pill-color: {{ $pStage['color'] }}; --pill-bg: {{ $pStage['color'] }}1a;">
            <div class="pipeline-flow-icon"><i class="{{ $pStage['icon'] }}"></i></div>
            <div class="pipeline-flow-info">
              <strong>{{ $pStage['name'] }}</strong>
              <small class="counter-num" data-target="{{ $pStage['count'] }}">{{ number_format($pStage['count']) }}</small>
            </div>
          </div>
        @endcan
      @endforeach
    </div>
  </section>

  <!-- ======================================================================
       ROW 1: TOP ANALYTICS GRID (2x2 KPI Block + 2 Donut Cards)
       ====================================================================== -->
  <section class="dash-top-analytics-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <!-- 2x2 Primary KPI Block -->
    <div class="kpi-block-2x2">
      <!-- KPI 1: Total Leads -->
      @can('leads.view')
        <a href="{{ route('v2.leads') }}" class="kpi-card-modern" style="--card-accent: #3b82f6; --icon-bg: rgba(59, 130, 246, 0.1);">
          <div class="kpi-card-head">
            <span class="kpi-card-label">{{ __('إجمالي العملاء') }}</span>
            <div class="kpi-card-icon"><i class="bi bi-people-fill"></i></div>
          </div>
          <div>
            <div class="kpi-card-value counter-num" data-target="{{ $totalLeads ?? 0 }}">{{ number_format($totalLeads ?? 0) }}</div>
            <div class="kpi-card-sub"><i class="bi bi-arrow-up-short" style="color: #10b981; font-size: 14px;"></i> {{ __('قاعدة العملاء النشطة') }}</div>
          </div>
        </a>
      @else
        <div class="kpi-card-modern" style="--card-accent: #3b82f6; --icon-bg: rgba(59, 130, 246, 0.1);">
          <div class="kpi-card-head">
            <span class="kpi-card-label">{{ __('إجمالي العملاء') }}</span>
            <div class="kpi-card-icon"><i class="bi bi-people-fill"></i></div>
          </div>
          <div>
            <div class="kpi-card-value counter-num" data-target="{{ $totalLeads ?? 0 }}">{{ number_format($totalLeads ?? 0) }}</div>
            <div class="kpi-card-sub">{{ __('قاعدة العملاء') }}</div>
          </div>
        </div>
      @endcan

      <!-- KPI 2: Dynamic Pipeline Stage-to-Stage Conversion Rate -->
      <div class="kpi-card-modern" id="kpiCardConversion" style="--card-accent: {{ $conversionMetrics['color'] ?? '#8b5cf6' }}; --icon-bg: {{ $conversionMetrics['color'] ?? '#8b5cf6' }}1a;">
        <div class="kpi-card-head">
          <div class="kpi-head-title-wrap">
            <span class="kpi-card-label">{{ __('crm.stage_conversion_rate') }}</span>
            <div class="kpi-stage-selectors-pair">
              <div class="kpi-stage-select-item">
                <span class="kpi-stage-select-tag">{{ __('crm.from_stage_prefix') ?? 'من:' }}</span>
                <select class="kpi-stage-select" id="conversionFromStageSelect" aria-label="{{ __('crm.from_stage_label') ?? 'من المرحلة' }}">
                  @foreach(($activePipelineStages ?? []) as $pStage)
                    <option value="{{ $pStage['id'] }}" @selected(($conversionMetrics['from_stage_id'] ?? null) == $pStage['id'])>
                      {{ $pStage['name'] }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="kpi-stage-select-item">
                <span class="kpi-stage-select-tag">{{ __('crm.to_stage_prefix') ?? 'إلى:' }}</span>
                <select class="kpi-stage-select" id="conversionStageSelect" aria-label="{{ __('crm.to_stage_label') ?? 'إلى المرحلة' }}">
                  @foreach(($activePipelineStages ?? []) as $pStage)
                    <option value="{{ $pStage['id'] }}" @selected(($conversionMetrics['to_stage_id'] ?? $conversionMetrics['stage_id'] ?? null) == $pStage['id'])>
                      {{ $pStage['name'] }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="kpi-card-icon" id="conversionIcon"><i class="{{ $conversionMetrics['icon'] ?? 'bi bi-funnel-fill' }}"></i></div>
        </div>
        <div>
          <div class="kpi-card-value counter-num" id="conversionValue" data-target="{{ $conversionMetrics['rate'] ?? 0 }}">
            {{ $conversionMetrics['display_value'] }}
          </div>
          <div class="kpi-card-sub" id="conversionSubtitle" style="color: var(--card-accent, #8b5cf6);">
            <i class="bi bi-arrow-left-right"></i>
            <span>{{ $conversionMetrics['subtitle'] }}</span>
          </div>
        </div>
      </div>

      <!-- KPI 3: Dynamic Pipeline Stage 1 -->
      <div class="kpi-card-modern" id="kpiCardStage1" style="--card-accent: {{ $stageKpi1['color'] ?? '#10b981' }}; --icon-bg: {{ $stageKpi1['color'] ?? '#10b981' }}1a;">
        <div class="kpi-card-head">
          <div class="kpi-head-title-wrap">
            <span class="kpi-card-label" id="stageKpi1Label">{{ $stageKpi1['stage_name'] ?? __('crm.stage') }}</span>
            <select class="kpi-stage-select" id="stageKpi1Select" aria-label="{{ __('crm.select_stage') }}">
              @foreach(($activePipelineStages ?? []) as $pStage)
                <option value="{{ $pStage['id'] }}" @selected(($stageKpi1['stage_id'] ?? null) == $pStage['id'])>
                  {{ $pStage['name'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="kpi-card-icon" id="stageKpi1Icon"><i class="{{ $stageKpi1['icon'] ?? 'bi bi-patch-check-fill' }}"></i></div>
        </div>
        <div>
          <div class="kpi-card-value counter-num" id="stageKpi1Value" data-target="{{ $stageKpi1['count'] ?? 0 }}">
            {{ number_format($stageKpi1['count'] ?? 0) }}
          </div>
          <div class="kpi-card-sub" id="stageKpi1Subtitle" style="color: var(--card-accent, #10b981);">
            <i class="bi bi-pie-chart"></i>
            <span>{{ $stageKpi1['subtitle'] }}</span>
          </div>
        </div>
      </div>

      <!-- KPI 4: Dynamic Pipeline Stage 2 -->
      <div class="kpi-card-modern" id="kpiCardStage2" style="--card-accent: {{ $stageKpi2['color'] ?? '#f59e0b' }}; --icon-bg: {{ $stageKpi2['color'] ?? '#f59e0b' }}1a;">
        <div class="kpi-card-head">
          <div class="kpi-head-title-wrap">
            <span class="kpi-card-label" id="stageKpi2Label">{{ $stageKpi2['stage_name'] ?? __('crm.stage') }}</span>
            <select class="kpi-stage-select" id="stageKpi2Select" aria-label="{{ __('crm.select_stage') }}">
              @foreach(($activePipelineStages ?? []) as $pStage)
                <option value="{{ $pStage['id'] }}" @selected(($stageKpi2['stage_id'] ?? null) == $pStage['id'])>
                  {{ $pStage['name'] }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="kpi-card-icon" id="stageKpi2Icon"><i class="{{ $stageKpi2['icon'] ?? 'bi bi-calendar2-check-fill' }}"></i></div>
        </div>
        <div>
          <div class="kpi-card-value counter-num" id="stageKpi2Value" data-target="{{ $stageKpi2['count'] ?? 0 }}">
            {{ number_format($stageKpi2['count'] ?? 0) }}
          </div>
          <div class="kpi-card-sub" id="stageKpi2Subtitle" style="color: var(--card-accent, #f59e0b);">
            <i class="bi bi-pie-chart"></i>
            <span>{{ $stageKpi2['subtitle'] }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2 Donut Analytics Cards -->
    <div class="donut-analytics-side">
      <!-- Donut Card 1: Status Mix Breakdown -->
      <div class="donut-card-modern">
        <div class="donut-card-head">
          <h4 class="donut-card-title">{{ __('توزيع حالات العملاء') }}</h4>
          <span class="donut-card-badge">{{ count($distribution) }} {{ __('حالات') }}</span>
        </div>
        <div class="donut-chart-wrap">
          <canvas id="statusDonutChart"></canvas>
          <div class="donut-center-stat">
            <strong>{{ number_format($totalLeads) }}</strong>
            <small>{{ __('عميل') }}</small>
          </div>
        </div>
        <div class="donut-card-footer">
          {{ __('مزيج حالات مسار المبيعات للفترة الحالية') }}
        </div>
      </div>

      <!-- Donut Card 2: Dynamic Final Stage Conversion Ring -->
      <div class="donut-card-modern">
        <div class="donut-card-head">
          <h4 class="donut-card-title">{{ $finalStageTitle }}</h4>
          <span class="donut-card-badge" style="background: {{ $finalStageColor }}1f; color: {{ $finalStageColor }};">
            {{ $finalConversionRate !== null ? $finalConversionRate . '%' : __('crm.not_available') }}
          </span>
        </div>
        <div class="donut-chart-wrap">
          <canvas id="conversionRingChart"></canvas>
          <div class="donut-center-stat">
            <strong style="color: {{ $finalStageColor }};">{{ $finalConversionRate !== null ? $finalConversionRate . '%' : '—' }}</strong>
            <small>{{ $finalConversionRate !== null ? __('crm.success_reach') : __('crm.not_available') }}</small>
          </div>
        </div>
        <div class="donut-card-footer">
          @if($finalActiveStage && $finalConversionRate !== null)
            {{ __('crm.customers_reached_stage', ['count' => number_format($finalStageReachedCount), 'stage' => $finalActiveStage->localizedName()]) }}
          @elseif($finalActiveStage)
            {{ __('crm.not_available') }}
          @else
            {{ __('crm.no_active_stages') }}
          @endif
        </div>
      </div>
    </div>
  </section>

  <!-- ======================================================================
       ROW 2: PRIMARY PERFORMANCE CHART + STACKED OPERATIONAL METRICS
       ====================================================================== -->
  <section class="dash-performance-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Large Performance Timeline Chart -->
    <div class="dash-chart-card">
      <div class="dash-chart-header">
        <div class="dash-chart-title-group">
          <h3>{{ __('مؤشر حركة وأداء العملاء') }}</h3>
          <p>{{ __('تحليل حركة العملاء عبر المراحل المختارة شهريًا') }}</p>
        </div>
        <div class="dash-chart-controls">
          <!-- Compact Multi-Select Dropdown for Pipeline Stages -->
          <div class="dash-chart-stage-dropdown" id="chartStageDropdown">
            <button
              type="button"
              class="dash-chart-dropdown-toggle"
              id="chartStageDropdownToggle"
              aria-expanded="false"
              aria-haspopup="true"
              aria-label="{{ __('crm.select_chart_stages') }}"
            >
              <i class="bi bi-funnel"></i>
              <span id="chartStageDropdownLabel">
                {{ __('crm.selected_stages_count', ['count' => count($performanceTimeline['selected_stages'] ?? $activePipelineStages ?? [])]) }}
              </span>
              <i class="bi bi-chevron-down toggle-arrow"></i>
            </button>
            <div class="dash-chart-dropdown-menu" id="chartStageDropdownMenu" role="menu">
              <div class="dash-chart-dropdown-actions">
                <button type="button" class="btn-dropdown-action" id="chartSelectAllStages">
                  <i class="bi bi-check-all"></i> {{ __('crm.select_all') }}
                </button>
                <button type="button" class="btn-dropdown-action" id="chartResetStages">
                  <i class="bi bi-arrow-counterclockwise"></i> {{ __('crm.reset') ?? __('إعادة ضبط') }}
                </button>
              </div>
              <div class="dash-chart-dropdown-list">
                @foreach(($activePipelineStages ?? []) as $pStage)
                  <label class="dash-chart-stage-item">
                    <input
                      type="checkbox"
                      class="chart-stage-checkbox"
                      value="{{ $pStage['id'] }}"
                      data-name="{{ $pStage['name'] }}"
                      data-color="{{ $pStage['color'] ?: '#3b82f6' }}"
                      @checked(in_array($pStage['id'], $performanceTimeline['selected_stages'] ?? []))
                    >
                    <span class="chart-stage-item-dot" style="background: {{ $pStage['color'] ?: '#3b82f6' }};"></span>
                    <span class="chart-stage-item-name">{{ $pStage['name'] }}</span>
                  </label>
                @endforeach
              </div>
            </div>
          </div>
          @can('reports.view')
            <a href="{{ route('v2.reports.leads') }}" class="dash-chart-report-link">
              {{ __('عرض التقارير التفصيلية') }} <i class="bi bi-arrow-{{ app()->getLocale() === 'en' ? 'right' : 'left' }}"></i>
            </a>
          @endcan
        </div>
      </div>
      @if($performanceTimeline['hasData'] ?? false)
        <div class="chart-container-relative">
          <canvas id="performanceChart"></canvas>
        </div>
      @else
        <div class="chart-empty-container">
          <div class="chart-empty-icon-wrap">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <h4 class="chart-empty-title">{{ __('لا توجد حركة كافية لعرض الرسم البياني') }}</h4>
          <p class="chart-empty-desc">{{ __('لم يتم تسجيل نشاط كاف خلال الفترة المحددة.') }}</p>
          @if(($filters['period'] ?? 'all') !== 'all' || !empty($filters['from']) || !empty($filters['to']))
            <a href="{{ route('dashboard', ['period' => 'all']) }}" class="btn-chart-empty-action">
              <i class="bi bi-arrow-clockwise"></i>
              <span>{{ __('عرض كل الفترات') }}</span>
            </a>
          @endif
        </div>
      @endif
    </div>

    <!-- Stacked Operational Metric Cards -->
    <div class="dash-stacked-metrics">
      <!-- Card A: Active Campaigns Strip -->
      <div class="metric-card-box">
        <div class="metric-box-head">
          <h4><i class="bi bi-megaphone-fill" style="color: #ec4899;"></i> {{ __('crm.active_campaigns') }}</h4>
          @can('campaigns.view')
            <a href="{{ route('v2.campaigns.index') }}" class="dash-campaigns-view-all">
              <span>{{ __('crm.view_campaigns') }}</span>
              <i class="bi bi-arrow-{{ app()->getLocale() === 'en' ? 'right' : 'left' }}"></i>
            </a>
          @endcan
        </div>
        <div class="dash-campaigns-strip">
          @forelse(($activeCampaigns ?? []) as $camp)
            @can('campaigns.view')
              <a href="{{ $camp['url'] }}" class="dash-campaign-chip" title="{{ $camp['name'] }}">
            @else
              <div class="dash-campaign-chip" title="{{ $camp['name'] }}">
            @endcan
                <div class="dash-campaign-chip-top">
                  <span class="dash-campaign-chip-name">{{ $camp['name'] }}</span>
                </div>
                <div class="dash-campaign-chip-bottom">
                  <span class="dash-campaign-chip-count"><i class="bi bi-people"></i> {{ number_format($camp['total_leads']) }} {{ app()->getLocale() === 'ar' ? __('عميل') : ($camp['total_leads'] == 1 ? 'customer' : 'customers') }}</span>
                </div>
            @can('campaigns.view')
              </a>
            @else
              </div>
            @endcan
          @empty
            <div class="dash-campaigns-empty">
              <i class="bi bi-megaphone"></i>
              <span>{{ __('crm.no_active_campaigns') }}</span>
            </div>
          @endforelse
        </div>
      </div>

      <!-- Card B: Dynamic Stage Activity & Follow-up Velocity -->
      <div class="metric-card-box stage-activity-card" id="stageActivityCard">
        <div class="metric-box-head">
          <div class="stage-activity-title-wrap">
            <h4><i class="bi bi-calendar-check-fill" style="color: #f59e0b;"></i> {{ __('crm.stage_activity') }}</h4>
            <select class="stage-activity-select" id="stageActivitySelect" aria-label="{{ __('crm.select_stage') }}">
              @foreach(($activePipelineStages ?? []) as $pStage)
                <option value="{{ $pStage['id'] }}" @selected(($stageActivity['stage_id'] ?? null) == $pStage['id'])>
                  {{ $pStage['name'] }}
                </option>
              @endforeach
            </select>
          </div>
          <button type="button" class="stage-activity-total-badge stage-activity-clickable-tile" id="stageActivityTotal" data-bucket="all" role="button" aria-label="{{ __('crm.all') }}" title="{{ __('crm.all') }}">
            {{ number_format($stageActivity['total_count'] ?? 0) }} {{ __('crm.all') }}
          </button>
        </div>
        <div class="metric-items-row stage-activity-counts-row">
          <button type="button" class="metric-sub-item stage-activity-clickable-tile" data-bucket="today" role="button" aria-label="{{ __('crm.today') }}" title="{{ __('crm.today') }}">
            <strong style="color: #f59e0b;" id="stageActivityToday">{{ number_format($stageActivity['today_count'] ?? 0) }}</strong>
            <small>{{ __('crm.today') }}</small>
          </button>
          <button type="button" class="metric-sub-item stage-activity-clickable-tile" data-bucket="overdue" role="button" aria-label="{{ __('crm.overdue_short') }}" title="{{ __('crm.overdue_short') }}">
            <strong style="color: #ef4444;" id="stageActivityOverdue">{{ number_format($stageActivity['overdue_count'] ?? 0) }}</strong>
            <small>{{ __('crm.overdue_short') }}</small>
          </button>
          <button type="button" class="metric-sub-item stage-activity-clickable-tile" data-bucket="upcoming" role="button" aria-label="{{ __('crm.upcoming_short') }}" title="{{ __('crm.upcoming_short') }}">
            <strong style="color: #10b981;" id="stageActivityUpcoming">{{ number_format($stageActivity['upcoming_count'] ?? 0) }}</strong>
            <small>{{ __('crm.upcoming_short') }}</small>
          </button>
        </div>
        <!-- Detail List for Selected Stage -->
        <div class="stage-activity-details" id="stageActivityDetails">
          @forelse(($stageActivity['leads'] ?? []) as $actLead)
            <div class="stage-activity-row">
              <div class="stage-activity-lead-info">
                @can('leads.view')
                  <a href="{{ $actLead['url'] }}" class="stage-activity-lead-link" title="{{ $actLead['name'] }}">{{ $actLead['name'] }}</a>
                @else
                  <span class="stage-activity-lead-name">{{ $actLead['name'] }}</span>
                @endcan
                <span class="stage-activity-lead-emp"><i class="bi bi-person"></i> {{ $actLead['employee_name'] }}</span>
              </div>
              <div class="stage-activity-meta">
                <span class="stage-activity-badge {{ $actLead['timing_class'] }}">{{ $actLead['timing_label'] }}</span>
                <span class="stage-activity-time"><i class="bi bi-clock"></i> {{ $actLead['scheduled_time'] }}</span>
              </div>
            </div>
          @empty
            <div class="stage-activity-empty" id="stageActivityEmpty">
              <i class="bi bi-calendar2-x"></i>
              <span>{{ __('crm.no_activity_for_stage') }}</span>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </section>
  <!-- ======================================================================
       ROW 3: RECENT ACTIVITY TABLE + MINI CALENDAR CARD
       ====================================================================== -->
  <section class="dash-activity-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <!-- Latest Followups Table -->
    <div class="dash-panel-card">
      <div class="dash-panel-head">
        <div>
          <h3>{{ __('أحدث المتابعات والأنشطة') }}</h3>
          <p>{{ __('آخر نشاط مسجل مباشرة بواسطة فريق المبيعات') }}</p>
        </div>
        <span style="font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 6px; background: var(--d-surface-alt); color: var(--d-text-muted);">
          {{ number_format($latestFollowups->count()) }} {{ __('متابعة') }}
        </span>
      </div>

      <div class="table-wrap">
        <table class="dash-activity-table">
          <thead>
            <tr>
              <th>{{ __('العميل') }}</th>
              <th>{{ __('نوع المتابعة') }}</th>
              <th>{{ __('الموظف') }}</th>
              <th>{{ __('التاريخ') }}</th>
              <th>{{ __('الحالة') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($latestFollowups as $followup)
              <tr>
                <td>
                  @if ($followup->lead)
                    <a href="{{ route('v2.leads.show', $followup->lead) }}" class="lead-name-link">
                      {{ $followup->lead->name }}
                    </a>
                  @else
                    {{ __('عميل غير متاح') }}
                  @endif
                </td>
                <td>
                  {{ __($communicationLabels[$followup->communication_type] ?? $followup->communication_type ?? '—') }}
                </td>
                <td>
                  <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-person-circle" style="color: var(--d-text-subtle);"></i>
                    {{ $followup->employee_name ?: '—' }}
                  </span>
                </td>
                <td style="font-family: Arial, sans-serif; font-size: 12px;">
                  {{ $followup->followed_up_at?->format('d/m/Y H:i') ?? '—' }}
                </td>
                <td>
                  <span class="status-tag">
                    {{ $followup->toStatus?->name_ar ? __($followup->toStatus->name_ar) : ($followup->lead?->status?->name_ar ? __($followup->lead->status->name_ar) : '—') }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5">
                  <div class="empty">
                    <i class="bi bi-inbox"></i>
                    <strong>{{ __('لا توجد متابعات حالياً') }}</strong>
                    <p>{{ __('ستظهر أحدث المتابعات هنا فور تسجيلها.') }}</p>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mini Calendar Card (Preserves all required test IDs and classes) -->
    <div class="dash-panel-card mini-calendar-panel">
      <div class="mini-calendar-head">
        <div class="calendar-title-group">
          <h3>{{ app()->getLocale() === 'ar' ? 'التقويم' : 'Calendar' }}</h3>
          <p class="calendar-subtext" id="miniCalendarMonthYear">{{ now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="mini-cal-nav-btns">
          <button type="button" class="mini-cal-nav-btn prev" id="miniCalPrev" aria-label="{{ __('الشهر السابق') }}">
            <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
          </button>
          <button type="button" class="mini-cal-nav-btn next" id="miniCalNext" aria-label="{{ __('الشهر القادم') }}">
            <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
          </button>
        </div>
      </div>

      <div class="mini-calendar-body">
        <div class="mini-cal-weekdays">
          <span>{{ app()->getLocale() === 'ar' ? 'أحد' : 'Sun' }}</span>
          <span>{{ app()->getLocale() === 'ar' ? 'إثنين' : 'Mon' }}</span>
          <span>{{ app()->getLocale() === 'ar' ? 'ثلاثاء' : 'Tue' }}</span>
          <span>{{ app()->getLocale() === 'ar' ? 'أربعاء' : 'Wed' }}</span>
          <span>{{ app()->getLocale() === 'ar' ? 'خميس' : 'Thu' }}</span>
          <span>{{ app()->getLocale() === 'ar' ? 'جمعة' : 'Fri' }}</span>
          <span>{{ app()->getLocale() === 'ar' ? 'سبت' : 'Sat' }}</span>
        </div>
        <div class="mini-cal-grid" id="miniCalGrid"></div>
      </div>

      <div class="mini-calendar-preview" id="miniCalPreview">
        <div class="mini-cal-preview-head">
          <span class="preview-date-label" id="miniCalSelectedDateLabel">{{ app()->getLocale() === 'ar' ? ('أحداث اليوم (' . now()->translatedFormat('j F Y') . ')') : ('Today\'s Events (' . now()->translatedFormat('j F Y') . ')') }}</span>
          @php
            $todayEvents = array_values(array_filter($miniCalendarEvents ?? [], fn($e) => ($e['date'] ?? '') === now()->format('Y-m-d')));
            $todayCount = count($todayEvents);
          @endphp
          <span class="preview-count-badge" id="miniCalEventCount">{{ $todayCount }} {{ app()->getLocale() === 'ar' ? 'أحداث' : 'events' }}</span>
        </div>
        <div class="mini-cal-events-list" id="miniCalEventsList">
          @forelse ($todayEvents as $ev)
            <div class="mini-cal-event-item">
              <div class="event-item-header">
                <span class="event-time-badge"><i class="bi bi-clock"></i> {{ $ev['time'] ?? '—' }}</span>
                @php
                  $typeLabels = [
                    'meeting' => app()->getLocale() === 'ar' ? 'اجتماع' : 'Meeting',
                    'call' => app()->getLocale() === 'ar' ? 'مكالمة' : 'Call',
                    'task' => app()->getLocale() === 'ar' ? 'مهمة' : 'Task',
                    'reminder' => app()->getLocale() === 'ar' ? 'تذكير' : 'Reminder',
                  ];
                  $statusLabels = [
                    'scheduled' => app()->getLocale() === 'ar' ? 'مجدول' : 'Scheduled',
                    'completed' => app()->getLocale() === 'ar' ? 'مكتمل' : 'Completed',
                    'canceled' => app()->getLocale() === 'ar' ? 'ملغي' : 'Canceled',
                  ];
                  $evType = $ev['type'] ?? 'meeting';
                  $evStatus = $ev['status'] ?? 'scheduled';
                @endphp
                <span class="event-type-badge event-type-{{ $evType }}">{{ $typeLabels[$evType] ?? $evType }}</span>
                <span class="event-status-badge event-status-{{ $evStatus }}">{{ $statusLabels[$evStatus] ?? $evStatus }}</span>
              </div>
              <a href="{{ $ev['action_url'] ?? ($ev['lead_url'] ?? route('v2.calendar.index')) }}" class="event-title-link" title="{{ $ev['title'] ?? '' }}">
                {{ $ev['title'] ?? (app()->getLocale() === 'ar' ? 'حدث' : 'Event') }}
              </a>
              @if(!empty($ev['lead_name']))
                <span class="event-lead-meta">
                  <i class="bi bi-person"></i> {{ $ev['lead_name'] }}
                  @if(!empty($ev['lead_company'])) — {{ $ev['lead_company'] }} @endif
                </span>
              @endif
            </div>
          @empty
            <div class="mini-cal-empty">
              <i class="bi bi-calendar2-x" style="font-size:18px;display:block;margin-bottom:4px;opacity:0.6"></i>
              {{ app()->getLocale() === 'ar' ? 'لا توجد أحداث لهذا اليوم' : 'No events for this day' }}
            </div>
          @endforelse
        </div>
      </div>

      <div class="mini-calendar-footer">
        @can('calendar.view')
          <a href="{{ route('v2.calendar.index') }}" class="btn-mini-cal-full">
            <i class="bi bi-calendar3"></i>
            <span>{{ app()->getLocale() === 'ar' ? 'عرض التقويم الكامل' : 'View Full Calendar' }}</span>
            <i class="bi bi-arrow-{{ app()->getLocale() === 'en' ? 'right' : 'left' }}"></i>
          </a>
        @endcan
      </div>
    </div>
  </section>

  <!-- ======================================================================
       ROW 4: QUICK ACTION SHORTCUTS
       ====================================================================== -->
  <section class="dash-shortcuts-section" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
    <div class="dash-shortcuts-head">
      <h4 class="dash-shortcuts-title">
        <i class="bi bi-lightning-charge-fill" style="color: #f59e0b;"></i>
        {{ __('crm.quick_actions') }}
      </h4>
    </div>

    <div class="quick-action-grid-wrap">
      @can('leads.create')
        <a href="{{ route('v2.leads.create') }}" class="quick-action-btn">
          <i class="bi bi-person-plus-fill"></i>
          <span>{{ __('crm.add_lead_short') }}</span>
        </a>
      @endcan
      @can('tasks.view')
        <a href="{{ route('v2.tasks.daily') }}" class="quick-action-btn">
          <i class="bi bi-check2-square"></i>
          <span>{{ __('crm.daily_tasks') }}</span>
        </a>
      @endcan
      @can('leads.view')
        <a href="{{ route('v2.leads.kanban') }}" class="quick-action-btn">
          <i class="bi bi-kanban"></i>
          <span>{{ __('crm.kanban') }}</span>
        </a>
      @endcan
    </div>
  </section>
</main>
</div>
<!-- Stage Activity Leads Modal -->
<div class="stage-activity-modal-backdrop" id="stageActivityModalBackdrop" hidden>
  <div class="stage-activity-modal" id="stageActivityModal" role="dialog" aria-modal="true" aria-labelledby="stageActivityModalTitle" tabindex="-1">
    <div class="stage-activity-modal-header">
      <div class="stage-activity-modal-title-wrap">
        <h3 class="stage-activity-modal-title" id="stageActivityModalTitle">
          <i class="bi bi-people-fill" style="color: var(--d-primary);"></i>
          <span id="stageActivityModalTitleText">{{ __('المتابعات') }}</span>
        </h3>
        <p class="stage-activity-modal-subtitle" id="stageActivityModalSubtitle">
          <span id="stageActivityModalStageName"></span> — <span id="stageActivityModalCountText"></span>
        </p>
      </div>
      <button type="button" class="stage-activity-modal-close" id="stageActivityModalClose" aria-label="{{ __('crm.close') }}">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Filter Tabs Inside Modal -->
    <div class="stage-activity-modal-tabs" role="tablist">
      <button type="button" class="stage-activity-modal-tab active" data-modal-bucket="all" role="tab" aria-selected="true">{{ __('crm.all') }}</button>
      <button type="button" class="stage-activity-modal-tab" data-modal-bucket="today" role="tab" aria-selected="false">{{ __('crm.today') }}</button>
      <button type="button" class="stage-activity-modal-tab" data-modal-bucket="overdue" role="tab" aria-selected="false">{{ __('crm.overdue_short') }}</button>
      <button type="button" class="stage-activity-modal-tab" data-modal-bucket="upcoming" role="tab" aria-selected="false">{{ __('crm.upcoming_short') }}</button>
    </div>

    <div class="stage-activity-modal-body" id="stageActivityModalBody">
      <!-- Dynamic list populated via JS -->
      <div class="stage-activity-modal-list" id="stageActivityModalList"></div>
      
      <!-- Empty state -->
      <div class="stage-activity-modal-empty" id="stageActivityModalEmpty" hidden>
        <i class="bi bi-calendar2-x"></i>
        <h4>{{ app()->getLocale() === 'en' ? 'No customers in this category' : 'لا توجد حالات في هذه الفئة' }}</h4>
        <p>{{ app()->getLocale() === 'en' ? 'No scheduled follow-ups found for the selected stage and timeframe.' : 'لم يتم العثور على متابعات مجدولة لهذه المرحلة والفترة المحددة.' }}</p>
      </div>

      <!-- Loading indicator -->
      <div class="stage-activity-modal-loading" id="stageActivityModalLoading" hidden>
        <div class="spinner-border spinner-border-sm" role="status"></div>
        <span>{{ __('جاري التحميل...') }}</span>
      </div>
    </div>

    <div class="stage-activity-modal-footer" id="stageActivityModalFooter">
      <div class="stage-activity-modal-info" id="stageActivityModalInfo"></div>
      <div class="stage-activity-modal-pagination">
        <button type="button" class="stage-activity-page-btn" id="stageActivityPrevPage" disabled>
          <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
          <span>{{ __('السابق') }}</span>
        </button>
        <span class="stage-activity-page-num" id="stageActivityPageNum">1 / 1</span>
        <button type="button" class="stage-activity-page-btn" id="stageActivityNextPage" disabled>
          <span>{{ __('التالي') }}</span>
          <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Live Data Notification -->
<div class="toast" id="toast">
  <span class="dot"></span>
  <span>{{ __('يتم عرض البيانات الحية من CRM v2.') }}</span>
</div>

<!-- Base Sidebar Navigation Script -->
<script>
(()=>{
  document.querySelectorAll('.toggle').forEach(x => {
    x.onclick = () => {
      let v = x.getAttribute('aria-expanded') !== 'true';
      x.setAttribute('aria-expanded', v);
      if (e) e.classList.toggle('open', v);
    };
  });
  try {
    const dateEl = document.getElementById('date');
    if (dateEl) {
      dateEl.textContent = new Intl.DateTimeFormat('{{ app()->getLocale() === 'en' ? 'en-US' : 'ar-EG' }}', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      }).format(new Date()) + ' — {{ __('نظرة عامة على أداء فريق المبيعات') }}';
    }
  } catch (e) {}
})();
</script>

<!-- DASHBOARD THEME & CHARTS INTEGRATION -->
<script>
(() => {
  const isDarkModeActive = () => {
    const root = document.documentElement;
    return root.classList.contains('dark-mode') || root.classList.contains('dark') || root.dataset.theme === 'dark';
  };

  const updateDashboardTheme = () => {
    const isDark = isDarkModeActive();
    const dashEl = document.getElementById('crmDashboardV2');
    if (dashEl) {
      dashEl.setAttribute('data-theme', isDark ? 'dark' : 'light');
    }
    if (typeof window.crmUpdateChartsTheme === 'function') {
      window.crmUpdateChartsTheme(isDark ? 'dark' : 'light');
    }
  };

  const observer = new MutationObserver(() => {
    updateDashboardTheme();
  });
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  });

  window.addEventListener('storage', (e) => {
    if (e.key === 'sokrat.crm.theme') {
      updateDashboardTheme();
    }
  });

  document.addEventListener('DOMContentLoaded', updateDashboardTheme);
  updateDashboardTheme();

  window.__chartLogs = [];
  const log = (msg) => { window.__chartLogs.push(msg); };
  const ensureChart = () => {
    if (typeof window.Chart === 'function') {
      return Promise.resolve(true);
    }
    if (window.__sokratChartLoadPromise) {
      return window.__sokratChartLoadPromise;
    }
    window.__sokratChartLoadPromise = (async () => {
      log('ensureChart start: typeof window.Chart = ' + typeof window.Chart);
      if (typeof window.Chart === 'function') return true;
      try {
        log('fetching chart.umd.min.js');
        const res = await fetch('{{ asset('js/chart.umd.min.js') }}?v={{ file_exists(public_path('js/chart.umd.min.js')) ? filemtime(public_path('js/chart.umd.min.js')) : '1.0' }}');
        log('fetch status: ' + res.status);
        if (res.ok) {
          const code = await res.text();
          log('code len: ' + code.length);
          (0, eval)(code);
          log('after eval: typeof window.Chart = ' + typeof window.Chart);
          return typeof window.Chart === 'function';
        }
      } catch (e) {
        log('fetch catch error: ' + e.message);
      }
      return false;
    })().catch((err) => {
      log('chart promise error: ' + err.message);
      return false;
    });
    return window.__sokratChartLoadPromise;
  };
  let perfChart = null;
  let statusDonutChart = null;
  let conversionRingChart = null;
  let chartsInitialized = false;
  let chartsInitializing = false;

  const isRTL = '{{ app()->getLocale() === 'ar' ? 'true' : 'false' }}' === 'true';
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const initDashboardCharts = async () => {
    log('initDashboardCharts called: chartsInitialized=' + chartsInitialized + ', chartsInitializing=' + chartsInitializing);
    if (chartsInitialized || chartsInitializing) return;
    chartsInitializing = true;
    try {
      if (typeof window.Chart !== 'function') {
        await ensureChart();
      }
      if (typeof window.Chart !== 'function') {
        chartsInitializing = false;
        return;
      }
      chartsInitialized = true;
    const Chart = window.Chart;
    const isDarkModeActive = () => {
      const root = document.documentElement;
      return root.classList.contains('dark-mode') || root.classList.contains('dark') || root.dataset.theme === 'dark';
    };

    const getChartThemeColors = (theme) => {
      const isDark = theme === 'dark' || isDarkModeActive();
      return {
        textColor: isDark ? '#94a3b8' : '#64748b',
        gridColor: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(148, 163, 184, 0.15)',
        tooltipBg: isDark ? '#0f172a' : '#182033ee',
        donutBorder: isDark ? '#111827' : '#ffffff',
        legendColor: isDark ? '#cbd5e1' : '#64748b'
      };
    };

    // Global theme update function
    window.crmUpdateChartsTheme = function(theme) {
      const colors = getChartThemeColors(theme);
      if (window.Chart) {
        window.Chart.defaults.color = colors.textColor;
      }
    if (perfChart) {
      if (perfChart.options.scales && perfChart.options.scales.y) {
        perfChart.options.scales.y.grid.color = colors.gridColor;
        perfChart.options.scales.y.ticks.color = colors.textColor;
      }
      if (perfChart.options.scales && perfChart.options.scales.x) {
        perfChart.options.scales.x.ticks.color = colors.textColor;
      }
      if (perfChart.options.plugins && perfChart.options.plugins.legend) {
        perfChart.options.plugins.legend.labels.color = colors.legendColor;
      }
      if (perfChart.options.plugins && perfChart.options.plugins.tooltip) {
        perfChart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
      }
      perfChart.update();
    }

    if (statusDonutChart) {
      if (statusDonutChart.data.datasets && statusDonutChart.data.datasets[0]) {
        statusDonutChart.data.datasets[0].borderColor = colors.donutBorder;
      }
      if (statusDonutChart.options.plugins && statusDonutChart.options.plugins.tooltip) {
        statusDonutChart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
      }
      statusDonutChart.update();
    }

    if (conversionRingChart) {
      const ringColor = '{{ $finalStageColor ?? '#10b981' }}';
      if (conversionRingChart.data.datasets && conversionRingChart.data.datasets[0]) {
        conversionRingChart.data.datasets[0].borderColor = colors.donutBorder;
        conversionRingChart.data.datasets[0].backgroundColor[0] = ringColor;
        conversionRingChart.data.datasets[0].backgroundColor[1] = isDarkModeActive() ? '#1e293b' : '#f1f5f9';
      }
      if (conversionRingChart.options.plugins && conversionRingChart.options.plugins.tooltip) {
        conversionRingChart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
      }
      conversionRingChart.update();
    }
  };

  // 1. Status Mix Donut Chart
  const statusDonutCtx = document.getElementById('statusDonutChart');
  if (statusDonutCtx) {
    const distribution = {!! json_encode(collect($distribution ?? [])) !!};
    const distLabels = distribution.map(d => d.name);
    const distCounts = distribution.map(d => d.count);
    const distColors = distribution.map(d => d.color || '#3b82f6');
    const colors = getChartThemeColors(isDarkModeActive() ? 'dark' : 'light');

    statusDonutChart = new Chart(statusDonutCtx.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: distLabels,
        datasets: [{
          data: distCounts,
          backgroundColor: distColors,
          borderWidth: 2,
          borderColor: colors.donutBorder,
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        animation: prefersReduced ? false : { animateRotate: true, duration: 800 },
        plugins: {
          legend: { display: false },
          tooltip: {
            rtl: isRTL,
            textDirection: isRTL ? 'rtl' : 'ltr',
            padding: 10,
            cornerRadius: 8,
            backgroundColor: colors.tooltipBg,
            callbacks: {
              label: (ctx) => ` ${ctx.label}: ${ctx.raw}`
            }
          }
        }
      }
    });
  }

  // 2. Conversion Rate Ring Chart
  const convRingCtx = document.getElementById('conversionRingChart');
  if (convRingCtx) {
    const convRate = {{ (float) ($finalConversionRate ?? $contractRate ?? 0) }};
    const remainRate = Math.max(0, 100 - convRate);
    const ringColor = '{{ $finalStageColor ?? '#10b981' }}';
    const colors = getChartThemeColors(isDarkModeActive() ? 'dark' : 'light');

    conversionRingChart = new Chart(convRingCtx.getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: ['{{ __('مكتمل') }}', '{{ __('متبقي') }}'],
        datasets: [{
          data: [convRate, remainRate],
          backgroundColor: [ringColor, isDarkModeActive() ? '#1e293b' : '#f1f5f9'],
          borderWidth: 2,
          borderColor: colors.donutBorder
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '78%',
        rotation: -90,
        circumference: 360,
        animation: prefersReduced ? false : { animateRotate: true, duration: 800 },
        plugins: {
          legend: { display: false },
          tooltip: {
            rtl: isRTL,
            textDirection: isRTL ? 'rtl' : 'ltr',
            padding: 10,
            cornerRadius: 8,
            backgroundColor: colors.tooltipBg,
            callbacks: {
              label: (ctx) => ` ${ctx.label}: ${ctx.raw}%`
            }
          }
        }
      }
    });
  }

  // 3. Primary Performance Chart (Customer Activity Timeline)
  const perfCtx = document.getElementById('performanceChart');
  if (perfCtx) {
    const perfTimeline = {!! json_encode($performanceTimeline ?? ['labels' => [], 'total' => [], 'newLeads' => [], 'followups' => [], 'contracts' => [], 'hasData' => false], JSON_UNESCAPED_UNICODE) !!};
    const currentThemeColors = getChartThemeColors(isDarkModeActive() ? 'dark' : 'light');

    const buildDatasets = (seriesList, fallback) => {
      if (seriesList && seriesList.length > 0) {
        return seriesList.map((item, idx) => {
          const color = item.color || '#38bdf8';
          return {
            type: 'bar',
            label: item.name,
            data: item.data || [],
            backgroundColor: color,
            borderRadius: 6,
            barThickness: Math.max(8, Math.min(20, Math.floor(36 / Math.max(1, seriesList.length)))),
            maxBarThickness: 24,
            order: idx + 1
          };
        });
      }
      return [
        {
          type: 'line',
          label: '{{ __('الإجمالي') }}',
          data: fallback.total || [],
          borderColor: '#dc2637',
          backgroundColor: 'rgba(220, 38, 55, 0.08)',
          fill: true,
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#dc2637',
          pointBorderWidth: 2,
          tension: 0.35,
          order: 1
        },
        {
          type: 'bar',
          label: '{{ __('عملاء جدد') }}',
          data: fallback.newLeads || [],
          backgroundColor: '#38bdf8',
          borderRadius: 6,
          barThickness: 12,
          maxBarThickness: 18,
          order: 2
        },
        {
          type: 'bar',
          label: '{{ __('متابعات') }}',
          data: fallback.followups || [],
          backgroundColor: '#8b5cf6',
          borderRadius: 6,
          barThickness: 12,
          maxBarThickness: 18,
          order: 3
        },
        {
          type: 'bar',
          label: '{{ __('تعاقد') }}',
          data: fallback.contracts || [],
          backgroundColor: '#10b981',
          borderRadius: 6,
          barThickness: 12,
          maxBarThickness: 18,
          order: 4
        }
      ];
    };

    const createPerfChart = () => {
      const ChartClass = window.Chart;
      if (!ChartClass) return;
      perfChart = new ChartClass(perfCtx.getContext('2d'), {
        type: 'bar',
        data: {
          labels: perfTimeline.labels || [],
          datasets: buildDatasets(perfTimeline.series, perfTimeline)
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: prefersReduced ? false : { duration: 900, easing: 'easeOutQuart' },
          plugins: {
            legend: {
              position: 'bottom',
              rtl: isRTL,
              textDirection: isRTL ? 'rtl' : 'ltr',
              labels: {
                color: currentThemeColors.legendColor,
                usePointStyle: true,
                boxWidth: 8,
                padding: 16,
                font: { size: 12, weight: '700' }
              }
            },
            tooltip: {
              rtl: isRTL,
              textDirection: isRTL ? 'rtl' : 'ltr',
              padding: 12,
              cornerRadius: 10,
              backgroundColor: currentThemeColors.tooltipBg
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: currentThemeColors.gridColor,
                borderDash: [4, 4],
                drawBorder: false
              },
              ticks: { color: currentThemeColors.textColor, precision: 0 }
            },
            x: {
              grid: { display: false },
              ticks: { color: currentThemeColors.textColor }
            }
          }
        }
      });
      window.perfChart = perfChart;
    };
    try {
      createPerfChart();
    } catch (err) {
      console.error('Failed to create perfChart', err);
    }
  }
  window.statusDonutChart = statusDonutChart;
  window.conversionRingChart = conversionRingChart;
    } catch (e) {
      log('initDashboardCharts error: ' + e.message);
    } finally {
      chartsInitializing = false;
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { initDashboardCharts(); }, { once: true });
  } else {
    initDashboardCharts();
  }
  window.addEventListener('load', () => {
    if (!chartsInitialized) initDashboardCharts();
  }, { once: true });
})();
</script>
@can('voip.view')
<script>
(() => {
  const loadVoipAsync = () => {
    fetch('{{ route('dashboard', ['widget' => 'voip_status']) }}', {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
      .then(res => res.ok ? res.json() : null)
      .then(data => {
        if (data && data.voipStatus) {
          window.__crmVoipStatus = data.voipStatus;
        }
      })
      .catch(() => {});
  };
  if (window.requestIdleCallback) {
    requestIdleCallback(loadVoipAsync, { timeout: 3000 });
  } else {
    setTimeout(loadVoipAsync, 500);
  }
})();
</script>
@endcan
<!-- NUMERIC COUNTERS & MINI CALENDAR LOGIC -->
<script>
(() => {
  const isArabic = '{{ app()->getLocale() }}' === 'ar';
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // KPI Numeric Counters (Western digits)
  const counters = document.querySelectorAll('.counter-num');
  const numberFormatter = new Intl.NumberFormat('en-US');

  if (prefersReduced || !('IntersectionObserver' in window)) {
    counters.forEach(c => {
      const raw = c.dataset.target !== undefined ? c.dataset.target : c.textContent.replace(/[^0-9.-]+/g, '');
      const target = parseFloat(raw);
      if (!isNaN(target)) {
        c.textContent = (c.dataset.float === 'true' || target % 1 !== 0) ? target.toFixed(1) : numberFormatter.format(target);
      }
    });
  } else {
    const observer = new IntersectionObserver((entries, obs) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          obs.unobserve(el);
          const raw = el.dataset.target !== undefined ? el.dataset.target : el.textContent.replace(/[^0-9.-]+/g, '');
          const target = parseFloat(raw);
          if (isNaN(target)) return;
          const isFloat = el.dataset.float === 'true' || target % 1 !== 0;
          const duration = 650;
          const startTime = performance.now();

          const step = (currentTime) => {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 4);
            const currentVal = target * ease;
            el.textContent = isFloat ? currentVal.toFixed(1) : numberFormatter.format(Math.round(currentVal));
            if (progress < 1) {
              requestAnimationFrame(step);
            } else {
              el.textContent = isFloat ? target.toFixed(1) : numberFormatter.format(target);
            }
          };
          requestAnimationFrame(step);
        }
      });
    }, { threshold: 0.15 });

    counters.forEach(c => observer.observe(c));
  }

  // Mini Calendar Component
  const gridEl = document.getElementById('miniCalGrid');
  const monthYearEl = document.getElementById('miniCalendarMonthYear');
  const selectedDateLabelEl = document.getElementById('miniCalSelectedDateLabel');
  const eventCountEl = document.getElementById('miniCalEventCount');
  const eventsListEl = document.getElementById('miniCalEventsList');
  const prevBtn = document.getElementById('miniCalPrev');
  const nextBtn = document.getElementById('miniCalNext');

  if (!gridEl) return;

  const rawEventsInput = {!! json_encode($miniCalendarEvents ?? [], JSON_UNESCAPED_UNICODE) !!};
  const rawEventsData = {};
  if (Array.isArray(rawEventsInput)) {
    rawEventsInput.forEach(ev => {
      const d = ev.date;
      if (d) {
        if (!rawEventsData[d]) rawEventsData[d] = [];
        rawEventsData[d].push(ev);
      }
    });
  } else if (typeof rawEventsInput === 'object' && rawEventsInput !== null) {
    Object.keys(rawEventsInput).forEach(k => {
      rawEventsData[k] = Array.isArray(rawEventsInput[k]) ? rawEventsInput[k] : [rawEventsInput[k]];
    });
  }

  const today = new Date();
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

  let viewYear = today.getFullYear();
  let viewMonth = today.getMonth();
  let selectedDateStr = todayStr;

  const arMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
  const enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

  const formatMonthYear = (year, month) => {
    const monthName = isArabic ? arMonths[month] : enMonths[month];
    return `${monthName} ${year}`;
  };

  const formatDateFull = (dateStr) => {
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    const monthName = isArabic ? arMonths[m] : enMonths[m];
    return `${d} ${monthName} ${y}`;
  };

  const escapeHtml = (str) => {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  };

  const renderCalendar = () => {
    if (monthYearEl) monthYearEl.textContent = formatMonthYear(viewYear, viewMonth);
    gridEl.innerHTML = '';

    const firstDay = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(viewYear, viewMonth, 0).getDate();

    for (let i = firstDay - 1; i >= 0; i--) {
      const dNum = daysInPrevMonth - i;
      const prevM = viewMonth === 0 ? 11 : viewMonth - 1;
      const prevY = viewMonth === 0 ? viewYear - 1 : viewYear;
      const dStr = `${prevY}-${String(prevM + 1).padStart(2, '0')}-${String(dNum).padStart(2, '0')}`;
      const cell = createDayCell(dNum, dStr, true);
      gridEl.appendChild(cell);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dStr = `${viewYear}-${String(viewMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      const cell = createDayCell(d, dStr, false);
      gridEl.appendChild(cell);
    }

    const totalRendered = firstDay + daysInMonth;
    const totalSlots = totalRendered > 35 ? 42 : 35;
    const nextPadding = totalSlots - totalRendered;
    for (let n = 1; n <= nextPadding; n++) {
      const nextM = viewMonth === 11 ? 0 : viewMonth + 1;
      const nextY = viewMonth === 11 ? viewYear + 1 : viewYear;
      const dStr = `${nextY}-${String(nextM + 1).padStart(2, '0')}-${String(n).padStart(2, '0')}`;
      const cell = createDayCell(n, dStr, true);
      gridEl.appendChild(cell);
    }

    renderEventsPreview();
  };

  const createDayCell = (dayNum, dateStr, isOtherMonth) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'mini-cal-day';
    if (isOtherMonth) btn.classList.add('other-month');
    if (dateStr === todayStr) btn.classList.add('today');
    if (dateStr === selectedDateStr) btn.classList.add('selected');

    const events = rawEventsData[dateStr] || [];
    const hasEvents = events.length > 0;

    btn.setAttribute('aria-label', `${formatDateFull(dateStr)}${hasEvents ? `, ${events.length} ${isArabic ? 'أحداث' : 'events'}` : ''}`);

    const numSpan = document.createElement('span');
    numSpan.className = 'mini-cal-day-num';
    numSpan.textContent = dayNum;
    btn.appendChild(numSpan);

    if (hasEvents) {
      const dot = document.createElement('span');
      dot.className = 'mini-cal-dot';
      btn.appendChild(dot);
    }

    btn.addEventListener('click', () => {
      selectedDateStr = dateStr;
      document.querySelectorAll('.mini-cal-day.selected').forEach(el => el.classList.remove('selected'));
      btn.classList.add('selected');
      renderEventsPreview();
    });

    return btn;
  };

  const renderEventsPreview = () => {
    if (selectedDateLabelEl) {
      if (selectedDateStr === todayStr) {
        selectedDateLabelEl.textContent = isArabic ? `أحداث اليوم (${formatDateFull(selectedDateStr)})` : `Today's Events (${formatDateFull(selectedDateStr)})`;
      } else {
        selectedDateLabelEl.textContent = isArabic ? `أحداث ${formatDateFull(selectedDateStr)}` : `Events for ${formatDateFull(selectedDateStr)}`;
      }
    }
    const events = rawEventsData[selectedDateStr] || [];
    const count = events.length;

    if (eventCountEl) eventCountEl.textContent = `${count} ${isArabic ? 'أحداث' : 'events'}`;
    if (!eventsListEl) return;
    eventsListEl.innerHTML = '';

    if (count === 0) {
      const empty = document.createElement('div');
      empty.className = 'mini-cal-empty';
      empty.innerHTML = `<i class="bi bi-calendar2-x" style="font-size:18px;display:block;margin-bottom:4px;opacity:0.6"></i>${isArabic ? 'لا توجد أحداث لهذا اليوم' : 'No events for this day'}`;
      eventsListEl.appendChild(empty);
    } else {
      const displayLimit = 4;
      events.slice(0, displayLimit).forEach(ev => {
        const item = document.createElement('div');
        item.className = 'mini-cal-event-item';

        const headerDiv = document.createElement('div');
        headerDiv.className = 'event-item-header';

        const timeSpan = document.createElement('span');
        timeSpan.className = 'event-time-badge';
        timeSpan.innerHTML = `<i class="bi bi-clock"></i> ${escapeHtml(ev.time || '—')}`;

        const typeLabels = {
          'meeting': isArabic ? 'اجتماع' : 'Meeting',
          'call': isArabic ? 'مكالمة' : 'Call',
          'task': isArabic ? 'مهمة' : 'Task',
          'reminder': isArabic ? 'تذكير' : 'Reminder'
        };
        const statusLabels = {
          'scheduled': isArabic ? 'مجدول' : 'Scheduled',
          'completed': isArabic ? 'مكتمل' : 'Completed',
          'canceled': isArabic ? 'ملغي' : 'Canceled'
        };

        const evType = ev.type || 'meeting';
        const evStatus = ev.status || 'scheduled';

        const typeSpan = document.createElement('span');
        typeSpan.className = `event-type-badge event-type-${evType}`;
        typeSpan.textContent = typeLabels[evType] || evType;

        const statusSpan = document.createElement('span');
        statusSpan.className = `event-status-badge event-status-${evStatus}`;
        statusSpan.textContent = statusLabels[evStatus] || evStatus;

        headerDiv.appendChild(timeSpan);
        headerDiv.appendChild(typeSpan);
        headerDiv.appendChild(statusSpan);
        item.appendChild(headerDiv);

        const titleLink = document.createElement('a');
        titleLink.className = 'event-title-link';
        titleLink.href = ev.action_url || ev.lead_url || ev.calendar_url || '{{ route("v2.calendar.index") }}';
        titleLink.textContent = ev.title || (isArabic ? 'حدث' : 'Event');
        titleLink.title = ev.title || '';
        item.appendChild(titleLink);

        if (ev.lead_name) {
          const leadMeta = document.createElement('span');
          leadMeta.className = 'event-lead-meta';
          leadMeta.innerHTML = `<i class="bi bi-person"></i> ${escapeHtml(ev.lead_name)}${ev.lead_company ? ' — ' + escapeHtml(ev.lead_company) : ''}`;
          item.appendChild(leadMeta);
        }

        eventsListEl.appendChild(item);
      });

      if (count > displayLimit) {
        const more = document.createElement('div');
        more.style.fontSize = '11px';
        more.style.fontWeight = '700';
        more.style.color = '#3b82f6';
        more.style.textAlign = 'center';
        more.style.padding = '4px 0';
        more.textContent = isArabic ? `+ ${count - displayLimit} أحداث إضافية` : `+ ${count - displayLimit} more events`;
        eventsListEl.appendChild(more);
      }
    }
  };

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (viewMonth === 0) {
        viewMonth = 11;
        viewYear--;
      } else {
        viewMonth--;
      }
      renderCalendar();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (viewMonth === 11) {
        viewMonth = 0;
        viewYear++;
      } else {
        viewMonth++;
      }
      renderCalendar();
    });
  }

  renderCalendar();
})();
</script>
<!-- DYNAMIC PIPELINE WIDGETS INTERACTION SCRIPT -->
<script>
(() => {
  const escapeHtml = (str) => {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  };

  const updateUrlParam = (key, val) => {
    try {
      const url = new URL(window.location.href);
      if (val) {
        url.searchParams.set(key, val);
      } else {
        url.searchParams.delete(key);
      }
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  };

  // 1. Stage-to-Stage Conversion Selectors (FROM and TO)
  const fromSelect = document.getElementById('conversionFromStageSelect');
  const toSelect = document.getElementById('conversionStageSelect');

  const updateConversion = async () => {
    const fromId = fromSelect ? fromSelect.value : '';
    const toId = toSelect ? toSelect.value : '';
    if (fromId) updateUrlParam('from_stage_id', fromId);
    if (toId) updateUrlParam('to_stage_id', toId);
    if (toId) updateUrlParam('conversion_stage_id', toId);

    try {
      const url = new URL(`{{ route('dashboard') }}`, window.location.origin);
      url.searchParams.set('ajax', '1');
      url.searchParams.set('widget', 'conversion');
      if (fromId) url.searchParams.set('from_stage_id', fromId);
      if (toId) url.searchParams.set('to_stage_id', toId);
      if (toId) url.searchParams.set('conversion_stage_id', toId);

      const res = await fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data.success) {
        const valEl = document.getElementById('conversionValue');
        const subEl = document.querySelector('#conversionSubtitle span');
        const cardEl = document.getElementById('kpiCardConversion');
        const iconEl = document.querySelector('#conversionIcon i');

        if (valEl) {
          valEl.textContent = data.display_value;
          valEl.dataset.target = data.rate !== null ? data.rate : 0;
        }
        if (subEl) subEl.textContent = data.subtitle;
        if (cardEl && data.color) {
          cardEl.style.setProperty('--card-accent', data.color);
          cardEl.style.setProperty('--icon-bg', data.color + '1a');
        }
        if (iconEl && data.icon) {
          iconEl.className = data.icon;
        }
      }
    } catch (err) {
      console.error('Failed to update conversion KPI', err);
    }
  };

  if (fromSelect) fromSelect.addEventListener('change', updateConversion);
  if (toSelect) toSelect.addEventListener('change', updateConversion);

  // Dynamic Performance Chart Stage Multi-Select Dropdown
  const stageDropdown = document.getElementById('chartStageDropdown');
  const stageDropdownToggle = document.getElementById('chartStageDropdownToggle');
  const stageDropdownMenu = document.getElementById('chartStageDropdownMenu');
  const stageDropdownLabel = document.getElementById('chartStageDropdownLabel');
  const stageSelectAllBtn = document.getElementById('chartSelectAllStages');
  const stageResetBtn = document.getElementById('chartResetStages');
  const stageCheckboxes = document.querySelectorAll('.chart-stage-checkbox');

  const updateDropdownSummary = (count, total) => {
    if (!stageDropdownLabel) return;
    const isArabic = '{{ app()->getLocale() === 'ar' ? 'true' : 'false' }}' === 'true';
    if (count === total) {
      stageDropdownLabel.textContent = isArabic ? `جميع المراحل (${total})` : `All Stages (${total})`;
    } else {
      stageDropdownLabel.textContent = isArabic ? `${count} مراحل مختارة` : `Selected Stages: ${count}`;
    }
  };

  const fetchChartData = async (selectedIds) => {
    updateUrlParam('chart_stages', selectedIds.join(','));
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('ajax', '1');
      url.searchParams.set('widget', 'performance_chart');
      url.searchParams.set('chart_stages', selectedIds.join(','));

      const res = await fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });
      if (!res.ok) return;
      const data = await res.json();
      const activeChart = window.perfChart || (typeof Chart !== 'undefined' && Chart.getChart ? Chart.getChart(document.getElementById('performanceChart')) : null);
      if (data.success && data.timeline && activeChart) {
        activeChart.data.labels = data.timeline.labels || [];
        if (data.timeline.series && data.timeline.series.length > 0) {
          activeChart.data.datasets = data.timeline.series.map((item, idx) => {
            const color = item.color || '#38bdf8';
            return {
              type: 'bar',
              label: item.name,
              data: item.data || [],
              backgroundColor: color,
              borderRadius: 6,
              barThickness: Math.max(8, Math.min(20, Math.floor(36 / Math.max(1, data.timeline.series.length)))),
              maxBarThickness: 24,
              order: idx + 1
            };
          });
        }
        activeChart.update();
      }
    } catch (err) {
      console.error('Failed to update chart stages', err);
    }
  };
  if (stageDropdownToggle && stageDropdown) {
    stageDropdownToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = stageDropdown.classList.toggle('is-open');
      stageDropdownToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!stageDropdown.contains(e.target)) {
        stageDropdown.classList.remove('is-open');
        stageDropdownToggle.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && stageDropdown.classList.contains('is-open')) {
        stageDropdown.classList.remove('is-open');
        stageDropdownToggle.setAttribute('aria-expanded', 'false');
        stageDropdownToggle.focus();
      }
    });
  }

  if (stageCheckboxes.length > 0) {
    stageCheckboxes.forEach((cb) => {
      cb.addEventListener('change', () => {
        const checked = [...stageCheckboxes].filter(c => c.checked);
        if (checked.length === 0) {
          cb.checked = true;
          return;
        }
        updateDropdownSummary(checked.length, stageCheckboxes.length);
        fetchChartData(checked.map(c => c.value));
      });
    });
  }

  if (stageSelectAllBtn) {
    stageSelectAllBtn.addEventListener('click', () => {
      stageCheckboxes.forEach(cb => { cb.checked = true; });
      updateDropdownSummary(stageCheckboxes.length, stageCheckboxes.length);
      fetchChartData([...stageCheckboxes].map(c => c.value));
    });
  }

  if (stageResetBtn) {
    stageResetBtn.addEventListener('click', () => {
      stageCheckboxes.forEach((cb) => { cb.checked = true; });
      updateDropdownSummary(stageCheckboxes.length, stageCheckboxes.length);
      fetchChartData([...stageCheckboxes].map(c => c.value));
    });
  }

  // 2. Dynamic KPI 1 Selector
  const kpi1Select = document.getElementById('stageKpi1Select');
  if (kpi1Select) {
    kpi1Select.addEventListener('change', async () => {
      const stageId = kpi1Select.value;
      updateUrlParam('stage_kpi_1', stageId);
      try {
        const res = await fetch(`{{ route('dashboard') }}?ajax=1&widget=stage_kpi_1&stage_kpi_1=${encodeURIComponent(stageId)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.success) {
          const labelEl = document.getElementById('stageKpi1Label');
          const valEl = document.getElementById('stageKpi1Value');
          const subEl = document.querySelector('#stageKpi1Subtitle span');
          const cardEl = document.getElementById('kpiCardStage1');
          const iconEl = document.querySelector('#stageKpi1Icon i');

          if (labelEl) labelEl.textContent = data.stage_name;
          if (valEl) {
            valEl.textContent = data.formatted_count;
            valEl.dataset.target = data.count;
          }
          if (subEl) subEl.textContent = data.subtitle;
          if (cardEl && data.color) {
            cardEl.style.setProperty('--card-accent', data.color);
            cardEl.style.setProperty('--icon-bg', data.color + '1a');
          }
          if (iconEl && data.icon) {
            iconEl.className = data.icon;
          }
        }
      } catch (err) {
        console.error('Failed to update stage KPI 1', err);
      }
    });
  }

  // 3. Dynamic KPI 2 Selector
  const kpi2Select = document.getElementById('stageKpi2Select');
  if (kpi2Select) {
    kpi2Select.addEventListener('change', async () => {
      const stageId = kpi2Select.value;
      updateUrlParam('stage_kpi_2', stageId);
      try {
        const res = await fetch(`{{ route('dashboard') }}?ajax=1&widget=stage_kpi_2&stage_kpi_2=${encodeURIComponent(stageId)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.success) {
          const labelEl = document.getElementById('stageKpi2Label');
          const valEl = document.getElementById('stageKpi2Value');
          const subEl = document.querySelector('#stageKpi2Subtitle span');
          const cardEl = document.getElementById('kpiCardStage2');
          const iconEl = document.querySelector('#stageKpi2Icon i');

          if (labelEl) labelEl.textContent = data.stage_name;
          if (valEl) {
            valEl.textContent = data.formatted_count;
            valEl.dataset.target = data.count;
          }
          if (subEl) subEl.textContent = data.subtitle;
          if (cardEl && data.color) {
            cardEl.style.setProperty('--card-accent', data.color);
            cardEl.style.setProperty('--icon-bg', data.color + '1a');
          }
          if (iconEl && data.icon) {
            iconEl.className = data.icon;
          }
        }
      } catch (err) {
        console.error('Failed to update stage KPI 2', err);
      }
    });
  }

  // 4. Dynamic Stage Activity Selector
  const activitySelect = document.getElementById('stageActivitySelect');
  if (activitySelect) {
    activitySelect.addEventListener('change', async () => {
      const stageId = activitySelect.value;
      updateUrlParam('activity_stage_id', stageId);
      try {
        const res = await fetch(`{{ route('dashboard') }}?ajax=1&widget=stage_activity&activity_stage_id=${encodeURIComponent(stageId)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.success) {
          const todayEl = document.getElementById('stageActivityToday');
          const overdueEl = document.getElementById('stageActivityOverdue');
          const upcomingEl = document.getElementById('stageActivityUpcoming');
          const totalEl = document.getElementById('stageActivityTotal');
          const detailsEl = document.getElementById('stageActivityDetails');

          const numFmt = new Intl.NumberFormat('en-US');
          if (todayEl) todayEl.textContent = numFmt.format(data.today_count);
          if (overdueEl) overdueEl.textContent = numFmt.format(data.overdue_count);
          if (upcomingEl) upcomingEl.textContent = numFmt.format(data.upcoming_count);
          if (totalEl) totalEl.textContent = `${numFmt.format(data.total_count)} {{ __('crm.all') }}`;

          if (detailsEl) {
            detailsEl.innerHTML = '';
            if (Array.isArray(data.leads) && data.leads.length > 0) {
              data.leads.forEach(lead => {
                const row = document.createElement('div');
                row.className = 'stage-activity-row';

                const info = document.createElement('div');
                info.className = 'stage-activity-lead-info';

                @can('leads.view')
                  const link = document.createElement('a');
                  link.href = lead.url;
                  link.className = 'stage-activity-lead-link';
                  link.title = lead.name;
                  link.textContent = lead.name;
                  info.appendChild(link);
                @else
                  const nameSpan = document.createElement('span');
                  nameSpan.className = 'stage-activity-lead-name';
                  nameSpan.textContent = lead.name;
                  info.appendChild(nameSpan);
                @endcan

                const emp = document.createElement('span');
                emp.className = 'stage-activity-lead-emp';
                emp.innerHTML = `<i class="bi bi-person"></i> ${escapeHtml(lead.employee_name)}`;
                info.appendChild(emp);

                const meta = document.createElement('div');
                meta.className = 'stage-activity-meta';

                const badge = document.createElement('span');
                badge.className = `stage-activity-badge ${lead.timing_class}`;
                badge.textContent = lead.timing_label;
                meta.appendChild(badge);

                const time = document.createElement('span');
                time.className = 'stage-activity-time';
                time.innerHTML = `<i class="bi bi-clock"></i> ${escapeHtml(lead.scheduled_time)}`;
                meta.appendChild(time);

                row.appendChild(info);
                row.appendChild(meta);
                detailsEl.appendChild(row);
              });
            } else {
              const empty = document.createElement('div');
              empty.className = 'stage-activity-empty';
              empty.id = 'stageActivityEmpty';
              empty.innerHTML = `<i class="bi bi-calendar2-x"></i> <span>{{ __('crm.no_activity_for_stage') }}</span>`;
              detailsEl.appendChild(empty);
            }
          }
        }
      } catch (err) {
        console.error('Failed to update stage activity', err);
      }
    });
  }

  // 5. Stage Activity Modal Controller & Clickable Tiles
  const stageModalBackdrop = document.getElementById('stageActivityModalBackdrop');
  const stageModal = document.getElementById('stageActivityModal');
  const stageModalClose = document.getElementById('stageActivityModalClose');
  const stageModalTitleText = document.getElementById('stageActivityModalTitleText');
  const stageModalStageName = document.getElementById('stageActivityModalStageName');
  const stageModalCountText = document.getElementById('stageActivityModalCountText');
  const stageModalList = document.getElementById('stageActivityModalList');
  const stageModalEmpty = document.getElementById('stageActivityModalEmpty');
  const stageModalLoading = document.getElementById('stageActivityModalLoading');
  const stageModalInfo = document.getElementById('stageActivityModalInfo');
  const stageModalPageNum = document.getElementById('stageActivityPageNum');
  const stagePrevBtn = document.getElementById('stageActivityPrevPage');
  const stageNextBtn = document.getElementById('stageActivityNextPage');

  let currentModalStageId = activitySelect ? activitySelect.value : '';
  let currentModalBucket = 'all';
  let currentModalPage = 1;
  let totalModalPages = 1;
  let isModalLoading = false;
  let lastActiveTile = null;

  const isLocaleAr = '{{ app()->getLocale() }}' === 'ar';
  const bucketTitleMap = {
    'all': isLocaleAr ? 'جميع المتابعات' : 'All Activity',
    'today': isLocaleAr ? 'متابعات اليوم' : 'Today\'s Activity',
    'overdue': isLocaleAr ? 'المتابعات المتأخرة' : 'Overdue Activity',
    'upcoming': isLocaleAr ? 'المتابعات القادمة' : 'Upcoming Activity',
  };

  const fetchStageActivityLeads = async (stageId, bucket, page = 1) => {
    if (isModalLoading) return;
    isModalLoading = true;
    currentModalStageId = stageId;
    currentModalBucket = bucket;
    currentModalPage = page;

    document.querySelectorAll('.stage-activity-modal-tab').forEach(tab => {
      const isActive = tab.dataset.modalBucket === bucket;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    if (stageModalList) stageModalList.innerHTML = '';
    if (stageModalEmpty) stageModalEmpty.hidden = true;
    if (stageModalLoading) stageModalLoading.hidden = false;

    const currentUrl = new URL(window.location.href);
    const employeeParam = currentUrl.searchParams.get('employee') || '';
    const fromParam = currentUrl.searchParams.get('from') || '';
    const toParam = currentUrl.searchParams.get('to') || '';

    const queryParams = new URLSearchParams({
      ajax: '1',
      widget: 'stage_activity_leads',
      activity_stage_id: stageId,
      bucket: bucket,
      page: String(page),
      per_page: '10',
    });
    if (employeeParam) queryParams.set('employee', employeeParam);
    if (fromParam) queryParams.set('from', fromParam);
    if (toParam) queryParams.set('to', toParam);

    try {
      const res = await fetch(`{{ route('dashboard') }}?${queryParams.toString()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });
      if (!res.ok) throw new Error('Network response was not ok');
      const data = await res.json();

      if (data.success) {
        totalModalPages = Math.max(1, data.last_page || 1);
        currentModalPage = Math.max(1, data.current_page || 1);

        const titlePrefix = data.bucket_label || bucketTitleMap[bucket] || 'Activity';
        if (stageModalTitleText) {
          stageModalTitleText.textContent = `${titlePrefix} — ${data.stage_name || ''}`;
        }
        if (stageModalStageName) {
          stageModalStageName.textContent = data.stage_name || '';
        }
        if (stageModalCountText) {
          const numFmt = new Intl.NumberFormat(isLocaleAr ? 'ar-EG' : 'en-US');
          stageModalCountText.textContent = isLocaleAr ? `${numFmt.format(data.total)} عميل` : `${numFmt.format(data.total)} Customers`;
        }

        if (stageModalPageNum) {
          stageModalPageNum.textContent = `${currentModalPage} / ${totalModalPages}`;
        }
        if (stagePrevBtn) stagePrevBtn.disabled = currentModalPage <= 1;
        if (stageNextBtn) stageNextBtn.disabled = currentModalPage >= totalModalPages;

        if (stageModalInfo) {
          const numFmt = new Intl.NumberFormat(isLocaleAr ? 'ar-EG' : 'en-US');
          if (data.total > 0) {
            stageModalInfo.textContent = isLocaleAr
              ? `عرض ${data.from}-${data.to} من أصل ${numFmt.format(data.total)} عميل`
              : `Showing ${data.from}-${data.to} of ${numFmt.format(data.total)} customers`;
          } else {
            stageModalInfo.textContent = isLocaleAr ? '0 عميل' : '0 customers';
          }
        }

        if (Array.isArray(data.leads) && data.leads.length > 0) {
          if (stageModalList) {
            stageModalList.innerHTML = '';
            data.leads.forEach(lead => {
              const card = document.createElement('div');
              card.className = 'stage-activity-lead-card';

              const main = document.createElement('div');
              main.className = 'stage-activity-lead-main';

              const titleRow = document.createElement('div');
              titleRow.className = 'stage-activity-lead-title-row';

              @can('leads.view')
                const nameLink = document.createElement('a');
                nameLink.href = lead.url;
                nameLink.className = 'stage-activity-lead-name-link';
                nameLink.textContent = lead.name;
                nameLink.title = lead.name;
                titleRow.appendChild(nameLink);
              @else
                const nameSpan = document.createElement('span');
                nameSpan.className = 'stage-activity-lead-name-link';
                nameSpan.textContent = lead.name;
                titleRow.appendChild(nameSpan);
              @endcan

              if (lead.status_name) {
                const statusPill = document.createElement('span');
                statusPill.className = 'stage-activity-lead-status-pill';
                statusPill.style.color = lead.status_color || '#64748b';
                statusPill.textContent = lead.status_name;
                titleRow.appendChild(statusPill);
              }

              const subRow = document.createElement('div');
              subRow.className = 'stage-activity-lead-sub-row';

              if (lead.company_name) {
                const compSpan = document.createElement('span');
                compSpan.innerHTML = `<i class="bi bi-building"></i> ${escapeHtml(lead.company_name)}`;
                subRow.appendChild(compSpan);
              }

              const empSpan = document.createElement('span');
              empSpan.innerHTML = `<i class="bi bi-person"></i> ${escapeHtml(lead.employee_name)}`;
              subRow.appendChild(empSpan);

              main.appendChild(titleRow);
              main.appendChild(subRow);

              const timingCol = document.createElement('div');
              timingCol.className = 'stage-activity-lead-timing-col';

              const badgeSpan = document.createElement('span');
              badgeSpan.className = `stage-activity-badge ${lead.timing_class}`;
              badgeSpan.textContent = lead.timing_label;

              const timeSpan = document.createElement('span');
              timeSpan.className = 'stage-activity-time';
              timeSpan.innerHTML = `<i class="bi bi-clock"></i> ${escapeHtml(lead.scheduled_time || lead.scheduled_at)}`;

              timingCol.appendChild(badgeSpan);
              timingCol.appendChild(timeSpan);

              const actionsCol = document.createElement('div');
              actionsCol.className = 'stage-activity-lead-actions';

              @can('leads.view')
                const viewBtn = document.createElement('a');
                viewBtn.href = lead.url;
                viewBtn.className = 'stage-activity-quick-btn';
                viewBtn.title = '{{ __("crm.view_lead") }}';
                viewBtn.innerHTML = `<i class="bi bi-eye"></i> <span>{{ __("عرض") }}</span>`;
                actionsCol.appendChild(viewBtn);
              @endcan

              if (lead.phone) {
                const callBtn = document.createElement('a');
                callBtn.href = `tel:${lead.phone}`;
                callBtn.className = 'stage-activity-quick-btn';
                callBtn.title = '{{ __("crm.call") }}';
                callBtn.innerHTML = `<i class="bi bi-telephone"></i>`;
                actionsCol.appendChild(callBtn);
              }

              card.appendChild(main);
              card.appendChild(timingCol);
              card.appendChild(actionsCol);
              stageModalList.appendChild(card);
            });
          }
        } else {
          if (stageModalEmpty) stageModalEmpty.hidden = false;
        }
      }
    } catch (err) {
      console.error('Failed to load stage activity leads', err);
      if (stageModalEmpty) stageModalEmpty.hidden = false;
    } finally {
      isModalLoading = false;
      if (stageModalLoading) stageModalLoading.hidden = true;
    }
  };

  const openStageModal = (bucket = 'all', triggerEl = null) => {
    lastActiveTile = triggerEl;
    const stageId = activitySelect ? activitySelect.value : '';
    if (stageModalBackdrop) stageModalBackdrop.hidden = false;
    document.body.classList.add('stage-modal-open');
    if (stageModal) stageModal.focus();
    fetchStageActivityLeads(stageId, bucket, 1);
  };

  const closeStageModal = () => {
    if (stageModalBackdrop) stageModalBackdrop.hidden = true;
    document.body.classList.remove('stage-modal-open');
    if (lastActiveTile && typeof lastActiveTile.focus === 'function') {
      lastActiveTile.focus();
    }
  };

  document.querySelectorAll('.stage-activity-clickable-tile').forEach(tile => {
    tile.addEventListener('click', (e) => {
      e.preventDefault();
      const bucket = tile.dataset.bucket || 'all';
      openStageModal(bucket, tile);
    });
    tile.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const bucket = tile.dataset.bucket || 'all';
        openStageModal(bucket, tile);
      }
    });
  });

  document.querySelectorAll('.stage-activity-modal-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const bucket = tab.dataset.modalBucket || 'all';
      const stageId = activitySelect ? activitySelect.value : '';
      fetchStageActivityLeads(stageId, bucket, 1);
    });
  });

  if (stagePrevBtn) {
    stagePrevBtn.addEventListener('click', () => {
      if (currentModalPage > 1) {
        fetchStageActivityLeads(currentModalStageId, currentModalBucket, currentModalPage - 1);
      }
    });
  }
  if (stageNextBtn) {
    stageNextBtn.addEventListener('click', () => {
      if (currentModalPage < totalModalPages) {
        fetchStageActivityLeads(currentModalStageId, currentModalBucket, currentModalPage + 1);
      }
    });
  }

  if (stageModalClose) {
    stageModalClose.addEventListener('click', closeStageModal);
  }
  if (stageModalBackdrop) {
    stageModalBackdrop.addEventListener('click', (e) => {
      if (e.target === stageModalBackdrop) {
        closeStageModal();
      }
    });
  }
  document.addEventListener('keydown', (e) => {
    if (stageModalBackdrop && !stageModalBackdrop.hidden && e.key === 'Escape') {
      closeStageModal();
    }
  });
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
</body>
</html>
