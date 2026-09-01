@php
 $crmSidebarAssetsLoaded = true;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta name="csrf-token" content="{{ csrf_token() }}">
 <title>{{ __('crm.technical_support') }} - SokratCRM</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
 <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <style>
  *{box-sizing:border-box}
  :root{
   --support-red:#dc2637;
   --support-red-dark:#b81829;
   --support-ink:#172033;
   --support-muted:#596579;
   --support-line:#e4e8ef;
   --support-bg:#f4f6fa;
   --support-panel:#ffffff;
   --support-soft:#f8fafc;
   --support-green:#0f7440;
   --support-green-bg:#e9f8ef;
   --support-warning:#d97706;
   --support-warning-bg:#fef3c7;
   --support-gray-bg:#eef1f5;
   --support-shadow:0 10px 30px rgba(17,24,39,.05);
   --support-card-shadow:0 8px 24px rgba(17,24,39,.04);
   --support-card-hover-shadow:0 20px 45px rgba(17,24,39,.09);
   --font-primary:'Tajawal',Tahoma,Arial,sans-serif;
  }
  html.dark-mode{
   --support-ink:#f3f5f8;
   --support-muted:#aeb7c6;
   --support-line:#333b49;
   --support-bg:#151922;
   --support-panel:#202631;
   --support-soft:#272e3a;
   --support-green:#57d58c;
   --support-green-bg:#173c2a;
   --support-warning:#fbbf24;
   --support-warning-bg:#452e0d;
   --support-gray-bg:#303744;
   --support-shadow:0 14px 38px rgba(0,0,0,.24);
   --support-card-shadow:0 10px 28px rgba(0,0,0,.25);
   --support-card-hover-shadow:0 22px 48px rgba(0,0,0,.45);
  }
  html{background:var(--support-bg)}
  body{
   margin:0;
   background:radial-gradient(circle at 10% 0,rgba(220,38,55,0.04),transparent 30rem),var(--support-bg);
   color:var(--support-ink);
   font-family:var(--font-primary);
  }
  ::selection{background:#dc26372a;color:var(--support-ink)}
  *{scrollbar-width:thin;scrollbar-color:#aeb6c4 transparent}
  *::-webkit-scrollbar{width:8px;height:8px}
  *::-webkit-scrollbar-thumb{background:#aeb6c4;border:2px solid transparent;border-radius:10px;background-clip:padding-box}
  :focus-visible{outline:3px solid rgba(220,38,55,.28);outline-offset:3px}
  .visually-hidden{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
  
  .support-shell{min-height:100vh;display:flex;flex-direction:row;max-width:100vw;overflow-x:hidden}
  .support-main{flex:1;min-width:0;max-width:100%;padding:24px 30px 48px}
  
  /* Header */
  .support-header, .crm-topbar, .topbar {display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:20px;flex-wrap:wrap;min-height:56px}
  .support-heading, .crm-topbar-left, .topbar-left {display:flex;align-items:center;gap:12px;min-width:0}
  .support-menu, .crm-topbar-menu-btn, .menu-button {
   display:none;width:44px;height:44px;min-height:44px;min-width:44px;place-items:center;flex:0 0 44px;
   border:1px solid var(--support-line);border-radius:12px;background:var(--support-panel);
   color:var(--support-ink);font-size:20px;cursor:pointer;touch-action:manipulation;
  }
  .support-header h1, .crm-topbar-title h1, .topbar h1 {margin:0 0 4px;font-size:clamp(22px,2.5vw,28px);line-height:1.2;font-weight:900;letter-spacing:-.02em;display:flex;align-items:center;gap:8px}
  html[dir="rtl"] .support-header h1, html[dir="rtl"] .crm-topbar-title h1, html[dir="rtl"] .topbar h1 {letter-spacing:0}
  .support-header p, .crm-topbar-title p, .topbar p {margin:0;color:var(--support-muted);font-size:13.5px;line-height:1.5}
  .support-header-actions, .crm-topbar-right, .top-actions {display:flex;align-items:center;gap:10px;flex:0 0 auto;flex-wrap:wrap}
  .crm-topbar-actions {display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap}
  
  .btn-primary-add{
   min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px;
   padding:0 20px;border:none;border-radius:11px;
   background:linear-gradient(135deg,var(--support-red),var(--support-red-dark));color:#fff;
   font:800 13.5px var(--font-primary);cursor:pointer;touch-action:manipulation;
   box-shadow:0 6px 18px rgba(220,38,55,.24);transition:transform .18s ease-out,box-shadow .18s ease-out;
  }
  .btn-primary-add:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(220,38,55,.32)}
  .btn-primary-add i{font-size:16px}
  
  .btn-tailscale-net{
   min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px;
   padding:0 16px;border:1px solid var(--support-line);border-radius:11px;
   background:var(--support-panel);color:var(--support-ink);font:800 13px var(--font-primary);
   cursor:pointer;box-shadow:0 4px 12px rgba(17,24,39,.03);transition:all .18s ease-out;touch-action:manipulation;
  }
  .btn-tailscale-net:hover{border-color:#2563eb;color:#2563eb;transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,.1)}
  .btn-tailscale-net i{font-size:15px;color:#2563eb}
  
  .support-refresh{
   min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:7px;
   padding:0 16px;border:1px solid var(--support-line);border-radius:11px;
   background:var(--support-panel);color:var(--support-ink);font-weight:800;font-size:13px;text-decoration:none;
   box-shadow:0 4px 12px rgba(17,24,39,.03);transition:transform .18s ease-out,border-color .18s ease-out,color .18s ease-out;touch-action:manipulation;
  }
  .support-refresh:hover{border-color:#e8aab1;color:var(--support-red);transform:translateY(-1px)}
  .support-refresh i{font-size:15px}
  
  /* Top Interactive KPI Bar (Single Row, Sleek & Compact) */
  .support-kpi-bar{
   display:grid;
   grid-template-columns:repeat(4,1fr);
   gap:14px;
   margin-bottom:20px;
  }
  
  .kpi-card-btn{
   display:flex;align-items:center;gap:14px;padding:14px 18px;
   border:1px solid var(--support-line);border-radius:14px;
   background:var(--support-panel);box-shadow:var(--support-shadow);
   color:var(--support-ink);text-align:start;font:inherit;cursor:pointer;
   transition:all .2s cubic-bezier(.2,.8,.4,1);position:relative;overflow:hidden;
  }
  .kpi-card-btn:hover{
   transform:translateY(-2px);box-shadow:0 12px 28px rgba(17,24,39,.08);
   border-color:color-mix(in srgb,var(--support-red) 30%,var(--support-line));
  }
  .kpi-card-btn.active-kpi{
   border-color:var(--support-red);
   background:linear-gradient(180deg,#fff4f5,#ffffff 50%);
   box-shadow:0 10px 24px rgba(220,38,55,.12);
  }
  .kpi-card-btn.online.active-kpi{
   border-color:var(--support-green);
   background:linear-gradient(180deg,#eef9f2,#ffffff 50%);
   box-shadow:0 10px 24px rgba(15,116,64,.12);
  }
  .kpi-card-btn.warning.active-kpi{
   border-color:var(--support-warning);
   background:linear-gradient(180deg,#fffbeb,#ffffff 50%);
   box-shadow:0 10px 24px rgba(217,119,6,.12);
  }
  .kpi-card-btn.offline.active-kpi{
   border-color:var(--support-red);
   background:linear-gradient(180deg,#fff4f5,#ffffff 50%);
   box-shadow:0 10px 24px rgba(220,38,55,.12);
  }
  html.dark-mode .kpi-card-btn.active-kpi{background:var(--support-soft)}
  
  .kpi-icon{
   width:44px;height:44px;flex:0 0 44px;display:grid;place-items:center;
   border-radius:12px;background:#fff0f2;color:var(--support-red);font-size:20px;
  }
  .kpi-card-btn.online .kpi-icon{background:var(--support-green-bg);color:var(--support-green)}
  .kpi-card-btn.warning .kpi-icon{background:var(--support-warning-bg);color:var(--support-warning)}
  .kpi-card-btn.offline .kpi-icon{background:#fff0f2;color:var(--support-red)}
  html.dark-mode .kpi-card-btn.offline .kpi-icon{background:#3d2026;color:#ff9aa4}
  
  .kpi-text{flex:1;min-width:0}
  .kpi-label{display:block;color:var(--support-muted);font-size:12px;font-weight:700}
  .kpi-value{display:block;margin-top:2px;font-size:22px;font-weight:900;font-variant-numeric:tabular-nums;line-height:1.1}
  
  /* Alert */
  .support-alert{
   display:flex;align-items:flex-start;gap:12px;margin-bottom:20px;padding:14px 16px;
   border:1px solid #efbec4;border-radius:13px;background:#fff2f3;color:#8e1f2b;
  }
  html.dark-mode .support-alert{border-color:#673842;background:#3c2228;color:#ffb1ba}
  .support-alert i{margin-top:2px;font-size:18px}
  .support-alert strong{display:block;margin-bottom:3px;font-size:14px}
  .support-alert p{margin:0;line-height:1.5;font-size:12.5px}
  
  /* Panel & Unified Toolbar */
  .support-panel{border:1px solid var(--support-line);border-radius:18px;background:var(--support-panel);box-shadow:var(--support-shadow);overflow:hidden}
  .support-toolbar{
   display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 20px;
   border-bottom:1px solid var(--support-line);background:var(--support-panel);flex-wrap:wrap;
  }
  .toolbar-left{display:flex;align-items:center;gap:12px;flex:1;min-width:280px;flex-wrap:wrap}
  .toolbar-right{display:flex;align-items:center;gap:10px;flex:0 0 auto;flex-wrap:wrap}
  
  .support-search{position:relative;flex:1;min-width:240px;max-width:420px}
  .support-search i{position:absolute;inset-inline-start:14px;top:50%;color:var(--support-muted);font-size:16px;transform:translateY(-50%);pointer-events:none}
  .support-search input{
   width:100%;height:42px;padding:0 42px 0 14px;border:1px solid var(--support-line);border-radius:11px;
   background:var(--support-soft);color:var(--support-ink);font:700 13.5px var(--font-primary);outline:none;
   box-shadow:0 2px 8px rgba(17,24,39,.02);transition:all .18s ease-out;
  }
  html[dir="ltr"] .support-search input{padding:0 14px 0 42px}
  .support-search input:focus{border-color:var(--support-red);box-shadow:0 0 0 3px rgba(220,38,55,.1);background:var(--support-panel)}
  .support-search input::placeholder{color:var(--support-muted);font-weight:500}
  
  /* Status Filter Tabs */
  .status-filter-tabs{display:flex;align-items:center;gap:4px;padding:4px;border-radius:11px;background:var(--support-soft);border:1px solid var(--support-line)}
  .status-tab-btn{
   min-height:34px;padding:0 12px;border:0;border-radius:8px;background:transparent;
   color:var(--support-muted);font:800 12.5px var(--font-primary);cursor:pointer;
   display:inline-flex;align-items:center;gap:6px;transition:all .15s ease-out;
  }
  .status-tab-btn:hover{color:var(--support-ink)}
  .status-tab-btn.active{
   background:var(--support-panel);color:var(--support-red);box-shadow:0 2px 8px rgba(17,24,39,.08);
  }
  .status-tab-badge{
   display:inline-block;padding:1px 6px;border-radius:999px;font-size:11px;font-weight:900;
   background:var(--support-gray-bg);color:var(--support-muted);
  }
  .status-tab-btn.active .status-tab-badge{background:#fff0f2;color:var(--support-red)}
  html.dark-mode .status-tab-btn.active .status-tab-badge{background:#3d2026;color:#ff9aa4}
  
  /* OS Filter Select */
  .os-filter-select{
   height:38px;padding:0 12px;border:1px solid var(--support-line);border-radius:10px;
   background:var(--support-soft);color:var(--support-ink);font:700 12.5px var(--font-primary);outline:none;
   cursor:pointer;
  }
  .os-filter-select:focus{border-color:var(--support-red)}
  
  /* View Switcher Toggle */
  .view-switcher{display:inline-flex;align-items:center;padding:3px;border-radius:10px;background:var(--support-soft);border:1px solid var(--support-line)}
  .view-btn{
   width:34px;height:34px;display:grid;place-items:center;border:none;border-radius:7px;
   background:transparent;color:var(--support-muted);font-size:15px;cursor:pointer;transition:all .15s;
  }
  .view-btn:hover{color:var(--support-ink)}
  .view-btn.active{background:var(--support-panel);color:var(--support-red);box-shadow:0 2px 6px rgba(17,24,39,.08)}
  
  /* Cards Grid (3 cards per row on large screens) */
  .support-content-area{padding:22px;background:var(--support-soft)}
  .support-cards-grid{
   display:grid;
   grid-template-columns:repeat(auto-fill,minmax(330px,1fr));
   gap:20px;
  }
  
  /* Modern SaaS Server Card */
  .server-card{
   border:1px solid var(--support-line);
   border-radius:16px;
   background:var(--support-panel);
   box-shadow:var(--support-card-shadow);
   display:flex;
   flex-direction:column;
   justify-content:space-between;
   transition:transform .2s cubic-bezier(.2,.8,.4,1),box-shadow .2s,border-color .2s;
   position:relative;
   overflow:hidden;
  }
  .server-card::before{
   content:"";
   position:absolute;
   inset-block-start:0;
   inset-inline-start:0;
   inset-inline-end:0;
   height:4px;
   background:var(--support-line);
   transition:background .2s;
  }
  .server-card:hover{
   transform:translateY(-3px);
   box-shadow:var(--support-card-hover-shadow);
  }
  
  /* Status Color Themes for Server Cards */
  .server-card[data-status="online"]::before{background:var(--support-green)}
  .server-card[data-status="online"]{
   border-color:color-mix(in srgb, var(--support-green) 22%, var(--support-line));
  }
  .server-card[data-status="warning"]::before{background:var(--support-warning)}
  .server-card[data-status="warning"]{
   border-color:color-mix(in srgb, var(--support-warning) 25%, var(--support-line));
  }
  .server-card[data-status="offline"]::before{background:var(--support-red)}
  .server-card[data-status="offline"]{
   border-color:color-mix(in srgb, var(--support-red) 22%, var(--support-line));
  }
  
  /* Card Top Section */
  .server-card-top{padding:18px 20px 14px;border-bottom:1px solid var(--support-line)}
  .card-head-row{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}
  .card-identity-box{display:flex;align-items:center;gap:12px;min-width:0;flex:1}
  
  .card-avatar{
   width:44px;height:44px;display:grid;place-items:center;flex:0 0 44px;
   border-radius:12px;background:var(--support-gray-bg);color:var(--support-muted);font-size:20px;
   overflow:hidden;
  }
  .server-card[data-status="online"] .card-avatar{background:var(--support-green-bg);color:var(--support-green)}
  .server-card[data-status="warning"] .card-avatar{background:var(--support-warning-bg);color:var(--support-warning)}
  .server-card[data-status="offline"] .card-avatar{background:#fff0f2;color:var(--support-red)}
  html.dark-mode .server-card[data-status="offline"] .card-avatar{background:#3d2026;color:#ff9aa4}
  .card-avatar img{width:100%;height:100%;object-fit:cover}
  
  .card-titles{min-width:0;flex:1}
  .card-server-name{
   margin:0 0 3px;font-size:17px;font-weight:900;color:var(--support-ink);line-height:1.3;
   white-space:normal;overflow:visible;text-overflow:clip;overflow-wrap:anywhere;
  }
  .card-server-name a{color:inherit;text-decoration:none;white-space:normal;overflow-wrap:anywhere;transition:color .15s}
  .card-server-name a:hover{color:var(--support-red)}
  
  .card-company-pill{
   display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:var(--support-muted);
   white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;
  }
  .card-company-pill i{color:var(--support-red);font-size:13px}
  .card-company-pill.company-name{display:flex;width:100%;max-width:none;align-items:flex-start;white-space:normal;overflow:visible;text-overflow:clip;line-height:1.55}
  .card-company-pill.company-name i{flex:0 0 auto;margin-top:2px}
  .card-company-pill.company-name span{min-width:0;white-space:normal;overflow:visible;text-overflow:clip;overflow-wrap:anywhere;word-break:break-word}
  
  .card-top-badges{display:flex;align-items:center;gap:8px;flex-shrink:0}
  
  /* Status Badge */
  .health-badge{
   display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;
   font-size:11.5px;font-weight:800;flex-shrink:0;
  }
  .health-badge::before{content:"";width:7px;height:7px;border-radius:50%}
  .health-badge.online{background:var(--support-green-bg);color:var(--support-green)}
  .health-badge.online::before{background:var(--support-green);box-shadow:0 0 0 2px rgba(15,116,64,.25);animation:greenPulse 2s infinite}
  .health-badge.warning{background:var(--support-warning-bg);color:var(--support-warning)}
  .health-badge.warning::before{background:var(--support-warning)}
  .health-badge.offline{background:#fff0f2;color:var(--support-red-dark)}
  .health-badge.offline::before{background:var(--support-red)}
  html.dark-mode .health-badge.offline{background:#3d2026;color:#ff9aa4}
  
  /* Ellipsis Actions Dropdown */
  .card-dropdown{position:relative}
  .btn-card-ellipsis{
   width:30px;height:30px;display:grid;place-items:center;border:none;border-radius:7px;
   background:transparent;color:var(--support-muted);font-size:15px;cursor:pointer;transition:all .15s;
  }
  .btn-card-ellipsis:hover{color:var(--support-ink);background:var(--support-gray-bg)}
  .card-menu-dropdown{
   position:absolute;inset-inline-end:0;top:100%;margin-top:4px;
   min-width:140px;background:var(--support-panel);border:1px solid var(--support-line);
   border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.15);padding:4px;z-index:40;
   display:none;
  }
  .card-menu-dropdown.show{display:block;animation:scaleUp .15s ease-out}
  .dropdown-item{
   width:100%;display:flex;align-items:center;gap:8px;padding:8px 10px;border:none;
   border-radius:6px;background:transparent;color:var(--support-ink);font:700 12px var(--font-primary);
   text-align:start;cursor:pointer;transition:background .12s;
  }
  .dropdown-item:hover{background:var(--support-soft);color:var(--support-red)}
  .dropdown-item.danger:hover{color:var(--support-red);background:#fff0f2}
  html.dark-mode .dropdown-item.danger:hover{background:#3d2026}
  
  /* Meta Grid */
  .server-specs-grid{
   display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;font-size:12px;
  }
  .spec-item{display:flex;flex-direction:column;gap:2px}
  .spec-label{font-size:10.5px;font-weight:800;color:var(--support-muted);text-transform:uppercase;letter-spacing:.02em}
  .spec-val{font-weight:800;color:var(--support-ink);display:flex;align-items:center;gap:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .spec-val.live{color:var(--support-green)}
  
  .server-stats-pill{
   display:inline-flex;align-items:center;gap:10px;margin-top:10px;padding:5px 10px;
   border-radius:8px;background:var(--support-soft);border:1px solid var(--support-line);
   font-size:11.5px;font-weight:700;color:var(--support-muted);
  }
  .server-stats-pill i{color:var(--support-ink);font-size:12px}
  
  /* IP Section */
  .server-card-ip-section{padding:14px 20px;flex:1;display:flex;flex-direction:column;justify-content:center;gap:8px}
  .ip-chip{
   display:flex;align-items:center;justify-content:space-between;gap:8px;
   padding:8px 12px;border-radius:10px;border:1px solid var(--support-line);
   background:var(--support-soft);transition:all .18s;
  }
  .ip-chip:hover{border-color:color-mix(in srgb,var(--support-red) 30%,var(--support-line));background:var(--support-panel)}
  .ip-chip-content{display:flex;align-items:center;gap:8px;min-width:0;flex:1}
  .ip-chip code{
   font:700 12.5px ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
   color:var(--support-ink);direction:ltr;text-align:start;overflow-wrap:anywhere;
  }
  .ip-chip-tag{
   display:inline-block;padding:1px 5px;border-radius:4px;background:var(--support-gray-bg);
   color:var(--support-muted);font-size:9.5px;font-weight:800;
  }
  .btn-ip-action{
   width:30px;height:30px;display:grid;place-items:center;border:1px solid var(--support-line);
   border-radius:7px;background:var(--support-panel);color:var(--support-muted);font-size:13px;
   cursor:pointer;transition:all .15s ease-out;
  }
  .btn-ip-action:hover{color:var(--support-red);border-color:color-mix(in srgb,var(--support-red) 40%,var(--support-line));transform:scale(1.04)}
  .btn-ip-action.copied{color:var(--support-green);border-color:var(--support-green);background:var(--support-green-bg)}
  
  .no-ip-row{
   display:flex;align-items:center;justify-content:space-between;padding:8px 12px;
   border-radius:10px;border:1px dashed var(--support-line);background:var(--support-soft);font-size:12px;
  }
  .no-ip-text{color:var(--support-muted);font-weight:600}
  .btn-add-ip-inline{
   padding:3px 8px;border:1px solid var(--support-line);border-radius:6px;background:var(--support-panel);
   color:var(--support-red);font:800 11px var(--font-primary);cursor:pointer;transition:all .15s;
  }
  .btn-add-ip-inline:hover{background:var(--support-red);color:#fff;border-color:var(--support-red)}
  
  /* Card Footer */
  .server-card-footer{
   display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 20px;
   border-top:1px solid var(--support-line);background:var(--support-soft);
  }
  .btn-details-primary{
   flex:1;min-height:36px;display:inline-flex;align-items:center;justify-content:center;gap:6px;
   padding:0 14px;border:1px solid var(--support-line);border-radius:9px;
   background:var(--support-panel);color:var(--support-ink);font:800 12.5px var(--font-primary);
   text-decoration:none;cursor:pointer;transition:all .15s;
  }
  .btn-details-primary:hover{border-color:var(--support-red);color:var(--support-red);transform:translateY(-1px);box-shadow:0 4px 12px rgba(220,38,55,.08)}
  .btn-quick-edit{
   width:36px;height:36px;display:grid;place-items:center;border:1px solid var(--support-line);
   border-radius:9px;background:var(--support-panel);color:var(--support-muted);font-size:14px;
   cursor:pointer;transition:all .15s;
  }
  .btn-quick-edit:hover{color:var(--support-ink);border-color:#cbd5e1}
  
  /* Table View Mode (High-Density) */
  .support-table-container{display:none;overflow-x:auto}
  .support-table{width:100%;border-collapse:collapse;text-align:start}
  .support-table th{
   padding:14px 18px;background:var(--support-soft);color:var(--support-muted);
   font-size:11.5px;font-weight:900;text-transform:uppercase;border-bottom:1px solid var(--support-line);
   white-space:nowrap;
  }
  .support-table td{
   padding:14px 18px;border-bottom:1px solid var(--support-line);vertical-align:middle;
   font-size:13px;color:var(--support-ink);
  }
  .support-table tr:hover td{background:var(--support-soft)}
  
  /* Empty & No Matches */
  .support-empty{display:grid;place-items:center;min-height:260px;padding:35px;text-align:center}
  .support-empty i{font-size:38px;color:#a4adbb}
  .support-empty strong{display:block;margin-top:12px;font-size:16px}
  .support-empty p{margin:4px 0 0;color:var(--support-muted);font-size:13px}
  .support-no-match[hidden]{display:none}
  
  /* Modal */
  .support-modal-backdrop{
   position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);
   z-index:999;display:none;place-items:center;padding:16px;animation:fadeIn .18s ease-out forwards;
  }
  .support-modal-backdrop.active{display:grid}
  .support-modal{
   width:100%;max-width:520px;background:var(--support-panel);border:1px solid var(--support-line);
   border-radius:18px;box-shadow:0 24px 50px rgba(0,0,0,.2);overflow:hidden;
   animation:scaleUp .2s cubic-bezier(.16,1,.3,1) forwards;
  }
  .modal-header{
   display:flex;align-items:center;justify-content:space-between;padding:18px 22px;
   border-bottom:1px solid var(--support-line);
  }
  .modal-header h3{margin:0;font-size:17px;font-weight:900;color:var(--support-ink)}
  .btn-modal-close{
   width:32px;height:32px;display:grid;place-items:center;border:none;border-radius:8px;
   background:var(--support-soft);color:var(--support-muted);font-size:16px;cursor:pointer;
  }
  .btn-modal-close:hover{color:var(--support-ink)}
  
  .modal-body{padding:22px;display:flex;flex-direction:column;gap:14px;max-height:80vh;overflow-y:auto}
  .form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .form-group{display:flex;flex-direction:column;gap:6px}
  .form-group label{font-size:12.5px;font-weight:900;color:var(--support-ink)}
  .form-group label span{color:var(--support-muted);font-weight:500}
  .form-control{
   width:100%;height:42px;padding:0 12px;border:1px solid var(--support-line);border-radius:10px;
   background:var(--support-soft);color:var(--support-ink);font:inherit;font-size:13.5px;outline:none;
  }
  .form-control:focus{border-color:var(--support-red);box-shadow:0 0 0 3px rgba(220,38,55,.09);background:var(--support-panel)}
  textarea.form-control{height:auto;min-height:75px;padding:10px;resize:vertical}
  
  .modal-footer{
   display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 22px;
   border-top:1px solid var(--support-line);background:var(--support-soft);
  }
  
  /* Tailscale Modal Table */
  .ts-table{width:100%;border-collapse:collapse;margin-top:8px}
  .ts-table th{padding:10px 12px;background:var(--support-soft);color:var(--support-muted);font-size:11.5px;font-weight:900;text-align:start}
  .ts-table td{padding:12px;border-top:1px solid var(--support-line);vertical-align:middle;font-size:12.5px}
  
  /* Toast */
  .support-toast-container{
   position:fixed;bottom:20px;inset-inline-end:20px;z-index:1100;
   display:flex;flex-direction:column;gap:8px;pointer-events:none;
  }
  .support-toast{
   padding:12px 18px;border-radius:12px;background:var(--support-ink);color:#fff;
   font:800 13.5px var(--font-primary);box-shadow:0 12px 30px rgba(0,0,0,.25);
   display:flex;align-items:center;gap:8px;pointer-events:auto;animation:toastIn .2s cubic-bezier(.2,.8,.4,1) forwards;
  }
  .support-toast.success{background:#0e6939}
  .support-toast.error{background:var(--support-red-dark)}
  
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes scaleUp{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}
  @keyframes toastIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  @keyframes greenPulse{0%{box-shadow:0 0 0 0 rgba(15,116,64,0.45)}70%{box-shadow:0 0 0 8px rgba(15,116,64,0)}100%{box-shadow:0 0 0 0 rgba(15,116,64,0)}}
  

  @media(max-width:1100px){
   .support-kpi-bar{grid-template-columns:repeat(2,1fr)}
  }
  @media(max-width:900px){
   .support-shell{display:block}.support-main{width:100%;padding:18px 16px 36px}.support-menu, .crm-topbar-menu-btn, .menu-button{display:grid}
   body.crm-side-open{overflow:hidden}
   .support-toolbar{flex-direction:column;align-items:stretch}.toolbar-left{flex-direction:column;align-items:stretch}.toolbar-right{justify-content:space-between}
   .support-cards-grid{grid-template-columns:1fr}
   .form-row-2{grid-template-columns:1fr}
  }
  @media(max-width:768px){
   .support-header, .crm-topbar, .topbar {flex-direction:column;align-items:stretch;gap:14px}
   .support-heading, .crm-topbar-left, .topbar-left {width:100%;justify-content:flex-start}
   .support-header-actions, .crm-topbar-right, .top-actions {width:100%;justify-content:space-between;gap:8px}
   .crm-topbar-actions {display:flex;flex:1 1 auto;gap:8px;flex-wrap:wrap}
   .btn-primary-add, .btn-tailscale-net, .support-refresh {flex:1 1 auto;min-height:44px}
  }
  @media(max-width:620px){
   .support-main{padding:14px 10px 28px}
   .support-kpi-bar{grid-template-columns:1fr}
   .status-filter-tabs{overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%}
   .status-tab-btn{min-height:44px;padding:0 14px}
  }
  @media(max-width:430px){
   .btn-primary-add, .btn-tailscale-net, .support-refresh {width:100%;justify-content:center}
  }
  @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
 </style>
</head>
<body>
@include('partials.page-loader')
@php
 $errorKey = match ($snapshot['error'] ?? null) {
     'command_failed' => 'crm.tailscale_error_command_failed',
     'invalid_response' => 'crm.tailscale_error_invalid_response',
     'not_running' => 'crm.tailscale_error_not_running',
     default => 'crm.tailscale_error_unavailable',
 };
 $tailscaleIpsList = $snapshot['tailscaleIps'] ?? [];
 $rawTailscaleDevices = $snapshot['rawTailscaleDevices'] ?? [];
 $totalDevices = count($snapshot['devices'] ?? []);
 $onlineCount = (int) ($snapshot['onlineCount'] ?? 0);
 $warningCount = (int) ($snapshot['warningCount'] ?? 0);
 $offlineCount = (int) ($snapshot['offlineCount'] ?? 0);
@endphp
<div class="support-shell">
 @include('partials.crm-sidebar')
 <button class="crm-overlay" id="supportSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>
 <main class="support-main">
  @php
    ob_start();
  @endphp
    @can('technical_support.manage')
    <button type="button" class="btn primary btn-primary-add" id="btnOpenAddCardModal">
     <i class="bi bi-plus-circle" aria-hidden="true"></i>
     <span>{{ __('crm.add_support_card') }}</span>
    </button>
    @endcan
    @if(auth()->user()->isSuperAdmin())
    <button type="button" class="btn soft btn-tailscale-net" id="btnOpenTailscaleModal">
     <i class="bi bi-diagram-3" aria-hidden="true"></i>
     <span>شبكة Tailscale</span>
    </button>
    @endif
    <a class="btn soft support-refresh" href="{{ route('v2.technical-support.index', ['refresh' => 1]) }}">
     <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
     <span>{{ __('crm.refresh_status') }}</span>
    </a>
  @php
    $supportActions = ob_get_clean();
  @endphp
  @include('partials.topbar', [
    'title' => __('crm.technical_support'),
    'subtitle' => __('crm.tailscale_network_subtitle'),
    'icon' => 'bi-headset',
    'actions' => $supportActions
  ])

  <!-- Top Single-Row KPI Monitoring Strip -->
  <section class="support-kpi-bar" aria-label="{{ __('crm.servers_overview') }}">
   <button type="button" class="kpi-card-btn active-kpi" data-metric-filter="all" title="{{ __('crm.all') }}">
    <div class="kpi-icon"><i class="bi bi-hdd-stack" aria-hidden="true"></i></div>
    <div class="kpi-text">
     <span class="kpi-label">{{ __('crm.total_servers') }}</span>
     <strong class="kpi-value" id="kpiTotalVal">{{ number_format($totalDevices) }}</strong>
    </div>
   </button>

   <button type="button" class="kpi-card-btn online" data-metric-filter="online" title="{{ __('crm.servers_online') }}">
    <div class="kpi-icon"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></div>
    <div class="kpi-text">
     <span class="kpi-label">{{ __('crm.servers_online') }}</span>
     <strong class="kpi-value" id="kpiOnlineVal">{{ number_format($onlineCount) }}</strong>
    </div>
   </button>

   <button type="button" class="kpi-card-btn offline" data-metric-filter="offline" title="{{ __('crm.servers_offline') }}">
    <div class="kpi-icon"><i class="bi bi-x-circle-fill" aria-hidden="true"></i></div>
    <div class="kpi-text">
     <span class="kpi-label">{{ __('crm.servers_offline') }}</span>
     <strong class="kpi-value" id="kpiOfflineVal">{{ number_format($offlineCount) }}</strong>
    </div>
   </button>

   <button type="button" class="kpi-card-btn warning" data-metric-filter="warning" title="{{ __('crm.needs_attention') }}">
    <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></div>
    <div class="kpi-text">
     <span class="kpi-label">{{ __('crm.needs_attention') }}</span>
     <strong class="kpi-value" id="kpiWarningVal">{{ number_format($warningCount) }}</strong>
    </div>
   </button>
  </section>

  @if (! ($snapshot['available'] ?? false))
   <div class="support-alert" role="alert">
    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
    <div>
     <strong>{{ __('crm.tailscale_unavailable') }}</strong>
     <p>{{ __($errorKey) }}</p>
    </div>
   </div>
  @endif

  <section class="support-panel" aria-label="{{ __('crm.devices') }}">
   <!-- Search & Filters Toolbar -->
   <div class="support-toolbar">
    <div class="toolbar-left">
     <label class="support-search">
      <span class="visually-hidden">{{ __('crm.search_devices') }}</span>
      <i class="bi bi-search" aria-hidden="true"></i>
      <input id="deviceSearch" type="search" autocomplete="off" placeholder="{{ __('crm.search_devices_placeholder') }}">
     </label>
     
     <div class="status-filter-tabs" role="tablist" aria-label="{{ __('crm.devices') }}">
      <button class="status-tab-btn active" type="button" data-filter="all">
       <span>{{ __('crm.all') }}</span>
       <span class="status-tab-badge" id="badgeCountAll">{{ $totalDevices }}</span>
      </button>
      <button class="status-tab-btn" type="button" data-filter="online">
       <span>{{ __('crm.servers_online') }}</span>
       <span class="status-tab-badge" id="badgeCountOnline">{{ $onlineCount }}</span>
      </button>
      <button class="status-tab-btn" type="button" data-filter="offline">
       <span>{{ __('crm.servers_offline') }}</span>
       <span class="status-tab-badge" id="badgeCountOffline">{{ $offlineCount }}</span>
      </button>
      <button class="status-tab-btn" type="button" data-filter="warning">
       <span>{{ __('crm.needs_attention') }}</span>
       <span class="status-tab-badge" id="badgeCountWarning">{{ $warningCount }}</span>
      </button>
     </div>
    </div>

    <div class="toolbar-right">
     <select class="os-filter-select" id="osFilterSelect" aria-label="{{ __('crm.all_os') }}">
      <option value="all">{{ __('crm.all_os') }}</option>
      <option value="linux">Linux</option>
      <option value="windows">Windows</option>
      <option value="macos">macOS</option>
      <option value="android">Android</option>
      <option value="ios">iOS</option>
      <option value="other">Other</option>
     </select>

     <div class="view-switcher" role="group" aria-label="View Switcher">
      <button class="view-btn active" type="button" id="btnViewGrid" title="{{ __('crm.grid_view') }}" aria-pressed="true">
       <i class="bi bi-grid-fill"></i>
      </button>
      <button class="view-btn" type="button" id="btnViewTable" title="{{ __('crm.table_view') }}" aria-pressed="false">
       <i class="bi bi-table"></i>
      </button>
     </div>
    </div>
   </div>

   @if (count($snapshot['devices']) > 0)
    <!-- Grid View Mode -->
    <div class="support-content-area" id="gridContainer">
     <div class="support-cards-grid" id="supportCardsGrid">
      @foreach ($snapshot['devices'] as $device)
       @php
        $osSlug = strtolower((string) ($device['os'] ?? 'linux'));
        $osIcon = match ($osSlug) {
            'windows' => 'bi-windows',
            'macos', 'darwin' => 'bi-apple',
            'android' => 'bi-android2',
            'ios' => 'bi-phone',
            'linux', 'ubuntu', 'debian' => 'bi-ubuntu',
            default => 'bi-hdd-network',
        };
        $lastActivity = !empty($device['lastActivity'])
            ? \Illuminate\Support\Carbon::parse($device['lastActivity'])
                ->locale(app()->getLocale())
                ->diffForHumans()
            : __('crm.never_connected');
        
        $allIpsList = $device['all_ips'] ?? [];
        $ipStrings = array_map(fn($item) => is_array($item) ? ($item['ip'] ?? '') : (string)$item, $allIpsList);
        
        $companyName = $device['company_name'] ?? null;
        $employeesCount = (int) ($device['employees_count'] ?? 0);
        $linesCount = (int) ($device['lines_count'] ?? 0);
        $imagePath = $device['image_path'] ?? null;

        $searchText = strtolower(implode(' ', array_filter([
            $device['name'] ?? '',
            $companyName ?? '',
            $device['dnsName'] ?? '',
            $device['os'] ?? '',
            $device['notes'] ?? '',
            ...$ipStrings,
        ])));
        $healthStatus = (string) ($device['health_status'] ?? 'offline');
        $deviceKey = (string) ($device['id'] ?? '');
       @endphp

       <article 
        class="server-card" 
        data-device-card 
        data-device-key="{{ $deviceKey }}"
        data-status="{{ $healthStatus }}"
        data-os="{{ $osSlug }}"
        data-search="{{ $searchText }}"
        id="card-{{ $deviceKey }}"
       >
        <div class="server-card-top">
         <div class="card-head-row">
          <div class="card-identity-box">
           <span class="card-avatar">
            @if ($imagePath)
             <img src="{{ $imagePath }}" alt="{{ $device['name'] }}">
            @else
             <i class="bi {{ $osIcon }}" aria-hidden="true"></i>
            @endif
           </span>
           <div class="card-titles">
            <h2 class="card-server-name">
             <a href="{{ route('v2.technical-support.cards.show', $deviceKey) }}">
              {{ $device['name'] }}
             </a>
            </h2>
            @if ($companyName)
              <div class="card-company-pill company-name">
              <i class="bi bi-building"></i> <span>{{ $companyName }}</span>
             </div>
            @elseif (!empty($device['dnsName']))
             <span class="card-company-pill" style="direction:ltr">{{ $device['dnsName'] }}</span>
            @endif
           </div>
          </div>

          <div class="card-top-badges">
           <span class="health-badge {{ $healthStatus }}">
            @if ($healthStatus === 'online')
             {{ __('crm.servers_online') }}
            @elseif ($healthStatus === 'warning')
             {{ __('crm.needs_attention') }}
            @else
             {{ __('crm.servers_offline') }}
            @endif
           </span>

           <!-- Dropdown Menu -->
           @can('technical_support.manage')
           <div class="card-dropdown">
            <button type="button" class="btn-card-ellipsis" data-dropdown-toggle title="{{ __('crm.more_actions') }}">
             <i class="bi bi-three-dots-vertical"></i>
            </button>
            <div class="card-menu-dropdown">
             <button 
              type="button" 
              class="dropdown-item"
              data-edit-card-btn
              data-device-key="{{ $deviceKey }}"
              data-company="{{ $companyName }}"
              data-name="{{ $device['name'] }}"
              data-employees="{{ $employeesCount }}"
              data-lines="{{ $linesCount }}"
              data-os="{{ $device['os'] ?? 'linux' }}"
              data-dns="{{ $device['dnsName'] ?? '' }}"
              data-notes="{{ $device['notes'] ?? '' }}"
             >
              <i class="bi bi-pencil-square"></i>
              <span>{{ __('crm.edit') ?? 'تعديل' }}</span>
             </button>

             <button 
              type="button" 
              class="dropdown-item danger" 
              data-delete-device-key="{{ $deviceKey }}"
             >
              <i class="bi bi-trash3"></i>
              <span>{{ __('crm.delete') }}</span>
             </button>
            </div>
           </div>
           @endcan
          </div>
         </div>

         <div class="server-specs-grid">
          <div class="spec-item">
           <span class="spec-label">{{ __('crm.operating_system') }}</span>
           <span class="spec-val"><i class="bi {{ $osIcon }}"></i> {{ ucfirst($device['os'] ?? 'linux') }}</span>
          </div>
          <div class="spec-item">
           <span class="spec-label">{{ __('crm.last_activity') }}</span>
           <span class="spec-val {{ $healthStatus === 'online' ? 'live' : '' }}">
            {{ $healthStatus === 'online' ? __('crm.active_connection') : $lastActivity }}
           </span>
          </div>
         </div>

         @if ($employeesCount > 0 || $linesCount > 0)
          <div class="server-stats-pill">
           @if ($employeesCount > 0)
            <span><i class="bi bi-people"></i> {{ number_format($employeesCount) }} {{ __('crm.employees') }}</span>
           @endif
           @if ($linesCount > 0)
            <span><i class="bi bi-telephone"></i> {{ number_format($linesCount) }} {{ __('crm.lines') }}</span>
           @endif
          </div>
         @endif
        </div>

        <div class="server-card-ip-section">
         @if (count($allIpsList) > 0)
          @foreach ($allIpsList as $ipItem)
           @php
            $ipVal = is_array($ipItem) ? ($ipItem['ip'] ?? '') : (string)$ipItem;
            $ipLabel = is_array($ipItem) ? ($ipItem['label'] ?? null) : null;
           @endphp
           <div class="ip-chip">
            <div class="ip-chip-content">
             <code>{{ $ipVal }}</code>
             @if (!empty($ipLabel))
              <span class="ip-chip-tag">{{ $ipLabel }}</span>
             @endif
            </div>
            <button 
             type="button" 
             class="btn-ip-action btn-copy" 
             data-copy-ip="{{ $ipVal }}" 
             title="{{ __('crm.copy_ip') }}"
             aria-label="{{ __('crm.copy_ip') }}: {{ $ipVal }}"
            >
             <i class="bi bi-clipboard" aria-hidden="true"></i>
            </button>
           </div>
          @endforeach
         @else
          <div class="no-ip-row">
           <span class="no-ip-text">IP: {{ __('crm.ip_not_specified') }}</span>
           @can('technical_support.manage')
           <button 
            type="button" 
            class="btn-add-ip-inline" 
            data-edit-card-btn
            data-device-key="{{ $deviceKey }}"
            data-company="{{ $companyName }}"
            data-name="{{ $device['name'] }}"
            data-employees="{{ $employeesCount }}"
            data-lines="{{ $linesCount }}"
            data-os="{{ $device['os'] ?? 'linux' }}"
            data-dns="{{ $device['dnsName'] ?? '' }}"
            data-notes="{{ $device['notes'] ?? '' }}"
           >
            + {{ __('crm.add_ip') }}
           </button>
           @endcan
          </div>
         @endif
        </div>

        <div class="server-card-footer">
         <a href="{{ route('v2.technical-support.cards.show', $deviceKey) }}" class="btn-details-primary">
          <span>{{ __('crm.view_card_details') }}</span>
          <i class="bi bi-arrow-left ltr:rotate-180"></i>
         </a>
         @can('technical_support.manage')
         <button 
          type="button" 
          class="btn-quick-edit"
          data-edit-card-btn
          data-device-key="{{ $deviceKey }}"
          data-company="{{ $companyName }}"
          data-name="{{ $device['name'] }}"
          data-employees="{{ $employeesCount }}"
          data-lines="{{ $linesCount }}"
          data-os="{{ $device['os'] ?? 'linux' }}"
          data-dns="{{ $device['dnsName'] ?? '' }}"
          data-notes="{{ $device['notes'] ?? '' }}"
          title="{{ __('crm.edit') }}"
         >
          <i class="bi bi-pencil-square"></i>
         </button>
         @endcan
        </div>
       </article>
      @endforeach
     </div>
    </div>

    <!-- Table View Mode -->
    <div class="support-table-container" id="tableContainer">
     <table class="support-table">
      <thead>
       <tr>
        <th>{{ __('crm.device') }}</th>
        <th>{{ __('crm.company_name') }}</th>
        <th>{{ __('crm.status') ?? 'الحالة' }}</th>
        <th>{{ __('crm.ip_addresses') }}</th>
        <th>{{ __('crm.operating_system') }}</th>
        <th>{{ __('crm.last_activity') }}</th>
        <th style="text-align:end">{{ __('crm.actions') ?? 'الإجراءات' }}</th>
       </tr>
      </thead>
      <tbody>
       @foreach ($snapshot['devices'] as $device)
        @php
         $osSlug = strtolower((string) ($device['os'] ?? 'linux'));
         $osIcon = match ($osSlug) {
             'windows' => 'bi-windows',
             'macos', 'darwin' => 'bi-apple',
             'android' => 'bi-android2',
             'ios' => 'bi-phone',
             'linux', 'ubuntu', 'debian' => 'bi-ubuntu',
             default => 'bi-hdd-network',
         };
         $lastActivity = !empty($device['lastActivity'])
             ? \Illuminate\Support\Carbon::parse($device['lastActivity'])
                 ->locale(app()->getLocale())
                 ->diffForHumans()
             : __('crm.never_connected');
         
         $allIpsList = $device['all_ips'] ?? [];
         $ipStrings = array_map(fn($item) => is_array($item) ? ($item['ip'] ?? '') : (string)$item, $allIpsList);
         $companyName = $device['company_name'] ?? null;
         $healthStatus = (string) ($device['health_status'] ?? 'offline');
         $deviceKey = (string) ($device['id'] ?? '');
         $searchText = strtolower(implode(' ', array_filter([
             $device['name'] ?? '',
             $companyName ?? '',
             $device['dnsName'] ?? '',
             $device['os'] ?? '',
             $device['notes'] ?? '',
             ...$ipStrings,
         ])));
        @endphp
        <tr 
         data-device-card
         data-device-key="{{ $deviceKey }}"
         data-status="{{ $healthStatus }}"
         data-os="{{ $osSlug }}"
         data-search="{{ $searchText }}"
        >
         <td>
          <div style="display:flex;align-items:center;gap:10px">
           <i class="bi {{ $osIcon }}" style="font-size:18px;color:var(--support-muted)"></i>
           <div>
            <strong style="display:block;font-size:14px">
             <a href="{{ route('v2.technical-support.cards.show', $deviceKey) }}" style="color:inherit;text-decoration:none">
              {{ $device['name'] }}
             </a>
            </strong>
            @if (!empty($device['dnsName']))
             <small style="color:var(--support-muted);direction:ltr;display:inline-block">{{ $device['dnsName'] }}</small>
            @endif
           </div>
          </div>
         </td>
         <td>
          @if ($companyName)
           <span style="font-weight:700">{{ $companyName }}</span>
          @else
           <span style="color:var(--support-muted)">—</span>
          @endif
         </td>
         <td>
          <span class="health-badge {{ $healthStatus }}">
           @if ($healthStatus === 'online')
            {{ __('crm.servers_online') }}
           @elseif ($healthStatus === 'warning')
            {{ __('crm.needs_attention') }}
           @else
            {{ __('crm.servers_offline') }}
           @endif
          </span>
         </td>
         <td>
          @if (count($allIpsList) > 0)
           <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
            @foreach ($allIpsList as $ipItem)
             @php
              $ipVal = is_array($ipItem) ? ($ipItem['ip'] ?? '') : (string)$ipItem;
             @endphp
             <code style="padding:3px 7px;border-radius:6px;background:var(--support-soft);border:1px solid var(--support-line);font-size:12px;direction:ltr">{{ $ipVal }}</code>
             <button type="button" class="btn-ip-action btn-copy" data-copy-ip="{{ $ipVal }}" title="{{ __('crm.copy_ip') }}" style="width:24px;height:24px;font-size:11px">
              <i class="bi bi-clipboard"></i>
             </button>
            @endforeach
           </div>
          @else
           <span style="color:var(--support-muted);font-size:12px">{{ __('crm.ip_not_specified') }}</span>
          @endif
         </td>
         <td>
          <span style="text-transform:capitalize;font-weight:700">{{ $device['os'] ?? 'Linux' }}</span>
         </td>
         <td>
          <span style="font-size:12.5px;color:var(--support-muted)">
           {{ $healthStatus === 'online' ? __('crm.active_connection') : $lastActivity }}
          </span>
         </td>
         <td style="text-align:end">
          <div style="display:inline-flex;align-items:center;gap:6px">
           <a href="{{ route('v2.technical-support.cards.show', $deviceKey) }}" class="btn-card-ellipsis" title="{{ __('crm.view_card_details') }}" style="display:grid;place-items:center;text-decoration:none">
            <i class="bi bi-eye"></i>
           </a>
           @can('technical_support.manage')
           <button 
            type="button" 
            class="btn-card-ellipsis" 
            data-edit-card-btn
            data-device-key="{{ $deviceKey }}"
            data-company="{{ $companyName }}"
            data-name="{{ $device['name'] }}"
            data-employees="{{ (int) ($device['employees_count'] ?? 0) }}"
            data-lines="{{ (int) ($device['lines_count'] ?? 0) }}"
            data-os="{{ $device['os'] ?? 'linux' }}"
            data-dns="{{ $device['dnsName'] ?? '' }}"
            data-notes="{{ $device['notes'] ?? '' }}"
            title="{{ __('crm.edit') }}"
           >
            <i class="bi bi-pencil-square"></i>
           </button>
           <button type="button" class="btn-card-ellipsis" data-delete-device-key="{{ $deviceKey }}" title="{{ __('crm.delete') }}" style="color:var(--support-red)">
            <i class="bi bi-trash3"></i>
           </button>
           @endcan
          </div>
         </td>
        </tr>
       @endforeach
      </tbody>
     </table>
    </div>

    <div class="support-empty support-no-match" id="noDeviceMatches" hidden>
     <div>
      <i class="bi bi-search" aria-hidden="true"></i>
      <strong>{{ __('crm.no_devices_match') }}</strong>
     </div>
    </div>
   @else
    <div class="support-empty">
     <div>
      <i class="bi bi-hdd-network" aria-hidden="true"></i>
      <strong>{{ __('crm.no_devices_found') }}</strong>
      <p>{{ __('crm.tailscale_network_subtitle') }}</p>
     </div>
    </div>
   @endif
  </section>
 </main>
</div>

<!-- Add Support Card Modal -->
<div class="support-modal-backdrop" id="addCardModal" role="dialog" aria-modal="true" aria-labelledby="addCardModalTitle">
 <div class="support-modal">
  <div class="modal-header">
   <h3 id="addCardModalTitle">{{ __('crm.create_support_card') }}</h3>
   <button type="button" class="btn-modal-close" id="btnCloseAddCardModal" aria-label="{{ __('crm.cancel') }}">
    <i class="bi bi-x-lg"></i>
   </button>
  </div>
  <form id="formCreateCard" method="POST" action="{{ route('v2.technical-support.devices.store') }}" enctype="multipart/form-data">
   @csrf
   <div class="modal-body">
    <div class="form-group">
     <label for="inputCompanyName">{{ __('crm.company_name') }}</label>
     <input type="text" id="inputCompanyName" name="company_name" class="form-control" placeholder="{{ __('crm.company_name_placeholder') }}" autocomplete="off">
    </div>

    <div class="form-group">
     <label for="inputCardName">{{ __('crm.card_name') }} *</label>
     <input type="text" id="inputCardName" name="name" class="form-control" placeholder="{{ __('crm.card_name_placeholder') }}" required autocomplete="off">
    </div>

    <div class="form-group">
     <label for="inputCardImage">{{ __('crm.card_image') }}</label>
     <input type="file" id="inputCardImage" name="image" class="form-control" accept="image/*">
    </div>

    <div class="form-row-2">
     <div class="form-group">
      <label for="inputEmployeesCount">{{ __('crm.employees_count') }}</label>
      <input type="number" id="inputEmployeesCount" name="employees_count" class="form-control" placeholder="0" min="0" value="0">
     </div>
     <div class="form-group">
      <label for="inputLinesCount">{{ __('crm.lines_count') }}</label>
      <input type="number" id="inputLinesCount" name="lines_count" class="form-control" placeholder="0" min="0" value="0">
     </div>
    </div>
    
    <div class="form-group">
     <label for="selectCardOs">{{ __('crm.operating_system') }} *</label>
     <select id="selectCardOs" name="os" class="form-control" required>
      <option value="linux">Linux / Ubuntu / Debian</option>
      <option value="windows">Windows</option>
      <option value="macos">macOS / Apple</option>
      <option value="android">Android</option>
      <option value="ios">iOS / iPhone / iPad</option>
      <option value="other">Other / Network Device</option>
     </select>
    </div>

    @if (count($tailscaleIpsList) > 0)
     <div class="form-group">
      <label for="selectTailscaleIp">{{ __('crm.select_tailscale_ip') }}</label>
      <select id="selectTailscaleIp" name="selected_tailscale_ip" class="form-control">
        <option value="">{{ __('crm.choose_tailscale_ip_placeholder') }}</option>
        @foreach ($tailscaleIpsList as $tsItem)
         <option value="{{ $tsItem['ip'] }}">{{ $tsItem['ip'] }}</option>
        @endforeach
      </select>
     </div>
    @endif

    <div class="form-group">
     <label for="inputInitialIp">{{ __('crm.initial_ip') }} <span>({{ __('crm.or_enter_custom_ip') }})</span></label>
     <input type="text" id="inputInitialIp" name="initial_ip" class="form-control" placeholder="192.168.1.10" autocomplete="off" style="direction:ltr">
    </div>

    <div class="form-group">
     <label for="inputInitialIpLabel">{{ __('crm.ip_label_placeholder') }}</label>
     <input type="text" id="inputInitialIpLabel" name="initial_ip_label" class="form-control" placeholder="LAN / WAN / Backup" autocomplete="off">
    </div>

    <div class="form-group">
     <label for="inputCardNotes">{{ __('crm.notes') }}</label>
     <textarea id="inputCardNotes" name="notes" class="form-control" placeholder="{{ __('crm.notes_placeholder') }}"></textarea>
    </div>
   </div>
   <div class="modal-footer">
    <button type="button" class="btn soft btn-card-ellipsis" id="btnCancelAddCardModal" style="width:auto;padding:0 14px;height:38px">{{ __('crm.cancel') }}</button>
    <button type="submit" class="btn primary btn-primary-add">
     <i class="bi bi-check-lg"></i>
     <span>{{ __('crm.create_support_card') }}</span>
    </button>
   </div>
  </form>
 </div>
</div>

<!-- Edit Support Card Modal -->
<div class="support-modal-backdrop" id="editIndexCardModal" role="dialog" aria-modal="true" aria-labelledby="editIndexCardModalTitle">
 <div class="support-modal">
  <div class="modal-header">
   <h3 id="editIndexCardModalTitle">{{ __('crm.edit_support_card') }}</h3>
   <button type="button" class="btn-modal-close" id="btnCloseIndexEditModal" aria-label="{{ __('crm.cancel') }}">
    <i class="bi bi-x-lg"></i>
   </button>
  </div>
  <form id="formIndexEditCard" method="POST" enctype="multipart/form-data">
   @csrf
   @method('PUT')
   <div class="modal-body">
    <div class="form-group">
     <label for="editIndexCompanyName">{{ __('crm.company_name') }}</label>
     <input type="text" id="editIndexCompanyName" name="company_name" class="form-control" placeholder="{{ __('crm.company_name_placeholder') }}" autocomplete="off">
    </div>

    <div class="form-group">
     <label for="editIndexCardName">{{ __('crm.card_name') }} *</label>
     <input type="text" id="editIndexCardName" name="name" class="form-control" required autocomplete="off">
    </div>

    <div class="form-group">
     <label for="editIndexCardImage">{{ __('crm.card_image') }}</label>
     <input type="file" id="editIndexCardImage" name="image" class="form-control" accept="image/*">
    </div>

    <div class="form-row-2">
     <div class="form-group">
      <label for="editIndexEmployeesCount">{{ __('crm.employees_count') }}</label>
      <input type="number" id="editIndexEmployeesCount" name="employees_count" class="form-control" min="0">
     </div>
     <div class="form-group">
      <label for="editIndexLinesCount">{{ __('crm.lines_count') }}</label>
      <input type="number" id="editIndexLinesCount" name="lines_count" class="form-control" min="0">
     </div>
    </div>

    <div class="form-group">
     <label for="editIndexCardOs">{{ __('crm.operating_system') }} *</label>
     <select id="editIndexCardOs" name="os" class="form-control" required>
      <option value="linux">Linux / Ubuntu / Debian</option>
      <option value="windows">Windows</option>
      <option value="macos">macOS / Apple</option>
      <option value="android">Android</option>
      <option value="ios">iOS / iPhone / iPad</option>
      <option value="other">Other / Network Device</option>
     </select>
    </div>

    @if (count($tailscaleIpsList) > 0)
     <div class="form-group">
      <label for="editIndexTailscaleIp">{{ __('crm.select_tailscale_ip') }}</label>
      <select id="editIndexTailscaleIp" name="selected_tailscale_ip" class="form-control">
        <option value="">{{ __('crm.choose_tailscale_ip_placeholder') }}</option>
        @foreach ($tailscaleIpsList as $tsItem)
         <option value="{{ $tsItem['ip'] }}">{{ $tsItem['ip'] }}</option>
        @endforeach
      </select>
     </div>
    @endif

    <div class="form-row-2">
     <div class="form-group">
      <label for="editIndexNewIp">{{ __('crm.add_ip') }} ({{ __('crm.custom_ip') }})</label>
      <input type="text" id="editIndexNewIp" name="new_ip_address" class="form-control" placeholder="192.168.1.10" style="direction:ltr">
     </div>
     <div class="form-group">
      <label for="editIndexNewIpLabel">{{ __('crm.ip_label_placeholder') }}</label>
      <input type="text" id="editIndexNewIpLabel" name="new_ip_label" class="form-control" placeholder="LAN / Backup">
     </div>
    </div>

    <div class="form-group">
     <label for="editIndexCardDns">{{ __('crm.dns_or_hostname') }}</label>
     <input type="text" id="editIndexCardDns" name="dns_name" class="form-control" autocomplete="off">
    </div>

    <div class="form-group">
     <label for="editIndexCardNotes">{{ __('crm.notes') }}</label>
     <textarea id="editIndexCardNotes" name="notes" class="form-control"></textarea>
    </div>
   </div>
   <div class="modal-footer">
    <button type="button" class="btn soft btn-card-ellipsis" id="btnCancelIndexEditModal" style="width:auto;padding:0 14px;height:38px">{{ __('crm.cancel') }}</button>
    <button type="submit" class="btn primary btn-primary-add">
     <i class="bi bi-check-lg"></i>
     <span>{{ __('crm.save') ?? 'حفظ' }}</span>
    </button>
   </div>
  </form>
 </div>
</div>

<!-- Tailscale Network Modal -->
<div class="support-modal-backdrop" id="modalTailscaleNetwork" role="dialog" aria-modal="true" aria-labelledby="tsModalTitle">
 <div class="support-modal" style="max-width:720px">
  <div class="modal-header">
   <h3 id="tsModalTitle" style="display:flex;align-items:center;gap:8px">
    <i class="bi bi-diagram-3" style="color:#2563eb"></i>
    <span>شبكة Tailscale الحالية</span>
   </h3>
   <button type="button" class="btn-modal-close" id="btnCloseTsModal" aria-label="{{ __('crm.cancel') }}">
    <i class="bi bi-x-lg"></i>
   </button>
  </div>
  <div class="modal-body" style="padding:18px 22px">
   <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px">
    <p style="margin:0;font-size:13px;color:var(--support-muted)">
     عرض الأجهزة وعناوين الـ IP المكتشفة حياً في شبكة Tailscale الخاصة بالسيرفر.
    </p>
    <input type="search" id="tsModalSearchInput" placeholder="بحث في الأجهزة..." class="form-control" style="max-width:200px;height:36px;font-size:12px">
   </div>

   <div style="max-height:420px;overflow-y:auto;border:1px solid var(--support-line);border-radius:12px">
    <table class="ts-table" id="tsModalTable">
     <thead>
      <tr>
       <th>اسم الجهاز</th>
       <th>عناوين الـ IP</th>
       <th>نظام التشغيل</th>
       <th>الحالة</th>
      </tr>
     </thead>
     <tbody>
      @forelse ($rawTailscaleDevices as $tsDev)
       @php
        $tsOnline = (bool) ($tsDev['online'] ?? false);
        $tsIps = $tsDev['ips'] ?? [];
        $tsSearch = strtolower(implode(' ', array_filter([$tsDev['name'] ?? '', $tsDev['dnsName'] ?? '', ...$tsIps])));
       @endphp
       <tr data-ts-row data-ts-search="{{ $tsSearch }}">
        <td>
         <strong style="font-size:15px">{{ $tsDev['name'] }}</strong>
         @if (!empty($tsDev['dnsName']))
          <br><span class="ts-dns-name" style="color:var(--support-muted);font-size:13px;font-weight:600;direction:ltr;display:inline-block;margin-top:2px">{{ $tsDev['dnsName'] }}</span>
         @endif
        </td>
        <td>
         @foreach ($tsIps as $tsIp)
          <code style="display:inline-block;padding:3px 7px;margin:2px;border-radius:6px;background:var(--support-soft);font:700 11px ui-monospace,monospace;direction:ltr">{{ $tsIp }}</code>
         @endforeach
        </td>
        <td><span style="text-transform:capitalize">{{ $tsDev['os'] ?? 'unknown' }}</span></td>
        <td>
         <span class="health-badge {{ $tsOnline ? 'online' : 'offline' }}">
          {{ $tsOnline ? __('crm.servers_online') : __('crm.servers_offline') }}
         </span>
        </td>
       </tr>
      @empty
       <tr>
        <td colspan="4" style="text-align:center;padding:24px;color:var(--support-muted)">
         {{ __('crm.no_devices_found') }}
        </td>
       </tr>
      @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div class="modal-footer">
   <button type="button" class="btn-card-ellipsis" id="btnCancelTsModal" style="width:auto;padding:0 14px;height:38px">{{ __('crm.close') ?? 'إغلاق' }}</button>
  </div>
 </div>
</div>

<!-- Toast Container -->
<div class="support-toast-container" id="supportToastContainer"></div>

<script>
 (() => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  
  // Toast notifications
  const toastContainer = document.getElementById('supportToastContainer');
  const showToast = (message, type = 'success') => {
   if (!toastContainer) return;
   const toast = document.createElement('div');
   toast.className = `support-toast ${type}`;
   const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
   toast.innerHTML = `<i class="bi ${iconClass}"></i><span>${message}</span>`;
   toastContainer.appendChild(toast);
   setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity .3s ease-out';
    setTimeout(() => toast.remove(), 300);
   }, 3000);
  };

  // Sidebar toggle
  const menu = document.getElementById('supportSidebarMenu');
  const overlay = document.getElementById('supportSidebarOverlay');
  const sidebar = document.getElementById('crmSidebar');
  const mobileSidebar = window.matchMedia('(max-width: 900px)');

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
   if (open) {
    window.requestAnimationFrame(() => sidebar?.querySelector('a, button')?.focus());
   }
  });
  overlay?.addEventListener('click', () => closeSidebar());
  document.addEventListener('keydown', (event) => {
   if (event.key === 'Escape' && document.body.classList.contains('crm-side-open')) {
    closeSidebar();
   }
  });
  mobileSidebar.addEventListener('change', () => closeSidebar(false));
  syncSidebarAccess(false);

  // View Switcher (Grid / Table) with localStorage persistence
  const btnViewGrid = document.getElementById('btnViewGrid');
  const btnViewTable = document.getElementById('btnViewTable');
  const gridContainer = document.getElementById('gridContainer');
  const tableContainer = document.getElementById('tableContainer');

  const setViewMode = (mode) => {
   if (mode === 'table') {
    gridContainer.style.display = 'none';
    tableContainer.style.display = 'block';
    btnViewTable.classList.add('active');
    btnViewTable.setAttribute('aria-pressed', 'true');
    btnViewGrid.classList.remove('active');
    btnViewGrid.setAttribute('aria-pressed', 'false');
   } else {
    gridContainer.style.display = 'block';
    tableContainer.style.display = 'none';
    btnViewGrid.classList.add('active');
    btnViewGrid.setAttribute('aria-pressed', 'true');
    btnViewTable.classList.remove('active');
    btnViewTable.setAttribute('aria-pressed', 'false');
   }
   try {
    localStorage.setItem('sokrat.crm.support.view_mode', mode);
   } catch (e) {}
  };

  btnViewGrid?.addEventListener('click', () => setViewMode('grid'));
  btnViewTable?.addEventListener('click', () => setViewMode('table'));

  try {
   const savedView = localStorage.getItem('sokrat.crm.support.view_mode');
   if (savedView === 'table') setViewMode('table');
  } catch (e) {}

  // Search & Filtering
  const search = document.getElementById('deviceSearch');
  const osFilter = document.getElementById('osFilterSelect');
  const cards = [...document.querySelectorAll('[data-device-card]')];
  const filterTabs = [...document.querySelectorAll('[data-filter]')];
  const kpiBtns = [...document.querySelectorAll('[data-metric-filter]')];
  const noMatches = document.getElementById('noDeviceMatches');
  let activeFilter = 'all';

  const applyFilters = () => {
   const query = (search?.value || '').trim().toLowerCase();
   const selectedOs = osFilter?.value || 'all';
   let visible = 0;

   cards.forEach((card) => {
    const status = card.dataset.status || 'offline';
    const os = card.dataset.os || 'linux';
    const matchesStatus = activeFilter === 'all' || status === activeFilter;
    const matchesOs = selectedOs === 'all' || os === selectedOs;
    const matchesSearch = !query || (card.dataset.search || '').includes(query);
    const show = matchesStatus && matchesOs && matchesSearch;

    card.style.display = show ? '' : 'none';
    if (show) visible += 1;
   });

   if (noMatches) noMatches.hidden = visible !== 0;

   // Sync filter tabs
   filterTabs.forEach((btn) => {
    const active = (btn.dataset.filter || 'all') === activeFilter;
    btn.classList.toggle('active', active);
   });

   // Sync KPI buttons
   kpiBtns.forEach((btn) => {
    const active = (btn.dataset.metricFilter || 'all') === activeFilter;
    btn.classList.toggle('active-kpi', active);
   });
  };

  search?.addEventListener('input', applyFilters);
  osFilter?.addEventListener('change', applyFilters);

  filterTabs.forEach((btn) => btn.addEventListener('click', () => {
   activeFilter = btn.dataset.filter || 'all';
   applyFilters();
  }));

  kpiBtns.forEach((btn) => btn.addEventListener('click', () => {
   activeFilter = btn.dataset.metricFilter || 'all';
   applyFilters();
  }));

  // Dropdown menu handling
  document.addEventListener('click', (e) => {
   const toggleBtn = e.target.closest('[data-dropdown-toggle]');
   const allDropdowns = document.querySelectorAll('.card-menu-dropdown');
   
   if (toggleBtn) {
    e.stopPropagation();
    const dropdown = toggleBtn.nextElementSibling;
    const isShown = dropdown.classList.contains('show');
    allDropdowns.forEach(d => d.classList.remove('show'));
    if (!isShown) dropdown.classList.add('show');
    return;
   }
   
   if (!e.target.closest('.card-menu-dropdown')) {
    allDropdowns.forEach(d => d.classList.remove('show'));
   }
  });

  // Tailscale Modal Search Filter
  const tsSearchInput = document.getElementById('tsModalSearchInput');
  const tsRows = [...document.querySelectorAll('[data-ts-row]')];

  tsSearchInput?.addEventListener('input', () => {
   const query = tsSearchInput.value.trim().toLowerCase();
   tsRows.forEach((row) => {
    const show = !query || (row.dataset.tsSearch || '').includes(query);
    row.style.display = show ? '' : 'none';
   });
  });

  // Copy IP to Clipboard with robust fallback
  const copyTextToClipboard = async (text) => {
   if (navigator.clipboard && window.isSecureContext) {
    await navigator.clipboard.writeText(text);
    return true;
   }
   const textArea = document.createElement('textarea');
   textArea.value = text;
   textArea.style.position = 'fixed';
   textArea.style.insetInlineStart = '-999999px';
   textArea.style.top = '-999999px';
   document.body.appendChild(textArea);
   textArea.focus();
   textArea.select();
   let successful = false;
   try {
    successful = document.execCommand('copy');
   } catch (err) {
    successful = false;
   }
   document.body.removeChild(textArea);
   return successful;
  };

  document.addEventListener('click', async (e) => {
   const copyBtn = e.target.closest('[data-copy-ip]');
   if (!copyBtn) return;
   const ip = copyBtn.dataset.copyIp;
   if (!ip) return;
   
   try {
    const ok = await copyTextToClipboard(ip);
    if (!ok) throw new Error('Copy execCommand failed');
    
    const icon = copyBtn.querySelector('i');
    copyBtn.classList.add('copied');
    if (icon) icon.className = 'bi bi-check2';
    
    showToast('{{ __("crm.ip_copied") }}: ' + ip, 'success');
    
    setTimeout(() => {
     copyBtn.classList.remove('copied');
     if (icon) icon.className = 'bi bi-clipboard';
    }, 1600);
   } catch (err) {
    showToast('{{ __("crm.copy_failed") }}', 'error');
   }
  });

  // Delete Support Card via AJAX
  document.addEventListener('click', async (e) => {
   const deleteDevBtn = e.target.closest('[data-delete-device-key]');
   if (!deleteDevBtn) return;

   if (!confirm('{{ __("crm.delete_card_confirm") }}')) return;

   const devKey = deleteDevBtn.dataset.deleteDeviceKey;
   const cardElements = document.querySelectorAll(`[data-device-key="${devKey}"]`);

   deleteDevBtn.disabled = true;

   try {
    const res = await fetch(`{{ url('/technical-support/cards') }}/${devKey}`, {
     method: 'DELETE',
     headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
     },
    });

    const data = await res.json();
    if (res.ok && data.success) {
     cardElements.forEach(el => el.remove());
     showToast(data.message || '{{ __("crm.device_deleted_successfully") }}', 'success');
     
     // Update metrics
     const totalMetric = document.getElementById('kpiTotalVal');
     const badgeAll = document.getElementById('badgeCountAll');
     if (totalMetric) {
      const newVal = Math.max(0, parseInt(totalMetric.textContent || '0', 10) - 1);
      totalMetric.textContent = newVal;
      if (badgeAll) badgeAll.textContent = newVal;
     }
    }
   } catch (err) {
    showToast('Failed to delete card', 'error');
   }
  });

  // Modal: Add Support Card
  const modal = document.getElementById('addCardModal');
  const btnOpenModal = document.getElementById('btnOpenAddCardModal');
  const btnCloseModal = document.getElementById('btnCloseAddCardModal');
  const btnCancelModal = document.getElementById('btnCancelAddCardModal');

  const openModal = () => {
   if (!modal) return;
   modal.classList.add('active');
   document.body.style.overflow = 'hidden';
   setTimeout(() => document.getElementById('inputCompanyName')?.focus(), 100);
  };

  const closeModal = () => {
   if (!modal) return;
   modal.classList.remove('active');
   document.body.style.overflow = '';
  };

  btnOpenModal?.addEventListener('click', openModal);
  btnCloseModal?.addEventListener('click', closeModal);
  btnCancelModal?.addEventListener('click', closeModal);

  modal?.addEventListener('click', (e) => {
   if (e.target === modal) closeModal();
  });

  // Modal: Edit Support Card
  const indexEditModal = document.getElementById('editIndexCardModal');
  const btnCloseIndexEdit = document.getElementById('btnCloseIndexEditModal');
  const btnCancelIndexEdit = document.getElementById('btnCancelIndexEditModal');
  const formIndexEdit = document.getElementById('formIndexEditCard');
  let currentEditDeviceKey = '';

  document.addEventListener('click', (e) => {
   const editBtn = e.target.closest('[data-edit-card-btn]');
   if (!editBtn) return;

   currentEditDeviceKey = editBtn.dataset.deviceKey;
   document.getElementById('editIndexCompanyName').value = editBtn.dataset.company || '';
   document.getElementById('editIndexCardName').value = editBtn.dataset.name || '';
   document.getElementById('editIndexEmployeesCount').value = editBtn.dataset.employees || '0';
   document.getElementById('editIndexLinesCount').value = editBtn.dataset.lines || '0';
   document.getElementById('editIndexCardOs').value = editBtn.dataset.os || 'linux';
   document.getElementById('editIndexCardDns').value = editBtn.dataset.dns || '';
   document.getElementById('editIndexCardNotes').value = editBtn.dataset.notes || '';

   if (formIndexEdit) {
    formIndexEdit.action = `{{ url('/technical-support/cards') }}/${currentEditDeviceKey}`;
   }

   indexEditModal?.classList.add('active');
   document.body.style.overflow = 'hidden';
  });

  const closeIndexEditModal = () => {
   indexEditModal?.classList.remove('active');
   document.body.style.overflow = '';
  };

  btnCloseIndexEdit?.addEventListener('click', closeIndexEditModal);
  btnCancelIndexEdit?.addEventListener('click', closeIndexEditModal);

  indexEditModal?.addEventListener('click', (e) => {
   if (e.target === indexEditModal) closeIndexEditModal();
  });

  // Submit Create / Edit forms with FormData for image upload
  document.getElementById('formCreateCard')?.addEventListener('submit', async (e) => {
   e.preventDefault();
   const form = e.target;
   const formData = new FormData(form);

   try {
    const res = await fetch(form.action, {
     method: 'POST',
     headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
     },
     body: formData,
    });

    const data = await res.json();
    if (res.ok && data.success) {
     window.location.reload();
    } else {
     showToast(data.message || 'Failed to create card', 'error');
    }
   } catch (err) {
    showToast('Failed to create card', 'error');
   }
  });

  formIndexEdit?.addEventListener('submit', async (e) => {
   e.preventDefault();
   const formData = new FormData(formIndexEdit);

   try {
    const res = await fetch(formIndexEdit.action, {
     method: 'POST',
     headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
     },
     body: formData,
    });

    const data = await res.json();
    if (res.ok && data.success) {
     window.location.reload();
    } else {
     showToast(data.message || 'Failed to update card', 'error');
    }
   } catch (err) {
    showToast('Failed to update card', 'error');
   }
  });

  // Modal: Tailscale Network
  const tsModal = document.getElementById('modalTailscaleNetwork');
  const btnOpenTsModal = document.getElementById('btnOpenTailscaleModal');
  const btnCloseTsModal = document.getElementById('btnCloseTsModal');
  const btnCancelTsModal = document.getElementById('btnCancelTsModal');

  btnOpenTsModal?.addEventListener('click', () => {
   tsModal?.classList.add('active');
   document.body.style.overflow = 'hidden';
  });
  btnCloseTsModal?.addEventListener('click', () => {
   tsModal?.classList.remove('active');
   document.body.style.overflow = '';
  });
  btnCancelTsModal?.addEventListener('click', () => {
   tsModal?.classList.remove('active');
   document.body.style.overflow = '';
  });

  tsModal?.addEventListener('click', (e) => {
   if (e.target === tsModal) {
    tsModal.classList.remove('active');
    document.body.style.overflow = '';
   }
  });

  document.addEventListener('keydown', (e) => {
   if (e.key === 'Escape') {
    if (modal?.classList.contains('active')) closeModal();
    if (indexEditModal?.classList.contains('active')) closeIndexEditModal();
    if (tsModal?.classList.contains('active')) {
     tsModal.classList.remove('active');
     document.body.style.overflow = '';
    }
   }
  });
 })();
</script>
</body>
</html>
