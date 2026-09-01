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
 .campaign-report{display:grid;gap:22px}
 .campaign-report ::selection{background:#dc263726;color:var(--dark)}

 /* Hero Intro Section */
 .campaign-report-intro{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 22px;border-radius:16px;background:linear-gradient(135deg,#182033 0%,#202d48 100%);color:#fff;box-shadow:0 12px 30px rgba(24,32,51,0.12);position:relative;overflow:hidden;flex-wrap:wrap}
 .campaign-report-intro::after{content:'';position:absolute;top:-40px;left:-40px;width:180px;height:180px;background:radial-gradient(circle,rgba(220,38,55,0.18) 0%,transparent 70%);border-radius:50%;pointer-events:none}
 .campaign-report-intro-label{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#cdd5e2}
 .campaign-report-intro-label i{color:var(--red,#dc2637);font-size:16px}
 .campaign-report-scope-grid{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
 .campaign-scope-chip{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border:1px solid rgba(255,255,255,0.16);border-radius:12px;background:rgba(255,255,255,0.08);color:#f1f5f9;font-size:12px;backdrop-filter:blur(6px)}
 .campaign-scope-chip span{color:#94a3b8;font-size:11px;font-weight:700}
 .campaign-scope-chip strong{font-weight:800;color:#fff}
 .campaign-scope-chip .stage-indicator{width:8px;height:8px;border-radius:50%;display:inline-block}

 /* Quick Campaign Selector Grid */
 .campaign-selector-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}
 .campaign-selector-head h2{margin:0;color:#1e293b;font-size:17px;font-weight:900;line-height:1.35}
 .campaign-selector-head p{max-width:720px;margin:4px 0 0;color:var(--muted);font-size:12px;line-height:1.6}
 .campaign-selector-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(235px,1fr));gap:14px;margin-top:14px}
 .campaign-selector-card{position:relative;min-width:0;display:grid;grid-template-rows:auto 1fr auto;gap:12px;min-height:175px;padding:16px 18px;border:1px solid var(--line);border-radius:16px;background:var(--card);color:inherit;text-decoration:none;box-shadow:0 6px 20px rgba(15,23,42,0.04);transition:all .2s cubic-bezier(0.4,0,0.2,1)}
 .campaign-selector-card:hover{transform:translateY(-3px);border-color:#dc263788;box-shadow:0 14px 30px rgba(220,38,55,0.12)}
 .campaign-selector-card:focus-visible{outline:3px solid rgba(220,38,55,0.4);outline-offset:2px}
 .campaign-selector-card.is-active{border-color:#dc2637;background:linear-gradient(to bottom,var(--card),rgba(220,38,55,0.02));box-shadow:0 10px 28px rgba(220,38,55,0.16)}
 .campaign-selector-card.is-all{background:#1e293b;color:#fff;border-color:#334155}
 .campaign-selector-card.is-all.is-active{border-color:#ef4444;box-shadow:0 12px 30px rgba(0,0,0,0.3)}
 .campaign-selector-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
 .campaign-selector-icon{width:42px;height:42px;display:grid;place-items:center;flex:0 0 42px;overflow:hidden;border-radius:12px;background:#fee2e2;color:#dc2637;font-size:18px}
 .campaign-selector-icon img{width:100%;height:100%;display:block;object-fit:cover}
 .campaign-selector-card.is-all .campaign-selector-icon{background:rgba(255,255,255,0.12);color:#fff}
 .campaign-selector-state{display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border-radius:999px;background:#eef2f7;color:#475569;font-size:10px;font-weight:800;white-space:nowrap}
 .campaign-selector-state.is-active{background:#ecfdf5;color:#059669}
 .campaign-selector-state.is-active::before{content:'';width:6px;height:6px;border-radius:50%;background:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,0.25)}
 .campaign-selector-state.is-ended{background:#f1f5f9;color:#64748b}
 .campaign-selector-state.is-upcoming{background:#fef3c7;color:#d97706}
 .campaign-selector-card h3{overflow:hidden;margin:0;color:#0f172a;font-size:14px;font-weight:800;line-height:1.45;text-overflow:ellipsis;white-space:nowrap}
 .campaign-selector-card.is-all h3{color:#fff}
 .campaign-selector-card p{margin:4px 0 0;color:var(--muted);font-size:11px;line-height:1.6}
 .campaign-selector-card.is-all p{color:#94a3b8}
 .campaign-selector-card .campaign-cost-badge{display:inline-block;margin-top:6px;padding:2px 8px;border-radius:6px;background:#f8fafc;color:#0f172a;font-size:11px;font-weight:800;font-variant-numeric:tabular-nums;border:1px solid #e2e8f0}
 .campaign-selector-card.is-all .campaign-cost-badge{background:rgba(255,255,255,0.1);color:#f1f5f9;border-color:rgba(255,255,255,0.15)}
 .campaign-selector-open{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px solid var(--line);color:#dc2637;font-size:11px;font-weight:800}
 .campaign-selector-card.is-all .campaign-selector-open{border-color:rgba(255,255,255,0.12);color:#f87171}

 /* Filter Panel */
 .campaign-report-filters{padding:20px 24px;border:1px solid var(--line);border-radius:18px;background:var(--card);box-shadow:var(--shadow)}
 .campaign-report-filters-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--line)}
 .campaign-report-filters-head h3{margin:0;color:#0f172a;font-size:15px;font-weight:900}
 .campaign-report-filters-head p{margin:3px 0 0;color:var(--muted);font-size:12px}
 .campaign-report-filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr)) auto;align-items:end;gap:12px}
 .campaign-report-field{display:grid;gap:6px;min-width:0}
 .campaign-report-field label{color:#475569;font-size:11px;font-weight:800}
 .campaign-report-field select,.campaign-report-field input{width:100%;height:44px;min-height:44px;padding:0 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#0f172a;font-size:13px;font-weight:700;outline:none;transition:border-color .15s ease,box-shadow .15s ease}
 .campaign-report-field select:focus,.campaign-report-field input:focus{border-color:#dc2637;box-shadow:0 0 0 3px rgba(220,38,55,0.15)}
 .campaign-report-field input:disabled{cursor:not-allowed;opacity:.5;background:#f8fafc}
 .campaign-report-actions{display:flex;gap:8px}
 .campaign-report-actions .btn{height:44px;min-height:44px;white-space:nowrap}
 .campaign-report-errors{margin:0 0 16px;padding:12px 16px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b;font-size:12px;font-weight:800}

 /* Metric Cards Strip */
 .campaign-metric-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
 .campaign-metric{position:relative;min-width:0;display:flex;flex-direction:column;justify-content:space-between;padding:20px 22px;border-radius:16px;background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow);transition:transform .18s ease,box-shadow .18s ease}
 .campaign-metric:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(15,23,42,0.08)}
 .campaign-metric-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
 .campaign-metric-label{color:#64748b;font-size:12px;font-weight:800}
 .campaign-metric-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:10px;background:#f1f5f9;color:#475569;font-size:16px}
 .campaign-metric strong{display:block;margin:12px 0 6px;color:#0f172a;font-size:28px;font-weight:900;font-variant-numeric:tabular-nums;letter-spacing:-.02em;line-height:1}
 .campaign-metric small{display:block;color:#64748b;font-size:11px;font-weight:700;line-height:1.5}
 
 /* Conversion Card Special Theme */
 .campaign-metric.is-conversion{background:linear-gradient(135deg,rgba(16,185,129,0.06) 0%,rgba(16,185,129,0.12) 100%);border-color:rgba(16,185,129,0.3)}
 .campaign-metric.is-conversion .campaign-metric-icon{background:rgba(16,185,129,0.15);color:#059669}
 .campaign-metric.is-conversion strong{color:#047857}
 .campaign-metric.is-cost{background:linear-gradient(135deg,rgba(245,158,11,0.05) 0%,rgba(245,158,11,0.1) 100%);border-color:rgba(245,158,11,0.3)}
 .campaign-metric.is-cost .campaign-metric-icon{background:rgba(245,158,11,0.15);color:#b45309}
 .campaign-metric.is-cost strong{color:#b45309}

 /* Quick Stage Switcher on Conversion Card */
 .stage-quick-picker{display:flex;align-items:center;gap:6px;margin-top:8px;padding-top:8px;border-top:1px solid rgba(16,185,129,0.2)}
 .stage-quick-picker select{width:100%;height:36px;min-height:36px;padding:0 8px;border:1px solid rgba(16,185,129,0.3);border-radius:8px;background:#fff;color:#047857;font-size:11px;font-weight:800;outline:none;cursor:pointer}
 .stage-quick-picker select:focus{border-color:#059669;box-shadow:0 0 0 2px rgba(16,185,129,0.2)}
 /* Charts Grid Section */
 .campaign-report-charts{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(320px,1fr);gap:18px}
 .campaign-report-charts.is-focused{grid-template-columns:minmax(0,1fr)}
 .campaign-report-panel{min-width:0;padding:22px 24px;border-radius:18px;background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow)}
 .campaign-report-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--line)}
 .campaign-report-panel h3{margin:0;color:#0f172a;font-size:16px;font-weight:900;line-height:1.35}
 .campaign-report-panel-head p{max-width:630px;margin:4px 0 0;color:var(--muted);font-size:12px;line-height:1.6}
 .campaign-report-chart-wrap{position:relative;height:320px;width:100%}
 .campaign-report-chart-wrap.is-stage{height:290px}
 .campaign-report-empty{height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px;color:var(--muted);text-align:center;font-size:13px;font-weight:700;gap:8px}
 .campaign-report-empty i{font-size:32px;color:#cbd5e1}

 /* Stage List Breakdown */
 .campaign-status-list{display:grid;gap:8px;margin-top:16px;max-height:250px;overflow-y:auto;padding-inline-end:4px}
 .campaign-status-row{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;background:#f8fafc;border:1px solid #f1f5f9;transition:background .15s ease}
 .campaign-status-row:hover{background:#f1f5f9}
 .campaign-status-row.is-target-stage{background:#ecfdf5;border-color:#a7f3d0}
 .campaign-status-dot{width:10px;height:10px;border-radius:50%;background:var(--status-color);flex:0 0 10px}
 .campaign-status-name{overflow:hidden;color:#334155;font-size:12px;font-weight:800;text-overflow:ellipsis;white-space:nowrap;display:flex;align-items:center;gap:6px}
 .campaign-status-target-tag{display:inline-block;padding:1px 6px;border-radius:4px;background:#059669;color:#fff;font-size:9px;font-weight:900}
 .campaign-status-value{color:#0f172a;font-size:12px;font-weight:900;font-variant-numeric:tabular-nums;white-space:nowrap}
 .campaign-snapshot-note{margin:14px 0 0;padding-top:12px;border-top:1px solid var(--line);color:#64748b;font-size:11px;line-height:1.6}
 .campaign-report-sr-only{position:absolute;width:1px;height:1px;overflow:hidden;margin:-1px;padding:0;border:0;clip:rect(0,0,0,0)}

 /* Responsive Breakpoints */
 @media(max-width:1100px){
  .campaign-report-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .campaign-report-actions{grid-column:1/-1}
  .campaign-report-charts{grid-template-columns:1fr}
  .campaign-report-chart-wrap.is-stage{height:300px}
 }
 @media(max-width:768px){
  .campaign-report-intro{align-items:stretch;flex-direction:column;padding:20px}
  .campaign-selector-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .campaign-metric-strip{grid-template-columns:repeat(2,minmax(0,1fr))}
  .campaign-report-chart-wrap{height:280px}
 }
 @media(max-width:560px){
  .campaign-selector-head{align-items:flex-start;flex-direction:column}
  .campaign-selector-grid{display:flex;overflow-x:auto;-webkit-overflow-scrolling:touch;scroll-snap-type:x mandatory;padding:2px 2px 12px}
  .campaign-selector-card{min-width:min(82vw,270px);scroll-snap-align:start}
  .campaign-report-filter-grid{grid-template-columns:1fr}
  .campaign-report-actions{grid-column:auto;display:grid;grid-template-columns:1fr auto}
  .campaign-report-actions .btn{justify-content:center}
  .campaign-metric-strip{grid-template-columns:1fr}
  .campaign-report-panel{padding:16px}
  .campaign-report-chart-wrap{height:250px}
 }
 /* Dark Mode Adjustments */
 html.dark-mode .campaign-report-intro{background:linear-gradient(135deg,#090d16 0%,#131c2e 100%);box-shadow:0 16px 36px rgba(0,0,0,0.5)}
 html.dark-mode .campaign-report-filters,html.dark-mode .campaign-metric,html.dark-mode .campaign-report-panel{background:var(--card);border-color:var(--line)}
 html.dark-mode .campaign-selector-head h2,html.dark-mode .campaign-report-filters-head h3,html.dark-mode .campaign-report-panel h3{color:#f8fafc}
 html.dark-mode .campaign-report-field label{color:#94a3b8}
 html.dark-mode .campaign-report-field select,html.dark-mode .campaign-report-field input{border-color:#334155;background:#0f172a;color:#f8fafc}
 html.dark-mode .campaign-report-field input:disabled{background:#1e293b}
 html.dark-mode .campaign-metric-label{color:#94a3b8}
 html.dark-mode .campaign-metric strong{color:#f8fafc}
 html.dark-mode .campaign-metric-icon{background:#334155;color:#f8fafc}
 html.dark-mode .campaign-metric.is-conversion{background:linear-gradient(135deg,rgba(16,185,129,0.12) 0%,rgba(16,185,129,0.2) 100%);border-color:rgba(16,185,129,0.4)}
 html.dark-mode .campaign-metric.is-conversion strong{color:#34d399}
 html.dark-mode .campaign-metric.is-conversion small{color:#a7f3d0}
 html.dark-mode .stage-quick-picker select{background:#0f172a;border-color:rgba(16,185,129,0.5);color:#34d399}
 html.dark-mode .campaign-metric.is-cost{background:linear-gradient(135deg,rgba(245,158,11,0.1) 0%,rgba(245,158,11,0.18) 100%);border-color:rgba(245,158,11,0.4)}
 html.dark-mode .campaign-metric.is-cost strong{color:#fbbf24}
 html.dark-mode .campaign-selector-card{background:#1e293b;border-color:var(--line);box-shadow:0 8px 24px rgba(0,0,0,0.3)}
 html.dark-mode .campaign-selector-card.is-all{background:#090d16;border-color:#334155}
 html.dark-mode .campaign-selector-card h3{color:#f8fafc}
 html.dark-mode .campaign-selector-card p{color:#94a3b8}
 html.dark-mode .campaign-selector-card .campaign-cost-badge{background:#0f172a;border-color:#334155;color:#f8fafc}
 html.dark-mode .campaign-selector-open{border-color:var(--line);color:#f87171}
 html.dark-mode .campaign-selector-state{background:#334155;color:#cbd5e1}
 html.dark-mode .campaign-selector-state.is-active{background:rgba(16,185,129,0.2);color:#34d399}
 html.dark-mode .campaign-selector-state.is-upcoming{background:rgba(245,158,11,0.2);color:#fbbf24}
 html.dark-mode .campaign-status-row{background:#0f172a;border-color:#1e293b}
 html.dark-mode .campaign-status-row.is-target-stage{background:rgba(16,185,129,0.15);border-color:rgba(16,185,129,0.35)}
 html.dark-mode .campaign-status-name{color:#cbd5e1}
 html.dark-mode .campaign-status-value{color:#f8fafc}
 html.dark-mode .campaign-snapshot-note{border-color:var(--line);color:#94a3b8}
 html.dark-mode .campaign-report-empty i{color:#475569}
</style>
@endpush

@section('content')
<div class="campaign-report" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
 <!-- Hero Summary Section -->
 <section class="campaign-report-intro" aria-label="{{ __('crm.campaign_report_definition') }}">
  <div class="campaign-report-intro-label">
   <i class="bi bi-funnel"></i>
   <span>{{ __('crm.campaign_report_definition') }}</span>
  </div>
  <div class="campaign-report-scope-grid">
   <div class="campaign-scope-chip">
    <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
    <span>{{ __('crm.campaign_filter') }}:</span>
    <strong>{{ $selectedCampaign?->name ?? __('crm.showing_all_campaigns') }}</strong>
   </div>
   <div class="campaign-scope-chip">
    <i class="bi bi-person-badge" aria-hidden="true"></i>
    <span>{{ __('crm.responsible_employee') }}:</span>
    <strong>{{ $selectedEmployee?->name ?? __('crm.all_employees') }}</strong>
   </div>
   <div class="campaign-scope-chip">
    <span class="stage-indicator" style="background:{{ $selectedStage?->color ?? '#10b981' }}"></span>
    <span>{{ __('crm.conversion_target_stage') }}:</span>
    <strong>{{ $selectedStage?->localizedName() ?? $selectedStage?->name_ar ?? __('crm.all_stages') }}</strong>
   </div>
  </div>
 </section>

 <!-- Filter Form -->
 <form class="campaign-report-filters" id="campaignReportFilters" method="GET" action="{{ route('v2.campaigns.reports') }}">
  <header class="campaign-report-filters-head">
   <div>
    <h3>{{ __('crm.filter_campaign_report') }}</h3>
    <p>{{ __('crm.filter_campaign_report_desc') }}</p>
   </div>
  </header>

  @if ($errors->any())
   <div class="campaign-report-errors" role="alert">
    {{ $errors->first() }}
   </div>
  @endif

  @if ($filters['campaign_id'] !== null)
   <input type="hidden" name="campaign_id" value="{{ $filters['campaign_id'] }}">
  @endif

  <div class="campaign-report-filter-grid">
   <!-- Employee Filter -->
   <div class="campaign-report-field">
    <label for="reportEmployee">{{ __('crm.responsible_employee') }}</label>
    <select id="reportEmployee" name="employee_id">
     <option value="">{{ __('crm.all_employees') }}</option>
     @foreach ($employees as $employee)
      <option value="{{ $employee->id }}" @selected($filters['employee_id'] === $employee->id)>
       {{ $employee->name }}
      </option>
     @endforeach
    </select>
   </div>

   <!-- Target Conversion Stage Filter -->
   <div class="campaign-report-field">
    <label for="reportStage">{{ __('crm.conversion_target_stage') }}</label>
    <select id="reportStage" name="stage_id">
     @foreach ($stages as $stg)
      <option value="{{ $stg->id }}" @selected((int) $filters['stage_id'] === (int) $stg->id)>
       {{ $stg->localizedName() }}
      </option>
     @endforeach
    </select>
   </div>

   <!-- Period Filter -->
   <div class="campaign-report-field">
    <label for="reportPeriod">{{ __('crm.report_period') }}</label>
    <select id="reportPeriod" name="period">
     <option value="all" @selected($filters['period'] === 'all')>{{ __('crm.all_periods') }}</option>
     <option value="today" @selected($filters['period'] === 'today')>{{ __('crm.today') }}</option>
     <option value="week" @selected($filters['period'] === 'week')>{{ __('crm.this_week') }}</option>
     <option value="month" @selected($filters['period'] === 'month')>{{ __('crm.this_month') }}</option>
     <option value="year" @selected($filters['period'] === 'year')>{{ __('crm.this_year') }}</option>
     <option value="custom" @selected($filters['period'] === 'custom')>{{ __('crm.custom_range') }}</option>
    </select>
   </div>

   <!-- From Date -->
   <div class="campaign-report-field">
    <label for="reportFrom">{{ __('crm.from_date') }}</label>
    <input id="reportFrom" name="from" type="date" value="{{ $filters['from'] }}">
   </div>

   <!-- To Date -->
   <div class="campaign-report-field">
    <label for="reportTo">{{ __('crm.to_date') }}</label>
    <input id="reportTo" name="to" type="date" value="{{ $filters['to'] }}">
   </div>

   <!-- Actions -->
   <div class="campaign-report-actions">
    <button class="btn primary" type="submit">
     <i class="bi bi-funnel-fill" aria-hidden="true"></i>
     <span>{{ __('crm.apply_filters') }}</span>
    </button>
    <a class="btn soft" href="{{ route('v2.campaigns.reports') }}" title="{{ __('crm.reset_filters') }}" aria-label="{{ __('crm.reset_filters') }}">
     <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
    </a>
   </div>
  </div>
 </form>

 @php
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

 <!-- Quick Campaign Cards Selector -->
 <section class="campaign-selector" aria-labelledby="campaignSelectorHeading">
  <header class="campaign-selector-head">
   <div>
    <h2 id="campaignSelectorHeading">{{ __('crm.choose_campaign_report') }}</h2>
    <p>{{ __('crm.choose_campaign_report_desc') }}</p>
   </div>
  </header>
  <div class="campaign-selector-grid">
   <!-- All Campaigns Card -->
   <a class="campaign-selector-card is-all {{ $selectedCampaign === null ? 'is-active' : '' }}" href="{{ route('v2.campaigns.reports', array_filter($campaignCardQuery)) }}" @if ($selectedCampaign === null) aria-current="page" @endif>
    <div class="campaign-selector-top">
     <span class="campaign-selector-icon"><i class="bi bi-collection-fill" aria-hidden="true"></i></span>
     <span class="campaign-selector-state">{{ __('crm.all') }}</span>
    </div>
    <div>
     <h3>{{ __('crm.all_campaigns') }}</h3>
     <p>{{ __('crm.all_campaigns_report_desc') }}</p>
     <span class="campaign-cost-badge">{{ __('crm.campaigns_available_count', ['count' => number_format($campaigns->count())]) }}</span>
    </div>
    <span class="campaign-selector-open">
     <span>{{ __('crm.view_campaign_report') }}</span>
     <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}" aria-hidden="true"></i>
    </span>
   </a>

   <!-- Individual Campaign Cards -->
   @foreach ($campaigns as $camp)
    @php
     $campaignState = $camp->ends_at->isPast()
      ? 'ended'
      : ($camp->starts_at->isFuture() ? 'upcoming' : 'active');
    @endphp
    <a class="campaign-selector-card {{ $selectedCampaign?->id === $camp->id ? 'is-active' : '' }}" href="{{ route('v2.campaigns.reports', array_merge(array_filter($campaignCardQuery), ['campaign_id' => $camp->id])) }}" @if ($selectedCampaign?->id === $camp->id) aria-current="page" @endif>
     <div class="campaign-selector-top">
      <span class="campaign-selector-icon">
       @if ($camp->image_path)
        <img src="{{ asset('storage/'.$camp->image_path) }}" alt="">
       @else
        <i class="bi bi-megaphone-fill" aria-hidden="true"></i>
       @endif
      </span>
      <span class="campaign-selector-state is-{{ $campaignState }}">
       {{ $campaignState === 'active' ? __('crm.active_now') : __($campaignState === 'ended' ? 'crm.ended' : 'crm.upcoming') }}
      </span>
     </div>
     <div>
      <h3 title="{{ $camp->name }}">{{ $camp->name }}</h3>
      <p>{{ $camp->starts_at->format('Y-m-d') }} - {{ $camp->ends_at->format('Y-m-d') }}</p>
      <span class="campaign-cost-badge">{{ number_format($camp->cost, 2) }} {{ __('crm.pound') }}</span>
     </div>
     <span class="campaign-selector-open">
      <span>{{ __('crm.view_campaign_report') }}</span>
      <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-arrow-left' : 'bi-arrow-right' }}" aria-hidden="true"></i>
     </span>
    </a>
   @endforeach
  </div>
 </section>

 <!-- Metrics Strip Section -->
 <section class="campaign-metric-strip" aria-label="{{ __('crm.campaign_reports') }}">
  @if ($selectedCampaign === null)
   <!-- Campaigns Created Count -->
   <article class="campaign-metric">
    <div class="campaign-metric-top">
     <span class="campaign-metric-label">{{ __('crm.campaigns_created') }}</span>
     <span class="campaign-metric-icon"><i class="bi bi-calendar-plus" aria-hidden="true"></i></span>
    </div>
    <strong>{{ number_format($metrics['created_campaigns']) }}</strong>
    <small>{{ __('crm.campaigns_created_help') }}</small>
   </article>

   <!-- Campaigns Ended Count -->
   <article class="campaign-metric">
    <div class="campaign-metric-top">
     <span class="campaign-metric-label">{{ __('crm.campaigns_ended') }}</span>
     <span class="campaign-metric-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
    </div>
    <strong>{{ number_format($metrics['ended_campaigns']) }}</strong>
    <small>{{ __('crm.campaigns_ended_help') }}</small>
   </article>
  @endif

  <!-- Total Campaign Leads -->
  <article class="campaign-metric">
   <div class="campaign-metric-top">
    <span class="campaign-metric-label">{{ __('crm.current_campaign_leads') }}</span>
    <span class="campaign-metric-icon"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
   </div>
   <strong>{{ number_format($metrics['current_leads']) }}</strong>
   <small>{{ __('crm.current_campaign_leads_help') }}</small>
  </article>

  <!-- Conversion Rate Card (Selectable Stage & No Donor Word) -->
  <article class="campaign-metric is-conversion" id="conversionMetricCard">
   <div class="campaign-metric-top">
    <span class="campaign-metric-label">{{ __('crm.conversion_rate') }} ({{ $metrics['conversion_stage_name'] }})</span>
    <span class="campaign-metric-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
   </div>
   <strong>{{ number_format($metrics['conversion_rate'], 1) }}%</strong>
   <small>
    {{ __('crm.converted_leads_count', ['converted' => number_format($metrics['converted_leads']), 'total' => number_format($metrics['current_leads'])]) }}
   </small>
   <!-- Inline Stage Quick Switcher -->
   <div class="stage-quick-picker">
    <label for="quickStagePicker" class="campaign-report-sr-only">{{ __('crm.change_target_stage') }}</label>
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
  <article class="campaign-metric is-cost">
   <div class="campaign-metric-top">
    <span class="campaign-metric-label">{{ __('crm.campaign_lead_cost') }}</span>
    <span class="campaign-metric-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
   </div>
   <strong>{{ number_format($metrics['lead_cost'], 2) }} {{ __('crm.pound') }}</strong>
   <small>{{ __('crm.campaign_lead_cost_help', ['cost' => number_format($metrics['total_campaign_cost'], 2), 'leads' => number_format($metrics['current_leads'])]) }}</small>
  </article>
 </section>

 <!-- Charts Panels Section -->
 <section class="campaign-report-charts {{ $selectedCampaign !== null ? 'is-focused' : '' }}">
  @if ($selectedCampaign === null)
   <!-- Performance Timeline Chart -->
   <article class="campaign-report-panel">
    <header class="campaign-report-panel-head">
     <div>
      <h3>{{ __('crm.campaign_performance_timeline') }}</h3>
      <p>{{ __('crm.campaign_timeline_desc') }}</p>
     </div>
    </header>
    <div class="campaign-report-chart-wrap">
     @if (collect($timeline)->sum('created') + collect($timeline)->sum('ended') > 0)
      <canvas id="campaignTimelineChart" role="img" aria-label="{{ __('crm.campaign_performance_timeline') }}"></canvas>
     @else
      <div class="campaign-report-empty">
       <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
       <span>{{ __('crm.no_campaign_activity') }}</span>
      </div>
     @endif
    </div>
    <table class="campaign-report-sr-only">
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
  <article class="campaign-report-panel">
   <header class="campaign-report-panel-head">
    <div>
     <h3>{{ __('crm.campaign_stage_distribution') }}</h3>
     <p>{{ __('crm.campaign_stage_distribution_desc') }}</p>
    </div>
   </header>
   <div class="campaign-report-chart-wrap is-stage">
    @if ($metrics['current_leads'] > 0)
     <canvas id="campaignStageChart" role="img" aria-label="{{ __('crm.campaign_stage_distribution') }}"></canvas>
    @else
     <div class="campaign-report-empty">
      <i class="bi bi-funnel" aria-hidden="true"></i>
      <span>{{ __('crm.no_campaign_leads') }}</span>
     </div>
    @endif
   </div>
   @if ($metrics['current_leads'] > 0)
    <div class="campaign-status-list">
     @foreach ($stageDistribution as $stage)
      @php
       $isTargetStage = (int) ($stage['id'] ?? 0) === (int) $metrics['conversion_stage_id'];
      @endphp
      <div class="campaign-status-row {{ $isTargetStage ? 'is-target-stage' : '' }}">
       <span class="campaign-status-dot" style="--status-color:{{ $stage['color'] }}" aria-hidden="true"></span>
       <span class="campaign-status-name">
        <span>{{ $stage['label'] }}</span>
        @if ($isTargetStage)
         <span class="campaign-status-target-tag">{{ __('crm.conversion_target_stage') }}</span>
        @endif
       </span>
       <span class="campaign-status-value">{{ number_format($stage['count']) }} · {{ number_format($stage['percentage'], 1) }}%</span>
      </div>
     @endforeach
    </div>
   @endif
   <p class="campaign-snapshot-note">
    {{ __('crm.campaign_stage_snapshot_notice') }}
   </p>
  </article>
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
     fallback.className = 'campaign-report-empty';
     fallback.innerHTML = '<i class="bi bi-bar-chart"></i><span>' + @json(__('crm.campaign_chart_unavailable')) + '</span>';
     canvas.replaceWith(fallback);
    });
    return;
   }

   const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
   const dark = document.documentElement.classList.contains('dark-mode');
   const textColor = dark ? '#cbd5e1' : '#475569';
   const gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(226,232,240,0.85)';
   const isRTL = @json(app()->getLocale() === 'ar');

   Chart.defaults.color = textColor;
   Chart.defaults.font.family = 'Tajawal, Cairo, Tahoma, Arial, sans-serif';
   Chart.defaults.font.size = 12;

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
        maxBarThickness: 32
       },
       {
        label: @json(__('crm.ended_campaigns_chart')),
        data: timeline.map(point => point.ended),
        backgroundColor: '#ef4444',
        borderRadius: 6,
        maxBarThickness: 32
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
         padding: 16,
         font: {weight: 'bold'}
        }
       },
       tooltip: {
        rtl: isRTL,
        padding: 10,
        boxPadding: 6,
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
       maxBarThickness: 28
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
        padding: 10,
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
