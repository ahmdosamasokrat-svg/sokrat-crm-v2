@extends('leads.transfer-layout')

@php
    $isEmployeeSelected = $selectedEmployee !== null;
@endphp

@section('title', $isEmployeeSelected ? __('تقرير الموظف: ') . $selectedEmployee->name : __('تقارير الموظفين'))
@section('page-title', $isEmployeeSelected ? __('تقرير أداء الموظف') : __('تقارير الموظفين'))
@section('page-description', $isEmployeeSelected ? __('متابعة تفصيلية لأداء ' . $selectedEmployee->name . ' ومؤشرات التحويل والعملاء والمتابعات.') : __('متابعة شاملة لأداء فريق المبيعات وتوزيع العملاء ومعدلات التحويل والحملات.'))

@section('back-url', route('dashboard'))
@section('back-title', __('crm.dashboard'))

@section('top-actions')
    <button class="btn soft btn-print-report" type="button" onclick="window.print()" title="{{ __('طباعة أو تصدير PDF') }}">
        <i class="bi bi-printer"></i>
        <span>{{ __('تصدير / طباعة') }}</span>
    </button>
    <a class="btn soft" href="{{ request()->fullUrl() }}" title="{{ __('تحديث البيانات الحالية') }}">
        <i class="bi bi-arrow-clockwise"></i>
        <span>{{ __('تحديث') }}</span>
    </a>
    @if ($isEmployeeSelected)
        <a class="btn soft" href="{{ route('v2.reports.employees') }}" title="{{ __('عرض تقارير كافة الموظفين') }}">
            <i class="bi bi-people"></i>
            <span>{{ __('كافة الموظفين') }}</span>
        </a>
    @endif
    <a class="btn primary" href="{{ route('dashboard') }}">
        <i class="bi bi-speedometer2"></i>
        <span>{{ __('crm.dashboard') }}</span>
    </a>
@endsection

@push('styles')
<style>
/* ==========================================================================
   EMPLOYEE PERFORMANCE REPORT - SAAS DASHBOARD REDESIGN (SokratCRM)
   ========================================================================== */
.emp-report-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
    width: 100%;
}

