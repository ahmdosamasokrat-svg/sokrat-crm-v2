@php
 $crmSidebarAssetsLoaded = true;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <meta name="csrf-token" content="{{ csrf_token() }}">
 <title>{{ $device['name'] }} - {{ __('crm.card_details') }} - SokratCRM</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
 <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
 <style>
  *{box-sizing:border-box}
  :root{
   --support-red:#dc2637;
   --support-red-dark:#b81829;
   --support-ink:#172033;
   --support-muted:#596579;
   --support-line:#e4e8ef;
   --support-bg:#f4f6fa;
   --support-panel:#fff;
   --support-soft:#f8fafc;
   --support-green:#0f7440;
   --support-green-bg:#e9f8ef;
   --support-gray-bg:#eef1f5;
   --support-shadow:0 14px 38px rgba(17,24,39,.06);
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
   --support-gray-bg:#303744;
   --support-shadow:0 18px 42px rgba(0,0,0,.24);
  }
  html{background:var(--support-bg)}
  body{
   margin:0;
   background:radial-gradient(circle at 10% 0,rgba(220,38,55,0.05),transparent 30rem),var(--support-bg);
   color:var(--support-ink);
   font-family:var(--font-primary);
  }
  ::selection{background:#dc26372a;color:var(--support-ink)}
  *{scrollbar-width:thin;scrollbar-color:#aeb6c4 transparent}
  *::-webkit-scrollbar{width:10px;height:10px}
  *::-webkit-scrollbar-thumb{background:#aeb6c4;border:3px solid transparent;border-radius:10px;background-clip:padding-box}
  :focus-visible{outline:3px solid rgba(220,38,55,.28);outline-offset:3px}
  .visually-hidden{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
  
  .support-shell{min-height:100vh;display:flex;flex-direction:row;max-width:100vw;overflow-x:clip}
  .support-main{flex:1;min-width:0;max-width:100%;padding:24px 32px 52px}
  
  .detail-topbar, .crm-topbar, .topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap;min-height:56px}
  .crm-topbar-left, .topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
  .crm-topbar-title h1, .topbar h1{margin:0;font-size:22px;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--support-ink)}
  .crm-topbar-title p, .topbar p{margin:2px 0 0;color:var(--support-muted);font-size:13px}
  .crm-topbar-right, .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .support-menu, .crm-topbar-menu-btn, .menu-button{
   display:none;width:44px;height:44px;min-height:44px;min-width:44px;place-items:center;flex:0 0 44px;
   border:1px solid var(--support-line);border-radius:12px;background:var(--support-panel);
   color:var(--support-ink);font-size:20px;cursor:pointer;touch-action:manipulation;
  }
  .crm-topbar-back-btn, .back-link{
   width:44px;height:44px;min-height:44px;min-width:44px;border-radius:10px;
   border:1px solid var(--support-line);background:var(--support-panel);
   display:inline-flex;align-items:center;justify-content:center;
   color:var(--support-ink);text-decoration:none;transition:all .15s;touch-action:manipulation;
  }
  .crm-topbar-back-btn:hover, .back-link:hover{border-color:var(--support-red);color:var(--support-red)}
  /* Header Card */
  .detail-header-panel{
   position:relative;overflow:hidden;border:1px solid var(--support-line);border-radius:16px;
   background:
    linear-gradient(115deg,color-mix(in srgb,var(--support-red) 5%,var(--support-panel)),var(--support-panel) 40%);
   box-shadow:var(--support-shadow);padding:30px;margin-bottom:16px;
   display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;
  }
  .detail-header-panel::after{
   content:"";position:absolute;inset-block-start:0;inset-inline:0;height:3px;background:var(--support-red)
  }
  .detail-header-panel[data-status="online"]::after{background:var(--support-green)}
  .detail-identity{display:flex;align-items:center;gap:20px;min-width:0;flex:1}
  .detail-os-icon{
   width:76px;height:76px;display:grid;place-items:center;flex:0 0 76px;
   border-radius:16px;background:var(--support-gray-bg);color:var(--support-muted);font-size:34px;
   overflow:hidden;border:1px solid var(--support-line);
  }
  .card-custom-avatar{width:100%;height:100%;object-fit:cover}
  .detail-header-panel[data-status="online"] .detail-os-icon{
   background:var(--support-green-bg);color:var(--support-green);
  }
  
  .detail-title-wrap{min-width:0;flex:1}
  .detail-company{
   display:inline-flex;align-items:center;gap:7px;font-size:14px;font-weight:900;
   color:var(--support-red);margin-bottom:7px;overflow-wrap:anywhere;
  }
  .detail-title{
   margin:0 0 8px;font-size:clamp(27px,2.8vw,36px);line-height:1.2;font-weight:900;color:var(--support-ink);
   display:flex;align-items:center;gap:10px;flex-wrap:wrap;overflow-wrap:anywhere;
  }
  .detail-subline{display:flex;align-items:center;gap:8px 16px;flex-wrap:wrap;color:var(--support-muted);font-size:13px;font-weight:700}
  .detail-subline span{display:inline-flex;align-items:center;gap:6px}
  .detail-dns{direction:ltr;text-align:start;font-family:monospace}
  
  .badge-tag{
   display:inline-flex;align-items:center;padding:3px 8px;border-radius:6px;
   font-size:10px;font-weight:900;
  }
  .badge-self{background:#fff0f2;color:var(--support-red-dark)}
  html.dark-mode .badge-self{background:#4b222a;color:#ff9aa4}
  
  .card-status-badge{
   display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:999px;
   background:var(--support-gray-bg);color:var(--support-muted);font-size:13px;font-weight:900;
  }
  .card-status-badge::before{content:"";width:8px;height:8px;border-radius:50%;background:#929baa}
  .card-status-badge.online{background:var(--support-green-bg);color:var(--support-green)}
  .card-status-badge.online::before{background:var(--support-green);box-shadow:0 0 0 3px rgba(15,116,64,.2)}
  
  .detail-actions{display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap;max-width:430px}
  .detail-actions .card-status-badge{min-height:44px;padding-inline:15px}
  .btn-action{
   min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px;
   padding:0 18px;border:1px solid var(--support-line);border-radius:12px;
   background:var(--support-panel);color:var(--support-ink);font:800 13px var(--font-primary);
   cursor:pointer;transition:all .18s;box-shadow:0 4px 12px rgba(17,24,39,.04);
  }
  .btn-action:hover{border-color:#ccd5e2;transform:translateY(-2px)}
  .btn-action.primary{
   border:none;background:linear-gradient(135deg,var(--support-red),var(--support-red-dark));
   color:#fff;box-shadow:0 6px 18px rgba(220,38,55,.24);
  }
  .btn-action.primary:hover{box-shadow:0 8px 22px rgba(220,38,55,.32)}
  .btn-action.danger{color:var(--support-red);border-color:#efbec4;background:#fff0f2}
  html.dark-mode .btn-action.danger{background:#4b222a}
  .btn-action.danger:hover{background:var(--support-red);color:#fff}
  
  /* Continuous server facts strip */
  .attrs-grid{
   display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin-bottom:22px;
   border:1px solid var(--support-line);border-radius:16px;background:var(--support-panel);
   box-shadow:0 8px 24px rgba(17,24,39,.04);overflow:hidden;
  }
  .attr-card{
   min-width:0;padding:17px 20px;background:transparent;display:grid;
   grid-template-columns:40px minmax(0,1fr);grid-template-rows:auto auto;column-gap:11px;align-items:center;
  }
  .attr-card + .attr-card{border-inline-start:1px solid var(--support-line)}
  .attr-label{grid-column:2;font-size:11px;font-weight:900;color:var(--support-muted);text-transform:uppercase}
  .attr-val{grid-column:1 / -1;display:grid;grid-template-columns:40px minmax(0,1fr);gap:11px;align-items:center;font-size:16px;font-weight:900;color:var(--support-ink)}
  .attr-val i{width:40px;height:40px;display:grid;place-items:center;grid-row:1 / span 2;border-radius:11px;background:var(--support-soft);color:var(--support-red);font-size:18px}
  .attr-val span{grid-column:2;overflow-wrap:anywhere}

  .support-workspace{display:grid;grid-template-columns:minmax(0,1.75fr) minmax(310px,.75fr);gap:22px;align-items:start}
  .support-primary-column,.support-context-column{min-width:0}
  .support-context-column{position:sticky;top:20px;display:flex;flex-direction:column;gap:18px}
  
  /* Details Body Sections */
  .detail-section{
   border:1px solid var(--support-line);border-radius:16px;
   background:var(--support-panel);box-shadow:var(--support-shadow);
   padding:22px;margin:0;
  }
  .section-title-wrap{
   display:flex;align-items:center;justify-content:space-between;gap:10px;
   padding-bottom:14px;border-bottom:1px solid var(--support-line);margin-bottom:20px;flex-wrap:wrap;
  }
  .section-title{
   margin:0;font-size:17px;font-weight:900;color:var(--support-ink);
   display:flex;align-items:center;gap:8px;
  }
  .section-title i{color:var(--support-red)}

  .page-feedback{
   display:flex;align-items:flex-start;gap:10px;margin-bottom:18px;padding:13px 15px;
   border-radius:12px;font-size:13px;font-weight:800;
  }
  .page-feedback.success{background:var(--support-green-bg);color:var(--support-green)}
  .page-feedback.error{background:#fff0f2;color:var(--support-red-dark)}
  html.dark-mode .page-feedback.error{background:#4b222a;color:#ff9aa4}
  html.dark-mode .detail-company,
  html.dark-mode .section-title i,
  html.dark-mode .ticket-composer summary,
  html.dark-mode .support-filter.active,
  html.dark-mode .field-error,
  html.dark-mode .attr-val i,
  html.dark-mode .btn-action.danger{color:#ff9aa4}

  .ticket-summary-counts{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .ticket-count{
   display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;
   background:var(--support-soft);color:var(--support-muted);font-size:13px;font-weight:900;
  }
  .ticket-count.open{background:#fff0f2;color:var(--support-red-dark)}
  html.dark-mode .ticket-count.open{background:#4b222a;color:#ff9aa4}
  .ticket-composer{margin-bottom:20px;border:1px solid color-mix(in srgb,var(--support-red) 24%,var(--support-line));border-radius:14px;background:color-mix(in srgb,var(--support-red) 4%,var(--support-soft))}
  html.dark-mode .ticket-composer{border-color:#434d5e}
  .ticket-composer summary{
   min-height:56px;display:flex;align-items:center;gap:10px;padding:0 17px;
   color:var(--support-red);font-size:16px;font-weight:900;cursor:pointer;list-style:none;
  }
  .ticket-composer summary::-webkit-details-marker{display:none}
  .ticket-composer summary::after{content:"+";margin-inline-start:auto;font-size:20px;font-weight:500}
  .ticket-composer[open] summary::after{content:"\2212"}
  .ticket-create-form{display:grid;gap:14px;padding:0 16px 16px;border-top:1px solid var(--support-line)}
  .ticket-create-form .form-group:first-child{padding-top:16px}
  .ticket-create-form label,.ticket-close-form label{font-size:15px}
  .ticket-create-form .form-control,.ticket-close-form .form-control{font-size:16px}
  .ticket-create-form .btn-action,.ticket-close-form .btn-action{font-size:14px}
  .field-error{margin:0;color:var(--support-red);font-size:13px;font-weight:800}
  .ticket-groups{display:grid;gap:22px}
  .ticket-group-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 2px 9px}
  .ticket-group-heading h3{display:flex;align-items:center;gap:8px;margin:0;color:var(--support-ink);font-size:15px;font-weight:900}
  .ticket-group-heading h3 i{color:var(--support-muted);font-size:15px}
  .ticket-group.open .ticket-group-heading h3 i{color:var(--support-red)}
  .ticket-group.closed .ticket-group-heading h3 i{color:var(--support-green)}
  .ticket-group-total{color:var(--support-muted);font-size:12px;font-weight:800;font-variant-numeric:tabular-nums}
  .ticket-list{display:grid;gap:14px}
  .ticket-record{padding:22px;border:1px solid var(--support-line);border-radius:14px;background:var(--support-panel);overflow:hidden}
  .ticket-record.open{background:color-mix(in srgb,var(--support-red) 4%,var(--support-panel))}
  .ticket-record.time-fast{--ticket-time-surface:#f1faf4;--ticket-time-soft:#e1f4e7;--ticket-time-edge:#82c99b;--ticket-time-ink:#0d6438;background:var(--ticket-time-surface)}
  .ticket-record.time-warning{--ticket-time-surface:#fff9e9;--ticket-time-soft:#fff0bd;--ticket-time-edge:#d8b840;--ticket-time-ink:#715300;background:var(--ticket-time-surface)}
  .ticket-record.time-critical{--ticket-time-surface:#fff2f4;--ticket-time-soft:#ffdfe3;--ticket-time-edge:#df8490;--ticket-time-ink:#a31626;background:var(--ticket-time-surface)}
  html.dark-mode .ticket-record.time-fast{--ticket-time-surface:#1b3026;--ticket-time-soft:#244c35;--ticket-time-edge:#4aa872;--ticket-time-ink:#8ce6af}
  html.dark-mode .ticket-record.time-warning{--ticket-time-surface:#332f1f;--ticket-time-soft:#51451c;--ticket-time-edge:#aa8d2f;--ticket-time-ink:#f4d36a}
  html.dark-mode .ticket-record.time-critical{--ticket-time-surface:#38242a;--ticket-time-soft:#552b34;--ticket-time-edge:#aa5360;--ticket-time-ink:#ff9aa4}
  .ticket-record-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:17px}
  .ticket-record-title{display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1}
  .ticket-title-icon{
   width:40px;height:40px;display:grid;place-items:center;flex:0 0 40px;border-radius:11px;
   background:var(--support-soft);color:var(--support-muted);font-size:18px;
  }
  .ticket-record.open .ticket-title-icon{background:#fff0f2;color:var(--support-red-dark)}
  .ticket-record.closed .ticket-title-icon{background:var(--support-green-bg);color:var(--support-green)}
  .ticket-record.time-fast .ticket-title-icon,
  .ticket-record.time-warning .ticket-title-icon,
  .ticket-record.time-critical .ticket-title-icon{background:var(--ticket-time-soft);color:var(--ticket-time-ink)}
  html.dark-mode .ticket-record.open .ticket-title-icon{background:#4b222a;color:#ff9aa4}
  html.dark-mode .ticket-record.time-fast .ticket-title-icon,
  html.dark-mode .ticket-record.time-warning .ticket-title-icon,
  html.dark-mode .ticket-record.time-critical .ticket-title-icon{background:var(--ticket-time-soft);color:var(--ticket-time-ink)}
  .ticket-title-copy{display:grid;gap:4px;min-width:0}
  .ticket-number{
   direction:ltr;display:inline-flex;width:max-content;color:var(--support-muted);
   font:800 12px ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-variant-numeric:tabular-nums;
  }
  .ticket-record h4{margin:0;font-size:19px;line-height:1.45;font-weight:900;color:var(--support-ink);overflow-wrap:anywhere}
  .ticket-badges{display:flex;align-items:center;justify-content:flex-end;gap:7px;flex-wrap:wrap}
  .ticket-state{display:inline-flex;align-items:center;gap:6px;flex-shrink:0;padding:6px 10px;border-radius:999px;font-size:13px;font-weight:800;white-space:nowrap}
  .ticket-state i{font-size:12px}
  .ticket-state.open{background:#fff0f2;color:var(--support-red-dark)}
  .ticket-state.closed{background:var(--support-green-bg);color:var(--support-green)}
  html.dark-mode .ticket-state.open{background:#4b222a;color:#ff9aa4}
  .ticket-time-badge{
   display:inline-flex;align-items:center;gap:6px;flex-shrink:0;padding:6px 10px;border:1px solid var(--ticket-time-edge);
   border-radius:999px;background:var(--ticket-time-soft);color:var(--ticket-time-ink);font-size:12px;font-weight:900;white-space:nowrap;
  }
  .ticket-time-badge i{font-size:12px}
  .ticket-description-block{margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--support-line)}
  .ticket-content-label{display:flex;align-items:center;gap:7px;margin-bottom:7px;color:var(--support-muted);font-size:12px;font-weight:800}
  .ticket-content-label i{color:var(--support-red);font-size:14px}
  .ticket-description{max-width:75ch;margin:0;color:var(--support-ink);font-size:16px;font-weight:500;line-height:1.85;white-space:pre-wrap;overflow-wrap:anywhere}
  .ticket-meta-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));padding:12px 14px;border-radius:11px;background:var(--support-soft)}
  .ticket-open-meta{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
  .ticket-meta-item{display:grid;grid-template-columns:34px minmax(0,1fr);align-items:center;gap:10px;min-width:0}
  .ticket-meta-item + .ticket-meta-item{border-inline-start:1px solid var(--support-line);padding-inline-start:14px}
  .ticket-meta-icon{width:34px;height:34px;display:grid;place-items:center;border-radius:9px;background:var(--support-panel);color:var(--support-muted);font-size:14px}
  .ticket-meta-copy{display:grid;gap:2px;min-width:0}
  .ticket-meta-label{color:var(--support-muted);font-size:12px;font-weight:700;line-height:1.35}
  .ticket-meta-value{color:var(--support-ink);font-size:14px;font-weight:800;line-height:1.55;overflow-wrap:anywhere}
  .ticket-resolution{margin-top:17px;padding:17px;border-radius:11px;background:var(--support-soft);border:1px solid var(--support-line)}
  .ticket-resolution-title{display:flex;align-items:center;gap:9px;margin-bottom:9px;color:var(--support-green)}
  .ticket-resolution-title i{width:30px;height:30px;display:grid;place-items:center;border-radius:8px;background:var(--support-green-bg);font-size:14px}
  .ticket-resolution-title strong{font-size:15px;font-weight:900}
  .ticket-resolution p{max-width:75ch;margin:0;color:var(--support-ink);font-size:16px;font-weight:500;line-height:1.85;white-space:pre-wrap;overflow-wrap:anywhere}
  .ticket-completion-meta{margin-top:12px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
  .ticket-close-form{display:grid;gap:12px;margin-top:17px;padding:17px;border:1px solid var(--support-line);border-radius:11px;background:var(--support-soft)}
  .ticket-close-form label{display:flex;align-items:center;gap:8px;color:var(--support-ink);font-weight:900}
  .ticket-close-form label i{color:var(--support-red);font-size:16px}
  .ticket-close-form textarea{min-height:92px}
  .ticket-close-actions{display:flex;justify-content:flex-end}
  .ticket-empty{padding:30px 18px;text-align:center;border:1px dashed #d1d9e6;border-radius:14px;background:var(--support-soft)}
  .ticket-empty i{display:block;margin-bottom:8px;color:var(--support-muted);font-size:25px}
  .ticket-empty strong{display:block;margin-bottom:4px;font-size:17px;color:var(--support-ink)}
  .ticket-empty p{margin:0;color:var(--support-muted);font-size:14px}
  #supportTickets .section-title{font-size:20px}
  .context-section .section-title-wrap{margin-bottom:16px}
  .server-notes-copy{margin:0;color:var(--support-ink);font-size:14px;line-height:1.85;white-space:pre-wrap;overflow-wrap:anywhere}
  .server-notes-copy.empty{color:var(--support-muted)}

  .support-filters{display:flex;align-items:center;gap:5px;padding:4px;border-radius:11px;background:var(--support-soft)}
  .support-filter{
   min-height:36px;padding:0 14px;border:0;border-radius:8px;background:transparent;
   color:var(--support-muted);font:800 12px var(--font-primary);cursor:pointer;transition:all .16s ease-out
  }
  .support-filter:hover{color:var(--support-ink)}
  .support-filter.active{background:var(--support-panel);color:var(--support-red);box-shadow:0 3px 10px rgba(17,24,39,.08)}
  
  /* IP Chips List */
  .ips-chips-list{display:flex;flex-direction:column;gap:10px}
  .ip-chip{
   display:flex;align-items:center;justify-content:space-between;gap:12px;
   padding:10px 14px;border-radius:12px;border:1px solid var(--support-line);
   background:var(--support-soft);transition:all .15s ease-out;
  }
  .ip-chip-content{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
  .ip-state-dot{width:8px;height:8px;border-radius:50%;background:#9aa3b1;flex-shrink:0}
  .ip-state-dot.online{background:var(--support-green);box-shadow:0 0 0 2px rgba(15,116,64,.2)}
  .ip-chip code{
   font:900 13.5px ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
   color:var(--support-ink);direction:ltr;text-align:start;overflow-wrap:anywhere;
  }
  .ip-chip-label{
   display:inline-block;padding:3px 8px;border-radius:6px;
   background:var(--support-gray-bg);color:var(--support-muted);font-size:10px;font-weight:900;
  }
  .ip-chip-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
  .btn-ip-action{
   width:30px;height:30px;display:grid;place-items:center;
   border:none;border-radius:7px;background:transparent;
   color:var(--support-muted);font-size:13px;cursor:pointer;transition:all .15s;
  }
  .btn-ip-action:hover{color:var(--support-ink);background:var(--support-gray-bg)}
  .btn-ip-action.btn-copy:hover{color:#2563eb}
  .btn-ip-action.btn-delete:hover{color:var(--support-red);background:#fff0f2}
  html.dark-mode .btn-ip-action.btn-delete:hover{background:#4b222a}
  
  /* Inline Add IP Box */
  .add-ip-box-full{
   padding:14px;border:1px dashed #d1d9e6;border-radius:14px;
   background:var(--support-soft);margin-top:16px;
  }
  html.dark-mode .add-ip-box-full{border-color:#434d5e}
  .add-ip-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .support-context-column .add-ip-row{display:grid;grid-template-columns:1fr}
  .support-context-column .select-ts-ip-full,
  .support-context-column .input-ip-val-full,
  .support-context-column .input-ip-label-full{width:100%;min-width:0}
  .support-context-column .add-ip-row .btn-action{width:100%}
  .select-ts-ip-full{
   flex:1;min-width:200px;height:42px;padding:0 12px;
   border:1px solid var(--support-line);border-radius:9px;
   background:var(--support-panel);color:var(--support-ink);font:700 13px var(--font-primary);outline:none;
  }
  .input-ip-val-full{
   flex:1.5;min-width:180px;height:42px;padding:0 12px;
   border:1px solid var(--support-line);border-radius:9px;
   background:var(--support-panel);color:var(--support-ink);font:800 13.5px ui-monospace,Consolas,monospace;
   outline:none;direction:ltr;
  }
  .input-ip-label-full{
   flex:1;min-width:130px;height:42px;padding:0 12px;
   border:1px solid var(--support-line);border-radius:9px;
   background:var(--support-panel);color:var(--support-ink);font:700 12px var(--font-primary);outline:none;
  }
  
  /* Modal */
  .support-modal-backdrop{
   position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);
   z-index:999;display:none;place-items:center;padding:16px;overflow-y:auto;animation:fadeIn .18s ease-out forwards;
  }
  .support-modal-backdrop.active{display:grid}
  .support-modal{
   width:100%;max-width:540px;background:var(--support-panel);border:1px solid var(--support-line);
   max-height:calc(100dvh - 32px);display:flex;flex-direction:column;
   border-radius:20px;box-shadow:0 24px 50px rgba(0,0,0,.2);overflow:hidden;
   animation:scaleUp .2s cubic-bezier(.16,1,.3,1) forwards;
  }
  .modal-header{
   display:flex;align-items:center;justify-content:space-between;padding:20px 24px;
   border-bottom:1px solid var(--support-line);
  }
  .modal-header h3{margin:0;font-size:18px;font-weight:900;color:var(--support-ink)}
  .btn-modal-close{
   width:34px;height:34px;display:grid;place-items:center;border:none;border-radius:9px;
   background:var(--support-soft);color:var(--support-muted);font-size:17px;cursor:pointer;
  }
  .btn-modal-close:hover{color:var(--support-ink)}
  .modal-body{padding:24px;display:flex;flex-direction:column;gap:16px;min-height:0;overflow-y:auto}
  .form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .form-group{display:flex;flex-direction:column;gap:7px}
  .form-group label{font-size:13px;font-weight:900;color:var(--support-ink)}
  .form-control{
   width:100%;height:44px;padding:0 14px;border:1px solid var(--support-line);border-radius:10px;
   background:var(--support-soft);color:var(--support-ink);font:inherit;font-size:14px;outline:none;
  }
  .form-control:focus{border-color:var(--support-red);box-shadow:0 0 0 3px rgba(220,38,55,.09);background:var(--support-panel)}
  textarea.form-control{height:auto;min-height:80px;padding:12px;resize:vertical}
  .modal-footer{
   display:flex;align-items:center;justify-content:flex-end;gap:12px;padding:18px 24px;
   border-top:1px solid var(--support-line);background:var(--support-soft);
  }
  
  /* Toast */
  .support-toast-container{
   position:fixed;bottom:24px;inset-inline-end:24px;z-index:1100;
   display:flex;flex-direction:column;gap:10px;pointer-events:none;
  }
  .support-toast{
   padding:14px 20px;border-radius:13px;background:var(--support-ink);color:#fff;
   font:800 14px var(--font-primary);box-shadow:0 14px 34px rgba(0,0,0,.26);
   display:flex;align-items:center;gap:10px;pointer-events:auto;animation:toastIn .24s cubic-bezier(.2,.8,.4,1) forwards;
  }
  .support-toast.success{background:#0e6939}
  .support-toast.error{background:var(--support-red-dark)}
  
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes scaleUp{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
  

  @media(max-width:1180px){
   .support-workspace{grid-template-columns:1fr}
   .support-context-column{position:static;display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}
  }
  @media(max-width:900px){
   .support-shell{display:block}.support-main{width:100%;padding:20px 18px 38px}.support-menu, .crm-topbar-menu-btn, .menu-button{display:grid}
   body.crm-side-open{overflow:hidden}
   .terminal-layout{grid-template-columns:1fr;min-height:auto}
   .terminal-left{border-inline-end:0;border-bottom:1px solid rgba(255,255,255,.1)}
  }
  @media(max-width:768px){
   .detail-topbar, .crm-topbar, .topbar{flex-direction:column;align-items:stretch;gap:12px}
   .crm-topbar-left, .topbar-left{width:100%;justify-content:flex-start}
   .crm-topbar-right, .top-actions{width:100%;justify-content:space-between}
  }
  @media(max-width:620px){
   .support-main{padding-inline:12px}
   .detail-header-panel{padding:20px 14px;align-items:stretch}
   .detail-identity{align-items:flex-start;gap:12px}
   .detail-quick-actions{width:100%;justify-content:stretch}
   .detail-quick-actions .btn-detail-action{flex:1;min-height:44px;justify-content:center}
  }
   .detail-actions .btn-action.danger{width:44px;padding:0}
   .detail-actions .btn-action.danger span{display:none}
   .attrs-grid{grid-template-columns:1fr}
   .attr-card + .attr-card,.attr-card:nth-child(3){border-inline-start:0;border-top:1px solid var(--support-line)}
   .support-context-column{grid-template-columns:1fr}
   .detail-section{padding:18px 15px}
   .support-modal-backdrop{padding:8px;place-items:start center}
   .support-modal{max-height:calc(100dvh - 16px)}
   .add-ip-row{flex-direction:column;align-items:stretch}
    .ticket-record{padding:17px 14px}
    .ticket-list{gap:12px}
    .ticket-record-head{gap:10px;flex-wrap:wrap}
    .ticket-badges{width:100%;justify-content:flex-start;padding-inline-start:46px}
    .ticket-title-icon{width:36px;height:36px;flex-basis:36px}
    .ticket-record h4{font-size:17px}
    .ticket-meta-grid,.ticket-completion-meta{grid-template-columns:1fr}
    .ticket-meta-item + .ticket-meta-item{border-inline-start:0;border-top:1px solid var(--support-line);padding-inline-start:0;padding-top:11px;margin-top:11px}
    .ticket-close-form{padding:14px}
    .ticket-close-actions .btn-action{width:100%}
  }
 </style>
</head>
<body>
@include('partials.page-loader')
@php
 $osIcon = match (strtolower((string) ($device['os'] ?? 'linux'))) {
     'windows' => 'bi-windows',
     'macos', 'darwin' => 'bi-apple',
     'android' => 'bi-android2',
     'ios' => 'bi-phone',
     'linux' => 'bi-ubuntu',
     default => 'bi-pc-display',
 };
 $lastActivity = !empty($device['lastActivity'])
     ? \Illuminate\Support\Carbon::parse($device['lastActivity'])
         ->locale(app()->getLocale())
         ->diffForHumans()
     : __('crm.never_connected');
 $isOnline = (bool) ($device['online'] ?? false);
 $deviceKey = (string) ($device['id'] ?? '');
 $allIpsList = $device['all_ips'] ?? [];
 $imagePath = $device['image_path'] ?? null;
@endphp
<div class="support-shell">
 @include('partials.crm-sidebar')
 <button class="crm-overlay" id="supportSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>
 <main class="support-main">
  
  @include('partials.topbar', [
    'title' => __('crm.technical_support'),
    'subtitle' => $device['name'] ?? '',
    'icon' => 'bi-headset',
    'backUrl' => route('v2.technical-support.index'),
    'backTitle' => __('crm.back_to_cards'),
  ])

  @if (session('success'))
   <div class="page-feedback success" role="status">
    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
    <span>{{ session('success') }}</span>
   </div>
  @endif
  @if ($errors->any())
   <div class="page-feedback error" role="alert">
    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
    <span>{{ $errors->first() }}</span>
   </div>
  @endif

  <!-- Main Hero Panel -->
  <section class="detail-header-panel" data-status="{{ $isOnline ? 'online' : 'offline' }}">
   <div class="detail-identity">
    <span class="detail-os-icon">
     @if ($imagePath)
      <img src="{{ $imagePath }}" alt="{{ $device['name'] }}" class="card-custom-avatar">
     @else
      <i class="bi {{ $osIcon }}" aria-hidden="true"></i>
     @endif
    </span>
    <div class="detail-title-wrap">
     @if (!empty($device['company_name']))
      <div class="detail-company">
       <i class="bi bi-building" aria-hidden="true"></i>
       <span>{{ $device['company_name'] }}</span>
      </div>
     @endif
     <h1 class="detail-title">
      {{ $device['name'] }}
      @if (!empty($device['self']))
       <span class="badge-tag badge-self">{{ __('crm.this_server') }}</span>
      @endif
     </h1>
     <div class="detail-subline">
      @if (!empty($device['dnsName']))
       <span class="detail-dns"><i class="bi bi-globe2" aria-hidden="true"></i>{{ $device['dnsName'] }}</span>
      @endif
      <span><i class="bi {{ $osIcon }}" aria-hidden="true"></i>{{ ucfirst($device['os'] ?? 'unknown') }}</span>
      <span><i class="bi bi-hdd-network" aria-hidden="true"></i>{{ count($allIpsList) }} {{ __('crm.ip_addresses') }}</span>
     </div>
    </div>
   </div>

   <div class="detail-actions">
    <span class="card-status-badge {{ $isOnline ? 'online' : '' }}">
     @if (count($allIpsList) > 0)
      {{ $isOnline ? __('crm.card_status_online_by_ip') : __('crm.card_status_offline_by_ip') }}
     @else
      {{ $isOnline ? __('crm.online') : __('crm.offline') }}
     @endif
    </span>
    @can('technical_support.manage')
    <button type="button" class="btn-action" id="btnOpenEditModal">
     <i class="bi bi-pencil-square" aria-hidden="true"></i>
     <span>{{ __('crm.edit') }}</span>
    </button>
    <button type="button" class="btn-action danger" id="btnDeleteCard">
     <i class="bi bi-trash3" aria-hidden="true"></i>
     <span>{{ __('crm.delete') }}</span>
    </button>
    @endcan
   </div>
  </section>

  <section class="attrs-grid" aria-label="{{ __('crm.server_details') }}">
   <div class="attr-card">
    <span class="attr-label">{{ __('crm.employees_count') }}</span>
    <div class="attr-val">
     <i class="bi bi-people"></i>
     <span>{{ number_format($device['employees_count'] ?? 0) }} {{ __('crm.employees') }}</span>
    </div>
   </div>

   <div class="attr-card">
    <span class="attr-label">{{ __('crm.lines_count') }}</span>
    <div class="attr-val">
     <i class="bi bi-telephone"></i>
     <span>{{ number_format($device['lines_count'] ?? 0) }} {{ __('crm.lines') }}</span>
    </div>
   </div>

   <div class="attr-card">
    <span class="attr-label">{{ __('crm.operating_system') }}</span>
    <div class="attr-val">
     <i class="bi {{ $osIcon }}"></i>
     <span>{{ ucfirst($device['os'] ?? 'unknown') }}</span>
    </div>
   </div>

   <div class="attr-card">
    <span class="attr-label">{{ __('crm.last_activity') }}</span>
    <div class="attr-val">
     <i class="bi bi-clock-history"></i>
     <span>{{ !empty($device['active']) ? __('crm.active_connection') : $lastActivity }}</span>
    </div>
   </div>
  </section>

  @php
   $openTickets = $tickets->where('status', \App\Models\TechnicalSupportTicket::STATUS_OPEN);
   $closedTickets = $tickets->where('status', \App\Models\TechnicalSupportTicket::STATUS_CLOSED);
  @endphp
  <div class="support-workspace">
   <div class="support-primary-column">
  <section class="detail-section ticket-panel" id="supportTickets">
   <div class="section-title-wrap">
    <h2 class="section-title">
     <i class="bi bi-ticket-detailed"></i>
     <span>{{ __('crm.support_tickets') }}</span>
    </h2>
    <div class="ticket-summary-counts" aria-label="{{ __('crm.support_tickets') }}">
     <span class="ticket-count open">{{ __('crm.open_tickets') }}: {{ $openTickets->count() }}</span>
     <span class="ticket-count">{{ __('crm.closed_tickets') }}: {{ $closedTickets->count() }}</span>
    </div>
   </div>

   @can('technical_support.manage')
   <details class="ticket-composer" @if ($errors->has('subject')) open @endif>
    <summary>
     <i class="bi bi-plus-circle" aria-hidden="true"></i>
     <span>{{ __('crm.open_support_ticket') }}</span>
    </summary>
    <form class="ticket-create-form" method="POST" action="{{ route('v2.technical-support.tickets.store', $deviceKey) }}">
     @csrf
     <div class="form-group">
      <label for="ticketSubject">{{ __('crm.ticket_subject') }} *</label>
      <input id="ticketSubject" class="form-control" type="text" name="subject" value="{{ old('subject') }}" maxlength="255" placeholder="{{ __('crm.ticket_subject_placeholder') }}" required>
      @error('subject')<p class="field-error">{{ $message }}</p>@enderror
     </div>
     <div class="ticket-close-actions">
      <button class="btn-action primary" type="submit">
       <i class="bi bi-ticket-perforated" aria-hidden="true"></i>
       <span>{{ __('crm.create_support_ticket') }}</span>
      </button>
     </div>
    </form>
   </details>
   @endcan

   @if ($tickets->isEmpty())
    <div class="ticket-empty">
     <i class="bi bi-ticket-detailed" aria-hidden="true"></i>
     <strong>{{ __('crm.no_support_tickets') }}</strong>
     <p>{{ __('crm.no_support_tickets_description') }}</p>
    </div>
   @else
     <div class="ticket-groups">
      @if ($openTickets->isNotEmpty())
       <div class="ticket-group open">
        <div class="ticket-group-heading">
         <h3><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span>{{ __('crm.open_tickets') }}</span></h3>
         <span class="ticket-group-total">{{ $openTickets->count() }}</span>
        </div>
        <div class="ticket-list">
          @foreach ($openTickets as $ticket)
           @php
             $ticketElapsedSeconds = $ticket->supportTimeSeconds();
             $ticketTimeBand = $ticket->supportTimeBand($ticketElapsedSeconds);
            $ticketElapsed = $ticketElapsedSeconds !== null
                ? \Carbon\CarbonInterval::seconds($ticketElapsedSeconds)->cascade()->locale(app()->getLocale())->forHumans(['parts' => 2])
                : null;
            $ticketTimeLabel = match ($ticketTimeBand) {
                'fast' => __('crm.ticket_time_fast'),
                'warning' => __('crm.ticket_time_warning'),
                'critical' => __('crm.ticket_time_critical'),
                default => null,
            };
           @endphp
           <article class="ticket-record open time-{{ $ticketTimeBand }}">
           <div class="ticket-record-head">
            <div class="ticket-record-title">
             <span class="ticket-title-icon"><i class="bi bi-ticket-detailed" aria-hidden="true"></i></span>
             <div class="ticket-title-copy">
              <bdi class="ticket-number">#{{ str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT) }}</bdi>
              <h4><bdi>{{ $ticket->subject }}</bdi></h4>
             </div>
            </div>
             <div class="ticket-badges">
              @if ($ticketTimeLabel)
               <span class="ticket-time-badge"><i class="bi bi-stopwatch" aria-hidden="true"></i>{{ $ticketTimeLabel }}</span>
              @endif
              <span class="ticket-state open"><i class="bi bi-hourglass-split" aria-hidden="true"></i>{{ __('crm.ticket_status_open') }}</span>
             </div>
           </div>
           @if (!empty($ticket->description))
            <div class="ticket-description-block">
             <div class="ticket-content-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i><span>{{ __('crm.issue_description') }}</span></div>
             <p class="ticket-description"><bdi>{{ $ticket->description }}</bdi></p>
            </div>
           @endif
            <div class="ticket-meta-grid ticket-open-meta">
            <div class="ticket-meta-item">
             <span class="ticket-meta-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
             <div class="ticket-meta-copy">
              <span class="ticket-meta-label">{{ __('crm.opened_by') }}</span>
              <bdi class="ticket-meta-value">{{ $ticket->openedBy?->name ?? __('crm.employee_unavailable') }}</bdi>
             </div>
            </div>
             <div class="ticket-meta-item">
              <span class="ticket-meta-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
              <div class="ticket-meta-copy">
               <span class="ticket-meta-label">{{ __('crm.opened_at') }}</span>
               <time class="ticket-meta-value" datetime="{{ $ticket->opened_at?->toIso8601String() }}">{{ $ticket->opened_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time>
              </div>
             </div>
             @if ($ticketElapsed)
              <div class="ticket-meta-item">
               <span class="ticket-meta-icon"><i class="bi bi-stopwatch" aria-hidden="true"></i></span>
               <div class="ticket-meta-copy">
                <span class="ticket-meta-label">{{ __('crm.support_time_elapsed') }}</span>
                <bdi class="ticket-meta-value">{{ $ticketElapsed }}</bdi>
               </div>
              </div>
             @endif
            </div>
           @can('technical_support.manage')
           <form class="ticket-close-form" method="POST" action="{{ route('v2.technical-support.tickets.close', [$deviceKey, $ticket]) }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
             <label for="ticketResolution{{ $ticket->id }}"><i class="bi bi-tools" aria-hidden="true"></i><span>{{ __('crm.work_performed') }} *</span></label>
             <textarea id="ticketResolution{{ $ticket->id }}" class="form-control" name="resolution" maxlength="5000" placeholder="{{ __('crm.work_performed_placeholder') }}" required></textarea>
            </div>
            <div class="ticket-close-actions">
             <button class="btn-action primary" type="submit">
              <i class="bi bi-check2-circle" aria-hidden="true"></i>
              <span>{{ __('crm.close_and_save_ticket') }}</span>
             </button>
            </div>
           </form>
           @endcan
          </article>
         @endforeach
        </div>
       </div>
      @endif

      @if ($closedTickets->isNotEmpty())
       <div class="ticket-group closed">
        <div class="ticket-group-heading">
         <h3><i class="bi bi-check2-circle" aria-hidden="true"></i><span>{{ __('crm.closed_tickets') }}</span></h3>
         <span class="ticket-group-total">{{ $closedTickets->count() }}</span>
        </div>
        <div class="ticket-list">
          @foreach ($closedTickets as $ticket)
           @php
            $ticketElapsedSeconds = $ticket->supportTimeSeconds();
            $ticketTimeBand = $ticket->supportTimeBand($ticketElapsedSeconds);
            $ticketDuration = $ticketElapsedSeconds !== null
                ? \Carbon\CarbonInterval::seconds($ticketElapsedSeconds)->cascade()->locale(app()->getLocale())->forHumans(['parts' => 2])
                : null;
            $ticketTimeLabel = match ($ticketTimeBand) {
                'fast' => __('crm.ticket_time_fast'),
                'warning' => __('crm.ticket_time_warning'),
                'critical' => __('crm.ticket_time_critical'),
                default => null,
            };
           @endphp
           <article class="ticket-record closed time-{{ $ticketTimeBand }}">
           <div class="ticket-record-head">
            <div class="ticket-record-title">
             <span class="ticket-title-icon"><i class="bi bi-ticket-detailed" aria-hidden="true"></i></span>
             <div class="ticket-title-copy">
              <bdi class="ticket-number">#{{ str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT) }}</bdi>
              <h4><bdi>{{ $ticket->subject }}</bdi></h4>
             </div>
            </div>
             <div class="ticket-badges">
              @if ($ticketTimeLabel)
               <span class="ticket-time-badge"><i class="bi bi-stopwatch" aria-hidden="true"></i>{{ $ticketTimeLabel }}</span>
              @endif
              <span class="ticket-state closed"><i class="bi bi-check2" aria-hidden="true"></i>{{ __('crm.ticket_status_closed') }}</span>
             </div>
           </div>
           @if (!empty($ticket->description))
            <div class="ticket-description-block">
             <div class="ticket-content-label"><i class="bi bi-chat-left-text" aria-hidden="true"></i><span>{{ __('crm.issue_description') }}</span></div>
             <p class="ticket-description"><bdi>{{ $ticket->description }}</bdi></p>
            </div>
           @endif
           <div class="ticket-meta-grid">
            <div class="ticket-meta-item">
             <span class="ticket-meta-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
             <div class="ticket-meta-copy">
              <span class="ticket-meta-label">{{ __('crm.opened_by') }}</span>
              <bdi class="ticket-meta-value">{{ $ticket->openedBy?->name ?? __('crm.employee_unavailable') }}</bdi>
             </div>
            </div>
            <div class="ticket-meta-item">
             <span class="ticket-meta-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
             <div class="ticket-meta-copy">
              <span class="ticket-meta-label">{{ __('crm.opened_at') }}</span>
              <time class="ticket-meta-value" datetime="{{ $ticket->opened_at?->toIso8601String() }}">{{ $ticket->opened_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time>
             </div>
            </div>
           </div>
           <div class="ticket-resolution">
            <div class="ticket-resolution-title"><i class="bi bi-tools" aria-hidden="true"></i><strong>{{ __('crm.work_performed') }}</strong></div>
            <p><bdi>{{ $ticket->resolution }}</bdi></p>
           </div>
           <div class="ticket-meta-grid ticket-completion-meta">
            <div class="ticket-meta-item">
             <span class="ticket-meta-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
             <div class="ticket-meta-copy">
              <span class="ticket-meta-label">{{ __('crm.closed_by') }}</span>
              <bdi class="ticket-meta-value">{{ $ticket->closedBy?->name ?? __('crm.employee_unavailable') }}</bdi>
             </div>
            </div>
            <div class="ticket-meta-item">
             <span class="ticket-meta-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
             <div class="ticket-meta-copy">
              <span class="ticket-meta-label">{{ __('crm.closed_at') }}</span>
              <time class="ticket-meta-value" datetime="{{ $ticket->closed_at?->toIso8601String() }}">{{ $ticket->closed_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A') }}</time>
             </div>
            </div>
            @if ($ticketDuration)
             <div class="ticket-meta-item">
              <span class="ticket-meta-icon"><i class="bi bi-stopwatch" aria-hidden="true"></i></span>
              <div class="ticket-meta-copy">
               <span class="ticket-meta-label">{{ __('crm.support_time_spent') }}</span>
               <bdi class="ticket-meta-value">{{ $ticketDuration }}</bdi>
              </div>
             </div>
            @endif
           </div>
          </article>
         @endforeach
        </div>
       </div>
      @endif
     </div>
   @endif
  </section>
   </div>

   <aside class="support-context-column" aria-label="{{ __('crm.server_details') }}">
   <section class="detail-section context-section">
    <div class="section-title-wrap">
     <h2 class="section-title">
      <i class="bi bi-card-text"></i>
      <span>{{ __('crm.notes') }}</span>
     </h2>
    </div>
    <p class="server-notes-copy {{ empty($device['notes']) ? 'empty' : '' }}">{{ $device['notes'] ?: __('crm.no_server_notes') }}</p>
   </section>

  <section class="detail-section context-section">
   <div class="section-title-wrap">
    <h2 class="section-title">
     <i class="bi bi-hdd-network"></i>
     <span>{{ __('crm.ip_addresses') }}</span>
     <span style="font-size:12px;color:var(--support-muted)">({{ count($allIpsList) }})</span>
    </h2>

    <div class="support-filters" role="group" aria-label="{{ __('crm.ip_status_filter') }}">
     <button class="support-filter active" type="button" data-ip-filter="all" aria-pressed="true">{{ __('crm.all') }}</button>
     <button class="support-filter" type="button" data-ip-filter="online" aria-pressed="false">{{ __('crm.online') }}</button>
     <button class="support-filter" type="button" data-ip-filter="offline" aria-pressed="false">{{ __('crm.offline') }}</button>
    </div>
   </div>

   <div class="ips-chips-list" id="detailIpsList">
    @forelse ($allIpsList as $ipItem)
     @php
      $ipVal = is_array($ipItem) ? ($ipItem['ip'] ?? '') : (string)$ipItem;
      $ipLabel = is_array($ipItem) ? ($ipItem['label'] ?? null) : null;
      $isCustomIp = is_array($ipItem) ? ($ipItem['is_custom'] ?? false) : false;
      $ipId = is_array($ipItem) ? ($ipItem['id'] ?? null) : null;
      $ipOnline = is_array($ipItem) ? ($ipItem['online'] ?? false) : false;
     @endphp
     <div class="ip-chip" id="ipChip-{{ $ipId ?? md5($ipVal) }}" data-ip-status="{{ $ipOnline ? 'online' : 'offline' }}">
      <div class="ip-chip-content">
        <i class="ip-state-dot {{ $ipOnline ? 'online' : '' }}" aria-hidden="true"></i>
        <span class="visually-hidden">{{ $ipOnline ? __('crm.online') : __('crm.offline') }}</span>
       <code class="ip-val-text">{{ $ipVal }}</code>
       @if (!empty($ipLabel))
        <span class="ip-chip-label">{{ $ipLabel }}</span>
       @elseif ($isCustomIp)
        <span class="ip-chip-label">{{ __('crm.custom_ip') }}</span>
       @endif
      </div>
      <div class="ip-chip-actions">
       <button 
        type="button" 
        class="btn-ip-action btn-copy" 
        data-copy-ip="{{ $ipVal }}" 
        title="{{ __('crm.copy_ip') }}"
       >
        <i class="bi bi-clipboard" aria-hidden="true"></i>
       </button>
       @if ($isCustomIp && $ipId)
        <button 
         type="button" 
         class="btn-ip-action btn-delete" 
         data-delete-ip-id="{{ $ipId }}"
         title="{{ __('crm.delete') }}"
        >
         <i class="bi bi-trash3" aria-hidden="true"></i>
        </button>
       @endif
      </div>
     </div>
    @empty
     <p style="margin:0;color:var(--support-muted);font-size:13px">{{ __('crm.no_ips_yet') }}</p>
    @endforelse
   </div>

   <!-- Form to Add IP -->
   @can('technical_support.manage')
   <div class="add-ip-box-full">
    <form id="formAddIpShow">
     <div class="add-ip-row">
       @if (count($tailscaleIpsList) > 0)
        <label class="visually-hidden" for="selectShowTsIp">{{ __('crm.select_tailscale_ip') }}</label>
        <select class="select-ts-ip-full" id="selectShowTsIp">
         <option value="">{{ __('crm.choose_tailscale_ip_placeholder') }}</option>
         @foreach ($tailscaleIpsList as $tsItem)
          <option value="{{ $tsItem['ip'] }}">{{ $tsItem['ip'] }}</option>
         @endforeach
       </select>
      @endif

       <label class="visually-hidden" for="inputShowIpVal">{{ __('crm.initial_ip') }}</label>
       <input 
       type="text" 
       id="inputShowIpVal"
       class="input-ip-val-full" 
       placeholder="{{ __('crm.enter_ip_placeholder') }}" 
       required 
       autocomplete="off"
      >
       <label class="visually-hidden" for="inputShowIpLabel">{{ __('crm.ip_label_placeholder') }}</label>
       <input 
       type="text" 
       id="inputShowIpLabel"
       class="input-ip-label-full" 
       placeholder="{{ __('crm.ip_label_placeholder') }}" 
       autocomplete="off"
      >

      <button type="submit" class="btn-action primary" style="min-height:40px">
       <i class="bi bi-plus-lg"></i>
       <span>{{ __('crm.add_ip') }}</span>
      </button>
     </div>
     <div class="ip-inline-error" id="showIpError" role="alert" aria-live="assertive" style="display:none;margin-top:8px"></div>
    </form>
    </div>
   @endcan
   </section>
   </aside>
  </div>
  </main>
</div>

<!-- Edit Support Card Modal (with Image Upload & IP options & Notes) -->
<div class="support-modal-backdrop" id="editCardModal" role="dialog" aria-modal="true" aria-labelledby="editCardModalTitle" aria-hidden="true">
 <div class="support-modal">
  <div class="modal-header">
   <h3 id="editCardModalTitle">{{ __('crm.edit_support_card') }}</h3>
   <button type="button" class="btn-modal-close" id="btnCloseEditModal" aria-label="{{ __('crm.cancel') }}">
    <i class="bi bi-x-lg"></i>
   </button>
  </div>
  <form id="formEditCard" method="POST" action="{{ route('v2.technical-support.cards.update', $deviceKey) }}" enctype="multipart/form-data">
   @csrf
   @method('PUT')
   <div class="modal-body">
    <div class="form-group">
     <label for="editCompanyName">{{ __('crm.company_name') }}</label>
     <input type="text" id="editCompanyName" name="company_name" class="form-control" value="{{ $device['company_name'] ?? '' }}" placeholder="{{ __('crm.company_name_placeholder') }}" autocomplete="off">
    </div>

    <div class="form-group">
     <label for="editCardName">{{ __('crm.card_name') }} *</label>
     <input type="text" id="editCardName" name="name" class="form-control" value="{{ $device['name'] }}" required autocomplete="off">
    </div>

    <div class="form-group">
     <label for="editCardImage">{{ __('crm.card_image') }}</label>
     <input type="file" id="editCardImage" name="image" class="form-control" accept="image/*">
    </div>

    <div class="form-row-2">
     <div class="form-group">
      <label for="editEmployeesCount">{{ __('crm.employees_count') }}</label>
      <input type="number" id="editEmployeesCount" name="employees_count" class="form-control" value="{{ $device['employees_count'] ?? 0 }}" min="0">
     </div>
     <div class="form-group">
      <label for="editLinesCount">{{ __('crm.lines_count') }}</label>
      <input type="number" id="editLinesCount" name="lines_count" class="form-control" value="{{ $device['lines_count'] ?? 0 }}" min="0">
     </div>
    </div>

    <div class="form-group">
     <label for="editCardOs">{{ __('crm.operating_system') }} *</label>
     <select id="editCardOs" name="os" class="form-control" required>
      <option value="linux" @selected(($device['os'] ?? '') === 'linux')>Linux / Ubuntu / Debian</option>
      <option value="windows" @selected(($device['os'] ?? '') === 'windows')>Windows</option>
      <option value="macos" @selected(($device['os'] ?? '') === 'macos')>macOS / Apple</option>
      <option value="android" @selected(($device['os'] ?? '') === 'android')>Android</option>
      <option value="ios" @selected(($device['os'] ?? '') === 'ios')>iOS / iPhone / iPad</option>
      <option value="other" @selected(($device['os'] ?? '') === 'other')>Other / Network Device</option>
     </select>
    </div>

    @if (count($tailscaleIpsList) > 0)
     <div class="form-group">
      <label for="editTailscaleIp">{{ __('crm.select_tailscale_ip') }}</label>
      <select id="editTailscaleIp" name="selected_tailscale_ip" class="form-control">
        <option value="">{{ __('crm.choose_tailscale_ip_placeholder') }}</option>
        @foreach ($tailscaleIpsList as $tsItem)
         <option value="{{ $tsItem['ip'] }}">{{ $tsItem['ip'] }}</option>
        @endforeach
      </select>
     </div>
    @endif

    <div class="form-row-2">
     <div class="form-group">
      <label for="editNewIp">{{ __('crm.add_ip') }} ({{ __('crm.custom_ip') }})</label>
      <input type="text" id="editNewIp" name="new_ip_address" class="form-control" placeholder="192.168.1.10" style="direction:ltr">
     </div>
     <div class="form-group">
      <label for="editNewIpLabel">{{ __('crm.ip_label_placeholder') }}</label>
      <input type="text" id="editNewIpLabel" name="new_ip_label" class="form-control" placeholder="LAN / Backup">
     </div>
    </div>

    <div class="form-group">
     <label for="editCardDns">{{ __('crm.dns_or_hostname') }}</label>
     <input type="text" id="editCardDns" name="dns_name" class="form-control" value="{{ $device['dnsName'] ?? '' }}" autocomplete="off">
    </div>

    <div class="form-group">
     <label for="editCardNotes">{{ __('crm.notes') }}</label>
     <textarea id="editCardNotes" name="notes" class="form-control">{{ $device['notes'] ?? '' }}</textarea>
    </div>
   </div>
   <div class="modal-footer">
    <button type="button" class="btn-action" id="btnCancelEditModal">{{ __('crm.cancel') }}</button>
    <button type="submit" class="btn-action primary">
     <i class="bi bi-check-lg"></i>
     <span>{{ __('crm.save') ?? 'Save' }}</span>
    </button>
   </div>
  </form>
 </div>
</div>

<!-- Toast Container -->
<div class="support-toast-container" id="supportToastContainer" aria-live="polite" aria-atomic="true"></div>

<script>
 (() => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const deviceKey = "{{ $deviceKey }}";
  
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
   }, 3200);
  };

  // Mobile sidebar
  const sidebarMenu = document.getElementById('supportSidebarMenu');
  const sidebarOverlay = document.getElementById('supportSidebarOverlay');
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
   sidebarMenu?.setAttribute('aria-expanded', 'false');
   syncSidebarAccess(false);
   if (wasOpen && restoreFocus) sidebarMenu?.focus();
  };

  sidebarMenu?.addEventListener('click', () => {
   const open = document.body.classList.toggle('crm-side-open');
   sidebarMenu.setAttribute('aria-expanded', open ? 'true' : 'false');
   syncSidebarAccess(open);
   if (open) {
    window.requestAnimationFrame(() => sidebar?.querySelector('a, button')?.focus());
   }
  });
  sidebarOverlay?.addEventListener('click', () => closeSidebar());
  mobileSidebar.addEventListener('change', () => closeSidebar(false));
  syncSidebarAccess(false);

  // IP Filtering in Detail View
  const ipFilters = [...document.querySelectorAll('[data-ip-filter]')];
  const ipChips = [...document.querySelectorAll('[data-ip-status]')];

  ipFilters.forEach((btn) => btn.addEventListener('click', () => {
   const filterVal = btn.dataset.ipFilter || 'all';
   ipFilters.forEach((f) => {
    const active = f === btn;
    f.classList.toggle('active', active);
    f.setAttribute('aria-pressed', active ? 'true' : 'false');
   });
   ipChips.forEach((chip) => {
    const chipStatus = chip.dataset.ipStatus;
    const show = filterVal === 'all' || chipStatus === filterVal;
    chip.style.display = show ? '' : 'none';
   });
  }));

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
  const tsSelect = document.getElementById('selectShowTsIp');
  tsSelect?.addEventListener('change', () => {
   const val = tsSelect.value;
   if (val) {
    document.getElementById('inputShowIpVal').value = val;
    const labelInput = document.getElementById('inputShowIpLabel');
    if (labelInput && !labelInput.value) labelInput.value = 'Tailscale';
   }
  });

  // Submit Add IP form on Show page
  const formAddIp = document.getElementById('formAddIpShow');
  formAddIp?.addEventListener('submit', async (e) => {
   e.preventDefault();
   const ipVal = document.getElementById('inputShowIpVal').value.trim();
   const labelVal = document.getElementById('inputShowIpLabel').value.trim();
   const errorDiv = document.getElementById('showIpError');

   if (!ipVal) return;

   errorDiv.style.display = 'none';

   try {
    const res = await fetch('{{ route("v2.technical-support.ips.store") }}', {
     method: 'POST',
     headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
     },
     body: JSON.stringify({
      device_key: deviceKey,
      ip_address: ipVal,
      label: labelVal,
     }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
     window.location.reload();
    } else {
     errorDiv.textContent = data.message || '{{ __("crm.invalid_ip_address") }}';
     errorDiv.style.display = 'block';
    }
   } catch (err) {
    errorDiv.textContent = '{{ __("crm.invalid_ip_address") }}';
    errorDiv.style.display = 'block';
   }
  });

  // Delete Custom IP
  document.addEventListener('click', async (e) => {
   const deleteBtn = e.target.closest('[data-delete-ip-id]');
   if (!deleteBtn) return;

   if (!confirm('{{ __("crm.delete_ip_confirm") }}')) return;

   const ipId = deleteBtn.dataset.deleteIpId;

   try {
    const res = await fetch(`{{ url('/technical-support/ips') }}/${ipId}`, {
     method: 'DELETE',
     headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
     },
    });
    const data = await res.json();
    if (res.ok && data.success) {
     window.location.reload();
    }
   } catch (err) {
    showToast('Failed to delete IP', 'error');
   }
  });

  // Delete Card
  document.getElementById('btnDeleteCard')?.addEventListener('click', async () => {
   if (!confirm('{{ __("crm.delete_card_confirm") }}')) return;

   try {
    const res = await fetch(`{{ route("v2.technical-support.cards.destroy", $deviceKey) }}`, {
     method: 'DELETE',
     headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
     },
    });
     const data = await res.json();
     if (res.ok && data.success) {
      window.location.href = "{{ route('v2.technical-support.index') }}";
     } else {
      showToast(data.message || '{{ __("crm.delete_server_failed") }}', 'error');
     }
    } catch (err) {
     showToast('{{ __("crm.delete_server_failed") }}', 'error');
    }
   });

  // Edit Modal
  const editModal = document.getElementById('editCardModal');
   const btnOpenEdit = document.getElementById('btnOpenEditModal');
   const btnCloseEdit = document.getElementById('btnCloseEditModal');
   const btnCancelEdit = document.getElementById('btnCancelEditModal');
   const pageShell = document.querySelector('.support-shell');
   let editModalTrigger = null;

   const openEditModal = () => {
    if (!editModal) return;
    editModalTrigger = document.activeElement;
    editModal.classList.add('active');
    editModal.setAttribute('aria-hidden', 'false');
    pageShell?.setAttribute('inert', '');
    document.body.style.overflow = 'hidden';
    window.requestAnimationFrame(() => editModal.querySelector('input, select, textarea, button')?.focus());
   };

   const closeEditModal = () => {
    if (!editModal) return;
    editModal.classList.remove('active');
    editModal.setAttribute('aria-hidden', 'true');
    pageShell?.removeAttribute('inert');
    document.body.style.overflow = '';
    if (editModalTrigger instanceof HTMLElement) editModalTrigger.focus();
   };

   btnOpenEdit?.addEventListener('click', openEditModal);
   btnCloseEdit?.addEventListener('click', closeEditModal);
   btnCancelEdit?.addEventListener('click', closeEditModal);

   editModal?.addEventListener('click', (e) => {
    if (e.target === editModal) closeEditModal();
   });

   document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
     if (editModal?.classList.contains('active')) {
      closeEditModal();
     } else if (document.body.classList.contains('crm-side-open')) {
      closeSidebar();
     }
     return;
    }

    if (event.key !== 'Tab' || !editModal?.classList.contains('active')) return;
    const focusable = [...editModal.querySelectorAll('button, input, select, textarea, [href], [tabindex]:not([tabindex="-1"])')]
     .filter((element) => !element.hasAttribute('disabled') && element.getClientRects().length > 0);
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

  // Submit Edit form with FormData
  const formEditCard = document.getElementById('formEditCard');
  formEditCard?.addEventListener('submit', async (e) => {
   e.preventDefault();
   const formData = new FormData(formEditCard);

   try {
    const res = await fetch(formEditCard.action, {
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
     showToast(data.message || 'Failed to update', 'error');
    }
   } catch (err) {
    showToast('Failed to update card', 'error');
   }
  });
 })();
</script>
</body>
</html>
