@php
 $crmSidebarAssetsLoaded = true;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta name="csrf-token" content="{{ csrf_token() }}">
 <title>{{ __('crm.support_team_title') }} - SokratCRM</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
 <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <style>
  *{box-sizing:border-box}
  :root{
   --team-red:#dc2637;
   --team-red-dark:#b81829;
   --team-ink:#0f172a;
   --team-muted:#64748b;
   --team-line:#e2e8f0;
   --team-bg:#f8fafc;
   --team-panel:#ffffff;
   --team-soft:#f1f5f9;
   --team-green:#059669;
   --team-green-bg:#ecfdf5;
   --team-green-bright:#10b981;
   --team-warning:#d97706;
   --team-warning-bg:#fffbeb;
   --team-critical:#e11d48;
   --team-critical-bg:#fff1f2;
   --team-blue:#2563eb;
   --team-blue-bg:#eff6ff;
   --team-shadow:0 1px 3px rgba(15,23,42,.04),0 6px 20px rgba(15,23,42,.04);
   --team-card-shadow:0 1px 3px rgba(15,23,42,.04),0 4px 14px rgba(15,23,42,.03);
   --team-card-hover:0 12px 30px rgba(15,23,42,.08);
   --font-primary:'Tajawal',Tahoma,Arial,sans-serif;
  }
  html.dark-mode{
   --team-ink:#f8fafc;
   --team-muted:#94a3b8;
   --team-line:#334155;
   --team-bg:#0b0f17;
   --team-panel:#171f2e;
   --team-soft:#1e293b;
   --team-green:#34d399;
   --team-green-bg:#064e3b33;
   --team-green-bright:#34d399;
   --team-warning:#fbbf24;
   --team-warning-bg:#78350f33;
   --team-critical:#fb7185;
   --team-critical-bg:#88133733;
   --team-blue:#60a5fa;
   --team-blue-bg:#1e3a8a33;
   --team-shadow:0 4px 20px rgba(0,0,0,.35);
   --team-card-shadow:0 4px 16px rgba(0,0,0,.3);
   --team-card-hover:0 12px 32px rgba(0,0,0,.5);
  }
  html{background:var(--team-bg)}
  body{
   margin:0;
   background:var(--team-bg);
   color:var(--team-ink);
   font-family:var(--font-primary);
  }
  button,input,select,textarea{font:inherit}
  a{color:inherit;text-decoration:none}
  ::selection{background:#dc26372a;color:var(--team-ink)}
  *{scrollbar-width:thin;scrollbar-color:#94a3b8 transparent}
  *::-webkit-scrollbar{width:7px;height:7px}
  *::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px}
  html.dark-mode *::-webkit-scrollbar-thumb{background:#475569}
  :focus-visible{outline:2px solid rgba(220,38,55,.4);outline-offset:2px}

  .team-shell{display:flex;min-height:100vh;max-width:100vw;overflow-x:hidden}
  .team-main{min-width:0;flex:1;max-width:100%;padding:20px 28px 44px}
  .team-stack{display:grid;gap:18px}

  /* 1. Header & Metric Cards */
  .dashboard-banner{
   display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
   margin-bottom:2px;
  }
  .banner-text h1{
   margin:0 0 4px;font-size:26px;font-weight:900;color:var(--team-ink);
   display:flex;align-items:center;gap:10px;
  }
  .banner-text h1 i{color:var(--team-red);font-size:26px}
  .banner-text p{margin:0;color:var(--team-muted);font-size:14.5px;font-weight:600}

  .metric-strip{
   display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;
  }
  .metric-card{
   display:flex;align-items:center;gap:14px;padding:16px 18px;border-radius:14px;
   background:var(--team-panel);border:1px solid var(--team-line);box-shadow:var(--team-card-shadow);
   transition:transform .15s ease,box-shadow .15s ease;
  }
  .metric-card:hover{transform:translateY(-1px);box-shadow:var(--team-shadow)}
  .metric-icon{
   width:42px;height:42px;border-radius:11px;display:grid;place-items:center;flex:0 0 42px;
   font-size:19px;background:var(--team-soft);color:var(--team-muted);
  }
  .metric-card.online .metric-icon{background:var(--team-green-bg);color:var(--team-green)}
  .metric-card.busy .metric-icon{background:var(--team-critical-bg);color:var(--team-critical)}
  .metric-card.free .metric-icon{background:var(--team-blue-bg);color:var(--team-blue)}
  .metric-data{display:grid;gap:2px;min-width:0}
  .metric-data span{color:var(--team-muted);font-size:13px;font-weight:800}
  .metric-data strong{font-size:24px;font-weight:900;color:var(--team-ink);font-variant-numeric:tabular-nums;line-height:1.2}

  /* 2. Control Toolbar */
  .control-toolbar{
   display:grid;gap:12px;padding:14px 18px;border-radius:14px;
   background:var(--team-panel);border:1px solid var(--team-line);box-shadow:var(--team-card-shadow);
  }
  .toolbar-top{
   display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
  }
  .status-pills{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap}
  .status-pill{
   display:inline-flex;align-items:center;gap:8px;padding:7px 15px;border-radius:999px;
   border:1px solid var(--team-line);background:var(--team-soft);color:var(--team-muted);
   font-size:13.5px;font-weight:800;cursor:pointer;transition:all .15s ease;user-select:none;
  }
  .status-pill:hover{border-color:var(--team-red);color:var(--team-red)}
  .status-pill.active{
   border-color:var(--team-red);background:var(--team-red);color:#fff;
   box-shadow:0 2px 8px rgba(220,38,55,.25);
  }
  .status-pill .badge-count{
   display:inline-block;padding:2px 7px;border-radius:999px;font-size:12px;font-weight:900;
   background:rgba(0,0,0,.08);font-variant-numeric:tabular-nums;
  }
  .status-pill.active .badge-count{background:rgba(255,255,255,.25)}

  .view-switcher{
   display:inline-flex;align-items:center;border:1px solid var(--team-line);border-radius:9px;
   background:var(--team-soft);padding:2px;gap:2px;
  }
  .btn-view{
   width:32px;height:30px;display:grid;place-items:center;border:none;border-radius:7px;
   background:transparent;color:var(--team-muted);font-size:15px;cursor:pointer;
   transition:all .15s ease;
  }
  .btn-view:hover{color:var(--team-ink)}
  .btn-view.active{
   background:var(--team-panel);color:var(--team-red);box-shadow:0 1px 3px rgba(0,0,0,.08);
  }

  .toolbar-bottom{
   display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
   padding-top:10px;border-top:1px solid var(--team-line);
  }
  .search-container{position:relative;flex:1;min-width:260px;max-width:440px}
  .search-container i{
   position:absolute;inset-inline-start:12px;top:50%;transform:translateY(-50%);
   color:var(--team-muted);font-size:14px;pointer-events:none;
  }
  .search-input{
   width:100%;height:40px;padding-inline-start:36px;padding-inline-end:14px;
   border:1px solid var(--team-line);border-radius:9px;background:var(--team-soft);
   color:var(--team-ink);font-size:13.5px;font-weight:700;outline:none;transition:all .15s ease;
  }
  .search-input:focus{border-color:var(--team-red);background:var(--team-panel)}

  .filter-selects{display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}
  .custom-select{
   height:40px;padding:0 12px;border:1px solid var(--team-line);border-radius:9px;
   background:var(--team-soft);color:var(--team-ink);font-size:13.5px;font-weight:800;
   outline:none;cursor:pointer;
  }
  .btn-apply{
   height:40px;padding:0 16px;border:none;border-radius:9px;background:var(--team-red);
   color:#fff;font-size:13.5px;font-weight:800;cursor:pointer;display:inline-flex;
   align-items:center;gap:6px;transition:background .15s ease;
  }
  .btn-apply:hover{background:var(--team-red-dark)}
  .btn-clear{
   height:40px;width:40px;display:grid;place-items:center;border:1px solid var(--team-line);
   border-radius:9px;background:var(--team-soft);color:var(--team-muted);cursor:pointer;
   transition:all .15s ease;
  }
  .btn-clear:hover{border-color:var(--team-red);color:var(--team-red)}

  /* 3. Grid View (Modern Cards) */
  .team-grid-view{
   display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:16px;
  }
  .team-card{
   display:flex;flex-direction:column;justify-content:space-between;
   border:1px solid var(--team-line);border-radius:16px;background:var(--team-panel);
   box-shadow:var(--team-card-shadow);transition:all .2s cubic-bezier(.16,1,.3,1);
   position:relative;overflow:visible;
  }
  .team-card:hover{
   transform:translateY(-2px);box-shadow:var(--team-card-hover);
   border-color:color-mix(in srgb,var(--team-red) 30%,var(--team-line));
  }
  .team-card.is-online{border-top:3px solid var(--team-green-bright)}
  .team-card.has-ticket{border-top:3px solid var(--team-red)}

  .card-body-section{padding:18px 20px 14px;display:grid;gap:12px;min-width:0;width:100%}

  /* Card Header: Avatar + Identity + IP Globe + Status */
  .card-header{
   display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
   min-width:0;width:100%;
  }
  .profile-group{display:flex;align-items:center;gap:12px;min-width:0;flex:1 1 0%}
  .avatar-box{position:relative;width:50px;height:50px;flex:0 0 50px}
  .avatar-img{
   width:50px;height:50px;border-radius:14px;display:grid;place-items:center;
   background:linear-gradient(135deg,color-mix(in srgb,var(--team-red) 14%,var(--team-soft)),var(--team-soft));
   color:var(--team-red);font-size:19px;font-weight:900;border:1px solid var(--team-line);
  }
  .presence-dot{
   position:absolute;bottom:-1px;inset-inline-end:-1px;width:13px;height:13px;
   border-radius:50%;border:2px solid var(--team-panel);box-sizing:content-box;
  }
  .presence-dot.online{
   background:var(--team-green-bright);
   box-shadow:0 0 0 0 rgba(16,185,129,.7);
   animation:pulse-green 2s infinite cubic-bezier(.66,0,0,1);
  }
  .presence-dot.offline{background:#94a3b8}

  @keyframes pulse-green{
   0%{box-shadow:0 0 0 0 rgba(16,185,129,.7)}
   70%{box-shadow:0 0 0 6px rgba(16,185,129,0)}
   100%{box-shadow:0 0 0 0 rgba(16,185,129,0)}
  }

  .identity-info{min-width:0;flex:1 1 0%;overflow:hidden}
  .employee-title{
   margin:0 0 3px;font-size:17.5px;font-weight:900;color:var(--team-ink);line-height:1.3;
   word-break:break-word;overflow-wrap:anywhere;display:-webkit-box;
   -webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
  }
  .role-tag{
   display:inline-block;padding:3px 8px;border-radius:6px;background:var(--team-soft);
   color:var(--team-muted);font-size:12.5px;font-weight:800;line-height:1.3;
  }

  .card-top-badges{display:flex;align-items:center;gap:6px;flex-shrink:0;position:relative}

  /* 4. Local IP Globe Icon with Tooltip (REQUESTED FEATURE) */
  .local-ip-badge{
   position:relative;width:30px;height:30px;display:grid;place-items:center;
   border-radius:8px;background:var(--team-soft);border:1px solid var(--team-line);
   color:var(--team-blue);font-size:15px;cursor:pointer;transition:all .15s ease;
  }
  .local-ip-badge:hover, .local-ip-badge:focus-visible{
   background:var(--team-blue-bg);border-color:var(--team-blue);color:var(--team-blue);
  }
  .ip-tooltip{
   position:absolute;top:calc(100% + 8px);bottom:auto;inset-inline-end:-6px;inset-inline-start:auto;
   min-width:175px;padding:9px 13px;border-radius:10px;background:#0f172a;color:#ffffff;
   border:1px solid rgba(255,255,255,.18);box-shadow:0 12px 28px rgba(0,0,0,.35);
   z-index:1000;pointer-events:none;opacity:0;visibility:hidden;transform:translateY(-4px);
   transition:opacity .15s ease,transform .15s ease,visibility .15s ease;
   text-align:start;direction:rtl;line-height:1.35;
  }
  .ip-tooltip::after{
   content:"";position:absolute;bottom:100%;inset-inline-end:12px;
   border:6px solid transparent;border-bottom-color:#0f172a;
  }
  .local-ip-badge:hover .ip-tooltip,
  .local-ip-badge:focus-visible .ip-tooltip{
   opacity:1;visibility:visible;transform:translateY(0);
  }
  .ip-tooltip-label{display:block;color:#94a3b8;font-size:11.5px;font-weight:700;margin-bottom:2px}
  .ip-tooltip-val{
   display:block;color:#38bdf8;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
   font-size:13.5px;font-weight:900;direction:ltr;text-align:start;
  }

  /* Presence Pill */
  .presence-pill{
   display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;
   font-size:12.5px;font-weight:800;white-space:nowrap;
  }
  .presence-pill.online{background:var(--team-green-bg);color:var(--team-green)}
  .presence-pill.offline{background:var(--team-soft);color:var(--team-muted);border:1px solid var(--team-line)}

  /* Contact Chips */
  .contact-strip{
   display:flex;align-items:center;gap:8px;padding:7px 11px;border-radius:9px;
   background:var(--team-soft);font-size:13px;font-weight:700;color:var(--team-muted);
  }
  .contact-chip{
   display:inline-flex;align-items:center;gap:6px;min-width:0;overflow:hidden;
   text-overflow:ellipsis;white-space:nowrap;flex:1;font-size:13px;font-weight:700;
  }
  .contact-chip i{color:var(--team-red);font-size:13px;flex:0 0 13px}
  .ext-chip{
   display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:5px;
   background:var(--team-panel);border:1px solid var(--team-line);color:var(--team-ink);
   font-family:monospace;font-size:12px;font-weight:900;direction:ltr;
  }

  /* Ticket Workload Area */
  .workload-area{min-height:74px;display:grid}
  .active-ticket-box{
   padding:11px 13px;border-radius:11px;
   background:color-mix(in srgb,var(--team-red) 4%,var(--team-soft));
   border:1px solid color-mix(in srgb,var(--team-red) 18%,var(--team-line));
   display:grid;gap:6px;
  }
  .active-ticket-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
  .ticket-label-chip{
   display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:5px;
   background:var(--team-critical-bg);color:var(--team-critical);font-size:12px;font-weight:900;
  }
  .ticket-subject-line{
   margin:0;font-size:14.5px;font-weight:900;color:var(--team-ink);line-height:1.4;
   overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  }
  .ticket-device-line{
   display:flex;align-items:center;gap:5px;color:var(--team-muted);font-size:13px;font-weight:700;
   overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  }
  .ticket-device-line i{color:var(--team-red);font-size:13px}

  /* Live Stopwatch Badge */
  .live-stopwatch{
   display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:6px;
   font-size:13px;font-weight:900;direction:rtl;font-variant-numeric:tabular-nums;
  }
  .live-stopwatch.band-fast{background:#dcfce7;color:#166534}
  .live-stopwatch.band-warning{background:#fef3c7;color:#92400e}
  .live-stopwatch.band-critical{background:#ffe4e6;color:#9f1239;animation:timer-pulse 2s infinite ease-in-out}

  .idle-box{
   padding:13px 15px;border-radius:11px;background:var(--team-soft);
   border:1px dashed var(--team-line);display:flex;align-items:center;gap:8px;
   color:var(--team-muted);font-size:14px;font-weight:800;
  }
  .idle-box i{color:var(--team-green);font-size:16px}

  /* Performance Micro-Metrics */
  .micro-metrics{
   display:flex;align-items:center;justify-content:space-between;gap:8px;
   padding-top:11px;border-top:1px solid var(--team-line);font-size:13px;font-weight:700;color:var(--team-muted);
  }
  .micro-col{display:inline-flex;align-items:center;gap:4px}
  .micro-col strong{color:var(--team-ink);font-weight:900;font-size:14px;font-variant-numeric:tabular-nums}
  .micro-col.active-t strong{color:var(--team-red)}
  .micro-col.closed-t strong{color:var(--team-green)}

  /* Card Footer Actions */
  .card-actions-bar{
   padding:12px 20px;border-top:1px solid var(--team-line);background:var(--team-soft);
   display:flex;align-items:center;justify-content:space-between;gap:10px;
  }
  .btn-details{
   width:100%;height:38px;display:inline-flex;align-items:center;justify-content:center;
   gap:6px;border-radius:9px;border:1px solid var(--team-line);background:var(--team-panel);
   color:var(--team-ink);font-size:13.5px;font-weight:800;cursor:pointer;transition:all .15s ease;
  }
  .btn-details:hover{
   border-color:var(--team-red);background:var(--team-red);color:#fff;
  }

  /* 5. List View (Compact Table Layout) */
  .team-list-view{
   display:none;border-radius:14px;overflow:hidden;border:1px solid var(--team-line);
   background:var(--team-panel);box-shadow:var(--team-card-shadow);
  }
  .team-list-view.active{display:block}
  .team-grid-view.hidden{display:none}

  .team-table{width:100%;border-collapse:collapse;font-size:14px;text-align:start}
  .team-table th{
   padding:13px 16px;background:var(--team-soft);color:var(--team-muted);
   font-weight:800;font-size:13.5px;border-bottom:1px solid var(--team-line);white-space:nowrap;
  }
  .team-table td{
   padding:13px 16px;border-bottom:1px solid var(--team-line);color:var(--team-ink);
   vertical-align:middle;font-size:14px;
  }
  .team-table tr:last-child td{border-bottom:0}
  .team-table tr:hover td{background:var(--team-soft)}

  .user-table-cell{display:flex;align-items:center;gap:10px;min-width:0;max-width:280px}
  .user-table-cell strong{display:block;word-break:break-word;overflow-wrap:anywhere;font-size:15.5px;font-weight:900}
  .user-table-avatar{
   width:40px;height:40px;border-radius:11px;display:grid;place-items:center;
   background:var(--team-soft);color:var(--team-red);font-size:15px;font-weight:900;
   border:1px solid var(--team-line);flex:0 0 40px;
  }

  /* Empty State */
  .empty-state-box{
   grid-column:1/-1;padding:50px 20px;text-align:center;border-radius:16px;
   border:2px dashed var(--team-line);background:var(--team-panel);display:grid;place-items:center;
  }
  .empty-state-box i{font-size:38px;color:var(--team-muted);margin-bottom:10px}
  .empty-state-box h3{margin:0 0 4px;font-size:18px;font-weight:900;color:var(--team-ink)}
  .empty-state-box p{margin:0;color:var(--team-muted);font-size:14px}

  /* 6. Modal Dialog */
  .modal-backdrop{
   position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);
   display:grid;place-items:center;z-index:9999;padding:20px;
   opacity:0;visibility:hidden;transition:all .2s ease;
  }
  .modal-backdrop.active{opacity:1;visibility:visible}
  .modal-dialog{
   width:100%;max-width:840px;max-height:88vh;display:flex;flex-direction:column;
   border-radius:18px;background:var(--team-panel);box-shadow:0 25px 60px rgba(0,0,0,.35);
   border:1px solid var(--team-line);transform:scale(.97);transition:transform .2s cubic-bezier(.16,1,.3,1);
   overflow:hidden;
  }
  .modal-backdrop.active .modal-dialog{transform:scale(1)}

  .modal-header{
   display:flex;align-items:flex-start;justify-content:space-between;gap:14px;
   padding:20px 24px;border-bottom:1px solid var(--team-line);background:var(--team-soft);
  }
  .modal-profile-wrap{display:flex;align-items:center;gap:14px;min-width:0}
  .modal-avatar{
   width:60px;height:60px;border-radius:15px;display:grid;place-items:center;
   background:linear-gradient(135deg,color-mix(in srgb,var(--team-red) 20%,var(--team-soft)),var(--team-soft));
   color:var(--team-red);font-size:22px;font-weight:900;border:1px solid var(--team-line);flex:0 0 60px;
  }
  .modal-title-data{min-width:0}
  .modal-title-data h2{margin:0 0 4px;font-size:23px;font-weight:900;color:var(--team-ink)}
  .modal-badges-row{display:flex;align-items:center;gap:6px;flex-wrap:wrap}

  .modal-ip-chip{
   display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:6px;
   background:var(--team-blue-bg);color:var(--team-blue);font-size:12.5px;font-weight:800;
   font-family:monospace;direction:ltr;
  }

  .modal-close-btn{
   width:34px;height:34px;display:grid;place-items:center;border-radius:8px;
   border:1px solid var(--team-line);background:var(--team-panel);color:var(--team-muted);
   font-size:18px;cursor:pointer;transition:all .15s ease;
  }
  .modal-close-btn:hover{border-color:var(--team-red);color:var(--team-red)}

  .modal-body{padding:22px 24px;overflow-y:auto;display:grid;gap:18px}

  .info-summary-grid{
   display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;
   padding:14px;border-radius:12px;background:var(--team-soft);border:1px solid var(--team-line);
  }
  .info-box{display:grid;gap:2px}
  .info-box span{color:var(--team-muted);font-size:12px;font-weight:800}
  .info-box strong{color:var(--team-ink);font-size:14.5px;font-weight:900;overflow-wrap:anywhere}

  .modal-stats-grid{
   display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;
  }
  .modal-stat-box{
   padding:12px;border-radius:11px;background:var(--team-soft);border:1px solid var(--team-line);
   display:grid;gap:3px;text-align:center;
  }
  .modal-stat-box span{color:var(--team-muted);font-size:12px;font-weight:800}
  .modal-stat-box strong{color:var(--team-ink);font-size:22px;font-weight:900;font-variant-numeric:tabular-nums}

  .modal-section-title{
   display:flex;align-items:center;gap:8px;margin:0 0 10px;font-size:17px;font-weight:900;color:var(--team-ink);
  }
  .modal-section-title i{color:var(--team-red);font-size:18px}

  .modal-tickets-table{
   width:100%;border-collapse:collapse;border:1px solid var(--team-line);border-radius:12px;
   overflow:hidden;font-size:13.5px;
  }
  .modal-tickets-table th{
   padding:11px 14px;background:var(--team-soft);color:var(--team-muted);font-weight:800;
   font-size:13px;border-bottom:1px solid var(--team-line);text-align:start;
  }
  .modal-tickets-table td{
   padding:11px 14px;border-bottom:1px solid var(--team-line);color:var(--team-ink);
   vertical-align:middle;text-align:start;font-size:13.5px;
  }
  .modal-tickets-table tr:last-child td{border-bottom:0}

  .btn-view-card{
   display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:6px;
   background:var(--team-soft);border:1px solid var(--team-line);color:var(--team-ink);
   font-size:11.5px;font-weight:800;
  }
  .btn-view-card:hover{background:var(--team-red);border-color:var(--team-red);color:#fff}

  .modal-loading{
   display:grid;place-items:center;padding:40px 20px;color:var(--team-muted);gap:10px;font-weight:800;
  }
  .spinner{
   width:32px;height:32px;border:3px solid var(--team-line);border-top-color:var(--team-red);
   border-radius:50%;animation:spin 0.8s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg)}}

  @media(max-width:980px){
   .metric-strip{grid-template-columns:repeat(2,minmax(0,1fr))}
   .modal-stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  }
  @media(max-width:768px){
   .team-main{padding:16px 14px 36px}
   .toolbar-top,.toolbar-bottom{flex-direction:column;align-items:stretch}
   .search-container{max-width:none}
   .filter-selects{width:100%}
   .filter-selects select{flex:1}
   .team-table th:nth-child(4), .team-table td:nth-child(4){display:none}
  }
  @media(max-width:540px){
   .metric-strip{grid-template-columns:1fr}
   .team-grid-view{grid-template-columns:1fr}
  }
 </style>
</head>
<body>
 @include('partials.page-loader')
 <div class="team-shell">
  @include('partials.crm-sidebar')
  <main class="team-main">
   @include('partials.topbar', [
    'title' => __('crm.support_team_title'),
    'subtitle' => __('crm.support_team_subtitle'),
    'icon' => 'bi-people',
   ])

   <div class="team-stack">
    <!-- 1. Header Metrics Strip -->
    <section class="metric-strip" aria-label="{{ __('crm.support_team_title') }}">
     <div class="metric-card">
      <span class="metric-icon"><i class="bi bi-people-fill"></i></span>
      <div class="metric-data">
       <span>{{ __('crm.support_team_all_count') }}</span>
       <strong>{{ number_format($metrics['total']) }}</strong>
      </div>
     </div>
     <div class="metric-card online">
      <span class="metric-icon"><i class="bi bi-wifi"></i></span>
      <div class="metric-data">
       <span>{{ __('crm.support_team_online_count') }}</span>
       <strong>{{ number_format($metrics['online']) }}</strong>
      </div>
     </div>
     <div class="metric-card busy">
      <span class="metric-icon"><i class="bi bi-hourglass-split"></i></span>
      <div class="metric-data">
       <span>{{ __('crm.support_team_busy_count') }}</span>
       <strong>{{ number_format($metrics['busy']) }}</strong>
      </div>
     </div>
     <div class="metric-card free">
      <span class="metric-icon"><i class="bi bi-shield-check"></i></span>
      <div class="metric-data">
       <span>{{ __('crm.support_team_free_count') }}</span>
       <strong>{{ number_format($metrics['free']) }}</strong>
      </div>
     </div>
    </section>

    <!-- 2. Control Toolbar -->
    <section class="control-toolbar">
     <!-- Top Row: Quick Tabs + View Switcher -->
     <div class="toolbar-top">
      <div class="status-pills">
       <a href="{{ route('v2.technical-support.team', array_merge(request()->except('status'), ['status' => 'all'])) }}" class="status-pill {{ $status === 'all' ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>{{ __('crm.filter_all_employees') }}</span>
        <span class="badge-count">{{ $metrics['total'] }}</span>
       </a>
       <a href="{{ route('v2.technical-support.team', array_merge(request()->except('status'), ['status' => 'online'])) }}" class="status-pill {{ $status === 'online' ? 'active' : '' }}">
        <i class="bi bi-circle-fill" style="color:var(--team-green-bright);font-size:9px"></i>
        <span>{{ __('crm.filter_online_only') }}</span>
        <span class="badge-count">{{ $metrics['online'] }}</span>
       </a>
       <a href="{{ route('v2.technical-support.team', array_merge(request()->except('status'), ['status' => 'has_ticket'])) }}" class="status-pill {{ $status === 'has_ticket' ? 'active' : '' }}">
        <i class="bi bi-ticket-detailed-fill" style="color:#ef4444"></i>
        <span>{{ __('crm.filter_has_ticket') }}</span>
        <span class="badge-count">{{ $metrics['busy'] }}</span>
       </a>
       <a href="{{ route('v2.technical-support.team', array_merge(request()->except('status'), ['status' => 'available'])) }}" class="status-pill {{ $status === 'available' ? 'active' : '' }}">
        <i class="bi bi-check2"></i>
        <span>{{ __('crm.filter_available_only') }}</span>
        <span class="badge-count">{{ $metrics['free'] }}</span>
       </a>
      </div>

      <div class="view-switcher" role="group" aria-label="View Switcher">
       <button class="btn-view active" id="btnViewGrid" title="{{ __('crm.grid_view') }}" type="button">
        <i class="bi bi-grid-fill"></i>
       </button>
       <button class="btn-view" id="btnViewList" title="{{ __('crm.list_view') }}" type="button">
        <i class="bi bi-list-ul"></i>
       </button>
      </div>
     </div>

     <!-- Bottom Row: Search, Group filter, Sort selector -->
     <form class="toolbar-bottom" method="GET" action="{{ route('v2.technical-support.team') }}">
      @if($status !== 'all')
       <input type="hidden" name="status" value="{{ $status }}">
      @endif

      <div class="search-container">
       <i class="bi bi-search"></i>
       <input class="search-input" type="search" name="search" value="{{ $search }}" placeholder="{{ __('crm.search_support_employees') }}">
      </div>

      <div class="filter-selects">
       @if($groups->isNotEmpty())
        <select class="custom-select" name="group" onchange="this.form.submit()">
         <option value="">{{ __('crm.groups') }} ({{ __('crm.all') }})</option>
         @foreach($groups as $group)
          <option value="{{ $group->code }}" @selected($groupCode === $group->code)>{{ $group->name }}</option>
         @endforeach
        </select>
       @endif

       <select class="custom-select" name="sort" onchange="this.form.submit()">
        <option value="workload" @selected(($sort ?? 'workload') === 'workload')>{{ __('crm.sort_workload') }}</option>
        <option value="name" @selected(($sort ?? '') === 'name')>{{ __('crm.sort_name') }}</option>
        <option value="tickets" @selected(($sort ?? '') === 'tickets')>{{ __('crm.sort_tickets') }}</option>
        <option value="online" @selected(($sort ?? '') === 'online')>{{ __('crm.sort_online') }}</option>
        <option value="recent" @selected(($sort ?? '') === 'recent')>{{ __('crm.sort_recent') }}</option>
       </select>

       <button class="btn-apply" type="submit">
        <i class="bi bi-funnel-fill"></i>
        <span>{{ __('crm.apply') }}</span>
       </button>

       @if($search !== '' || $groupCode !== '' || ($sort ?? 'workload') !== 'workload' || $status !== 'all')
        <a class="btn-clear" href="{{ route('v2.technical-support.team') }}" title="{{ __('crm.reset_filters') }}">
         <i class="bi bi-x-lg"></i>
        </a>
       @endif
      </div>
     </form>
    </section>

    <!-- 3. Cards Grid View -->
    <section class="team-grid-view" id="teamGridView">
     @forelse($cards as $card)
      <article class="team-card {{ $card['is_online'] ? 'is-online' : '' }} {{ $card['has_open_ticket'] ? 'has-ticket' : '' }}" data-user-id="{{ $card['id'] }}">
       <div class="card-body-section">
        <!-- Card Header -->
        <div class="card-header">
         <div class="profile-group">
          <div class="avatar-box">
           <div class="avatar-img">
            {{ mb_substr($card['name'], 0, 2) }}
           </div>
           <span class="presence-dot {{ $card['is_online'] ? 'online' : 'offline' }}"></span>
          </div>

          <div class="identity-info">
           <h3 class="employee-title">{{ $card['name'] }}</h3>
           <span class="role-tag">{{ $card['role_name'] }}</span>
          </div>
         </div>

         <div class="card-top-badges">
          <!-- REQUESTED FEATURE: Small Globe Icon with Local IP on hover -->
          <div class="local-ip-badge" tabindex="0" aria-label="{{ __('crm.device_local_ip') }}: {{ $card['local_ip'] ?? __('crm.ip_not_recorded') }}">
           <i class="bi bi-globe2"></i>
           <div class="ip-tooltip">
            <span class="ip-tooltip-label">{{ __('crm.device_local_ip') }}</span>
            <span class="ip-tooltip-val">{{ $card['local_ip'] ?? __('crm.ip_not_recorded') }}</span>
           </div>
          </div>

          <!-- Presence Pill -->
          @if($card['is_online'])
           <span class="presence-pill online">
            <i class="bi bi-circle-fill" style="font-size:8px"></i>
            <span>{{ __('crm.online_now') }}</span>
           </span>
          @else
           <span class="presence-pill offline" title="{{ $card['last_seen_text'] ? __('crm.last_seen') . ': ' . $card['last_seen_text'] : '' }}">
            <span>{{ __('crm.offline') }}</span>
           </span>
          @endif
         </div>
        </div>

        <!-- Contact Strip -->
        <div class="contact-strip">
         @if($card['email'])
          <div class="contact-chip" title="{{ $card['email'] }}">
           <i class="bi bi-envelope"></i>
           <span>{{ $card['email'] }}</span>
          </div>
         @endif

         @if(!empty($card['voip_extension']))
          <span class="ext-chip" title="{{ __('crm.voip_extension') }}">
           <i class="bi bi-telephone-fill" style="font-size:9px;color:var(--team-red)"></i>
           <span>{{ $card['voip_extension'] }}</span>
          </span>
         @endif
        </div>

        <!-- Workload / Ticket Status Area -->
        <div class="workload-area">
         @if($card['has_open_ticket'])
          <div class="active-ticket-box">
           <div class="active-ticket-head">
            <span class="ticket-label-chip">
             <i class="bi bi-ticket-perforated-fill"></i>
             <span>{{ __('crm.ticket_in_progress') }} #{{ str_pad((string) $card['active_ticket']['id'], 4, '0', STR_PAD_LEFT) }}</span>
            </span>

            <!-- Stopwatch Live Elapsed Timer -->
            <div class="live-stopwatch band-{{ $card['active_ticket']['time_band'] }}" data-opened-at="{{ $card['active_ticket']['opened_at_iso'] }}" title="{{ __('crm.ticket_live_timer') }}">
             <i class="bi bi-stopwatch-fill"></i>
             <span class="timer-title" style="display:none">{{ __('crm.ticket_live_timer') }}</span>
             <span class="timer-clock">{{ $card['active_ticket']['formatted_time'] }}</span>
            </div>
           </div>

           <p class="ticket-subject-line" title="{{ $card['active_ticket']['subject'] }}">
            {{ $card['active_ticket']['subject'] }}
           </p>

           <div class="ticket-device-line">
            <i class="bi bi-hdd-network"></i>
            <span>{{ $card['active_ticket']['device_name'] }}{{ $card['active_ticket']['company_name'] ? ' — ' . $card['active_ticket']['company_name'] : '' }}</span>
           </div>
          </div>
         @else
          <div class="idle-box">
           <i class="bi bi-shield-check"></i>
           <span>{{ __('crm.no_active_ticket') }}</span>
          </div>
         @endif
        </div>

        <!-- Performance Micro-Metrics -->
        <div class="micro-metrics">
         <span class="micro-col active-t">
          <span>{{ __('crm.active_tickets') }}:</span>
          <strong>{{ $card['open_tickets_count'] }}</strong>
         </span>
         <span class="micro-col closed-t">
          <span>{{ __('crm.completed_tickets') }}:</span>
          <strong>{{ $card['closed_tickets'] }}</strong>
         </span>
         <span class="micro-col">
          <span>{{ __('crm.active_tasks_count') }}:</span>
          <strong>{{ $card['active_tasks_count'] }}</strong>
         </span>
        </div>
       </div>

       <!-- Card Footer Button -->
       <div class="card-actions-bar">
        <button class="btn-details btn-open-modal" type="button" data-user-id="{{ $card['id'] }}">
         <i class="bi bi-person-lines-fill"></i>
         <span>{{ __('crm.view_employee_details') }}</span>
        </button>
       </div>
      </article>
     @empty
      <div class="empty-state-box">
       <i class="bi bi-people"></i>
       <h3>{{ __('crm.no_support_employees_found') }}</h3>
       <p>{{ __('crm.no_support_report_data_desc') }}</p>
      </div>
     @endforelse
    </section>

    <!-- 4. List View (Dense Table Layout) -->
    <section class="team-list-view" id="teamListView">
     <table class="team-table">
      <thead>
       <tr>
        <th>{{ __('crm.support_employee_card') }}</th>
        <th>{{ __('crm.groups') }}</th>
        <th>{{ __('crm.status') }}</th>
        <th>{{ __('crm.device_local_ip') }}</th>
        <th>{{ __('crm.active_ticket') }}</th>
        <th>{{ __('crm.total_tickets') }}</th>
        <th></th>
       </tr>
      </thead>
      <tbody>
       @forelse($cards as $card)
        <tr>
         <td>
          <div class="user-table-cell">
           <div class="user-table-avatar">
            {{ mb_substr($card['name'], 0, 2) }}
           </div>
           <div>
            <strong>{{ $card['name'] }}</strong>
            <div style="color:var(--team-muted);font-size:11px">{{ $card['email'] }}</div>
           </div>
          </div>
         </td>
         <td>
          <span class="role-tag">{{ $card['role_name'] }}</span>
          @if(!empty($card['voip_extension']))
           <span class="ext-chip" style="margin-inline-start:4px">{{ $card['voip_extension'] }}</span>
          @endif
         </td>
         <td>
          @if($card['is_online'])
           <span class="presence-pill online">
            <i class="bi bi-circle-fill" style="font-size:8px"></i>
            <span>{{ __('crm.online_now') }}</span>
           </span>
          @else
           <span class="presence-pill offline">
            <span>{{ __('crm.offline') }}</span>
           </span>
          @endif
         </td>
         <td>
          <!-- Globe Icon + IP text in list view -->
          <span style="display:inline-flex;align-items:center;gap:6px;font-family:monospace;font-size:12px;direction:ltr">
           <i class="bi bi-globe2" style="color:var(--team-blue)"></i>
           <span>{{ $card['local_ip'] ?? __('crm.ip_not_recorded') }}</span>
          </span>
         </td>
         <td>
          @if($card['has_open_ticket'])
           <div style="display:grid;gap:2px">
            <span style="font-weight:800;color:var(--team-ink);font-size:12px">{{ $card['active_ticket']['subject'] }}</span>
            <div class="live-stopwatch band-{{ $card['active_ticket']['time_band'] }}" data-opened-at="{{ $card['active_ticket']['opened_at_iso'] }}" title="{{ __('crm.ticket_live_timer') }}" style="width:fit-content">
             <i class="bi bi-stopwatch-fill"></i>
             <span class="timer-title" style="display:none">{{ __('crm.ticket_live_timer') }}</span>
             <span class="timer-clock">{{ $card['active_ticket']['formatted_time'] }}</span>
            </div>
           </div>
          @else
           <span style="color:var(--team-muted);font-size:12px">{{ __('crm.no_active_ticket') }}</span>
          @endif
         </td>
         <td>
          <span style="font-variant-numeric:tabular-nums;font-weight:800">{{ $card['closed_tickets'] }} / {{ $card['total_tickets'] }}</span>
         </td>
         <td style="text-align:end">
          <button class="btn-details btn-open-modal" style="width:auto;padding:5px 12px" type="button" data-user-id="{{ $card['id'] }}">
           <i class="bi bi-person-lines-fill"></i>
           <span>{{ __('crm.employee_details') }}</span>
          </button>
         </td>
        </tr>
       @empty
        <tr>
         <td colspan="7" style="text-align:center;padding:30px;color:var(--team-muted)">
          {{ __('crm.no_support_employees_found') }}
         </td>
        </tr>
       @endforelse
      </tbody>
     </table>
    </section>
   </div>
  </main>
 </div>

 <!-- Employee Details Modal -->
 <div class="modal-backdrop" id="employeeModalBackdrop" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="modal-dialog">
   <div class="modal-header">
    <div class="modal-profile-wrap">
     <div class="modal-avatar" id="modalAvatar">--</div>
     <div class="modal-title-data">
      <h2 id="modalUserName">--</h2>
      <div class="modal-badges-row" id="modalBadges"></div>
     </div>
    </div>
    <button class="modal-close-btn" id="modalCloseBtn" aria-label="{{ __('crm.close') }}">
     <i class="bi bi-x-lg"></i>
    </button>
   </div>

   <div class="modal-body" id="modalBody">
    <div class="modal-loading">
     <div class="spinner"></div>
     <span>{{ __('crm.loading') }}...</span>
    </div>
   </div>
  </div>
 </div>

 <script>
  (function () {
   'use strict';

   // 1. Grid / List View Switcher with localStorage persistence
   const btnGrid = document.getElementById('btnViewGrid');
   const btnList = document.getElementById('btnViewList');
   const gridView = document.getElementById('teamGridView');
   const listView = document.getElementById('teamListView');

   const savedView = localStorage.getItem('sokrat_team_view') || 'grid';
   if (savedView === 'list') {
    setListView();
   }

   btnGrid?.addEventListener('click', setGridView);
   btnList?.addEventListener('click', setListView);

   function setGridView() {
    btnGrid?.classList.add('active');
    btnList?.classList.remove('active');
    gridView?.classList.remove('hidden');
    listView?.classList.remove('active');
    localStorage.setItem('sokrat_team_view', 'grid');
   }

   function setListView() {
    btnList?.classList.add('active');
    btnGrid?.classList.remove('active');
    gridView?.classList.add('hidden');
    listView?.classList.add('active');
    localStorage.setItem('sokrat_team_view', 'list');
   }

   // 2. Live Running Stopwatch Timers
   function formatElapsed(totalSeconds) {
    if (totalSeconds < 0) totalSeconds = 0;
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (num) => String(num).padStart(2, '0');

    if (hours > 0) {
     return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
    }
    return `${pad(minutes)}:${pad(seconds)}`;
   }

   function updateLiveTimers() {
    const timerElements = document.querySelectorAll('.live-stopwatch[data-opened-at]');
    const now = Date.now();

    timerElements.forEach((el) => {
     const openedAtIso = el.getAttribute('data-opened-at');
     if (!openedAtIso) return;

     const openedAt = new Date(openedAtIso).getTime();
     if (isNaN(openedAt)) return;

     const elapsedSeconds = Math.max(0, Math.floor((now - openedAt) / 1000));
     const clockEl = el.querySelector('.timer-clock');
     if (clockEl) {
      clockEl.textContent = formatElapsed(elapsedSeconds);
     }

     el.classList.remove('band-fast', 'band-warning', 'band-critical');
     if (elapsedSeconds > 1800) {
      el.classList.add('band-critical');
     } else if (elapsedSeconds >= 600) {
      el.classList.add('band-warning');
     } else {
      el.classList.add('band-fast');
     }
    });
   }

   setInterval(updateLiveTimers, 1000);
   updateLiveTimers();

   // 3. Employee Details Modal
   const modalBackdrop = document.getElementById('employeeModalBackdrop');
   const modalCloseBtn = document.getElementById('modalCloseBtn');
   const modalBody = document.getElementById('modalBody');
   const modalUserName = document.getElementById('modalUserName');
   const modalAvatar = document.getElementById('modalAvatar');
   const modalBadges = document.getElementById('modalBadges');

   function closeModal() {
    modalBackdrop?.classList.remove('active');
    modalBackdrop?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
   }

   function openModal() {
    modalBackdrop?.classList.add('active');
    modalBackdrop?.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
   }

   modalCloseBtn?.addEventListener('click', closeModal);
   modalBackdrop?.addEventListener('click', function (e) {
    if (e.target === modalBackdrop) closeModal();
   });

   document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modalBackdrop?.classList.contains('active')) {
     closeModal();
    }
   });

   // Bind click on card or details button
   document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-open-modal');
    if (btn) {
     const userId = btn.getAttribute('data-user-id');
     if (userId) {
      openModal();
      fetchEmployeeDetails(userId);
      return;
     }
    }

    const card = e.target.closest('.team-card');
    if (card && !e.target.closest('a') && !e.target.closest('.local-ip-badge')) {
     const userId = card.getAttribute('data-user-id');
     if (userId) {
      openModal();
      fetchEmployeeDetails(userId);
     }
    }
   });

   async function fetchEmployeeDetails(userId) {
    if (!modalBody) return;
    modalBody.innerHTML = `
     <div class="modal-loading">
      <div class="spinner"></div>
      <span>{{ __('crm.loading') }}...</span>
     </div>
    `;

    try {
     const url = `{{ url('/technical-support/team') }}/${userId}`;
     const response = await fetch(url, {
      headers: {
       'Accept': 'application/json',
       'X-Requested-With': 'XMLHttpRequest',
      }
     });

     if (!response.ok) throw new Error(`HTTP ${response.status}`);
     const data = await response.json();
     renderModalContent(data);
    } catch (err) {
     modalBody.innerHTML = `
      <div class="empty-state-box" style="padding:24px">
       <i class="bi bi-exclamation-triangle" style="color:var(--team-red)"></i>
       <h3>حدث خطأ أثناء جلب البيانات</h3>
       <p>${err.message || 'تعذر الاتصال بالخادم.'}</p>
      </div>
     `;
    }
   }

   function renderModalContent(data) {
    const u = data.user;
    const s = data.stats;
    const tickets = data.tickets || [];
    const tasks = data.tasks || [];

    if (modalUserName) modalUserName.textContent = u.name;
    if (modalAvatar) modalAvatar.textContent = (u.name || '').substring(0, 2);

    let badgesHtml = '';
    if (u.is_online) {
     badgesHtml += `
      <span class="presence-pill online">
       <i class="bi bi-circle-fill" style="font-size:7px"></i>
       <span>{{ __('crm.online_now') }}</span>
      </span>
     `;
    } else {
     badgesHtml += `
      <span class="presence-pill offline">
       <span>{{ __('crm.offline') }}</span>
      </span>
     `;
    }

    if (u.local_ip) {
     badgesHtml += `
      <span class="modal-ip-chip" title="{{ __('crm.device_local_ip') }}">
       <i class="bi bi-globe2"></i>
       <span>IP: ${escapeHtml(u.local_ip)}</span>
      </span>
     `;
    }

    (u.groups || []).forEach((groupName) => {
     badgesHtml += `<span class="role-tag">${escapeHtml(groupName)}</span>`;
    });

    if (u.voip_extension) {
     badgesHtml += `
      <span class="ext-chip">
       <i class="bi bi-telephone-fill" style="font-size:9px;color:var(--team-red)"></i>
       <span>${escapeHtml(u.voip_extension)}</span>
      </span>
     `;
    }

    if (modalBadges) modalBadges.innerHTML = badgesHtml;

    let html = `
     <!-- Info grid -->
     <div class="info-summary-grid">
      <div class="info-box">
       <span>{{ __('crm.email') }}</span>
       <strong>${u.email ? escapeHtml(u.email) : '-'}</strong>
      </div>
      <div class="info-box">
       <span>{{ __('crm.username') }}</span>
       <strong>${u.username ? escapeHtml(u.username) : '-'}</strong>
      </div>
      <div class="info-box">
       <span>{{ __('crm.device_local_ip') }}</span>
       <strong style="font-family:monospace;direction:ltr">${u.local_ip ? escapeHtml(u.local_ip) : '{{ __("crm.ip_not_recorded") }}'}</strong>
      </div>
      <div class="info-box">
       <span>{{ __('crm.voip_extension') }}</span>
       <strong>${u.voip_extension ? escapeHtml(u.voip_extension) : '-'}</strong>
      </div>
      <div class="info-box">
       <span>{{ __('crm.last_seen') }}</span>
       <strong>${u.last_seen_text || u.last_login_at || '-'}</strong>
      </div>
      <div class="info-box">
       <span>{{ __('crm.created_at') }}</span>
       <strong>${u.created_at || '-'}</strong>
      </div>
     </div>

     <!-- Stats Strip -->
     <div class="modal-stats-grid">
      <div class="modal-stat-box">
       <span>{{ __('crm.total_tickets') }}</span>
       <strong>${s.total_tickets}</strong>
      </div>
      <div class="modal-stat-box">
       <span>{{ __('crm.active_tickets') }}</span>
       <strong style="color:var(--team-red)">${s.open_tickets}</strong>
      </div>
      <div class="modal-stat-box">
       <span>{{ __('crm.completed_tickets') }}</span>
       <strong style="color:var(--team-green)">${s.closed_tickets}</strong>
      </div>
      <div class="modal-stat-box">
       <span>{{ __('crm.avg_support_time') }}</span>
       <strong style="font-size:14px">${s.avg_support_time || '-'}</strong>
      </div>
     </div>

     <!-- Recent Tickets Table -->
     <div>
      <h3 class="modal-section-title">
       <i class="bi bi-ticket-detailed-fill"></i>
       <span>{{ __('crm.recent_tickets') }} (${tickets.length})</span>
      </h3>
    `;

    if (tickets.length === 0) {
     html += `
      <div class="empty-state-box" style="padding:22px">
       <i class="bi bi-ticket-perforated" style="font-size:26px"></i>
       <h4 style="margin:0 0 2px;font-size:14px">{{ __('crm.no_tickets_yet_for_employee') }}</h4>
      </div>
     `;
    } else {
     html += `
      <table class="modal-tickets-table">
       <thead>
        <tr>
         <th>{{ __('crm.ticket_subject') }}</th>
         <th>{{ __('crm.server_details') }}</th>
         <th>{{ __('crm.status') }}</th>
         <th>{{ __('crm.opened_at') }}</th>
         <th>{{ __('crm.support_time_spent') }}</th>
         <th></th>
        </tr>
       </thead>
       <tbody>
     `;

     tickets.forEach((t) => {
      const isClosed = t.status === 'closed';
      const statusLabel = isClosed ? '{{ __("crm.ticket_status_closed") }}' : '{{ __("crm.ticket_status_open") }}';
      const statusClass = isClosed ? 'presence-pill online' : 'presence-pill' ;
      const serverText = escapeHtml(t.device_name) + (t.company_name ? ` — ${escapeHtml(t.company_name)}` : '');

      html += `
       <tr>
        <td>
         <div style="display:grid;gap:1px">
          <span style="direction:ltr;font-family:monospace;font-size:11px;color:var(--team-muted)">${t.number}</span>
          <strong>${escapeHtml(t.subject)}</strong>
          ${t.resolution ? `<small style="color:var(--team-muted);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(t.resolution)}</small>` : ''}
         </div>
        </td>
        <td>
         <div style="display:flex;align-items:center;gap:5px;font-size:12px">
          <i class="bi bi-hdd-network" style="color:var(--team-red)"></i>
          <span>${serverText}</span>
         </div>
        </td>
        <td>
         <span class="${statusClass}" style="font-size:11px">
          <i class="bi bi-${isClosed ? 'check2' : 'hourglass-split'}"></i>
          ${statusLabel}
         </span>
        </td>
        <td style="white-space:nowrap;color:var(--team-muted);font-size:11.5px">${t.opened_at || '-'}</td>
        <td>
         <span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;color:var(--team-muted)">
          <i class="bi bi-stopwatch"></i>
          <span>${t.duration_formatted || '-'}</span>
         </span>
        </td>
        <td style="text-align:end">
         <a href="${t.view_url}" class="btn-view-card" target="_blank" rel="noopener">
          <i class="bi bi-box-arrow-up-right"></i>
          <span>{{ __('crm.view_card_details') }}</span>
         </a>
        </td>
       </tr>
      `;
     });

     html += `
        </tbody>
       </table>
     `;
    }

    html += `</div>`;

    // Tasks section if any
    if (tasks.length > 0) {
     html += `
      <div>
       <h3 class="modal-section-title">
        <i class="bi bi-list-check"></i>
        <span>{{ __('crm.recent_tasks') }} (${tasks.length})</span>
       </h3>
       <table class="modal-tickets-table">
        <thead>
         <tr>
          <th>{{ __('crm.support_task_title') }}</th>
          <th>{{ __('crm.status') }}</th>
          <th>{{ __('crm.support_task_priority') }}</th>
          <th>{{ __('crm.support_task_due_date') }}</th>
         </tr>
        </thead>
        <tbody>
     `;

     tasks.forEach((tk) => {
      html += `
       <tr>
        <td><strong>${escapeHtml(tk.title)}</strong></td>
        <td><span class="role-tag">${escapeHtml(tk.status)}</span></td>
        <td><span style="font-weight:800;color:var(--team-muted)">${escapeHtml(tk.priority)}</span></td>
        <td style="color:var(--team-muted)">${tk.due_date || '-'}</td>
       </tr>
      `;
     });

     html += `
        </tbody>
       </table>
      </div>
     `;
    }

    modalBody.innerHTML = html;
   }

   function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
   }
  })();
 </script>
</body>
</html>