/* 1. HORIZONTAL COMPACT FILTER BAR */
.emp-filter-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 16px;
    background: var(--card, #fff);
    border: 1px solid var(--line, #e2e8f0);
    border-radius: 14px;
    box-shadow: var(--shadow, 0 2px 8px rgba(0,0,0,0.03));
}
.emp-filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 800;
    color: var(--dark, #182033);
    padding-inline-end: 10px;
    border-inline-end: 1px solid var(--line, #e2e8f0);
    flex-shrink: 0;
}
.emp-filter-tag i {
    color: var(--red, #dc2637);
    font-size: 14px;
}
.emp-filter-item {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 1 1 140px;
    min-width: 130px;
}
.emp-filter-item label {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted, #64748b);
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.emp-filter-item select,
.emp-filter-item input {
    width: 100%;
    min-width: 110px;
    height: 38px;
    min-height: 38px;
    padding: 0 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: var(--bg, #f8fafc);
    color: var(--dark, #0f172a);
    font-size: 12px;
    font-weight: 600;
    outline: none;
    transition: all 0.15s ease;
}
.emp-filter-item select:focus,
.emp-filter-item input:focus {
    border-color: var(--red, #dc2637);
    box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.12);
    background: #fff;
}
.emp-filter-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
    margin-inline-start: auto;
}
.emp-filter-actions .btn {
    height: 38px;
    min-height: 38px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* 2. 5 BALANCED KPI CARDS */
.emp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
}
.emp-kpi-card {
    background: var(--card, #fff);
    border: 1px solid var(--line, #e2e8f0);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: var(--shadow, 0 4px 12px rgba(0,0,0,0.03));
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}
.emp-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}
.emp-kpi-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.emp-kpi-head .kpi-head-title-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}
.kpi-stage-select-inline {
    height: 28px;
    min-height: 28px;
    padding: 0 6px;
    border: 1px solid var(--line, #cbd5e1);
    border-radius: 6px;
    background: var(--bg, #f8fafc);
    color: var(--dark, #0f172a);
    font-size: 11px;
    font-weight: 700;
    outline: none;
    cursor: pointer;
}
html.dark-mode .kpi-stage-select-inline {
    background: #0f172a;
    border-color: #334155;
    color: #f8fafc;
}
.emp-kpi-label {
    font-size: 12px;
    font-weight: 800;
    color: var(--muted, #64748b);
    display: flex;
    align-items: center;
    gap: 6px;
}
.emp-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 18px;
    flex-shrink: 0;
}
.emp-kpi-card.leads .emp-kpi-icon { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
.emp-kpi-card.converted .emp-kpi-icon { background: rgba(16, 185, 129, 0.12); color: #059669; }
.emp-kpi-card.rate .emp-kpi-icon { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }
.emp-kpi-card.followups .emp-kpi-icon { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.emp-kpi-card.due .emp-kpi-icon { background: rgba(239, 68, 68, 0.12); color: #dc2637; }

.emp-kpi-value {
    font-size: 26px;
    font-weight: 900;
    color: var(--dark, #0f172a);
    font-family: Arial, Tahoma, sans-serif;
    line-height: 1.1;
    letter-spacing: -0.5px;
    font-variant-numeric: tabular-nums;
}
.emp-kpi-card.converted .emp-kpi-value { color: #047857; }
.emp-kpi-card.rate .emp-kpi-value { color: #6d28d9; }

.emp-kpi-footer {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.emp-kpi-sub {
    font-size: 11px;
    font-weight: 700;
    color: var(--muted, #64748b);
}
.emp-kpi-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 5px;
    align-self: flex-start;
}
.emp-kpi-badge.blue { background: #eff6ff; color: #2563eb; }
.emp-kpi-badge.green { background: #ecfdf5; color: #059669; }
.emp-kpi-badge.purple { background: #f5f3ff; color: #7c3aed; }
.emp-kpi-badge.amber { background: #fef3c7; color: #b45309; }
.emp-kpi-badge.cyan { background: #f0f9ff; color: #0284c7; }
.emp-kpi-badge.red { background: #fef2f2; color: #dc2637; }

/* DYNAMIC PIPELINE STAGES STRIP */
.dash-pipeline-strip-wrap {
    background: var(--card, #fff);
    border: 1px solid var(--line, #e2e8f0);
    border-radius: 16px;
    padding: 12px 16px;
    box-shadow: var(--shadow, 0 2px 8px rgba(0,0,0,0.03));
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
}
.dash-pipeline-strip-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.dash-pipeline-strip-title {
    font-size: 13px;
    font-weight: 800;
    color: var(--dark, #182033);
    display: flex;
    align-items: center;
    gap: 7px;
}
.dash-pipeline-strip-title i {
    color: var(--red, #dc2637);
    font-size: 16px;
}
.dash-kanban-link {
    font-size: 12px;
    font-weight: 800;
    color: var(--red, #dc2637);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(220, 38, 55, 0.06);
    transition: all 0.15s ease;
}
.dash-kanban-link:hover {
    background: var(--red, #dc2637);
    color: #fff;
}
.dash-pipeline-strip {
    display: flex;
    flex-wrap: nowrap;
    gap: 10px;
    overflow-x: auto;
    min-width: 0;
    max-width: 100%;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
    padding: 4px 2px 10px;
    scroll-snap-type: x mandatory;
}
.dash-pipeline-strip::-webkit-scrollbar {
    height: 5px;
}
.dash-pipeline-strip::-webkit-scrollbar-track {
    background: var(--bg, #f8fafc);
    border-radius: 99px;
}
.dash-pipeline-strip::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 99px;
}
.pipeline-flow-pill {
    flex: 1 0 155px;
    min-width: 155px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    background: var(--card, #fff);
    border: 1px solid var(--line, #e2e8f0);
    text-decoration: none;
    transition: all 0.2s ease;
    scroll-snap-align: start;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
    position: relative;
    overflow: hidden;
    cursor: pointer;
}
.pipeline-flow-pill::before {
    content: '';
    position: absolute;
    top: 0;
    inset-inline-start: 0;
    inset-inline-end: 0;
    height: 2.5px;
    background: var(--pill-color, var(--red, #dc2637));
    opacity: 0.85;
    transition: all 0.2s ease;
}
.pipeline-flow-pill:hover,
.pipeline-flow-pill.is-selected {
    transform: translateY(-2px);
    border-color: var(--pill-color, var(--red, #dc2637));
    box-shadow: 0 6px 16px -3px rgba(0, 0, 0, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04);
}
.pipeline-flow-pill:hover::before,
.pipeline-flow-pill.is-selected::before {
    height: 3.5px;
    opacity: 1;
}
.pipeline-flow-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 18px;
    background: var(--pill-bg, rgba(59, 130, 246, 0.12));
    color: var(--pill-color, #3b82f6);
    transition: transform 0.2s ease;
}
.pipeline-flow-pill:hover .pipeline-flow-icon,
.pipeline-flow-pill.is-selected .pipeline-flow-icon {
    transform: scale(1.06);
}
.pipeline-flow-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
}
.pipeline-stage-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--dark, #0f172a);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.25;
}
.pipeline-stage-count-wrap {
    display: inline-flex;
    align-items: baseline;
    gap: 4px;
}
.pipeline-stage-count {
    font-size: 18px;
    font-weight: 900;
    color: var(--pill-color, var(--dark, #0f172a));
    font-family: Arial, Tahoma, sans-serif;
    line-height: 1.1;
    letter-spacing: -0.3px;
}
.pipeline-stage-unit {
    font-size: 10px;
    font-weight: 600;
    color: var(--muted, #64748b);
}

/* 3. TWO-COLUMN SPLIT SECTION (Table on one side, Chart on the other) */
.emp-split-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, 1fr);
    gap: 18px;
}
.emp-panel {
    background: var(--card, #fff);
    border: 1px solid var(--line, #e2e8f0);
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: var(--shadow, 0 4px 12px rgba(0,0,0,0.03));
    display: flex;
    flex-direction: column;
}
.emp-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--line, #e2e8f0);
}
.emp-panel-head h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--dark, #0f172a);
    display: flex;
    align-items: center;
    gap: 8px;
}
.emp-panel-head h3 i {
    color: var(--red, #dc2637);
}
.emp-panel-head p {
    margin: 3px 0 0;
    font-size: 12px;
    color: var(--muted, #64748b);
}

.emp-channel-toggle {
    display: inline-flex;
    background: var(--bg, #f8fafc);
    border: 1px solid var(--line, #e2e8f0);
    border-radius: 8px;
    padding: 2px;
    gap: 2px;
}
html.dark-mode .emp-channel-toggle {
    background: #0f172a;
    border-color: #1f293d;
}
.btn-channel-tab {
    border: none;
    background: transparent;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: var(--muted, #64748b);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.btn-channel-tab:hover {
    color: var(--dark, #0f172a);
}
.btn-channel-tab.active {
    background: var(--card, #fff);
    color: var(--red, #dc2637);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
html.dark-mode .btn-channel-tab.active {
    background: #1e293b;
    color: #f87171;
}

/* 4. RECENT LEADS & BREAKDOWN TABLES */
.emp-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
}
.emp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.emp-table th {
    background: var(--bg, #f8fafc);
    color: var(--muted, #64748b);
    font-weight: 800;
    text-align: start;
    padding: 10px 12px;
    border-bottom: 1px solid var(--line, #e2e8f0);
    white-space: nowrap;
}
.emp-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--line, #f1f5f9);
    color: var(--dark, #1e293b);
    font-weight: 600;
    white-space: nowrap;
}
.emp-table tr:hover td {
    background: rgba(0, 0, 0, 0.015);
}
.emp-table tr:last-child td {
    border-bottom: none;
}
.lead-client-cell {
    display: flex;
    flex-direction: column;
    min-width: 130px;
}
.lead-client-cell strong {
    color: var(--dark, #0f172a);
    font-size: 12px;
    font-weight: 800;
}
.lead-client-cell small {
    color: var(--muted, #64748b);
    font-size: 11px;
}
.stage-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 800;
}
.stage-pill-badge i {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.status-tag-sm {
    font-size: 11px;
    color: var(--muted, #64748b);
}
.camp-tag-sm {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 6px;
    background: #f1f5f9;
    color: #475569;
    font-size: 10px;
    font-weight: 700;
}

/* 5. STAGE DISTRIBUTION LIST IN BOTTOM ROW */
.stage-dist-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 14px;
    max-height: 250px;
    overflow-y: auto;
    padding-inline-end: 4px;
}
.stage-dist-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 8px 12px;
    background: var(--bg, #f8fafc);
    border: 1px solid #f1f5f9;
    border-radius: 10px;
}
.stage-dist-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 800;
}
.stage-dist-name {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--dark, #0f172a);
}
.stage-dist-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.stage-dist-val {
    font-variant-numeric: tabular-nums;
    color: var(--dark, #0f172a);
}
.stage-bar-track {
    width: 100%;
    height: 5px;
    border-radius: 99px;
    background: #e2e8f0;
    overflow: hidden;
}
.stage-bar-val {
    height: 100%;
    border-radius: 99px;
    transition: width 0.4s ease;
}

/* 6. CHARTS WRAPPERS */
.chart-box-wrap {
    position: relative;
    height: 280px;
    width: 100%;
}

/* EMPTY STATES */
.emp-empty-state {
    height: 100%;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 24px;
    text-align: center;
    color: var(--muted, #64748b);
}
.emp-empty-state i {
    font-size: 32px;
    color: #cbd5e1;
}
.emp-empty-state strong {
    font-size: 13px;
    font-weight: 800;
    color: var(--dark, #1e293b);
}
.emp-empty-state p {
    margin: 0;
    font-size: 11px;
    line-height: 1.5;
}

/* ==========================================================================
   DARK MODE ADAPTATIONS
   ========================================================================== */
html.dark-mode .emp-filter-bar,
html.dark-mode .emp-kpi-card,
html.dark-mode .emp-panel,
html.dark-mode .dash-pipeline-strip-wrap,
html.dark-mode .pipeline-flow-pill {
    background: #111827;
    border-color: #1f293d;
}
html.dark-mode .dash-pipeline-strip-title,
html.dark-mode .pipeline-stage-name,
html.dark-mode .pipeline-stage-count {
    color: #f8fafc;
}
html.dark-mode .dash-pipeline-strip::-webkit-scrollbar-track {
    background: #0f172a;
}
html.dark-mode .dash-pipeline-strip::-webkit-scrollbar-thumb {
    background: #334155;
}
html.dark-mode .emp-filter-tag,
html.dark-mode .emp-kpi-value,
html.dark-mode .emp-panel-head h3,
html.dark-mode .emp-table td,
html.dark-mode .lead-client-cell strong,
html.dark-mode .stage-dist-name,
html.dark-mode .stage-dist-val,
html.dark-mode .emp-empty-state strong {
    color: #f8fafc;
}
html.dark-mode .emp-filter-item select,
html.dark-mode .emp-filter-item input {
    background: #0f172a;
    border-color: #334155;
    color: #f8fafc;
}
html.dark-mode .emp-table th,
html.dark-mode .stage-dist-item {
    background: #0f172a;
    border-color: #1e293d;
}
html.dark-mode .stage-bar-track {
    background: #1e293d;
}
html.dark-mode .camp-tag-sm {
    background: #1e293b;
    color: #94a3b8;
}

/* ==========================================================================
   PRINT / PDF EXPORT
   ========================================================================== */
@media print {
    .btn-print-report,
    .emp-filter-bar,
    .crm-sidebar,
    .crm-topbar-menu-btn {
        display: none !important;
    }
    .emp-report-container {
        gap: 14px;
    }
    .emp-split-grid {
        grid-template-columns: 1fr !important;
        page-break-inside: avoid;
    }
    .emp-panel, .emp-kpi-card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
}

/* RESPONSIVE BREAKPOINTS */
@media (max-width: 1024px) {
    .emp-split-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 768px) {
    .emp-filter-bar {
        gap: 8px;
    }
    .emp-filter-item {
        flex: 1 1 100%;
        min-width: 100%;
    }
    .emp-filter-actions {
        width: 100%;
        margin-inline-start: 0;
        justify-content: stretch;
    }
    .emp-filter-actions .btn {
        flex: 1;
        justify-content: center;
    }
    .emp-kpi-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 480px) {
    .emp-kpi-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
<div class="emp-report-container" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

    <!-- 1. HORIZONTAL COMPACT FILTER BAR (بالعرض) -->
    <form class="emp-filter-bar" id="empReportFilters" method="GET" action="{{ route('v2.reports.employees') }}">
        <input type="hidden" name="target_stage_id" id="filterTargetStageId" value="{{ $metrics['target_stage_id'] }}">
        <div class="emp-filter-tag">
            <i class="bi bi-funnel-fill"></i>
            <span>{{ __('تصفية التقارير:') }}</span>
        </div>

        <!-- Filter 1: Employee -->
        <div class="emp-filter-item">
            <label for="filterEmployee"><i class="bi bi-person-badge"></i> {{ __('الموظف') }}:</label>
            <select id="filterEmployee" name="employee_id">
                <option value="">{{ __('كافة الموظفين') }}</option>
                @foreach ($assignableUsers as $u)
                    <option value="{{ $u->id }}" @selected($filters['employee_id'] === $u->id)>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter 2: Stage -->
        <div class="emp-filter-item">
            <label for="filterStage"><i class="bi bi-flag"></i> {{ __('المرحلة') }}:</label>
            <select id="filterStage" name="stage_id">
                <option value="">{{ __('كافة المراحل') }}</option>
                @foreach ($stages as $stg)
                    <option value="{{ $stg->id }}" @selected((int) $filters['stage_id'] === (int) $stg->id)>
                        {{ $stg->localizedName() }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter 3: Campaign -->
        <div class="emp-filter-item">
            <label for="filterCampaign"><i class="bi bi-shop-window"></i> {{ __('الحملة') }}:</label>
            <select id="filterCampaign" name="campaign_id">
                <option value="">{{ __('كافة الحملات') }}</option>
                @foreach ($campaigns as $camp)
                    <option value="{{ $camp->id }}" @selected((int) $filters['campaign_id'] === (int) $camp->id)>
                        {{ $camp->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Filter 4: Period -->
        <div class="emp-filter-item">
            <label for="filterPeriod"><i class="bi bi-calendar-range"></i> {{ __('الفترة') }}:</label>
            <select id="filterPeriod" name="period">
                <option value="all" @selected($filters['period'] === 'all')>{{ __('crm.all_periods') }}</option>
                <option value="today" @selected($filters['period'] === 'today')>{{ __('crm.today') }}</option>
                <option value="week" @selected($filters['period'] === 'week')>{{ __('crm.this_week') }}</option>
                <option value="month" @selected($filters['period'] === 'month')>{{ __('crm.this_month') }}</option>
                <option value="year" @selected($filters['period'] === 'year')>{{ __('crm.this_year') }}</option>
                <option value="custom" @selected($filters['period'] === 'custom')>{{ __('crm.custom_range') }}</option>
            </select>
        </div>

        <!-- From Date -->
        <div class="emp-filter-item" id="fieldFilterFrom" style="{{ $filters['period'] === 'custom' ? 'display:flex' : 'display:none' }}">
            <label for="filterFrom"><i class="bi bi-calendar-event"></i> {{ __('من') }}:</label>
            <input id="filterFrom" name="from" type="date" value="{{ $filters['from'] }}" @disabled($filters['period'] !== 'custom')>
        </div>

        <!-- To Date -->
        <div class="emp-filter-item" id="fieldFilterTo" style="{{ $filters['period'] === 'custom' ? 'display:flex' : 'display:none' }}">
            <label for="filterTo"><i class="bi bi-calendar-check"></i> {{ __('إلى') }}:</label>
            <input id="filterTo" name="to" type="date" value="{{ $filters['to'] }}" @disabled($filters['period'] !== 'custom')>
        </div>

        <!-- Action Buttons -->
        <div class="emp-filter-actions">
            <button class="btn primary" type="submit" title="{{ __('crm.apply_filters') }}">
                <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                <span>{{ __('تطبيق') }}</span>
            </button>
            <a class="btn soft" href="{{ route('v2.reports.employees') }}" title="{{ __('crm.reset_filters') }}">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                <span>{{ __('إعادة ضبط') }}</span>
            </a>
        </div>
    </form>

    <!-- 2. KPI CARDS ROW (5 Balanced Metric Cards) -->
    <section class="emp-kpi-grid" aria-label="{{ __('المؤشرات الرئيسية لأداء الموظف') }}">
        <!-- KPI 1: Total Leads -->
        <article class="emp-kpi-card leads">
            <div class="emp-kpi-head">
                <span class="emp-kpi-label"><i class="bi bi-people-fill"></i> {{ __('إجمالي العملاء') }}</span>
                <span class="emp-kpi-icon"><i class="bi bi-people-fill"></i></span>
            </div>
            <div class="emp-kpi-value">{{ number_format($metrics['total_leads']) }}</div>
            <div class="emp-kpi-footer">
                <span class="emp-kpi-sub">{{ __('العملاء المسندين في هذا النطاق') }}</span>
                <span class="emp-kpi-badge blue">{{ __('قاعدة العملاء') }}</span>
            </div>
        </article>

        <!-- KPI 2: Dynamic Target Stage Leads -->
        <article class="emp-kpi-card converted" style="--card-accent: {{ $metrics['target_stage_color'] }};">
            <div class="emp-kpi-head">
                <div class="kpi-head-title-wrap">
                    <span class="emp-kpi-label"><i class="bi bi-bullseye"></i> {{ __('عملاء مرحلة:') }}</span>
                    <select class="kpi-stage-select-inline" id="targetStageSelect" aria-label="{{ __('المرحلة المستهدفة') }}" onchange="document.getElementById('filterTargetStageId').value = this.value; document.getElementById('empReportFilters').submit();">
                        @foreach ($stages as $stg)
                            <option value="{{ $stg->id }}" @selected((int) $metrics['target_stage_id'] === (int) $stg->id)>
                                {{ $stg->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <span class="emp-kpi-icon" style="background: {{ $metrics['target_stage_color'] }}1a; color: {{ $metrics['target_stage_color'] }};">
                    <i class="bi bi-funnel-fill"></i>
                </span>
            </div>
            <div class="emp-kpi-value" style="color: {{ $metrics['target_stage_color'] }};">{{ number_format($metrics['target_stage_leads']) }}</div>
            <div class="emp-kpi-footer">
                <span class="emp-kpi-sub">{{ number_format($metrics['target_stage_leads']) }} {{ __('من إجمالي') }} {{ number_format($metrics['total_leads']) }} {{ __('عميل') }}</span>
                <span class="emp-kpi-badge green">{{ $metrics['target_stage_name'] }}</span>
            </div>
        </article>

        <!-- KPI 3: Conversion Rate to Selected Stage -->
        <article class="emp-kpi-card rate">
            <div class="emp-kpi-head">
                <span class="emp-kpi-label"><i class="bi bi-graph-up-arrow"></i> {{ __('معدل التحويل') }}</span>
                <span class="emp-kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>
            </div>
            <div class="emp-kpi-value">{{ number_format($metrics['target_stage_rate'], 1) }}%</div>
            <div class="emp-kpi-footer">
                <span class="emp-kpi-sub">{{ __('نسبة الإنجاز لمرحلة') }} {{ $metrics['target_stage_name'] }}</span>
                <span class="emp-kpi-badge purple">{{ __('معدل التحويل المحقق') }}</span>
            </div>
        </article>

        <!-- KPI 4: Total Followups -->
        <article class="emp-kpi-card followups">
            <div class="emp-kpi-head">
                <span class="emp-kpi-label"><i class="bi bi-telephone-outbound-fill"></i> {{ __('المتابعات المنفذة') }}</span>
                <span class="emp-kpi-icon"><i class="bi bi-telephone-outbound-fill"></i></span>
            </div>
            <div class="emp-kpi-value">{{ number_format($metrics['total_followups']) }}</div>
            <div class="emp-kpi-footer">
                <span class="emp-kpi-sub">{{ __('مكالمة وتواصل تم تسجيلها') }}</span>
                <span class="emp-kpi-badge amber">{{ __('نشاط التواصل المنجز') }}</span>
            </div>
        </article>

        <!-- KPI 5: Due & Overdue Followups (Replaces Quotation Value Card) -->
        <article class="emp-kpi-card due">
            <div class="emp-kpi-head">
                <span class="emp-kpi-label"><i class="bi bi-clock-history"></i> {{ __('المتابعات المستحقة') }}</span>
                <span class="emp-kpi-icon"><i class="bi bi-clock-history"></i></span>
            </div>
            <div class="emp-kpi-value">{{ number_format($metrics['due_followups']) }}</div>
            <div class="emp-kpi-footer">
                <span class="emp-kpi-sub">
                    @if ($metrics['overdue_followups'] > 0)
                        {{ number_format($metrics['overdue_followups']) }} {{ __('متابعة متأخرة تتطلب تدخلاً') }}
                    @else
                        {{ __('متابعات تتطلب اتخاذ إجراء') }}
                    @endif
                </span>
                <span class="emp-kpi-badge {{ $metrics['overdue_followups'] > 0 ? 'red' : 'amber' }}">
                    {{ $metrics['overdue_followups'] > 0 ? __('متأخرة') : __('مستحقة') }}
                </span>
            </div>
        </article>
    </section>

    <!-- 3. DYNAMIC PIPELINE STAGES STRIP (Directly under KPI cards, same as Dashboard) -->
    <section class="dash-pipeline-strip-wrap" aria-label="{{ __('مراحل مسار المبيعات النشطة') }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
        <div class="dash-pipeline-strip-header">
            <span class="dash-pipeline-strip-title">
                <i class="bi bi-diagram-3-fill"></i>
                <span>{{ __('مراحل مسار المبيعات') }}</span>
                <small style="color:var(--muted);font-weight:600;font-size:11px">({{ __('اضغط على أي مرحلة لاحتساب مؤشرات التحويل فورياً') }})</small>
            </span>
            @can('leads.view')
                <a href="{{ route('v2.leads.kanban') }}" class="dash-kanban-link" target="_blank">
                    <i class="bi bi-kanban"></i>
                    <span>{{ __('crm.kanban') }}</span>
                </a>
            @endcan
        </div>

        <div class="dash-pipeline-strip">
            @foreach (($activePipelineStages ?? []) as $pStage)
                @php
                    $isStripSelected = (int) $pStage['id'] === (int) $metrics['target_stage_id'];
                @endphp
                <div
                    class="pipeline-flow-pill {{ $isStripSelected ? 'is-selected' : '' }}"
                    style="--pill-color: {{ $pStage['color'] }}; --pill-bg: {{ $pStage['color'] }}1a;"
                    data-stage-id="{{ $pStage['id'] }}"
                    title="{{ __('عرض مؤشرات') }} {{ $pStage['name'] }}"
                >
                    <div class="pipeline-flow-icon"><i class="{{ $pStage['icon'] }}"></i></div>
                    <div class="pipeline-flow-info">
                        <strong class="pipeline-stage-name">{{ $pStage['name'] }}</strong>
                        <div class="pipeline-stage-count-wrap">
                            <small class="counter-num">{{ number_format($pStage['count']) }}</small>
                            <span class="pipeline-stage-unit">{{ __('عميل') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- 3. MIDDLE SECTION (ROW 1): Recent Leads Table + Monthly Trend Chart -->
    <section class="emp-split-grid">
        <!-- Part A: Recent Leads Table -->
        <article class="emp-panel">
            <header class="emp-panel-head">
                <div>
                    <h3>
                        <i class="bi bi-person-lines-fill"></i>
                        <span>{{ __('أحدث العملاء والتفاعلات') }}</span>
                    </h3>
                    <p>{{ __('سجل تفاعل ونشاط أحدث العملاء المرتبطين بالموظف المحدد') }}</p>
                </div>
                @can('leads.view')
                    <a class="btn soft small" href="{{ route('v2.leads', array_filter(['employee' => $selectedEmployee?->name])) }}">
                        <span>{{ __('عرض الكل') }}</span>
                        <i class="bi bi-arrow-left rtl-flip"></i>
                    </a>
                @endcan
            </header>

            @if ($recentLeads->isNotEmpty())
                <div class="emp-table-wrap">
                    <table class="emp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('crm.client') }}</th>
                                <th>{{ __('crm.phone') }}</th>
                                <th>{{ __('المرحلة والحالة') }}</th>
                                <th>{{ __('الحملة') }}</th>
                                <th>{{ __('تاريخ الإضافة') }}</th>
                                <th style="text-align:center">{{ __('عرض') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentLeads as $idx => $rLead)
                                @php
                                    $stg = $rLead->status?->stage;
                                    $stgColor = $stg?->color ?: '#64748b';
                                    $camp = $rLead->campaigns->first();
                                @endphp
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="lead-client-cell">
                                            <strong>{{ $rLead->name }}</strong>
                                            <small>{{ $rLead->company_name ?: __('بدون شركة') }}</small>
                                        </div>
                                    </td>
                                    <td dir="ltr" style="font-family: Arial, sans-serif; font-weight:700;">
                                        {{ $rLead->phone ?: '—' }}
                                    </td>
                                    <td>
                                        <span class="stage-pill-badge" style="background: {{ $stgColor }}18; color: {{ $stgColor }}; border: 1px solid {{ $stgColor }}33;">
                                            <i style="background: {{ $stgColor }};"></i>
                                            {{ $stg?->localizedName() ?? '—' }}
                                        </span>
                                        <div class="status-tag-sm">{{ $rLead->status?->name_ar ?? '' }}</div>
                                    </td>
                                    <td>
                                        @if ($camp)
                                            <span class="camp-tag-sm" title="{{ $camp->name }}"><i class="bi bi-megaphone"></i> {{ Str::limit($camp->name, 16) }}</span>
                                        @else
                                            <span style="color:var(--muted)">{{ __('مباشر') }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size:11px;color:var(--muted)">
                                        {{ $rLead->created_at?->format('Y-m-d') ?? '—' }}
                                    </td>
                                    <td style="text-align:center">
                                        @can('leads.view')
                                            <a class="btn soft small" style="height:28px;min-height:28px;padding:0 8px;font-size:11px;" href="{{ route('v2.leads.show', $rLead) }}" title="{{ __('عرض التفاصيل') }}">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="emp-empty-state">
                    <i class="bi bi-person-x"></i>
                    <strong>{{ __('لا توجد عملاء مسجلة') }}</strong>
                    <p>{{ __('لم يتم العثور على أي عملاء مرتبطين بالموظف ضمن الفلاتر الزمنية الحالية.') }}</p>
                </div>
            @endif
        </article>

        <!-- Part B: Monthly Activity Trend Chart -->
        <article class="emp-panel">
            <header class="emp-panel-head">
                <div>
                    <h3>
                        <i class="bi bi-graph-up"></i>
                        <span>{{ __('مسار النشاط والأداء الزمني') }}</span>
                    </h3>
                    <p>{{ __('حركة العملاء الجدد والمتابعات والتحويلات المكتملة عبر الأشهر') }}</p>
                </div>
            </header>

            <div class="chart-box-wrap">
                <canvas id="empTimelineChart" role="img" aria-label="{{ __('مسار النشاط والأداء الزمني') }}"></canvas>
            </div>
        </article>
    </section>

    <!-- 4. BOTTOM SECTION (ROW 2): Customer Stage Distribution Funnel + Breakdown Table -->
    <section class="emp-split-grid">
        <!-- Part A: Lead Sources (Default) & Communication Channels Distribution -->
        <article class="emp-panel">
            <header class="emp-panel-head">
                <div>
                    <h3 id="panelDistributionTitle">
                        <i class="bi bi-diagram-2-fill"></i>
                        <span>{{ __('مصادر استقطاب العملاء') }}</span>
                    </h3>
                    <p id="panelDistributionDesc">{{ __('توزيع العملاء حسب مصدر الاستقطاب الأول') }}</p>
                </div>
                <div class="emp-channel-toggle">
                    <button type="button" class="btn-channel-tab active" id="tabSourcesBtn">
                        <i class="bi bi-diagram-2"></i>
                        <span>{{ __('مصادر العملاء') }}</span>
                    </button>
                    <button type="button" class="btn-channel-tab" id="tabChannelsBtn">
                        <i class="bi bi-telephone"></i>
                        <span>{{ __('قنوات التواصل') }}</span>
                    </button>
                </div>
            </header>

            <!-- Sources View (Default) -->
            <div id="viewSourcesContainer">
                <div class="chart-box-wrap" style="height:210px;">
                    <canvas id="empSourcesChart" role="img" aria-label="{{ __('مصادر استقطاب العملاء') }}"></canvas>
                </div>

                <div class="stage-dist-list">
                    @forelse (($sourcesDistribution ?? []) as $src)
                        <div class="stage-dist-item">
                            <div class="stage-dist-item-top">
                                <span class="stage-dist-name">
                                    <span class="stage-dist-dot" style="background: {{ $src['color'] }};"></span>
                                    <span>{{ $src['name'] }}</span>
                                </span>
                                <span class="stage-dist-val">
                                    {{ number_format($src['count']) }} {{ __('عميل') }} ({{ number_format($src['percentage'], 1) }}%)
                                </span>
                            </div>
                            <div class="stage-bar-track">
                                <div class="stage-bar-val" style="width: {{ min(100, max(2, $src['percentage'])) }}%; background: {{ $src['color'] }};"></div>
                            </div>
                        </div>
                    @empty
                        <div class="emp-empty-state" style="min-height:140px;padding:12px;">
                            <i class="bi bi-funnel"></i>
                            <p>{{ __('لا توجد مصادر مسجلة للعملاء الحاليين.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Channels View -->
            <div id="viewChannelsContainer" style="display:none;">
                <div class="chart-box-wrap" style="height:210px;">
                    <canvas id="empChannelsChart" role="img" aria-label="{{ __('قنوات التواصل والمتابعات') }}"></canvas>
                </div>

                <div class="stage-dist-list">
                    @foreach (($channelsDistribution ?? []) as $ch)
                        <div class="stage-dist-item">
                            <div class="stage-dist-item-top">
                                <span class="stage-dist-name">
                                    <span class="stage-dist-dot" style="background: {{ $ch['color'] }};"></span>
                                    <i class="bi {{ $ch['icon'] }}" style="color: {{ $ch['color'] }}; font-size:12px;"></i>
                                    <span>{{ $ch['name'] }}</span>
                                </span>
                                <span class="stage-dist-val">
                                    {{ number_format($ch['count']) }} {{ __('متابعة') }} ({{ number_format($ch['percentage'], 1) }}%)
                                </span>
                            </div>
                            <div class="stage-bar-track">
                                <div class="stage-bar-val" style="width: {{ min(100, max(2, $ch['percentage'])) }}%; background: {{ $ch['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <!-- Part B: Breakdown Table (Campaigns or Employees) -->
        <article class="emp-panel">
            <header class="emp-panel-head">
                <div>
                    <h3>
                        <i class="bi bi-table"></i>
                        <span>{{ $isEmployeeSelected ? __('أداء الحملات المرتبطة بالموظف') : __('مقارنة أداء موظفي المبيعات') }}</span>
                    </h3>
                    <p>{{ $isEmployeeSelected ? __('معدل استقطاب وتحويل العملاء لكل حملة عمل عليها الموظف') : __('مقارنة تفصيلية بين الموظفين في عدد العملاء ونسب التحويل') }}</p>
                </div>
            </header>

            @if (!empty($breakdownRows))
                <div class="emp-table-wrap">
                    <table class="emp-table">
                        <thead>
                            <tr>
                                <th>{{ $isEmployeeSelected ? __('اسم الحملة') : __('اسم الموظف') }}</th>
                                <th>{{ __('العملاء') }}</th>
                                <th>{{ __('المحولين') }}</th>
                                <th>{{ __('معدل التحويل') }}</th>
                                <th>{{ __('الحالة') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($breakdownRows as $row)
                                <tr>
                                    <td>
                                        <div class="lead-client-cell">
                                            <strong>{{ $row['title'] }}</strong>
                                            <small>{{ $row['subtitle'] }}</small>
                                        </div>
                                    </td>
                                    <td style="font-weight:800; font-variant-numeric: tabular-nums;">
                                        {{ number_format($row['leads_count']) }}
                                    </td>
                                    <td style="font-weight:800; color:#059669; font-variant-numeric: tabular-nums;">
                                        {{ number_format($row['converted_count']) }}
                                    </td>
                                    <td>
                                        <span style="font-weight:800; color: {{ $row['rate'] >= 20 ? '#059669' : ($row['rate'] > 0 ? '#d97706' : '#64748b') }};">
                                            {{ number_format($row['rate'], 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="camp-tag-sm" style="{{ $row['badge_class'] === 'active' ? 'background:#ecfdf5;color:#059669;' : ($row['badge_class'] === 'ended' ? 'background:#f1f5f9;color:#64748b;' : 'background:#fef3c7;color:#b45309;') }}">
                                            {{ $row['badge'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="emp-empty-state">
                    <i class="bi bi-inbox"></i>
                    <strong>{{ __('لا توجد سجلات مطابقة') }}</strong>
                    <p>{{ __('لم يتم العثور على بيانات مقارنة أو حملات مرتبطة ضمن الفلاتر المحددة.') }}</p>
                </div>
            @endif
        </article>
    </section>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
(() => {
    // 1. Dynamic Custom Date Synchronizer
    const period = document.getElementById('filterPeriod');
    const from = document.getElementById('filterFrom');
    const to = document.getElementById('filterTo');
    const fieldFrom = document.getElementById('fieldFilterFrom');
    const fieldTo = document.getElementById('fieldFilterTo');

    const syncCustomDates = () => {
        const custom = period?.value === 'custom';
        if (fieldFrom) fieldFrom.style.display = custom ? 'flex' : 'none';
        if (fieldTo) fieldTo.style.display = custom ? 'flex' : 'none';
        if (from) from.disabled = !custom;
        if (to) to.disabled = !custom;
    };

    period?.addEventListener('change', () => {
        syncCustomDates();
        if (period.value === 'custom') from?.focus();
    });
    syncCustomDates();

    // 2. Seamless Dynamic Target Stage Switcher (No full page reload)
    const targetStageSelect = document.getElementById('targetStageSelect');
    const stagePills = document.querySelectorAll('.pipeline-flow-pill');
    const filterTargetInput = document.getElementById('filterTargetStageId');

    const updateTargetStageAsync = async (stageId) => {
        if (!stageId) return;

        const card2 = document.querySelector('.emp-kpi-card.converted');
        const card3 = document.querySelector('.emp-kpi-card.rate');
        if (card2) card2.style.opacity = '0.5';
        if (card3) card3.style.opacity = '0.5';

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', '1');
            url.searchParams.set('target_stage_id', String(stageId));

            const res = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!res.ok) return;

            const data = await res.json();
            if (data.success) {
                const card2Val = card2?.querySelector('.emp-kpi-value');
                const card2Sub = card2?.querySelector('.emp-kpi-sub');
                const card2Badge = card2?.querySelector('.emp-kpi-badge');

                if (card2Val) {
                    card2Val.textContent = data.target_stage_leads_formatted;
                    card2Val.style.color = data.target_stage_color;
                }
                if (card2Sub) card2Sub.textContent = data.subtitle_leads;
                if (card2Badge) card2Badge.textContent = data.target_stage_name;
                if (card2) card2.style.setProperty('--card-accent', data.target_stage_color);

                const card3Val = card3?.querySelector('.emp-kpi-value');
                const card3Sub = card3?.querySelector('.emp-kpi-sub');
                if (card3Val) card3Val.textContent = data.target_stage_rate_formatted;
                if (card3Sub) card3Sub.textContent = data.subtitle_rate;

                stagePills.forEach(pill => {
                    if (pill.dataset.stageId === String(stageId)) {
                        pill.classList.add('is-selected');
                    } else {
                        pill.classList.remove('is-selected');
                    }
                });

                if (targetStageSelect) targetStageSelect.value = String(stageId);
                if (filterTargetInput) filterTargetInput.value = String(stageId);

                url.searchParams.delete('ajax');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (err) {
            console.error('Failed to update stage metrics', err);
        } finally {
            if (card2) card2.style.opacity = '1';
            if (card3) card3.style.opacity = '1';
        }
    };

    targetStageSelect?.addEventListener('change', (e) => {
        updateTargetStageAsync(e.target.value);
    });

    stagePills.forEach(pill => {
        pill.addEventListener('click', () => {
            const stageId = pill.dataset.stageId;
            if (stageId) updateTargetStageAsync(stageId);
        });
    });

    // 3. Chart.js Visualizations
    const ensureChart = async () => {
        if (typeof window.Chart === 'function') return true;
        try {
            const res = await fetch('{{ asset('js/chart.umd.min.js') }}');
            if (res.ok) {
                const code = await res.text();
                (0, eval)(code);
                return typeof window.Chart === 'function';
            }
        } catch (e) {}
        return false;
    };

    const initCharts = async () => {
        if (typeof window.Chart !== 'function') {
            await ensureChart();
        }
        if (typeof window.Chart !== 'function') {
            return;
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const dark = document.documentElement.classList.contains('dark-mode');
        const textColor = dark ? '#cbd5e1' : '#475569';
        const gridColor = dark ? 'rgba(255,255,255,0.06)' : 'rgba(226,232,240,0.85)';
        const isRTL = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar' || {{ app()->getLocale() === 'ar' ? 'true' : 'false' }};

        Chart.defaults.color = textColor;
        Chart.defaults.font.family = 'Tajawal, Cairo, Tahoma, Arial, sans-serif';
        Chart.defaults.font.size = 12;
        // A. Performance Monthly Timeline Chart
        const timelineCanvas = document.getElementById('empTimelineChart');
        if (timelineCanvas) {
            const timeline = @json($monthlyTimeline ?? []);
            new Chart(timelineCanvas, {
                type: 'bar',
                data: {
                    labels: timeline.labels || [],
                    datasets: [
                        {
                            label: @json(__('العملاء الجدد')),
                            data: timeline.new_leads || [],
                            backgroundColor: '#3b82f6',
                            borderRadius: 6,
                            maxBarThickness: 28
                        },
                        {
                            label: @json(__('المتابعات المنفذة')),
                            data: timeline.followups || [],
                            backgroundColor: '#f59e0b',
                            borderRadius: 6,
                            maxBarThickness: 28
                        },
                        {
                            label: @json(__('التحويلات المكتملة')),
                            data: timeline.converted || [],
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                            maxBarThickness: 28
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: reducedMotion ? false : { duration: 500, easing: 'easeOutQuart' },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: isRTL,
                            labels: {
                                usePointStyle: true,
                                padding: 14,
                                font: { weight: 'bold', size: 11 }
                            }
                        },
                        tooltip: {
                            rtl: isRTL,
                            padding: 10,
                            boxPadding: 6,
                            usePointStyle: true,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxRotation: 0, autoSkip: true, color: textColor }
                        },
                        y: {
                            beginAtZero: true,
                            position: isRTL ? 'right' : 'left',
                            grid: { color: gridColor },
                            ticks: { precision: 0, color: textColor }
                        }
                    }
                }
            });
        }

        // B. Lead Sources Horizontal Bar Chart (Default)
        const sourcesCanvas = document.getElementById('empSourcesChart');
        let sourcesChart = null;
        if (sourcesCanvas) {
            const sources = @json($sourcesDistribution ?? []);
            sourcesChart = new Chart(sourcesCanvas, {
                type: 'bar',
                data: {
                    labels: sources.map(s => s.name),
                    datasets: [{
                        data: sources.map(s => s.count),
                        backgroundColor: sources.map(s => s.color),
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 22
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: reducedMotion ? false : { duration: 600, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            rtl: isRTL,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label(context) {
                                    const src = sources[context.dataIndex];
                                    return ` ${src.name}: ${src.count} ${@json(__('عميل'))} (${src.percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            position: 'bottom',
                            grid: { color: gridColor },
                            ticks: { precision: 0, color: textColor }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                color: textColor,
                                font: { weight: 'bold', size: 11 }
                            }
                        }
                    }
                }
            });
        }

        // C. Communication Channels Doughnut Chart (Lazily on Tab Click)
        const channelsCanvas = document.getElementById('empChannelsChart');
        let channelsChart = null;
        const initChannelsChart = () => {
            if (!channelsCanvas || channelsChart) return;
            const channels = @json($channelsDistribution ?? []);
            channelsChart = new Chart(channelsCanvas, {
                type: 'doughnut',
                data: {
                    labels: channels.map(c => c.name),
                    datasets: [{
                        data: channels.map(c => c.count),
                        backgroundColor: channels.map(c => c.color),
                        borderWidth: dark ? 2 : 1,
                        borderColor: dark ? '#111827' : '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: reducedMotion ? false : { duration: 600, easing: 'easeOutQuart' },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: isRTL,
                            labels: {
                                usePointStyle: true,
                                padding: 12,
                                font: { weight: 'bold', size: 11 },
                                color: textColor,
                            }
                        },
                        tooltip: {
                            rtl: isRTL,
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label(context) {
                                    const ch = channels[context.dataIndex];
                                    return ` ${ch.name}: ${ch.count} ${@json(__('متابعة'))} (${ch.percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                }
            });
        };

        // D. Tab Switcher for Sources (Default) vs Channels
        const tabSourcesBtn = document.getElementById('tabSourcesBtn');
        const tabChannelsBtn = document.getElementById('tabChannelsBtn');
        const viewSources = document.getElementById('viewSourcesContainer');
        const viewChannels = document.getElementById('viewChannelsContainer');
        const panelTitle = document.getElementById('panelDistributionTitle')?.querySelector('span');
        const panelDesc = document.getElementById('panelDistributionDesc');

        tabSourcesBtn?.addEventListener('click', () => {
            tabSourcesBtn.classList.add('active');
            tabChannelsBtn?.classList.remove('active');
            if (viewSources) viewSources.style.display = 'block';
            if (viewChannels) viewChannels.style.display = 'none';
            if (panelTitle) panelTitle.textContent = @json(__('مصادر استقطاب العملاء'));
            if (panelDesc) panelDesc.textContent = @json(__('توزيع العملاء حسب مصدر الاستقطاب الأول'));
        });

        tabChannelsBtn?.addEventListener('click', () => {
            tabChannelsBtn.classList.add('active');
            tabSourcesBtn?.classList.remove('active');
            if (viewChannels) viewChannels.style.display = 'block';
            if (viewSources) viewSources.style.display = 'none';
            if (panelTitle) panelTitle.textContent = @json(__('قنوات التواصل والمتابعات'));
            if (panelDesc) panelDesc.textContent = @json(__('توزيع المكالمات والرسائل والمقابلات المسجلة مع العملاء'));
            initChannelsChart();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        initCharts();
    }
})();
</script>
@endpush
