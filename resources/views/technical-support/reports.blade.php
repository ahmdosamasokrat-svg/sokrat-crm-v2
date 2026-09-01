<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>{{ __('crm.support_reports_title') }}</title>
 <meta name="csrf-token" content="{{ csrf_token() }}">
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <style>
  :root{
   --report-red:#dc2637;--report-red-dark:#b81829;--report-bg:#f4f6fa;--report-panel:#fff;
   --report-soft:#f8fafc;--report-ink:#172033;--report-muted:#596579;--report-line:#e4e8ef;
   --report-green:#0f7440;--report-green-bg:#e9f8ef;--report-warning:#986800;--report-warning-bg:#fff6d8;
   --report-shadow:0 14px 38px rgba(17,24,39,.06);--font-primary:'Tajawal',Tahoma,Arial,sans-serif;
  }
  html.dark-mode{
   --report-bg:#151922;--report-panel:#202631;--report-soft:#272e3a;--report-ink:#f3f5f8;
   --report-muted:#aeb7c6;--report-line:#333b49;--report-green:#57d58c;--report-green-bg:#173c2a;
   --report-warning:#f0c75e;--report-warning-bg:#3c3218;--report-shadow:0 18px 42px rgba(0,0,0,.24);
  }
  *{box-sizing:border-box}
  body{margin:0;min-width:320px;background:var(--report-bg);color:var(--report-ink);font-family:var(--font-primary)}
  button,input,select{font:inherit}a{color:inherit}
  ::selection{background:#dc26372a;color:var(--report-ink)}
  :focus-visible{outline:3px solid rgba(220,38,55,.28);outline-offset:3px}
  .report-shell{display:flex;min-height:100vh;max-width:100vw;overflow-x:hidden}
  .report-main{min-width:0;flex:1;max-width:100%;padding:24px 30px 48px}
  .report-header, .crm-topbar, .topbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px;flex-wrap:wrap;min-height:56px}
  .report-heading, .crm-topbar-left, .topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
  .report-menu, .crm-topbar-menu-btn, .menu-button{display:none;width:44px;height:44px;min-height:44px;min-width:44px;place-items:center;flex:0 0 44px;border:1px solid var(--report-line);border-radius:12px;background:var(--report-panel);color:var(--report-ink);font-size:20px;cursor:pointer;touch-action:manipulation}
  .report-heading h1, .crm-topbar-title h1, .topbar h1{margin:0 0 4px;font-size:28px;line-height:1.25;font-weight:900;display:flex;align-items:center;gap:8px}
  .report-heading p, .crm-topbar-title p, .topbar p{max-width:72ch;margin:0;color:var(--report-muted);font-size:13px;line-height:1.65}
  .report-header-actions, .crm-topbar-right, .top-actions{display:flex;align-items:center;gap:10px;flex:0 0 auto;flex-wrap:wrap}
  .crm-topbar-actions{display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}
  .report-back{min-height:44px;display:inline-flex;align-items:center;gap:8px;padding:0 15px;border:1px solid var(--report-line);border-radius:11px;background:var(--report-panel);color:var(--report-ink);font-size:13px;font-weight:800;text-decoration:none;transition:border-color .16s,color .16s,transform .16s;touch-action:manipulation}
  .report-back:hover{border-color:var(--report-red);color:var(--report-red);transform:translateY(-1px)}
  .report-stack{display:grid;gap:18px}
   .metric-strip{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));overflow:hidden;border:1px solid var(--report-line);border-radius:16px;background:var(--report-panel);box-shadow:var(--report-shadow)}
  .metric-item{min-width:0;padding:18px 20px}
  .metric-item+.metric-item{border-inline-start:1px solid var(--report-line)}
  .metric-label{display:flex;align-items:center;gap:7px;color:var(--report-muted);font-size:11px;font-weight:800}
  .metric-label i{color:var(--report-red);font-size:14px}
   .metric-value{display:block;margin-top:9px;color:var(--report-ink);font-size:22px;font-weight:900;line-height:1.2;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
   .metric-item.time .metric-value{font-size:17px;color:var(--report-green)}
   .performance-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
   .performance-chart{min-width:0;border:1px solid var(--report-line);border-radius:16px;background:var(--report-panel);box-shadow:var(--report-shadow);overflow:hidden}
   .performance-chart-head{display:flex;align-items:flex-start;gap:11px;padding:18px 20px;border-bottom:1px solid var(--report-line)}
   .performance-chart-icon{width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;border-radius:11px;background:color-mix(in srgb,var(--report-red) 8%,var(--report-panel));color:var(--report-red);font-size:15px}
   .performance-chart.duration .performance-chart-icon{background:var(--report-green-bg);color:var(--report-green)}
   .performance-chart-head h2{margin:0;color:var(--report-ink);font-size:15px;font-weight:900;line-height:1.4}
   .performance-chart-head p{margin:4px 0 0;color:var(--report-muted);font-size:14px;font-weight:600;line-height:1.65}
   .performance-leader{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:13px 20px;border-bottom:1px solid var(--report-line);background:var(--report-soft)}
   .performance-leader-copy{display:flex;align-items:center;gap:9px;min-width:0}
   .performance-leader-rank{width:30px;height:30px;display:grid;place-items:center;flex:0 0 30px;border-radius:8px;background:var(--report-red);color:#fff;font-size:15px}
   .performance-chart.duration .performance-leader-rank{background:var(--report-green)}
   .performance-leader-copy span,.performance-leader-copy strong{display:block}
   .performance-leader-copy span{color:var(--report-muted);font-size:11px;font-weight:800}
   .performance-leader-copy strong{margin-top:2px;color:var(--report-ink);font-size:14px;font-weight:900;overflow-wrap:anywhere}
   .performance-leader-value{text-align:end;flex:0 0 auto}
   .performance-leader-value strong,.performance-leader-value span{display:block}
   .performance-leader-value strong{color:var(--report-red);font-size:20px;font-weight:900;font-variant-numeric:tabular-nums}
   .performance-chart.duration .performance-leader-value strong{color:var(--report-green);font-size:15px}
   .performance-leader-value span{margin-top:2px;color:var(--report-muted);font-size:11px;font-weight:800}
   .performance-chart-scroll{max-height:580px;overflow-y:auto;scrollbar-color:var(--report-line) transparent;scrollbar-width:thin}
   .performance-chart-scroll::-webkit-scrollbar{width:8px}.performance-chart-scroll::-webkit-scrollbar-thumb{border-radius:8px;background:var(--report-line)}
   .performance-chart-wrap{position:relative;min-height:290px;padding:14px 14px 10px}
   .chart-unavailable{height:290px;display:grid;place-items:center;padding:24px;color:var(--report-muted);font-size:14px;font-weight:800;text-align:center}
   .report-sr-only{position:absolute;width:1px;height:1px;overflow:hidden;margin:-1px;padding:0;border:0;clip:rect(0,0,0,0);white-space:nowrap}
   .report-panel{border:1px solid var(--report-line);border-radius:16px;background:var(--report-panel);box-shadow:var(--report-shadow);overflow:hidden}
  .panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid var(--report-line)}
  .panel-title{display:flex;align-items:flex-start;gap:11px;min-width:0}
  .panel-title-icon{width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;border-radius:10px;background:#fff0f2;color:var(--report-red);font-size:16px}
  html.dark-mode .panel-title-icon{background:#4b222a;color:#ff9aa4}
  .panel-title h2{margin:0;color:var(--report-ink);font-size:17px;font-weight:900;line-height:1.35}
  .panel-title p{max-width:72ch;margin:4px 0 0;color:var(--report-muted);font-size:12px;line-height:1.65}
  .filter-panel{padding:18px 20px}
  .filter-grid{display:grid;grid-template-columns:repeat(3,minmax(150px,1fr)) repeat(2,minmax(140px,.75fr));gap:12px;align-items:end}
  .filter-field{display:grid;gap:7px;min-width:0}
  .filter-field label{color:var(--report-muted);font-size:11px;font-weight:900}
  .filter-field input,.filter-field select{width:100%;height:43px;padding:0 12px;border:1px solid var(--report-line);border-radius:10px;background:var(--report-soft);color:var(--report-ink);font-size:13px;font-weight:700;outline:none}
  .filter-field input:focus,.filter-field select:focus{border-color:var(--report-red);box-shadow:0 0 0 3px rgba(220,38,55,.1);background:var(--report-panel)}
  .filter-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}
  .btn-filter{min-height:42px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 17px;border:0;border-radius:10px;background:var(--report-red);color:#fff;font-size:13px;font-weight:900;cursor:pointer;text-decoration:none}
  .btn-filter.secondary{border:1px solid var(--report-line);background:var(--report-panel);color:var(--report-muted)}
  .filter-errors{margin:0 0 14px;padding:11px 13px;border:1px solid #efbec4;border-radius:10px;background:#fff0f2;color:#9c1a29;font-size:12px;font-weight:800}
  html.dark-mode .filter-errors{border-color:#71343d;background:#4b222a;color:#ffb4bc}
  .report-table-wrap{overflow-x:auto}
  .report-table{width:100%;border-collapse:collapse;table-layout:fixed}
  .report-table th{padding:12px 14px;background:var(--report-soft);color:var(--report-muted);font-size:11px;font-weight:900;text-align:start}
  .report-table td{padding:15px 14px;border-top:1px solid var(--report-line);color:var(--report-ink);font-size:13px;font-weight:700;line-height:1.55;vertical-align:middle;overflow-wrap:anywhere}
  .report-table tbody tr:first-child td{border-top:0}
  .report-table tbody tr:hover{background:color-mix(in srgb,var(--report-red) 2%,var(--report-soft))}
   .employee-cell{display:flex;align-items:center;gap:10px;min-width:0}
   .employee-icon{width:34px;height:34px;display:grid;place-items:center;flex:0 0 34px;border-radius:9px;background:var(--report-green-bg);color:var(--report-green);font-size:14px}
   .employee-cell strong,.entity-cell strong{display:block;color:var(--report-ink);font-size:14px;font-weight:900}
   .employee-summary-copy{display:grid;gap:6px;min-width:0}
   .employee-ticket-link{width:max-content;max-width:100%;min-height:32px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 10px;border:1px solid color-mix(in srgb,var(--report-red) 28%,var(--report-line));border-radius:8px;background:color-mix(in srgb,var(--report-red) 5%,var(--report-panel));color:var(--report-red-dark);font-size:11px;font-weight:900;text-decoration:none;white-space:nowrap;cursor:pointer;transition:border-color .16s,background-color .16s,color .16s}
   .employee-ticket-link:hover{border-color:var(--report-red);background:var(--report-red);color:#fff}
   .report-modal-backdrop[hidden]{display:none}
    .report-modal-backdrop{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:20px;background:rgba(18,24,39,.55);backdrop-filter:blur(4px)}
    .report-modal-dialog{width:min(900px,100%);max-height:calc(100dvh - 40px);display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--report-line);border-radius:16px;background:var(--report-panel);box-shadow:0 18px 45px rgba(15,23,42,.16)}
   .report-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:18px 20px;border-bottom:1px solid var(--report-line)}
    .report-modal-head h2{margin:0;color:var(--report-ink);font-size:15px;font-weight:900;line-height:1.4}
    .report-modal-head p{margin:4px 0 0;color:var(--report-muted);font-size:14px;font-weight:700;line-height:1.65}
    .report-modal-close{width:38px;height:38px;display:grid;place-items:center;flex:0 0 38px;border:1px solid var(--report-line);border-radius:11px;background:var(--report-soft);color:var(--report-muted);font-size:15px;cursor:pointer}
   .report-modal-close:hover{border-color:var(--report-red);color:var(--report-red)}
   .report-modal-body{min-height:0;overflow-y:auto;padding:0 20px}
   .followup-ticket{padding:20px 0;border-bottom:1px solid var(--report-line)}
   .followup-ticket:last-child{border-bottom:0}
   .followup-ticket-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:13px}
   .followup-ticket-title{display:grid;gap:4px;min-width:0}
   .followup-ticket-number{direction:ltr;width:max-content;color:var(--report-red);font:900 11px ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
   .followup-ticket-title h3{margin:0;color:var(--report-ink);font-size:15px;font-weight:900;line-height:1.5;overflow-wrap:anywhere}
   .followup-context{display:flex;justify-content:flex-end;gap:6px;flex-wrap:wrap}
   .followup-context span{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border:1px solid var(--report-line);border-radius:8px;background:var(--report-soft);color:var(--report-muted);font-size:11px;font-weight:800}
   .followup-content{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .followup-block{min-width:0;padding:13px 14px;border-radius:11px;background:var(--report-soft)}
   .followup-block.resolution{background:var(--report-green-bg)}
   .followup-block strong{display:flex;align-items:center;gap:6px;margin-bottom:7px;color:var(--report-muted);font-size:11px;font-weight:900}
   .followup-block.resolution strong{color:var(--report-green)}
    .followup-block p{margin:0;color:var(--report-ink);font-size:14px;font-weight:700;line-height:1.75;white-space:pre-wrap;overflow-wrap:anywhere}
   .followup-block p.empty{color:var(--report-muted);font-weight:600}
   .followup-meta{display:flex;align-items:center;gap:8px 16px;flex-wrap:wrap;margin-top:12px;color:var(--report-muted);font-size:11px;font-weight:800}
   .followup-meta span{display:inline-flex;align-items:center;gap:5px}
   .report-modal-foot{display:flex;justify-content:flex-end;padding:14px 20px;border-top:1px solid var(--report-line);background:var(--report-soft)}
    .report-modal-action{min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 18px;border:0;border-radius:11px;background:var(--report-red);color:#fff;font-size:11px;font-weight:900;cursor:pointer}
   body.report-modal-open{overflow:hidden}
   .entity-cell small{display:block;margin-top:3px;color:var(--report-muted);font-size:11px;font-weight:700}
  .numeric{font-variant-numeric:tabular-nums;font-weight:900!important}
  .duration{color:var(--report-green)!important;font-weight:900!important}
  .server-list{display:flex;gap:6px;flex-wrap:wrap}
  .server-chip{display:inline-flex;align-items:center;gap:5px;max-width:100%;padding:5px 8px;border:1px solid var(--report-line);border-radius:8px;background:var(--report-soft);color:var(--report-muted);font-size:11px;font-weight:800}
  .server-chip bdi{overflow-wrap:anywhere}
  .ticket-ref{display:grid;gap:3px}
  .ticket-ref a{color:var(--report-ink);font-size:13px;font-weight:900;text-decoration:none;overflow-wrap:anywhere}
  .ticket-ref a:hover{color:var(--report-red)}
  .ticket-ref span{direction:ltr;width:max-content;color:var(--report-muted);font:800 11px ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
  .empty-report{display:grid;place-items:center;padding:46px 20px;text-align:center}
  .empty-report i{width:48px;height:48px;display:grid;place-items:center;margin-bottom:12px;border-radius:13px;background:var(--report-soft);color:var(--report-muted);font-size:21px}
  .empty-report strong{color:var(--report-ink);font-size:16px;font-weight:900}
  .empty-report p{max-width:54ch;margin:5px 0 0;color:var(--report-muted);font-size:12px;line-height:1.7}
  .report-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px;border-top:1px solid var(--report-line);color:var(--report-muted);font-size:12px;font-weight:800}
  .pagination-actions{display:flex;gap:7px}
  .pagination-link{min-height:36px;display:inline-flex;align-items:center;gap:6px;padding:0 12px;border:1px solid var(--report-line);border-radius:9px;background:var(--report-panel);color:var(--report-ink);text-decoration:none}
  .pagination-link.disabled{cursor:not-allowed;opacity:.45}

   @media(max-width:1100px){
    .metric-strip{grid-template-columns:repeat(3,minmax(0,1fr))}.metric-item:nth-child(4){border-inline-start:0}.metric-item:nth-child(n+4){border-top:1px solid var(--report-line)}
    .filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.performance-charts{grid-template-columns:1fr}
  }
  @media(max-width:900px){
   .report-shell{display:block}.report-main{width:100%;padding:18px 16px 36px}.report-menu{display:grid}
   body.crm-side-open{overflow:hidden}
   .report-table thead{display:none}.report-table,.report-table tbody,.report-table tr,.report-table td{display:block;width:100%}
   .report-table tbody{display:grid;gap:12px;padding:14px}.report-table tr{overflow:hidden;border:1px solid var(--report-line);border-radius:12px;background:var(--report-panel)}
   .report-table td{display:grid;grid-template-columns:minmax(110px,.45fr) minmax(0,1fr);gap:12px;border-top:1px solid var(--report-line);padding:11px 13px}
   .report-table td::before{content:attr(data-label);color:var(--report-muted);font-size:11px;font-weight:900}
    .report-table td:first-child{border-top:0}.report-table tbody tr:hover{background:var(--report-panel)}
  }
   @media(max-width:768px){
   .report-header, .crm-topbar, .topbar{align-items:stretch;flex-direction:column;gap:14px}
   .report-heading, .crm-topbar-left, .topbar-left{width:100%;justify-content:flex-start}
   .report-header-actions, .crm-topbar-right, .top-actions{width:100%;justify-content:space-between;gap:8px}
   .crm-topbar-actions{display:flex;flex:1 1 auto;gap:8px;flex-wrap:wrap}
   .report-back{flex:1 1 auto;justify-content:center;min-height:44px}
   }
   @media(max-width:620px){
   .report-main{padding:14px 10px 28px}
   .report-heading h1, .crm-topbar-title h1{font-size:23px}.metric-strip{grid-template-columns:1fr 1fr}.metric-item:nth-child(n){border-top:1px solid var(--report-line);border-inline-start:0}.metric-item:first-child,.metric-item:nth-child(2){border-top:0}.metric-item:nth-child(even){border-inline-start:1px solid var(--report-line)}.metric-item:last-child{grid-column:1/-1;border-inline-start:0}
    .filter-grid{grid-template-columns:1fr}.filter-actions{display:grid;grid-template-columns:1fr auto}.report-table td{grid-template-columns:1fr;gap:5px}.panel-head{padding:16px}.filter-panel{padding:15px}.report-pagination{align-items:stretch;flex-direction:column}.pagination-actions{display:grid;grid-template-columns:1fr 1fr}.pagination-link{justify-content:center;min-height:44px}
    .filter-grid select, .filter-grid input, .filter-actions .btn { min-height: 44px; }
   }
  @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
 </style>
</head>
<body>
<!-- THESIS: Support reporting is an auditable work ledger, not a decorative analytics dashboard.
OWN-WORLD: SokratCRM neutral panels, Tajawal typography, red interaction emphasis, and compact operational tables.
STORY: Filter completed work, compare employee totals, then inspect every ticket behind the totals.
FIRST VIEWPORT: Header, one metric strip, and filters before employee and ticket records.
FORM: Established support workspace extended as a responsive report ledger.
FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance. -->
@include('partials.page-loader')
@php
 $formatSupportDuration = static function (int $seconds): string {
     return \Carbon\CarbonInterval::seconds(max(0, $seconds))
         ->cascade()
         ->locale(app()->getLocale())
          ->forHumans(['parts' => 2]);
  };
  $supportChartHeight = max(290, count($performanceCharts['tickets']) * 48 + 72);
  $topTicketEmployee = $performanceCharts['tickets'][0] ?? null;
  $topDurationEmployee = $performanceCharts['duration'][0] ?? null;
  $durationChartPoints = collect($performanceCharts['duration'])
      ->map(static fn (array $point): array => [
          ...$point,
          'formatted' => $formatSupportDuration((int) $point['value']),
      ])
      ->values();
 @endphp
<div class="report-shell">
 @include('partials.crm-sidebar')
 <button class="crm-overlay" id="supportSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>
 <main class="report-main">
  @php
    ob_start();
  @endphp
    @can('technical_support.view')
    <a class="btn soft report-back" href="{{ route('v2.technical-support.index') }}"><i class="bi bi-hdd-rack" aria-hidden="true"></i><span>{{ __('crm.support_servers') }}</span></a>
    @endcan
  @php
    $reportActions = ob_get_clean();
  @endphp
  @include('partials.topbar', [
    'title' => __('crm.support_reports_title'),
    'subtitle' => __('crm.support_reports_subtitle'),
    'icon' => 'bi-file-earmark-bar-graph',
    'actions' => $reportActions
  ])

  <div class="report-stack">
    <section class="metric-strip" aria-label="{{ __('crm.support_reports_title') }}">
    <div class="metric-item"><span class="metric-label"><i class="bi bi-ticket-detailed" aria-hidden="true"></i>{{ __('crm.closed_tickets') }}</span><strong class="metric-value">{{ number_format($metrics['tickets']) }}</strong></div>
    <div class="metric-item"><span class="metric-label"><i class="bi bi-people" aria-hidden="true"></i>{{ __('crm.employees_in_report') }}</span><strong class="metric-value">{{ number_format($metrics['employees']) }}</strong></div>
    <div class="metric-item"><span class="metric-label"><i class="bi bi-hdd-rack" aria-hidden="true"></i>{{ __('crm.servers_in_report') }}</span><strong class="metric-value">{{ number_format($metrics['servers']) }}</strong></div>
    <div class="metric-item time"><span class="metric-label"><i class="bi bi-stopwatch" aria-hidden="true"></i>{{ __('crm.total_support_time') }}</span><strong class="metric-value">{{ $formatSupportDuration($metrics['total_seconds']) }}</strong></div>
    <div class="metric-item time"><span class="metric-label"><i class="bi bi-speedometer2" aria-hidden="true"></i>{{ __('crm.average_ticket_time') }}</span><strong class="metric-value">{{ $formatSupportDuration($metrics['average_seconds']) }}</strong></div>
    </section>

    @if ($topTicketEmployee !== null && $topDurationEmployee !== null)
     <section class="performance-charts" aria-label="{{ __('crm.employee_performance_charts') }}">
      <article class="performance-chart">
       <header class="performance-chart-head">
        <span class="performance-chart-icon"><i class="bi bi-bar-chart-fill" aria-hidden="true"></i></span>
        <div><h2>{{ __('crm.tickets_by_employee') }}</h2><p>{{ __('crm.tickets_by_employee_desc') }}</p></div>
       </header>
       <div class="performance-leader">
        <div class="performance-leader-copy"><span class="performance-leader-rank"><i class="bi bi-trophy-fill" aria-hidden="true"></i></span><div><span>{{ __('crm.top_support_employee') }}</span><strong><bdi>{{ $topTicketEmployee['employee_name'] }}</bdi></strong></div></div>
        <div class="performance-leader-value"><strong>{{ number_format($topTicketEmployee['value']) }}</strong><span>{{ __('crm.closed_tickets') }}</span></div>
       </div>
       <div class="performance-chart-scroll">
        <div class="performance-chart-wrap" style="height:{{ $supportChartHeight }}px"><canvas id="employeeTicketsChart" role="img" aria-label="{{ __('crm.tickets_by_employee') }}"></canvas></div>
       </div>
       <table class="report-sr-only"><caption>{{ __('crm.tickets_by_employee') }}</caption><thead><tr><th>{{ __('crm.responsible_employee') }}</th><th>{{ __('crm.closed_tickets') }}</th></tr></thead><tbody>@foreach ($performanceCharts['tickets'] as $point)<tr><td>{{ $point['employee_name'] }}</td><td>{{ $point['value'] }}</td></tr>@endforeach</tbody></table>
      </article>

      <article class="performance-chart duration">
       <header class="performance-chart-head">
        <span class="performance-chart-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
        <div><h2>{{ __('crm.support_duration_by_employee') }}</h2><p>{{ __('crm.support_duration_by_employee_desc') }}</p></div>
       </header>
       <div class="performance-leader">
        <div class="performance-leader-copy"><span class="performance-leader-rank"><i class="bi bi-stopwatch-fill" aria-hidden="true"></i></span><div><span>{{ __('crm.longest_support_employee') }}</span><strong><bdi>{{ $topDurationEmployee['employee_name'] }}</bdi></strong></div></div>
        <div class="performance-leader-value"><strong>{{ $formatSupportDuration($topDurationEmployee['value']) }}</strong><span>{{ __('crm.total_support_time') }}</span></div>
       </div>
       <div class="performance-chart-scroll">
        <div class="performance-chart-wrap" style="height:{{ $supportChartHeight }}px"><canvas id="employeeDurationChart" role="img" aria-label="{{ __('crm.support_duration_by_employee') }}"></canvas></div>
       </div>
       <table class="report-sr-only"><caption>{{ __('crm.support_duration_by_employee') }}</caption><thead><tr><th>{{ __('crm.responsible_employee') }}</th><th>{{ __('crm.total_support_time') }}</th></tr></thead><tbody>@foreach ($durationChartPoints as $point)<tr><td>{{ $point['employee_name'] }}</td><td>{{ $point['formatted'] }}</td></tr>@endforeach</tbody></table>
      </article>
     </section>
    @endif

    <section class="report-panel" aria-labelledby="supportReportFiltersTitle">
    <div class="panel-head">
     <div class="panel-title">
      <span class="panel-title-icon"><i class="bi bi-funnel" aria-hidden="true"></i></span>
      <div><h2 id="supportReportFiltersTitle">{{ __('crm.filter_support_reports') }}</h2><p>{{ __('crm.filter_support_reports_desc') }}</p></div>
     </div>
    </div>
    <form class="filter-panel" method="GET" action="{{ route('v2.technical-support.reports') }}">
     @if ($errors->any())<div class="filter-errors" role="alert">{{ $errors->first() }}</div>@endif
     <div class="filter-grid">
      <div class="filter-field">
       <label for="reportEmployee">{{ __('crm.responsible_employee') }}</label>
       <select id="reportEmployee" name="employee_id">
        <option value="">{{ __('crm.all_employees') }}</option>
        @foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected($filters['employee_id'] === $employee->id)>{{ $employee->name }}</option>@endforeach
       </select>
      </div>
      <div class="filter-field">
       <label for="reportServer">{{ __('crm.server_details') }}</label>
       <select id="reportServer" name="device_key">
        <option value="">{{ __('crm.all_support_servers') }}</option>
        @foreach ($devices as $server)<option value="{{ $server->device_key }}" @selected($filters['device_key'] === $server->device_key)>{{ $server->name }}</option>@endforeach
       </select>
      </div>
      <div class="filter-field">
       <label for="reportCompany">{{ __('crm.company_name') }}</label>
       <select id="reportCompany" name="company_name">
        <option value="">{{ __('crm.all_support_companies') }}</option>
        @foreach ($companies as $company)<option value="{{ $company }}" @selected($filters['company_name'] === $company)>{{ $company }}</option>@endforeach
       </select>
      </div>
      <div class="filter-field"><label for="reportFromDate">{{ __('crm.from_date') }}</label><input id="reportFromDate" type="date" name="from_date" value="{{ $filters['from_date'] }}"></div>
      <div class="filter-field"><label for="reportToDate">{{ __('crm.to_date') }}</label><input id="reportToDate" type="date" name="to_date" value="{{ $filters['to_date'] }}"></div>
     </div>
     <div class="filter-actions">
      <button class="btn primary btn-filter" type="submit"><i class="bi bi-funnel-fill" aria-hidden="true"></i>{{ __('crm.apply_filters') }}</button>
      <a class="btn soft btn-filter secondary" href="{{ route('v2.technical-support.reports') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>{{ __('crm.reset_filters') }}</a>
     </div>
    </form>
   </section>

   <section class="report-panel" aria-labelledby="employeeSupportSummaryTitle">
    <div class="panel-head">
     <div class="panel-title">
      <span class="panel-title-icon"><i class="bi bi-person-workspace" aria-hidden="true"></i></span>
      <div><h2 id="employeeSupportSummaryTitle">{{ __('crm.employee_support_performance') }}</h2><p>{{ __('crm.employee_support_performance_desc') }}</p></div>
     </div>
    </div>
    @if ($employeeSummaries->isEmpty())
     <div class="empty-report"><i class="bi bi-inbox" aria-hidden="true"></i><strong>{{ __('crm.no_support_report_data') }}</strong><p>{{ __('crm.no_support_report_data_desc') }}</p></div>
    @else
     <div class="report-table-wrap">
      <table class="report-table">
        <thead><tr><th style="width:24%">{{ __('crm.responsible_employee') }}</th><th style="width:10%">{{ __('crm.closed_tickets') }}</th><th style="width:10%">{{ __('crm.servers_in_report') }}</th><th style="width:14%">{{ __('crm.total_support_time') }}</th><th style="width:14%">{{ __('crm.average_ticket_time') }}</th><th>{{ __('crm.servers_and_companies') }}</th></tr></thead>
       <tbody>
        @foreach ($employeeSummaries as $summary)
         <tr>
           <td data-label="{{ __('crm.responsible_employee') }}">
            <div class="employee-cell">
             <span class="employee-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
             <div class="employee-summary-copy">
              <strong><bdi>{{ $summary['employee_name'] }}</bdi></strong>
              @if ($summary['employee_id'] !== null)
               <button class="employee-ticket-link" type="button" data-ticket-details-open="employeeTicketDetails{{ $summary['employee_id'] }}" data-modal-title="{{ __('crm.employee_ticket_followups', ['employee' => $summary['employee_name']]) }}"><i class="bi bi-ticket-detailed" aria-hidden="true"></i>{{ __('crm.view_employee_tickets') }}</button>
              @endif
             </div>
            </div>
           </td>
          <td class="numeric" data-label="{{ __('crm.closed_tickets') }}">{{ number_format($summary['ticket_count']) }}</td>
          <td class="numeric" data-label="{{ __('crm.servers_in_report') }}">{{ number_format($summary['server_count']) }}</td>
          <td class="duration" data-label="{{ __('crm.total_support_time') }}">{{ $formatSupportDuration($summary['total_seconds']) }}</td>
          <td class="duration" data-label="{{ __('crm.average_ticket_time') }}">{{ $formatSupportDuration($summary['average_seconds']) }}</td>
          <td data-label="{{ __('crm.servers_and_companies') }}"><div class="server-list">@foreach ($summary['servers'] as $server)<span class="server-chip"><i class="bi bi-hdd-rack" aria-hidden="true"></i><bdi>{{ $server['name'] }}@if ($server['company_name']) - {{ $server['company_name'] }}@endif</bdi></span>@endforeach</div></td>
         </tr>
        @endforeach
       </tbody>
      </table>
     </div>
    @endif
   </section>

    <section class="report-panel" aria-labelledby="ticketReportDetailsTitle">
    <div class="panel-head">
     <div class="panel-title">
      <span class="panel-title-icon"><i class="bi bi-list-check" aria-hidden="true"></i></span>
      <div><h2 id="ticketReportDetailsTitle">{{ __('crm.ticket_report_details') }}</h2><p>{{ __('crm.ticket_report_details_desc') }}</p></div>
     </div>
    </div>
    @if ($tickets->isEmpty())
     <div class="empty-report"><i class="bi bi-ticket-detailed" aria-hidden="true"></i><strong>{{ __('crm.no_support_report_data') }}</strong><p>{{ __('crm.no_support_report_data_desc') }}</p></div>
    @else
     <div class="report-table-wrap">
      <table class="report-table">
       <thead><tr><th style="width:22%">{{ __('crm.ticket_subject') }}</th><th style="width:14%">{{ __('crm.completed_by') }}</th><th style="width:18%">{{ __('crm.server_details') }}</th><th style="width:15%">{{ __('crm.opened_at') }}</th><th style="width:15%">{{ __('crm.ticket_closed_date') }}</th><th>{{ __('crm.support_time_spent') }}</th></tr></thead>
       <tbody>
        @foreach ($tickets as $ticket)
         <tr>
          <td data-label="{{ __('crm.ticket_subject') }}"><div class="ticket-ref"><span>#{{ str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT) }}</span>@can('technical_support.view')<a href="{{ route('v2.technical-support.cards.show', $ticket->device_key) }}"><bdi>{{ $ticket->subject }}</bdi></a>@else<bdi>{{ $ticket->subject }}</bdi>@endcan</div></td>
          <td data-label="{{ __('crm.completed_by') }}"><div class="employee-cell"><span class="employee-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span><strong><bdi>{{ $ticket->closedBy?->name ?? __('crm.employee_unavailable') }}</bdi></strong></div></td>
          <td data-label="{{ __('crm.server_details') }}"><div class="entity-cell"><strong><bdi>{{ $ticket->device?->name ?? $ticket->device_key }}</bdi></strong><small><bdi>{{ $ticket->device?->company_name ?: __('crm.ip_not_specified') }}</bdi></small></div></td>
          <td data-label="{{ __('crm.opened_at') }}"><time datetime="{{ $ticket->opened_at?->toIso8601String() }}">{{ $ticket->opened_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time></td>
          <td data-label="{{ __('crm.ticket_closed_date') }}"><time datetime="{{ $ticket->closed_at?->toIso8601String() }}">{{ $ticket->closed_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time></td>
          <td class="duration" data-label="{{ __('crm.support_time_spent') }}">{{ $formatSupportDuration($ticket->supportTimeSeconds() ?? 0) }}</td>
         </tr>
        @endforeach
       </tbody>
      </table>
     </div>
     @if ($tickets->hasPages())
      <nav class="report-pagination" aria-label="{{ __('crm.page') }}">
       <span>{{ __('crm.page') }} {{ $tickets->currentPage() }} {{ __('crm.of') }} {{ $tickets->lastPage() }}</span>
       <div class="pagination-actions">
        @if ($tickets->onFirstPage())<span class="pagination-link disabled"><i class="bi bi-chevron-right" aria-hidden="true"></i>{{ __('crm.previous') }}</span>@else<a class="pagination-link" href="{{ $tickets->previousPageUrl() }}"><i class="bi bi-chevron-right" aria-hidden="true"></i>{{ __('crm.previous') }}</a>@endif
        @if ($tickets->hasMorePages())<a class="pagination-link" href="{{ $tickets->nextPageUrl() }}">{{ __('crm.next') }}<i class="bi bi-chevron-left" aria-hidden="true"></i></a>@else<span class="pagination-link disabled">{{ __('crm.next') }}<i class="bi bi-chevron-left" aria-hidden="true"></i></span>@endif
       </div>
      </nav>
     @endif
    @endif
   </section>
  </div>
 </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
 (() => {
  const ticketCanvas = document.getElementById('employeeTicketsChart');
  const durationCanvas = document.getElementById('employeeDurationChart');
  if (!ticketCanvas || !durationCanvas) return;

  if (typeof Chart === 'undefined') {
   [ticketCanvas, durationCanvas].forEach((canvas) => {
    const fallback = document.createElement('div');
    fallback.className = 'chart-unavailable';
    fallback.textContent = @json(__('crm.support_chart_unavailable'));
    canvas.replaceWith(fallback);
   });
   return;
  }

  const ticketPerformance = @json($performanceCharts['tickets']);
  const durationPerformance = @json($durationChartPoints);
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const dark = document.documentElement.classList.contains('dark-mode');
  const rtl = @json(app()->getLocale() === 'ar');
  const textColor = dark ? '#aeb7c6' : '#596579';
  const gridColor = dark ? 'rgba(255,255,255,.08)' : 'rgba(228,232,239,.85)';
  Chart.defaults.color = textColor;
  Chart.defaults.font.family = 'Tajawal, Tahoma, Arial, sans-serif';

  const sharedOptions = {
   indexAxis: 'y',
   responsive: true,
   maintainAspectRatio: false,
   animation: reducedMotion ? false : { duration: 620, easing: 'easeOutQuart' },
   plugins: { legend: { display: false } },
   scales: {
    x: { beginAtZero: true, grid: { color: gridColor } },
    y: { position: rtl ? 'right' : 'left', grid: { display: false }, ticks: { autoSkip: false } },
   },
  };

  new Chart(ticketCanvas, {
   type: 'bar',
   data: {
    labels: ticketPerformance.map((point) => point.employee_name),
    datasets: [{
     data: ticketPerformance.map((point) => point.value),
     backgroundColor: ticketPerformance.map((_, index) => index === 0 ? '#dc2637' : 'rgba(220,38,55,.34)'),
     borderRadius: 6,
     borderSkipped: false,
     maxBarThickness: 28,
    }],
   },
   options: {
    ...sharedOptions,
    plugins: {
     legend: { display: false },
     tooltip: { callbacks: { label: (context) => `${@json(__('crm.closed_tickets'))}: ${context.raw}` } },
    },
    scales: {
     ...sharedOptions.scales,
     x: { ...sharedOptions.scales.x, ticks: { precision: 0 } },
    },
   },
  });

  new Chart(durationCanvas, {
   type: 'bar',
   data: {
    labels: durationPerformance.map((point) => point.employee_name),
    datasets: [{
     data: durationPerformance.map((point) => point.value),
     backgroundColor: durationPerformance.map((_, index) => index === 0 ? '#0f7440' : 'rgba(15,116,64,.3)'),
     borderRadius: 6,
     borderSkipped: false,
     maxBarThickness: 28,
    }],
   },
   options: {
    ...sharedOptions,
    plugins: {
     legend: { display: false },
     tooltip: { callbacks: { label: (context) => durationPerformance[context.dataIndex]?.formatted || '' } },
    },
    scales: {
     ...sharedOptions.scales,
     x: {
      ...sharedOptions.scales.x,
      ticks: {
       callback(value) {
        if (value >= 3600) return `${Number(value / 3600).toFixed(value % 3600 === 0 ? 0 : 1)} ${@json(__('crm.hours_short'))}`;
        if (value >= 60) return `${Math.round(value / 60)} ${@json(__('crm.minutes_short'))}`;
        return `${value} ${@json(__('crm.seconds_short'))}`;
       },
      },
     },
    },
   },
  });
 })();
</script>

@foreach ($employeeSummaries as $summary)
 @if ($summary['employee_id'] !== null)
  <template id="employeeTicketDetails{{ $summary['employee_id'] }}">
   @foreach ($summary['tickets'] as $ticket)
    <article class="followup-ticket">
     <div class="followup-ticket-head">
      <div class="followup-ticket-title">
       <bdi class="followup-ticket-number">#{{ str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT) }}</bdi>
       <h3><bdi>{{ $ticket->subject }}</bdi></h3>
      </div>
      <div class="followup-context">
       <span><i class="bi bi-hdd-rack" aria-hidden="true"></i><bdi>{{ $ticket->device?->name ?? $ticket->device_key }}</bdi></span>
       <span><i class="bi bi-building" aria-hidden="true"></i><bdi>{{ $ticket->device?->company_name ?: __('crm.not_specified') }}</bdi></span>
      </div>
     </div>
     <div class="followup-content">
      <section class="followup-block">
       <strong><i class="bi bi-chat-left-text" aria-hidden="true"></i>{{ __('crm.issue_description') }}</strong>
       <p @class(['empty' => empty($ticket->description)])><bdi>{{ $ticket->description ?: __('crm.no_issue_description_recorded') }}</bdi></p>
      </section>
      <section class="followup-block resolution">
       <strong><i class="bi bi-tools" aria-hidden="true"></i>{{ __('crm.work_performed') }}</strong>
       <p @class(['empty' => empty($ticket->resolution)])><bdi>{{ $ticket->resolution ?: __('crm.not_specified') }}</bdi></p>
      </section>
     </div>
     <div class="followup-meta">
      <span><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ __('crm.opened_at') }}: <time datetime="{{ $ticket->opened_at?->toIso8601String() }}">{{ $ticket->opened_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time></span>
      <span><i class="bi bi-clock-history" aria-hidden="true"></i>{{ __('crm.ticket_closed_date') }}: <time datetime="{{ $ticket->closed_at?->toIso8601String() }}">{{ $ticket->closed_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time></span>
      <span><i class="bi bi-stopwatch" aria-hidden="true"></i>{{ __('crm.support_time_spent') }}: {{ $formatSupportDuration($ticket->supportTimeSeconds() ?? 0) }}</span>
     </div>
    </article>
   @endforeach
  </template>
 @endif
@endforeach

<div class="report-modal-backdrop" id="employeeTicketsModal" role="dialog" aria-modal="true" aria-labelledby="employeeTicketsModalTitle" aria-describedby="employeeTicketsModalDescription" aria-hidden="true" hidden>
 <div class="report-modal-dialog">
  <header class="report-modal-head">
   <div>
    <h2 id="employeeTicketsModalTitle"></h2>
    <p id="employeeTicketsModalDescription">{{ __('crm.employee_ticket_followups_desc') }}</p>
   </div>
   <button class="report-modal-close" type="button" data-ticket-modal-close aria-label="{{ __('crm.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
  </header>
  <div class="report-modal-body" id="employeeTicketsModalBody"></div>
  <footer class="report-modal-foot"><button class="report-modal-action" type="button" data-ticket-modal-close><i class="bi bi-x-lg" aria-hidden="true"></i>{{ __('crm.close') }}</button></footer>
 </div>
</div>
<script>
  (() => {
   const menu = document.getElementById('supportSidebarMenu');
   const overlay = document.getElementById('supportSidebarOverlay');
   const sidebar = document.getElementById('crmSidebar');
   const reportShell = document.querySelector('.report-shell');
   const ticketsModal = document.getElementById('employeeTicketsModal');
   const ticketsModalTitle = document.getElementById('employeeTicketsModalTitle');
   const ticketsModalBody = document.getElementById('employeeTicketsModalBody');
   const mobileSidebar = window.matchMedia('(max-width: 900px)');
   let activeModalTrigger = null;

   const closeTicketsModal = () => {
    if (!ticketsModal || ticketsModal.hidden) return;
    ticketsModal.hidden = true;
    ticketsModal.setAttribute('aria-hidden', 'true');
    reportShell?.removeAttribute('inert');
    document.body.classList.remove('report-modal-open');
    activeModalTrigger?.focus();
    activeModalTrigger = null;
   };

   document.querySelectorAll('[data-ticket-details-open]').forEach((button) => {
    button.addEventListener('click', () => {
     const template = document.getElementById(button.dataset.ticketDetailsOpen || '');
     if (!template || !ticketsModal || !ticketsModalTitle || !ticketsModalBody) return;

     activeModalTrigger = button;
     ticketsModalTitle.textContent = button.dataset.modalTitle || '';
     ticketsModalBody.replaceChildren(template.content.cloneNode(true));
     ticketsModal.hidden = false;
     ticketsModal.setAttribute('aria-hidden', 'false');
     reportShell?.setAttribute('inert', '');
     document.body.classList.add('report-modal-open');
     ticketsModal.querySelector('[data-ticket-modal-close]')?.focus();
    });
   });

   document.querySelectorAll('[data-ticket-modal-close]').forEach((button) => button.addEventListener('click', closeTicketsModal));
   ticketsModal?.addEventListener('click', (event) => {
    if (event.target === ticketsModal) closeTicketsModal();
   });
   ticketsModal?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
     event.preventDefault();
     closeTicketsModal();
     return;
    }
    if (event.key !== 'Tab') return;

    const focusable = [...ticketsModal.querySelectorAll('button:not([disabled]),a[href]')];
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
     event.preventDefault();
     last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
     event.preventDefault();
     first.focus();
    }
   });

  const syncSidebarAccess = (open) => {
   if (!sidebar) return;
   if (mobileSidebar.matches) {
    sidebar.toggleAttribute('inert', !open);
    sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
   } else {
    sidebar.removeAttribute('inert');
    sidebar.removeAttribute('aria-hidden');
   }
  };
  const closeSidebar = (restoreFocus = true) => {
   const wasOpen = document.body.classList.contains('crm-side-open');
   document.body.classList.remove('crm-side-open');
   menu?.setAttribute('aria-expanded', 'false');
   syncSidebarAccess(false);
   if (wasOpen && restoreFocus) menu?.focus();
  };
  menu?.addEventListener('click', () => {
   const open = document.body.classList.toggle('crm-side-open');
   menu.setAttribute('aria-expanded', open ? 'true' : 'false');
   syncSidebarAccess(open);
   if (open) window.requestAnimationFrame(() => sidebar?.querySelector('a, button')?.focus());
  });
  overlay?.addEventListener('click', () => closeSidebar());
  document.addEventListener('keydown', (event) => {
   if (event.key === 'Escape' && document.body.classList.contains('crm-side-open')) closeSidebar();
  });
  mobileSidebar.addEventListener('change', () => closeSidebar(false));
  syncSidebarAccess(false);
 })();
</script>
</body>
</html>
