@extends('leads.transfer-layout')

@section('title', __('crm.campaign_reports'))
@section('page-title', __('crm.campaign_reports'))
@section('page-description', __('crm.campaign_reports_subtitle'))

@section('back-url', route('v2.campaigns.index'))
@section('back-title', __('crm.view_campaigns'))
@section('top-actions')
 <a class="btn soft" href="{{ route('v2.campaigns.index') }}">
  <i class="bi bi-megaphone" aria-hidden="true"></i>
  <span>{{ __('crm.view_campaigns') }}</span>
 </a>
@endsection

@push('styles')
<style>
 html, body {
   overflow-x: hidden !important;
   position: relative !important;
   max-width: 100vw !important;
 }
 .crm-app {
   overflow-x: hidden !important;
   max-width: 100vw !important;
 }
 /* ==========================================================================
    CAMPAIGN REPORTS MANAGEMENT DASHBOARD (SCOPED)
    ========================================================================== */
 .campaign-report-page {
   display: flex;
   flex-direction: column;
   gap: 16px;
   width: 100%;
   max-width: 100%;
 }
 .campaign-report-page ::selection {
   background: rgba(220, 38, 55, 0.18);
   color: var(--dark);
 }

 /* --------------------------------------------------------------------------
    1. Active Filter Summary Strip
    -------------------------------------------------------------------------- */
 .campaign-report-page .cr-summary-strip {
   display: flex;
   align-items: center;
   justify-content: space-between;
   gap: 12px;
   padding: 10px 16px;
   border-radius: 12px;
   background: var(--card);
   border: 1px solid var(--line);
   box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
   flex-wrap: wrap;
 }
 .campaign-report-page .cr-summary-info {
   display: inline-flex;
   align-items: center;
   gap: 8px;
   font-size: 12px;
   font-weight: 700;
   color: var(--muted);
 }
 .campaign-report-page .cr-summary-info i {
   color: var(--red, #dc2637);
   font-size: 14px;
 }
 .campaign-report-page .cr-filter-chips {
   display: flex;
   align-items: center;
   flex-wrap: wrap;
   gap: 8px;
 }
 .campaign-report-page .cr-chip {
   display: inline-flex;
   align-items: center;
   gap: 6px;
   padding: 4px 10px;
   border-radius: 8px;
   background: #f1f5f9;
   border: 1px solid #e2e8f0;
   font-size: 11.5px;
   color: #334155;
   white-space: nowrap;
 }
 .campaign-report-page .cr-chip i {
   font-size: 11px;
   color: var(--muted);
 }
 .campaign-report-page .cr-chip-label {
   color: var(--muted);
   font-weight: 600;
 }
 .campaign-report-page .cr-chip strong {
   color: #0f172a;
   font-weight: 800;
 }
 .campaign-report-page .cr-stage-dot {
   width: 8px;
   height: 8px;
   border-radius: 50%;
   display: inline-block;
   flex-shrink: 0;
 }

 /* --------------------------------------------------------------------------
    2. Compact Responsive Filter Card
    -------------------------------------------------------------------------- */
 .campaign-report-page .cr-filter-card {
   background: var(--card);
   border: 1px solid var(--line);
   border-radius: 14px;
   padding: 14px 18px;
   box-shadow: var(--shadow);
 }
 .campaign-report-page .cr-filter-head {
   display: flex;
   align-items: center;
   justify-content: space-between;
   gap: 12px;
   margin-bottom: 12px;
   padding-bottom: 10px;
   border-bottom: 1px solid var(--line);
 }
 .campaign-report-page .cr-filter-head h4 {
   margin: 0;
   font-size: 13.5px;
   font-weight: 900;
   color: var(--dark);
   display: flex;
   align-items: center;
   gap: 7px;
 }
 .campaign-report-page .cr-filter-head h4 i {
   color: var(--red, #dc2637);
   font-size: 14px;
 }
 .campaign-report-page .cr-filter-head small {
   color: var(--muted);
   font-size: 11px;
 }
 .campaign-report-page .cr-filter-grid {
   display: flex;
   flex-direction: column;
   gap: 12px;
 }
 .campaign-report-page .cr-filter-row-1 {
   display: grid;
   grid-template-columns: repeat(3, minmax(0, 1fr));
   gap: 12px;
 }
 .campaign-report-page .cr-filter-row-2 {
   display: grid;
   grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
   gap: 12px;
   align-items: flex-end;
 }
 .campaign-report-page .cr-field {
   display: flex;
   flex-direction: column;
   gap: 4px;
   min-width: 0;
 }
 .campaign-report-page .cr-field label {
   font-size: 11px;
   font-weight: 800;
   color: var(--muted);
   display: flex;
   align-items: center;
   gap: 5px;
   margin: 0;
 }
 .campaign-report-page .cr-field label i {
   font-size: 11px;
   opacity: 0.8;
 }
 .campaign-report-page .cr-field select,
 .campaign-report-page .cr-field input {
   width: 100%;
   height: 38px;
   min-height: 38px;
   padding: 0 10px;
   border: 1px solid #cbd5e1;
   border-radius: 8px;
   background: #fff;
   color: #0f172a;
   font-size: 12.5px;
   font-weight: 700;
   outline: none;
   transition: border-color .15s ease, box-shadow .15s ease;
 }
 .campaign-report-page .cr-field select:focus,
 .campaign-report-page .cr-field input:focus {
   border-color: var(--red, #dc2637);
   box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.15);
 }
 .campaign-report-page .cr-field input:disabled {
   cursor: not-allowed;
   opacity: 0.55;
   background: #f8fafc;
   border-color: #e2e8f0;
 }
 .campaign-report-page .cr-filter-actions {
   display: flex;
   align-items: center;
   gap: 8px;
 }
 .campaign-report-page .cr-filter-actions .btn {
   height: 38px;
   min-height: 38px;
   padding: 0 14px;
   font-size: 12px;
   border-radius: 8px;
   white-space: nowrap;
 }
 .campaign-report-page .cr-filter-actions .btn.reset-btn {
   padding: 0 10px;
   color: var(--muted);
 }
 .campaign-report-page .cr-filter-actions .btn.reset-btn:hover {
   color: #0f172a;
 }
 .campaign-report-page .cr-errors {
   margin: 0 0 12px;
   padding: 10px 14px;
   border: 1px solid #fecaca;
   border-radius: 8px;
   background: #fef2f2;
   color: #991b1b;
   font-size: 12px;
   font-weight: 800;
 }

 /* --------------------------------------------------------------------------
    3. Campaign Selector Section & Grid
    -------------------------------------------------------------------------- */
 .campaign-report-page .cr-section-head {
   display: flex;
   align-items: flex-end;
   justify-content: space-between;
   gap: 12px;
   margin-bottom: 10px;
 }
 .campaign-report-page .cr-section-head h3 {
   margin: 0;
   color: var(--dark);
   font-size: 14.5px;
   font-weight: 900;
   line-height: 1.35;
   display: flex;
   align-items: center;
   gap: 7px;
 }
 .campaign-report-page .cr-section-head h3 i {
   color: var(--red, #dc2637);
   font-size: 15px;
 }
 .campaign-report-page .cr-section-head p {
   margin: 3px 0 0;
   color: var(--muted);
   font-size: 11px;
   line-height: 1.5;
 }
 .campaign-report-page .cr-count-pill {
   display: inline-flex;
   align-items: center;
   padding: 3px 9px;
   border-radius: 999px;
   background: #e2e8f0;
   color: #475569;
   font-size: 11px;
   font-weight: 800;
   white-space: nowrap;
 }
 .campaign-report-page .cr-campaign-grid {
   display: grid;
   grid-template-columns: repeat(4, minmax(0, 1fr));
   gap: 12px;
 }

 /* Campaign Card */
 .campaign-report-page .cr-card {
   position: relative;
   min-width: 0;
   display: flex;
   flex-direction: column;
   justify-content: space-between;
   gap: 10px;
   min-height: 140px;
   padding: 13px 15px;
   border: 1px solid var(--line);
   border-radius: 13px;
   background: var(--card);
   color: inherit;
   text-decoration: none;
   box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
   transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
 }
 .campaign-report-page .cr-card:hover {
   transform: translateY(-2px);
   border-color: #cbd5e1;
   box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
 }
 .campaign-report-page .cr-card:focus-visible {
   outline: 2px solid var(--red, #dc2637);
   outline-offset: 2px;
 }

 /* Active State */
 .campaign-report-page .cr-card.is-active {
   border: 2px solid var(--red, #dc2637);
   background: linear-gradient(to bottom, var(--card), rgba(220, 38, 55, 0.025));
   box-shadow: 0 0 0 3px rgba(220, 38, 55, 0.12), 0 8px 20px rgba(220, 38, 55, 0.08);
 }

 /* All Campaigns Card */
 .campaign-report-page .cr-card.is-all {
   background: linear-gradient(135deg, #182033 0%, #202d48 100%);
   color: #fff;
   border-color: #334155;
 }
 .campaign-report-page .cr-card.is-all .cr-card-title {
   color: #fff;
 }
 .campaign-report-page .cr-card.is-all .cr-card-desc {
   color: #94a3b8;
 }
 .campaign-report-page .cr-card.is-all .cr-card-link {
   color: #fca5a5;
   border-top-color: rgba(255, 255, 255, 0.12);
 }
 .campaign-report-page .cr-card.is-all.is-active {
   border: 2px solid #ef4444;
   box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25), 0 10px 24px rgba(0, 0, 0, 0.35);
 }

 .campaign-report-page .cr-card-top {
   display: flex;
   align-items: center;
   justify-content: space-between;
   gap: 8px;
 }
 .campaign-report-page .cr-card-icon {
   width: 32px;
   height: 32px;
   display: grid;
   place-items: center;
   flex: 0 0 32px;
   overflow: hidden;
   border-radius: 9px;
   background: #fee2e2;
   color: var(--red, #dc2637);
   font-size: 14px;
 }
 .campaign-report-page .cr-card.is-all .cr-card-icon {
   background: rgba(255, 255, 255, 0.12);
   color: #fff;
 }
 .campaign-report-page .cr-card-icon img {
   width: 100%;
   height: 100%;
   display: block;
   object-fit: cover;
 }
 .campaign-report-page .cr-card-badges {
   display: flex;
   align-items: center;
   gap: 5px;
 }
 .campaign-report-page .cr-status-badge {
   display: inline-flex;
   align-items: center;
   gap: 4px;
   padding: 2px 7px;
   border-radius: 999px;
   background: #eef2f7;
   color: #475569;
   font-size: 9.5px;
   font-weight: 800;
   white-space: nowrap;
 }
 .campaign-report-page .cr-status-badge.is-active {
   background: #ecfdf5;
   color: #059669;
 }
 .campaign-report-page .cr-status-badge.is-active::before {
   content: '';
   width: 5px;
   height: 5px;
   border-radius: 50%;
   background: #10b981;
 }
 .campaign-report-page .cr-status-badge.is-ended {
   background: #f1f5f9;
   color: #64748b;
 }
 .campaign-report-page .cr-status-badge.is-upcoming {
   background: #fef3c7;
   color: #d97706;
 }
 .campaign-report-page .cr-active-indicator {
   width: 18px;
   height: 18px;
   display: grid;
   place-items: center;
   border-radius: 50%;
   background: var(--red, #dc2637);
   color: #fff;
   font-size: 11px;
   font-weight: 900;
 }
 .campaign-report-page .cr-card-mid {
   display: flex;
   flex-direction: column;
   gap: 4px;
   min-width: 0;
 }
 .campaign-report-page .cr-card-title {
   overflow: hidden;
   margin: 0;
   color: #0f172a;
   font-size: 13.5px;
   font-weight: 800;
   line-height: 1.35;
   text-overflow: ellipsis;
   white-space: nowrap;
 }
 .campaign-report-page .cr-card-dates {
   color: var(--muted);
   font-size: 10.5px;
   display: flex;
   align-items: center;
   gap: 4px;
   white-space: nowrap;
   overflow: hidden;
   text-overflow: ellipsis;
 }
 .campaign-report-page .cr-card-dates i {
   font-size: 10px;
 }
 .campaign-report-page .cr-card-desc {
   margin: 0;
   color: var(--muted);
   font-size: 10.5px;
   line-height: 1.4;
   overflow: hidden;
   text-overflow: ellipsis;
   white-space: nowrap;
 }
 .campaign-report-page .cr-cost-badge {
   display: inline-block;
   align-self: flex-start;
   margin-top: 2px;
   padding: 1px 7px;
   border-radius: 5px;
   background: #f8fafc;
   color: #0f172a;
   font-size: 10.5px;
   font-weight: 800;
   font-variant-numeric: tabular-nums;
   border: 1px solid #e2e8f0;
 }
 .campaign-report-page .cr-card.is-all .cr-cost-badge {
   background: rgba(255, 255, 255, 0.1);
   color: #f1f5f9;
   border-color: rgba(255, 255, 255, 0.16);
 }
 .campaign-report-page .cr-card-link {
   display: flex;
   align-items: center;
   justify-content: space-between;
   gap: 6px;
   padding-top: 8px;
   border-top: 1px solid var(--line);
   color: var(--red, #dc2637);
   font-size: 11px;
   font-weight: 800;
 }
 .campaign-report-page .cr-card-link i {
   font-size: 11px;
   transition: transform .15s ease;
 }
 .campaign-report-page .cr-card:hover .cr-card-link i {
   transform: translateX(app()->getLocale() === 'ar' ? -3px : 3px);
 }

 /* --------------------------------------------------------------------------
    4. KPI Summary Cards Grid
    -------------------------------------------------------------------------- */
 .campaign-report-page .cr-kpi-grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
   gap: 12px;
 }
 .campaign-report-page .cr-kpi-card {
   position: relative;
   min-width: 0;
   display: flex;
   flex-direction: column;
   justify-content: space-between;
   padding: 14px 16px;
   border-radius: 13px;
   background: var(--card);
   border: 1px solid var(--line);
   box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
   transition: transform .18s ease, box-shadow .18s ease;
 }
 .campaign-report-page .cr-kpi-card:hover {
   transform: translateY(-2px);
   box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
 }
 .campaign-report-page .cr-kpi-top {
   display: flex;
   align-items: center;
   justify-content: space-between;
   gap: 8px;
 }
 .campaign-report-page .cr-kpi-label {
   color: #64748b;
   font-size: 11.5px;
   font-weight: 800;
   line-height: 1.3;
 }
 .campaign-report-page .cr-kpi-icon {
   width: 30px;
   height: 30px;
   display: grid;
   place-items: center;
   border-radius: 8px;
   background: #f1f5f9;
   color: #475569;
   font-size: 13.5px;
   flex-shrink: 0;
 }
 .campaign-report-page .cr-kpi-val {
   display: block;
   margin: 8px 0 4px;
   color: #0f172a;
   font-size: 24px;
   font-weight: 900;
   font-variant-numeric: tabular-nums;
   letter-spacing: -0.02em;
   line-height: 1.1;
 }
 .campaign-report-page .cr-kpi-sub {
   display: block;
   color: #64748b;
   font-size: 10.5px;
   font-weight: 700;
   line-height: 1.4;
 }
 /* Conversion Card Highlight */
 .campaign-report-page .cr-kpi-card.is-conversion {
   background: linear-gradient(135deg, rgba(16, 185, 129, 0.04) 0%, rgba(16, 185, 129, 0.1) 100%);
   border-color: rgba(16, 185, 129, 0.28);
 }
 .campaign-report-page .cr-kpi-card.is-conversion .cr-kpi-icon {
   background: rgba(16, 185, 129, 0.15);
   color: #059669;
 }
 .campaign-report-page .cr-kpi-card.is-conversion .cr-kpi-val {
   color: #047857;
 }
 /* Cost Card Highlight */
 .campaign-report-page .cr-kpi-card.is-cost {
   background: linear-gradient(135deg, rgba(245, 158, 11, 0.03) 0%, rgba(245, 158, 11, 0.09) 100%);
   border-color: rgba(245, 158, 11, 0.28);
 }
 .campaign-report-page .cr-kpi-card.is-cost .cr-kpi-icon {
   background: rgba(245, 158, 11, 0.15);
   color: #b45309;
 }
 .campaign-report-page .cr-kpi-card.is-cost .cr-kpi-val {
   color: #b45309;
 }
 /* Inline Quick Stage Switcher */
 .campaign-report-page .cr-stage-switcher {
   display: flex;
   align-items: center;
   gap: 6px;
   margin-top: 6px;
   padding-top: 6px;
   border-top: 1px solid rgba(16, 185, 129, 0.2);
 }
 .campaign-report-page .cr-stage-switcher select {
   width: 100%;
   height: 28px;
   min-height: 28px;
   padding: 0 6px;
   border: 1px solid rgba(16, 185, 129, 0.35);
   border-radius: 6px;
   background: #fff;
   color: #047857;
   font-size: 10.5px;
   font-weight: 800;
   outline: none;
   cursor: pointer;
 }

 /* --------------------------------------------------------------------------
    5. Analytics Panels & Charts
    -------------------------------------------------------------------------- */
 .campaign-report-page .cr-analytics-grid {
   display: grid;
   grid-template-columns: minmax(0, 1.4fr) minmax(320px, 1fr);
   gap: 16px;
 }
 .campaign-report-page .cr-analytics-grid.is-single {
   grid-template-columns: minmax(0, 1fr);
 }
 .campaign-report-page .cr-panel {
   min-width: 0;
   padding: 16px 20px;
   border-radius: 14px;
   background: var(--card);
   border: 1px solid var(--line);
   box-shadow: var(--shadow);
 }
 .campaign-report-page .cr-panel-head {
   display: flex;
   align-items: flex-start;
   justify-content: space-between;
   gap: 12px;
   margin-bottom: 14px;
   padding-bottom: 10px;
   border-bottom: 1px solid var(--line);
 }
 .campaign-report-page .cr-panel-head h4 {
   margin: 0;
   color: var(--dark);
   font-size: 14px;
   font-weight: 900;
   line-height: 1.35;
 }
 .campaign-report-page .cr-panel-head p {
   margin: 2px 0 0;
   color: var(--muted);
   font-size: 11px;
   line-height: 1.5;
 }
 .campaign-report-page .cr-chart-box {
   position: relative;
   height: 270px;
   width: 100%;
 }
 .campaign-report-page .cr-chart-empty {
   height: 100%;
   display: flex;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   padding: 24px;
   color: var(--muted);
   text-align: center;
   font-size: 12px;
   font-weight: 700;
   gap: 6px;
 }
 .campaign-report-page .cr-chart-empty i {
   font-size: 28px;
   color: #cbd5e1;
 }
 .campaign-report-page .cr-status-list {
   display: grid;
   gap: 6px;
   margin-top: 14px;
   max-height: 220px;
   overflow-y: auto;
   padding-inline-end: 4px;
 }
 .campaign-report-page .cr-status-row {
   display: grid;
   grid-template-columns: auto minmax(0, 1fr) auto;
   align-items: center;
   gap: 8px;
   padding: 7px 10px;
   border-radius: 8px;
   background: #f8fafc;
   border: 1px solid #f1f5f9;
   transition: background .15s ease;
 }
 .campaign-report-page .cr-status-row:hover {
   background: #f1f5f9;
 }
 .campaign-report-page .cr-status-row.is-target-stage {
   background: #ecfdf5;
   border-color: #a7f3d0;
 }
 .campaign-report-page .cr-status-dot {
   width: 9px;
   height: 9px;
   border-radius: 50%;
   background: var(--status-color);
   flex: 0 0 9px;
 }
 .campaign-report-page .cr-status-name {
   overflow: hidden;
   color: #334155;
   font-size: 11.5px;
   font-weight: 800;
   text-overflow: ellipsis;
   white-space: nowrap;
   display: flex;
   align-items: center;
   gap: 6px;
 }
 .campaign-report-page .cr-status-tag {
   display: inline-block;
   padding: 1px 5px;
   border-radius: 4px;
   background: #059669;
   color: #fff;
   font-size: 8.5px;
   font-weight: 900;
 }
 .campaign-report-page .cr-status-value {
   color: #0f172a;
   font-size: 11.5px;
   font-weight: 900;
   font-variant-numeric: tabular-nums;
   white-space: nowrap;
 }
 .campaign-report-page .cr-note {
   margin: 10px 0 0;
   padding-top: 10px;
   border-top: 1px solid var(--line);
   color: #64748b;
   font-size: 10.5px;
   line-height: 1.5;
 }
 .campaign-report-page .sr-only {
   position: absolute;
   width: 1px;
   height: 1px;
   overflow: hidden;
   margin: -1px;
   padding: 0;
   border: 0;
   clip: rect(0, 0, 0, 0);
 }

 /* --------------------------------------------------------------------------
    6. Responsive Breakpoints
    -------------------------------------------------------------------------- */
 @media (max-width: 1200px) {
   .campaign-report-page .cr-campaign-grid {
     grid-template-columns: repeat(3, minmax(0, 1fr));
   }
 }
 @media (max-width: 1024px) {
   .campaign-report-page .cr-filter-row-1 {
     grid-template-columns: repeat(2, minmax(0, 1fr));
   }
   .campaign-report-page .cr-filter-row-1 .cr-field:last-child {
     grid-column: 1 / -1;
   }
   .campaign-report-page .cr-filter-row-2 {
     grid-template-columns: 1fr 1fr;
   }
   .campaign-report-page .cr-filter-actions {
     grid-column: 1 / -1;
     justify-content: flex-end;
   }
   .campaign-report-page .cr-campaign-grid {
     grid-template-columns: repeat(3, minmax(0, 1fr));
   }
   .campaign-report-page .cr-analytics-grid {
     grid-template-columns: 1fr;
   }
 }
 @media (max-width: 768px) {
   .campaign-report-page {
     gap: 14px;
   }
   .campaign-report-page .cr-summary-strip {
     flex-direction: column;
     align-items: stretch;
     gap: 8px;
   }
   .campaign-report-page .cr-filter-row-1 {
     grid-template-columns: 1fr;
   }
   .campaign-report-page .cr-filter-row-1 .cr-field:last-child {
     grid-column: auto;
   }
   .campaign-report-page .cr-filter-row-2 {
     grid-template-columns: 1fr 1fr;
   }
   .campaign-report-page .cr-filter-actions {
     grid-column: 1 / -1;
     width: 100%;
   }
   .campaign-report-page .cr-filter-actions .btn {
     flex: 1 1 auto;
     justify-content: center;
   }
   .campaign-report-page .cr-campaign-grid {
     grid-template-columns: repeat(2, minmax(0, 1fr));
     gap: 10px;
   }
   .campaign-report-page .cr-kpi-grid {
     grid-template-columns: repeat(2, minmax(0, 1fr));
   }
 }
 @media (max-width: 480px) {
   .campaign-report-page .cr-filter-row-2 {
     grid-template-columns: 1fr;
   }
   .campaign-report-page .cr-campaign-grid {
     grid-template-columns: 1fr;
   }
   .campaign-report-page .cr-kpi-grid {
     grid-template-columns: 1fr;
   }
   .campaign-report-page .cr-chart-box {
     height: 230px;
   }
 }

 /* --------------------------------------------------------------------------
    7. Dark Mode Overrides
    -------------------------------------------------------------------------- */
 html.dark-mode .campaign-report-page .cr-summary-strip {
   background: #1e293b;
   border-color: #334155;
 }
 html.dark-mode .campaign-report-page .cr-summary-info {
   color: #94a3b8;
 }
 html.dark-mode .campaign-report-page .cr-chip {
   background: #0f172a;
   border-color: #334155;
   color: #cbd5e1;
 }
 html.dark-mode .campaign-report-page .cr-chip strong {
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-filter-card,
 html.dark-mode .campaign-report-page .cr-kpi-card,
 html.dark-mode .campaign-report-page .cr-panel {
   background: #1e293b;
   border-color: #334155;
 }
 html.dark-mode .campaign-report-page .cr-filter-head h4,
 html.dark-mode .campaign-report-page .cr-section-head h3,
 html.dark-mode .campaign-report-page .cr-panel-head h4 {
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-field select,
 html.dark-mode .campaign-report-page .cr-field input {
   background: #0f172a;
   border-color: #334155;
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-field input:disabled {
   background: #1e293b;
   border-color: #334155;
 }
 html.dark-mode .campaign-report-page .cr-card {
   background: #1e293b;
   border-color: #334155;
 }
 html.dark-mode .campaign-report-page .cr-card.is-active {
   background: #1e293b;
   border-color: var(--red, #dc2637);
 }
 html.dark-mode .campaign-report-page .cr-card-title {
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-cost-badge {
   background: #0f172a;
   border-color: #334155;
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-status-badge {
   background: #334155;
   color: #cbd5e1;
 }
 html.dark-mode .campaign-report-page .cr-kpi-val {
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-kpi-icon {
   background: #334155;
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-kpi-card.is-conversion {
   background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.18) 100%);
   border-color: rgba(16, 185, 129, 0.35);
 }
 html.dark-mode .campaign-report-page .cr-kpi-card.is-conversion .cr-kpi-val {
   color: #34d399;
 }
 html.dark-mode .campaign-report-page .cr-stage-switcher select {
   background: #0f172a;
   border-color: rgba(16, 185, 129, 0.4);
   color: #34d399;
 }
 html.dark-mode .campaign-report-page .cr-kpi-card.is-cost {
   background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(245, 158, 11, 0.15) 100%);
   border-color: rgba(245, 158, 11, 0.35);
 }
 html.dark-mode .campaign-report-page .cr-kpi-card.is-cost .cr-kpi-val {
   color: #fbbf24;
 }
 html.dark-mode .campaign-report-page .cr-status-row {
   background: #0f172a;
   border-color: #1e293b;
 }
 html.dark-mode .campaign-report-page .cr-status-row.is-target-stage {
   background: rgba(16, 185, 129, 0.15);
   border-color: rgba(16, 185, 129, 0.35);
 }
 html.dark-mode .campaign-report-page .cr-status-name {
   color: #cbd5e1;
 }
 html.dark-mode .campaign-report-page .cr-status-value {
   color: #f8fafc;
 }
 html.dark-mode .campaign-report-page .cr-note {
   border-color: #334155;
   color: #94a3b8;
 }
 html.dark-mode .campaign-report-page .cr-count-pill {
   background: #334155;
   color: #cbd5e1;
 }
</style>
@endpush

@section('content')
@php
  $activePeriodLabel = match ($filters['period']) {
    'today' => __('crm.today'),
    'week' => __('crm.this_week'),
    'month' => __('crm.this_month'),
    'year' => __('crm.this_year'),
    'custom' => ($filters['from'] && $filters['to'] ? $filters['from'] . ' — ' . $filters['to'] : __('crm.custom_range')),
    default => __('crm.all_periods'),
  };

  $campaignCardQuery = [
    'period' => $filters['period'],
    'stage_id' => $filters['stage_id'],
    'employee_id' => $filters['employee_id'],
  ];
  if ($filters['period'] === 'custom') {
    $campaignCardQuery['from'] = $filters['from'];
    $campaignCardQuery['to'] = $filters['to'];
  }
@endphp

<div class="campaign-report-page" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

  <!-- 1. ACTIVE FILTER SUMMARY STRIP -->
  <section class="cr-summary-strip" aria-label="{{ __('crm.campaign_report_definition') }}">
    <div class="cr-summary-info">
      <i class="bi bi-funnel-fill" aria-hidden="true"></i>
      <span>{{ __('crm.campaign_report_definition') }}</span>
    </div>
    <div class="cr-filter-chips">
      <span class="cr-chip">
        <i class="bi bi-megaphone" aria-hidden="true"></i>
        <span class="cr-chip-label">{{ __('crm.campaign_filter') }}:</span>
        <strong>{{ $selectedCampaign?->name ?? __('crm.all_campaigns') }}</strong>
      </span>
      <span class="cr-chip">
        <i class="bi bi-person-badge" aria-hidden="true"></i>
        <span class="cr-chip-label">{{ __('crm.responsible_employee') }}:</span>
        <strong>{{ $selectedEmployee?->name ?? __('crm.all_employees') }}</strong>
      </span>
      <span class="cr-chip">
        <span class="cr-stage-dot" style="background:{{ $selectedStage?->color ?? '#10b981' }}"></span>
        <span class="cr-chip-label">{{ __('crm.conversion_target_stage') }}:</span>
        <strong>{{ $selectedStage?->localizedName() ?? $selectedStage?->name_ar ?? __('crm.all_stages') }}</strong>
      </span>
      <span class="cr-chip">
        <i class="bi bi-calendar3" aria-hidden="true"></i>
        <span class="cr-chip-label">{{ __('crm.report_period') }}:</span>
        <strong>{{ $activePeriodLabel }}</strong>
      </span>
    </div>
  </section>

  <!-- 2. COMPACT RESPONSIVE FILTER CARD -->
  <form class="cr-filter-card" id="campaignReportFilters" method="GET" action="{{ route('v2.campaigns.reports') }}">
    <header class="cr-filter-head">
      <h4>
        <i class="bi bi-sliders" aria-hidden="true"></i>
        <span>{{ __('crm.filter_campaign_report') }}</span>
      </h4>
      <small>{{ __('crm.filter_campaign_report_desc') }}</small>
    </header>

    @if (isset($errors) && $errors->any())
      <div class="cr-errors" role="alert">
        {{ $errors->first() }}
      </div>
    @endif

    @if ($filters['campaign_id'] !== null)
      <input type="hidden" name="campaign_id" value="{{ $filters['campaign_id'] }}">
    @endif

    <div class="cr-filter-grid">
      <!-- Row 1: Employee, Target Stage, Period -->
      <div class="cr-filter-row-1">
        <div class="cr-field">
          <label for="reportEmployee">
            <i class="bi bi-person"></i>
            <span>{{ __('crm.responsible_employee') }}</span>
          </label>
          <select id="reportEmployee" name="employee_id">
            <option value="">{{ __('crm.all_employees') }}</option>
            @foreach ($employees as $employee)
              <option value="{{ $employee->id }}" @selected($filters['employee_id'] === $employee->id)>
                {{ $employee->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="cr-field">
          <label for="reportStage">
            <i class="bi bi-bullseye"></i>
            <span>{{ __('crm.conversion_target_stage') }}</span>
          </label>
          <select id="reportStage" name="stage_id">
            @foreach ($stages as $stg)
              <option value="{{ $stg->id }}" @selected((int) $filters['stage_id'] === (int) $stg->id)>
                {{ $stg->localizedName() }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="cr-field">
          <label for="reportPeriod">
            <i class="bi bi-calendar-range"></i>
            <span>{{ __('crm.report_period') }}</span>
          </label>
          <select id="reportPeriod" name="period">
            <option value="all" @selected($filters['period'] === 'all')>{{ __('crm.all_periods') }}</option>
            <option value="today" @selected($filters['period'] === 'today')>{{ __('crm.today') }}</option>
            <option value="week" @selected($filters['period'] === 'week')>{{ __('crm.this_week') }}</option>
            <option value="month" @selected($filters['period'] === 'month')>{{ __('crm.this_month') }}</option>
            <option value="year" @selected($filters['period'] === 'year')>{{ __('crm.this_year') }}</option>
            <option value="custom" @selected($filters['period'] === 'custom')>{{ __('crm.custom_range') }}</option>
          </select>
        </div>
      </div>

      <!-- Row 2: From Date, To Date, Actions -->
      <div class="cr-filter-row-2">
        <div class="cr-field">
          <label for="reportFrom">
            <i class="bi bi-calendar-event"></i>
            <span>{{ __('crm.from_date') }}</span>
          </label>
          <input id="reportFrom" name="from" type="date" value="{{ $filters['from'] }}">
        </div>

        <div class="cr-field">
          <label for="reportTo">
            <i class="bi bi-calendar-event"></i>
            <span>{{ __('crm.to_date') }}</span>
          </label>
          <input id="reportTo" name="to" type="date" value="{{ $filters['to'] }}">
        </div>

        <div class="cr-filter-actions">
          <button class="btn primary" type="submit">
            <i class="bi bi-funnel-fill" aria-hidden="true"></i>
            <span>{{ __('crm.apply_filters') }}</span>
          </button>
          <a class="btn soft reset-btn" href="{{ route('v2.campaigns.reports') }}" title="{{ __('crm.reset_filters') }}" aria-label="{{ __('crm.reset_filters') }}">
            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
            <span>{{ __('crm.reset_filters') }}</span>
          </a>
        </div>
      </div>
    </div>
  </form>

  <!-- 3. CAMPAIGN SELECTOR -->
  <section class="cr-campaign-selector" aria-labelledby="campaignSelectorHeading">
    <header class="cr-section-head">
      <div>
        <h3 id="campaignSelectorHeading">
          <i class="bi bi-megaphone" aria-hidden="true"></i>
          <span>{{ __('crm.choose_campaign_report') }}</span>
        </h3>
        <p>{{ __('crm.choose_campaign_report_desc') }}</p>
      </div>
      <span class="cr-count-pill">{{ __('crm.campaigns_available_count', ['count' => number_format($campaigns->count())]) }}</span>
    </header>

    <div class="cr-campaign-grid">
      <!-- Special All Campaigns Card -->
      <a class="cr-card is-all {{ $selectedCampaign === null ? 'is-active' : '' }}" href="{{ route('v2.campaigns.reports', array_filter($campaignCardQuery)) }}" @if ($selectedCampaign === null) aria-current="page" @endif>
        <div class="cr-card-top">
          <span class="cr-card-icon"><i class="bi bi-collection-fill" aria-hidden="true"></i></span>
          <div class="cr-card-badges">
            <span class="cr-status-badge">{{ __('crm.all') }}</span>
            @if ($selectedCampaign === null)
              <span class="cr-active-indicator" title="Selected" aria-label="Selected"><i class="bi bi-check2"></i></span>
            @endif
          </div>
        </div>
        <div class="cr-card-mid">
          <h4 class="cr-card-title">{{ __('crm.all_campaigns') }}</h4>
          <p class="cr-card-desc">{{ __('crm.all_campaigns_report_desc') }}</p>
          <span class="cr-cost-badge">{{ __('crm.campaigns_available_count', ['count' => number_format($campaigns->count())]) }}</span>
        </div>
        <div class="cr-card-link">
          <span>{{ __('crm.view_campaign_report') }}</span>
          <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}" aria-hidden="true"></i>
        </div>
      </a>

      <!-- Individual Campaign Cards -->
      @foreach ($campaigns as $camp)
        @php
          $campaignState = $camp->ends_at->isPast()
            ? 'ended'
            : ($camp->starts_at->isFuture() ? 'upcoming' : 'active');
          $isActiveCard = $selectedCampaign?->id === $camp->id;
        @endphp
        <a class="cr-card {{ $isActiveCard ? 'is-active' : '' }}" href="{{ route('v2.campaigns.reports', array_merge(array_filter($campaignCardQuery), ['campaign_id' => $camp->id])) }}" @if ($isActiveCard) aria-current="page" @endif>
          <div class="cr-card-top">
            <span class="cr-card-icon">
              @if ($camp->image_path)
                <img src="{{ asset('storage/'.$camp->image_path) }}" alt="">
              @else
                <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
              @endif
            </span>
            <div class="cr-card-badges">
              <span class="cr-status-badge is-{{ $campaignState }}">
                {{ $campaignState === 'active' ? __('crm.active_now') : __($campaignState === 'ended' ? 'crm.ended' : 'crm.upcoming') }}
              </span>
              @if ($isActiveCard)
                <span class="cr-active-indicator" title="Selected" aria-label="Selected"><i class="bi bi-check2"></i></span>
              @endif
            </div>
          </div>
          <div class="cr-card-mid">
            <h4 class="cr-card-title" title="{{ $camp->name }}">{{ $camp->name }}</h4>
            <div class="cr-card-dates">
              <i class="bi bi-calendar3"></i>
              <span>{{ $camp->starts_at->format('Y-m-d') }} — {{ $camp->ends_at->format('Y-m-d') }}</span>
            </div>
            <span class="cr-cost-badge">{{ number_format($camp->cost, 2) }} {{ __('crm.pound') }}</span>
          </div>
          <div class="cr-card-link">
            <span>{{ __('crm.view_campaign_report') }}</span>
            <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}" aria-hidden="true"></i>
          </div>
        </a>
      @endforeach
    </div>
  </section>

  <!-- 4. KPI SUMMARY SECTION -->
  <section class="cr-kpi-section" aria-label="{{ __('crm.performance_summary') }}">
    <header class="cr-section-head">
      <div>
        <h3>
          <i class="bi bi-bar-chart-fill" aria-hidden="true"></i>
          <span>{{ __('crm.performance_summary') }}</span>
        </h3>
      </div>
    </header>

    <div class="cr-kpi-grid">
      @if ($selectedCampaign === null)
        <!-- Created Campaigns -->
        <article class="cr-kpi-card">
          <div class="cr-kpi-top">
            <span class="cr-kpi-label">{{ __('crm.campaigns_created') }}</span>
            <span class="cr-kpi-icon"><i class="bi bi-calendar-plus" aria-hidden="true"></i></span>
          </div>
          <strong class="cr-kpi-val">{{ number_format($metrics['created_campaigns']) }}</strong>
          <small class="cr-kpi-sub">{{ __('crm.campaigns_created_help') }}</small>
        </article>

        <!-- Ended Campaigns -->
        <article class="cr-kpi-card">
          <div class="cr-kpi-top">
            <span class="cr-kpi-label">{{ __('crm.campaigns_ended') }}</span>
            <span class="cr-kpi-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
          </div>
          <strong class="cr-kpi-val">{{ number_format($metrics['ended_campaigns']) }}</strong>
          <small class="cr-kpi-sub">{{ __('crm.campaigns_ended_help') }}</small>
        </article>
      @else
        <!-- Campaign Budget / Cost -->
        <article class="cr-kpi-card">
          <div class="cr-kpi-top">
            <span class="cr-kpi-label">{{ __('crm.campaign_budget') }}</span>
            <span class="cr-kpi-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
          </div>
          <strong class="cr-kpi-val">{{ number_format($selectedCampaign->cost, 2) }} <span style="font-size:14px;font-weight:700">{{ __('crm.pound') }}</span></strong>
          <small class="cr-kpi-sub">{{ __('crm.campaign_budget_help') }}</small>
        </article>
      @endif

      <!-- Total Leads -->
      <article class="cr-kpi-card">
        <div class="cr-kpi-top">
          <span class="cr-kpi-label">{{ __('crm.current_campaign_leads') }}</span>
          <span class="cr-kpi-icon"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
        </div>
        <strong class="cr-kpi-val">{{ number_format($metrics['current_leads']) }}</strong>
        <small class="cr-kpi-sub">{{ __('crm.current_campaign_leads_help') }}</small>
      </article>

      <!-- Conversion Rate Card (Selectable Stage) -->
      <article class="cr-kpi-card is-conversion" id="conversionMetricCard">
        <div class="cr-kpi-top">
          <span class="cr-kpi-label">{{ __('crm.conversion_rate') }} ({{ $metrics['conversion_stage_name'] }})</span>
          <span class="cr-kpi-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
        </div>
        <strong class="cr-kpi-val">{{ number_format($metrics['conversion_rate'], 1) }}%</strong>
        <small class="cr-kpi-sub">
          {{ __('crm.converted_leads_count', ['converted' => number_format($metrics['converted_leads']), 'total' => number_format($metrics['current_leads'])]) }}
        </small>
        <!-- Inline Stage Quick Switcher -->
        <div class="cr-stage-switcher">
          <label for="quickStagePicker" class="sr-only">{{ __('crm.change_target_stage') }}</label>
          <select id="quickStagePicker" title="{{ __('crm.change_target_stage') }}" onchange="document.getElementById('reportStage').value=this.value; document.getElementById('campaignReportFilters').submit();">
            @foreach ($stages as $stg)
              <option value="{{ $stg->id }}" @selected((int) $filters['stage_id'] === (int) $stg->id)>
                {{ $stg->localizedName() }}
              </option>
            @endforeach
          </select>
        </div>
      </article>

      <!-- Cost Per Lead (CPL) -->
      <article class="cr-kpi-card is-cost">
        <div class="cr-kpi-top">
          <span class="cr-kpi-label">{{ __('crm.campaign_lead_cost') }}</span>
          <span class="cr-kpi-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
        </div>
        <strong class="cr-kpi-val">{{ number_format($metrics['lead_cost'], 2) }} <span style="font-size:14px;font-weight:700">{{ __('crm.pound') }}</span></strong>
        <small class="cr-kpi-sub">{{ __('crm.campaign_lead_cost_help', ['cost' => number_format($metrics['total_campaign_cost'], 2), 'leads' => number_format($metrics['current_leads'])]) }}</small>
      </article>
    </div>
  </section>

  <!-- 5. DETAILED ANALYTICS & CHARTS -->
  <section class="cr-analytics-section" aria-label="{{ __('crm.detailed_analytics') }}">
    <header class="cr-section-head">
      <div>
        <h3>
          <i class="bi bi-pie-chart-fill" aria-hidden="true"></i>
          <span>{{ __('crm.detailed_analytics') }}</span>
        </h3>
      </div>
    </header>

    <div class="cr-analytics-grid {{ $selectedCampaign !== null ? 'is-single' : '' }}">
      @if ($selectedCampaign === null)
        <!-- Performance Timeline Chart -->
        <article class="cr-panel">
          <header class="cr-panel-head">
            <div>
              <h4>{{ __('crm.campaign_performance_timeline') }}</h4>
              <p>{{ __('crm.campaign_timeline_desc') }}</p>
            </div>
          </header>
          <div class="cr-chart-box">
            @if (collect($timeline)->sum('created') + collect($timeline)->sum('ended') > 0)
              <canvas id="campaignTimelineChart" role="img" aria-label="{{ __('crm.campaign_performance_timeline') }}"></canvas>
            @else
              <div class="cr-chart-empty">
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                <span>{{ __('crm.no_campaign_activity') }}</span>
              </div>
            @endif
          </div>
          <table class="sr-only">
            <caption>{{ __('crm.campaign_performance_timeline') }}</caption>
            <thead><tr><th>{{ __('crm.period') }}</th><th>{{ __('crm.created_campaigns_chart') }}</th><th>{{ __('crm.ended_campaigns_chart') }}</th></tr></thead>
            <tbody>
              @foreach ($timeline as $point)
                <tr><td>{{ $point['label'] }}</td><td>{{ $point['created'] }}</td><td>{{ $point['ended'] }}</td></tr>
              @endforeach
            </tbody>
          </table>
        </article>
      @endif

      <!-- Stage Distribution Chart -->
      <article class="cr-panel">
        <header class="cr-panel-head">
          <div>
            <h4>{{ __('crm.campaign_stage_distribution') }}</h4>
            <p>{{ __('crm.campaign_stage_distribution_desc') }}</p>
          </div>
        </header>
        <div class="cr-chart-box">
          @if ($metrics['current_leads'] > 0)
            <canvas id="campaignStageChart" role="img" aria-label="{{ __('crm.campaign_stage_distribution') }}"></canvas>
          @else
            <div class="cr-chart-empty">
              <i class="bi bi-funnel" aria-hidden="true"></i>
              <span>{{ __('crm.no_campaign_leads') }}</span>
            </div>
          @endif
        </div>
        @if ($metrics['current_leads'] > 0)
          <div class="cr-status-list">
            @foreach ($stageDistribution as $stage)
              @php
                $isTargetStage = (int) ($stage['id'] ?? 0) === (int) $metrics['conversion_stage_id'];
              @endphp
              <div class="cr-status-row {{ $isTargetStage ? 'is-target-stage' : '' }}">
                <span class="cr-status-dot" style="--status-color:{{ $stage['color'] }}" aria-hidden="true"></span>
                <span class="cr-status-name">
                  <span>{{ $stage['label'] }}</span>
                  @if ($isTargetStage)
                    <span class="cr-status-tag">{{ __('crm.conversion_target_stage') }}</span>
                  @endif
                </span>
                <span class="cr-status-value">{{ number_format($stage['count']) }} · {{ number_format($stage['percentage'], 1) }}%</span>
              </div>
            @endforeach
          </div>
        @endif
        <p class="cr-note">
          {{ __('crm.campaign_stage_snapshot_notice') }}
        </p>
      </article>
    </div>
  </section>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
 (() => {
  const period = document.getElementById('reportPeriod');
  const from = document.getElementById('reportFrom');
  const to = document.getElementById('reportTo');

  const syncCustomDates = () => {
    const custom = period?.value === 'custom';
    if (from) from.disabled = !custom;
    if (to) to.disabled = !custom;
  };

  period?.addEventListener('change', () => {
    syncCustomDates();
    if (period.value === 'custom') from?.focus();
  });
  syncCustomDates();

  const initCharts = () => {
    if (typeof Chart === 'undefined') {
      document.querySelectorAll('#campaignTimelineChart, #campaignStageChart').forEach((canvas) => {
        const fallback = document.createElement('div');
        fallback.className = 'cr-chart-empty';
        fallback.innerHTML = '<i class="bi bi-bar-chart"></i><span>' + @json(__('crm.campaign_chart_unavailable')) + '</span>';
        canvas.replaceWith(fallback);
      });
      return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const dark = document.documentElement.classList.contains('dark-mode') || document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = dark ? '#cbd5e1' : '#475569';
    const gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(226,232,240,0.85)';
    const isRTL = @json(app()->getLocale() === 'ar');

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'Tajawal, Cairo, Tahoma, Arial, sans-serif';
    Chart.defaults.font.size = 11.5;

    // Timeline Chart
    const timelineCanvas = document.getElementById('campaignTimelineChart');
    if (timelineCanvas) {
      const timeline = @json($timeline);
      new Chart(timelineCanvas, {
        type: 'bar',
        data: {
          labels: timeline.map(point => point.label),
          datasets: [
            {
              label: @json(__('crm.created_campaigns_chart')),
              data: timeline.map(point => point.created),
              backgroundColor: '#3b82f6',
              borderRadius: 6,
              maxBarThickness: 28
            },
            {
              label: @json(__('crm.ended_campaigns_chart')),
              data: timeline.map(point => point.ended),
              backgroundColor: '#ef4444',
              borderRadius: 6,
              maxBarThickness: 28
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: reducedMotion ? false : {duration: 500, easing: 'easeOutQuart'},
          plugins: {
            legend: {
              position: 'bottom',
              rtl: isRTL,
              labels: {
                usePointStyle: true,
                padding: 14,
                font: {weight: 'bold'}
              }
            },
            tooltip: {
              rtl: isRTL,
              padding: 9,
              boxPadding: 5,
              usePointStyle: true
            }
          },
          scales: {
            x: {
              grid: {display: false},
              ticks: {maxRotation: 0, autoSkip: true, color: textColor}
            },
            y: {
              beginAtZero: true,
              position: isRTL ? 'right' : 'left',
              grid: {color: gridColor},
              ticks: {precision: 0, color: textColor}
            }
          }
        }
      });
    }

    // Stage Distribution Horizontal Bar Chart
    const stageCanvas = document.getElementById('campaignStageChart');
    if (stageCanvas) {
      const stages = @json($stageDistribution);
      new Chart(stageCanvas, {
        type: 'bar',
        data: {
          labels: stages.map(stage => stage.label),
          datasets: [{
            data: stages.map(stage => stage.percentage),
            backgroundColor: stages.map(stage => stage.color),
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: 24
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          animation: reducedMotion ? false : {duration: 600, easing: 'easeOutQuart'},
          plugins: {
            legend: {display: false},
            tooltip: {
              rtl: isRTL,
              padding: 9,
              callbacks: {
                label(context) {
                  const stage = stages[context.dataIndex];
                  return ` ${stage.label}: ${stage.count} ${@json(__('crm.leads_unit'))} (${stage.percentage}%)`;
                }
              }
            }
          },
          scales: {
            x: {
              beginAtZero: true,
              max: 100,
              position: 'bottom',
              grid: {color: gridColor},
              ticks: {
                color: textColor,
                callback: value => `${value}%`
              }
            },
            y: {
              grid: {display: false},
              ticks: {
                color: textColor,
                font: {weight: 'bold'}
              }
            }
          }
        }
      });
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCharts);
  } else {
    initCharts();
  }
 })();
</script>
@endpush
