<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $lead->name }} — {{ __('crm.lead_details') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
    <link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v=1.0.0">
    <style>
        *{box-sizing:border-box}
        :root{--red:#dc2637;--dark:#182033;--muted:#7e899b;--line:#e4e8ef;--bg:#f4f6f9;--card:#fff;--blue:#3478f6}
        body{margin:0;background:var(--bg);color:var(--dark);font-family:var(--font-primary)}
        .crm-app{min-height:100vh;display:flex}
        .crm-main{min-width:0;flex:1;padding:24px 30px 60px}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;flex-wrap:wrap}
        .topbar-left{display:flex;align-items:center;gap:14px}
        .topbar h1{margin:0;font-size:24px;font-weight:900;color:var(--dark)}
        .topbar p{margin:4px 0 0;color:var(--muted);font-size:13px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:44px;padding:0 16px;border:1px solid var(--line);border-radius:10px;background:#fff;color:var(--dark);font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit;font-size:13px;transition:all .15s}
        .topbar .btn{height:44px;min-height:44px;box-sizing:border-box}
        .topbar-left .btn.small,.topbar .btn.small{width:44px;height:44px;min-height:44px;padding:0;display:inline-grid;place-items:center;border-radius:10px}
        .btn.primary{background:var(--red);border-color:var(--red);color:#fff}
        .btn.primary:hover{background:#b81829;border-color:#b81829;color:#fff}
        .btn.soft{background:var(--card);color:var(--dark)}
        .btn.soft:hover{background:var(--bg);border-color:var(--dark)}
        .btn.small{min-height:38px;padding:0 12px;font-size:12px}
        .btn.success{background:#16a34a;border-color:#16a34a;color:#fff}
        .btn.success:hover{background:#15803d;border-color:#15803d;color:#fff}
        .badge{display:inline-flex;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:900;background:#eef2f7;color:#536078}
        .badge.active{background:#e7f8ed;color:#18733a}
        .badge.inactive{background:#fff0f1;color:#b42332}
        .badge.system{background:#fff5d9;color:#8a6100}

        /* Lead Profile Header Card */
        .lead-header-card{background:transparent !important;border:0 !important;border-radius:16px;padding:12px 0 20px;margin-bottom:12px;box-shadow:none !important}
        .lead-header-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
        .lead-identity{display:flex;align-items:center;gap:16px}
        .lead-avatar{width:56px;height:56px;border-radius:16px;background:#fef2f2;color:var(--red);display:grid;place-items:center;font-size:24px;font-weight:900}
        .lead-names h2{margin:0;font-size:20px;font-weight:900;color:var(--dark)}
        .lead-names p{margin:4px 0 0;color:var(--muted);font-size:13px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}

        /* Enhanced Lead Stage Indicator Card */
        .lead-stage-card {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 8px 18px 8px 10px;
            background: color-mix(in srgb, var(--stage-color, #3478f6) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--stage-color, #3478f6) 28%, var(--line, #e2e8f0));
            border-radius: 999px;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--stage-color, #3478f6) 12%, transparent);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .lead-stage-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px color-mix(in srgb, var(--stage-color, #3478f6) 22%, transparent);
            border-color: var(--stage-color, #3478f6);
        }
        .stage-icon-halo {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--stage-color, #3478f6), color-mix(in srgb, var(--stage-color, #3478f6) 80%, #000));
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px color-mix(in srgb, var(--stage-color, #3478f6) 45%, transparent);
        }
        .stage-pulse {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 2px solid var(--stage-color, #3478f6);
            opacity: 0.4;
            animation: stagePulseAnim 2.4s infinite ease-out;
        }
        @keyframes stagePulseAnim {
            0% { transform: scale(0.92); opacity: 0.6; }
            50% { transform: scale(1.18); opacity: 0.08; }
            100% { transform: scale(0.92); opacity: 0.6; }
        }
        .stage-text-group {
            display: flex;
            flex-direction: column;
            line-height: 1.25;
            text-align: start;
        }
        .stage-caption {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted, #64748b);
        }
        .stage-title {
            font-size: 14px;
            font-weight: 900;
            color: var(--dark, #0f172a);
        }
        html.dark-mode .lead-stage-card {
            background: color-mix(in srgb, var(--stage-color, #3478f6) 14%, rgba(255,255,255,0.02));
            border-color: color-mix(in srgb, var(--stage-color, #3478f6) 38%, rgba(255,255,255,0.12));
            box-shadow: 0 4px 18px rgba(0,0,0,0.35);
        }
        html.dark-mode .stage-title { color: #f8fafc; }
        html.dark-mode .stage-caption { color: #94a3b8; }

        .metrics-bar{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:20px;padding-top:20px;border-top:1px solid var(--line)}
        .metric-box{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:12px 14px}
        .metric-box span{display:block;color:var(--muted);font-size:11px;font-weight:800;margin-bottom:4px}
        .metric-box b{display:block;font-size:15px;font-weight:900;color:var(--dark)}

        /* Two Columns Layout */
        .details-grid{display:grid;grid-template-columns:1fr 1.2fr;gap:20px}
        .panel{background:transparent !important;border:0 !important;border-radius:16px;padding:16px 0;margin-bottom:20px;box-shadow:none !important}
        .panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;background:transparent !important;border:0 !important}
        .panel-head h2{margin:0;font-size:16px;font-weight:900;color:var(--dark);display:flex;align-items:center;gap:8px}

        .info-list{display:grid;gap:10px}
        .info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--bg);border:1px solid var(--line);border-radius:10px;font-size:13px}
        .info-row.is-primary-phone{background:rgba(22,163,74,0.08);border:1px solid rgba(22,163,74,0.25)}
        .info-label{color:var(--muted);font-weight:700}
        .info-value{font-weight:800;color:var(--dark);text-align:end}

        /* Timeline Styles */
        .timeline{position:relative;padding-inline-start:24px;margin-top:10px}
        .timeline::before{
            content:'';position:absolute;top:0;bottom:0;inset-inline-start:7px;
            width:2px;background:var(--line);
        }
        .timeline-item{position:relative;margin-bottom:20px}
        .timeline-dot{
            position:absolute;inset-inline-start:-24px;top:4px;
            width:16px;height:16px;border-radius:50%;background:var(--red);border:3px solid #fff;
            box-shadow:0 0 0 2px var(--line);
        }
        .timeline-card{
            background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:14px 16px;
        }
        .timeline-head{
            display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px;flex-wrap:wrap;
        }
        .timeline-date{color:var(--muted);font-size:12px;font-weight:700}
        .timeline-employee{font-weight:800;color:var(--dark);font-size:13px}
        .timeline-body{color:#334155;font-size:13px;line-height:1.6}

        /* VoIP Call Insights Styles */
        .call-filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:10px;align-items:end;margin-bottom:16px}
        .call-filters label{display:block;color:var(--muted);font-size:11px;font-weight:800;margin-bottom:5px}
        .call-filters input,.call-filters select{width:100%;border:1px solid var(--line);border-radius:9px;padding:9px 10px;background:var(--card);color:var(--dark);font-family:inherit}
        .call-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:16px}
        .call-metric{padding:12px;background:var(--bg);border-radius:12px;border:1px solid var(--line)}
        .call-metric span{display:block;color:var(--muted);font-size:11px;font-weight:800}
        .call-metric strong{display:block;margin-top:5px;font-size:20px;font-variant-numeric:tabular-nums}
        .call-bars{display:grid;gap:9px;padding:14px;background:var(--bg);border:1px solid var(--line);border-radius:12px;margin-bottom:16px}
        .call-bar{display:grid;grid-template-columns:78px 1fr 34px;gap:8px;align-items:center;font-size:12px;font-weight:800}
        .call-bar-track{height:7px;background:var(--line);border-radius:999px;overflow:hidden}
        .call-bar-fill{display:block;height:100%;border-radius:inherit}
        .call-list{display:grid;gap:9px}
        .call-row{display:grid;grid-template-columns:minmax(120px,.9fr) minmax(120px,1fr) 90px 100px;gap:12px;align-items:center;padding:14px;border:1px solid var(--line);border-radius:12px;background:var(--card);transition:border-color .15s}
        .call-row small{display:block;color:var(--muted);margin-top:3px}
        .call-state{text-align:center;padding:26px;color:var(--muted);font-weight:700}
        .call-state i{display:block;font-size:26px;margin-bottom:7px}
        .crm-audio-player{grid-column:1/-1;display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--bg);border:1px solid var(--line);border-radius:12px;margin-top:4px;transition:border-color .15s}
        .crm-audio-player.is-playing{border-color:var(--red)}
        .audio-play-btn{width:32px;height:32px;flex:0 0 32px;border-radius:50%;background:var(--red);color:#fff;border:0;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:transform .15s,background .15s;padding:0}
        .audio-play-btn:hover{background:#b81829;transform:scale(1.06)}
        .audio-track-wrap{flex:1;min-width:0;display:flex;flex-direction:column;gap:3px}
        .audio-seek{width:100%;height:6px;-webkit-appearance:none;appearance:none;background:var(--line);border-radius:999px;outline:none;cursor:pointer;margin:0}
        .audio-seek::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:12px;height:12px;border-radius:50%;background:var(--red);cursor:pointer;transition:transform .1s}
        .audio-seek::-webkit-slider-thumb:hover{transform:scale(1.25)}
        .audio-seek::-moz-range-thumb{width:12px;height:12px;border-radius:50%;background:var(--red);border:0;cursor:pointer}
        .audio-time-row{display:flex;justify-content:space-between;font-size:10px;font-weight:700;color:var(--muted);font-variant-numeric:tabular-nums;font-family:monospace}
        .audio-speed-btn{min-width:32px;height:26px;padding:0 5px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--dark);font-size:11px;font-weight:800;cursor:pointer;transition:all .15s}
        .audio-speed-btn:hover{border-color:var(--red);color:var(--red)}
        .audio-download-btn{width:26px;height:26px;flex:0 0 26px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--muted);display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-size:12px;transition:all .15s}
        .audio-download-btn:hover{border-color:var(--dark);color:var(--dark)}
        .call-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 4px 4px;margin-top:6px;border-top:1px solid var(--line);flex-wrap:wrap}
        .call-pagination-info{font-size:12px;font-weight:700;color:var(--muted)}
        .call-pagination-controls{display:flex;align-items:center;gap:6px}
        .btn-call-page{min-width:30px;height:30px;padding:0 8px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--dark);font-size:12px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s}
        .btn-call-page:hover:not(:disabled){background:var(--bg);border-color:var(--dark)}
        .btn-call-page.active{background:var(--red)!important;border-color:var(--red)!important;color:#fff!important}
        .btn-call-page:disabled{opacity:0.35;cursor:not-allowed}

        /* Field changes & stage answers */
        .followup-field-changes{margin-top:10px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card)}
        .followup-field-changes strong{display:block;margin-bottom:8px;font-size:12px;color:var(--dark)}
        .followup-change-list{display:grid;gap:6px;margin:0;padding:0;list-style:none}
        .followup-change-item{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 8px;border-radius:6px;background:var(--bg);font-size:12px}
        .followup-change-label{color:var(--muted);font-weight:700}
        .followup-change-old{color:#b42332;text-decoration:line-through}
        .followup-change-new{color:#15803d;font-weight:800}

        html.dark-mode {
            --bg: #09090b;
            --card: #18181b;
            --dark: #f4f4f5;
            --muted: #a1a1aa;
            --line: rgba(255,255,255,0.08);
        }
        html.dark-mode body { background: #09090b !important; color: #f4f4f5 !important; }
        html.dark-mode .info-row { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .info-row.is-primary-phone { background: rgba(34,197,94,0.12) !important; border-color: rgba(34,197,94,0.3) !important; }
        html.dark-mode .info-row strong, html.dark-mode .info-value { color: #f4f4f5 !important; }
        html.dark-mode .metric-box { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .timeline-card { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
        html.dark-mode .timeline-body { color: #d4d4d8; }
        html.dark-mode .btn.soft { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); color: #f4f4f5; }
        html.dark-mode .btn.soft:hover { background: rgba(255,255,255,0.12); color: #fff; }
        html.dark-mode .badge { background: rgba(255,255,255,0.08); color: #d4d4d8; }
        html.dark-mode .badge.active { background: rgba(34,197,94,0.18); color: #4ade80; }
        html.dark-mode .call-metric { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); }
        html.dark-mode .call-bars { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); }
        html.dark-mode .call-filters input, html.dark-mode .call-filters select { background: #18181b; border-color: rgba(255,255,255,0.12); color: #f4f4f5; }
        html.dark-mode .call-row { background: #18181b; border-color: rgba(255,255,255,0.08); }
        html.dark-mode .crm-audio-player { background: #27272a; border-color: rgba(255,255,255,0.1); }
        html.dark-mode .audio-speed-btn, html.dark-mode .audio-download-btn, html.dark-mode .btn-call-page { background: #18181b; border-color: rgba(255,255,255,0.12); color: #f4f4f5; }
        html.dark-mode .audio-seek { background: rgba(255,255,255,0.12); }
        html.dark-mode .followup-field-changes { background: #18181b; border-color: rgba(255,255,255,0.08); }
        html.dark-mode .followup-change-item { background: rgba(255,255,255,0.03); }

        @media(max-width:1024px){
            .details-grid{grid-template-columns:1fr}
            .metrics-bar{grid-template-columns:repeat(2,1fr)}
        }
        @media(max-width:768px){
            .crm-main{padding:16px 12px 60px;min-width:0;width:100%;max-width:100%}
            .topbar{flex-direction:column;align-items:stretch;gap:12px}
            .top-actions{width:100%;flex-wrap:wrap;gap:8px}
            .top-actions .btn{flex:1 1 auto;min-height:44px}
            .lead-header-top{flex-direction:column;align-items:stretch;gap:14px}
            .lead-stage-card{align-self:flex-start}
            .metrics-bar{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
            .call-filters{grid-template-columns:1fr}
            .call-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}
            .call-row{grid-template-columns:1fr;gap:8px}
            .info-row{flex-wrap:wrap;gap:8px}
            .info-row .btn.small{min-height:44px;min-width:44px;height:44px;padding:0 12px}
        }
        @media(max-width:480px){
            .metrics-bar{grid-template-columns:1fr}
            .call-metrics{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
<div class="crm-app lead-detail-page">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        @php
            $showTopActions = '';
            if (auth()->user()->can('leads.update')) {
                $showTopActions .= '<a href="' . route('v2.leads.edit', $lead) . '" class="btn soft"><i class="bi bi-pencil-square"></i> ' . __('crm.edit_data') . '</a>';
            }
            if (auth()->user()->can('leads.followups.view')) {
                if ($callPhone && empty($maskPhones)) {
                    $showTopActions .= '<a class="btn primary js-call-followup" href="' . route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) . '" data-call-href="tel:' . $callPhone . '" title="' . __('crm.open_microsip_followup') . '"><i class="bi bi-telephone-outbound"></i> ' . __('crm.call_action') . '</a>';
                }
                $showTopActions .= '<a href="' . route('v2.leads.followups.index', $lead) . '" class="btn soft" title="' . __('crm.log_new_followup') . '"><i class="bi bi-plus-lg"></i> ' . __('crm.log_new_followup') . '</a>';
            } else {
                if ($callPhone && empty($maskPhones)) {
                    $showTopActions .= '<a class="btn primary" href="tel:' . $callPhone . '" title="' . __('crm.call_action') . '"><i class="bi bi-telephone-outbound"></i> ' . __('crm.call_action') . '</a>';
                }
            }
        @endphp

        @include('partials.topbar', [
            'title' => __('crm.lead_details'),
            'subtitle' => '<span>' . __('crm.lead_code_label') . ': #' . $lead->id . '</span> <span style="margin:0 6px">•</span> <span>' . __('crm.registered_at') . ': ' . ($lead->created_at?->format('Y-m-d') ?? '—') . '</span>',
            'icon' => 'bi-person-badge-fill',
            'backUrl' => route('v2.leads', $backQuery),
            'backTitle' => __('crm.back_to_leads_list'),
            'actions' => $showTopActions,
        ])

        <!-- PROFILE HEADER CARD -->
        <section class="lead-header-card">
            <div class="lead-header-top">
                <div class="lead-identity">
                    <div class="lead-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="lead-names">
                        <h2>{{ $lead->name }}</h2>
                        <p>
                            @if ($lead->company_name)
                                <span><i class="bi bi-building"></i> {{ $lead->company_name }}</span>
                                <span>•</span>
                            @endif
                            @if ($lead->governorate || $lead->address)
                                <span><i class="bi bi-geo-alt"></i> {{ $lead->governorate ?: $lead->address }}</span>
                                <span>•</span>
                            @endif
                            <span><i class="bi bi-person-badge"></i> {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?? __('crm.unassigned') }}</span>
                        </p>
                    </div>
                </div>
                <div>
                    @php
                        $stageCode = strtolower((string) ($lead->status?->code ?? $lead->status?->stage?->code ?? ''));
                        $stageIcon = match(true) {
                            str_contains($stageCode, 'won') || str_contains($stageCode, 'closed') || str_contains($stageCode, 'contract') || str_contains($stageCode, 'donor') => 'bi-check-circle-fill',
                            str_contains($stageCode, 'not') || str_contains($stageCode, 'reject') || str_contains($stageCode, 'lost') || str_contains($stageCode, 'disinterest') => 'bi-slash-circle-fill',
                            str_contains($stageCode, 'follow') || str_contains($stageCode, 'no_answer') || str_contains($stageCode, 'negotiation') => 'bi-arrow-repeat',
                            str_contains($stageCode, 'new') => 'bi-stars',
                            str_contains($stageCode, 'quotation') || str_contains($stageCode, 'proposal') => 'bi-file-earmark-text-fill',
                            default => 'bi-diagram-3-fill',
                        };
                        $stageName = $lead->status?->localizedName() ?? ($lead->status?->stage?->localizedName() ?? ($lead->status?->name_ar ?? __('crm.no_status')));
                    @endphp
                    <div class="lead-stage-card" style="--stage-color: {{ $statusColor }};">
                        <div class="stage-icon-halo">
                            <span class="stage-pulse"></span>
                            <i class="bi {{ $stageIcon }}"></i>
                        </div>
                        <div class="stage-text-group">
                            <span class="stage-caption">{{ __('crm.current_stage') }}</span>
                            <span class="stage-title">{{ $stageName }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="metrics-bar">
                <div class="metric-box">
                    <span>{{ __('crm.company_name') }}</span>
                    <b>{{ $lead->company_name ?: '—' }}</b>
                </div>
                <div class="metric-box">
                    <span>{{ __('crm.solution_type') }}</span>
                    <b>{{ $solutionTypeLabel ?: '—' }}</b>
                </div>
                <div class="metric-box">
                    <span>{{ __('crm.quotation_status') }}</span>
                    <b style="color:{{ $lead->quotation_sent ? '#16a34a' : ($hasQuotationFile ? '#2563eb' : 'inherit') }}">
                        {{ $lead->quotation_sent ? 'تم الإرسال للعميل' : ($hasQuotationFile ? 'جاهز للإرسال' : 'لم يتم الإنشاء') }}
                    </b>
                </div>
                <div class="metric-box">
                    <span>{{ __('crm.next_followup') }}</span>
                    <b>
                        @if ($lead->next_follow_up_at)
                            {{ $lead->next_follow_up_at->format('Y-m-d h:i A') }}
                        @else
                            {{ __('crm.no_followup_scheduled') }}
                        @endif
                    </b>
                </div>
            </div>
        </section>

        <div class="details-grid">
            <!-- LEFT COLUMN: PROFILE, CONTACTS & RECORDS -->
            <div>
                <!-- CUSTOMER CORE INFO -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-info-circle"></i> {{ __('crm.customer_data') }}</h2>
                    </div>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.full_name') }}</span>
                            <span class="info-value">{{ $lead->name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.company_name') }}</span>
                            <span class="info-value">{{ $lead->company_name ?: '—' }}</span>
                        </div>
                        @if ($lead->job_title)
                            <div class="info-row">
                                <span class="info-label">{{ __('crm.job_title') }}</span>
                                <span class="info-value">{{ $lead->job_title }}</span>
                            </div>
                        @endif
                        @if ($lead->activity)
                            <div class="info-row">
                                <span class="info-label">{{ __('crm.activity') }}</span>
                                <span class="info-value">{{ $lead->activity }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.address') }}</span>
                            <span class="info-value">{{ trim(($lead->governorate ?? '').' '.($lead->address ?? '')) ?: '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.solution_type') }}</span>
                            <span class="info-value">{{ $solutionTypeLabel ?: '—' }}</span>
                        </div>
                        @if ($lead->lines_count)
                            <div class="info-row">
                                <span class="info-label">عدد الخطوط المطلوبة</span>
                                <span class="info-value">{{ $lead->lines_count }} خطوط</span>
                            </div>
                        @endif
                        @if ($lead->extensions)
                            <div class="info-row">
                                <span class="info-label">التحويلات المطلوبة</span>
                                <span class="info-value">{{ $lead->extensions }}</span>
                            </div>
                        @endif
                        @if ($lead->departments)
                            <div class="info-row">
                                <span class="info-label">الأقسام المطلوبة</span>
                                <span class="info-value">{{ $lead->departments }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.lead_source') }}</span>
                            <span class="info-value">{{ $lead->source ? __($lead->source) : '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.assigned_employee') }}</span>
                            <span class="info-value">{{ $lead->assignedUser?->name ?? $lead->assigned_employee ?? __('crm.unassigned') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('crm.registration_date') }}</span>
                            <span class="info-value">{{ $lead->created_at ? $lead->created_at->format('Y-m-d h:i A') : '—' }}</span>
                        </div>
                        @if ($lead->creator || $lead->created_by)
                            <div class="info-row">
                                <span class="info-label">{{ __('crm.registered_by') }}</span>
                                <span class="info-value">{{ $lead->creator?->name ?? $lead->created_by }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                <!-- PHONE NUMBERS & CONTACTS -->
                <section class="panel">
                    <div class="panel-head">
                        <h2><i class="bi bi-telephone"></i> {{ __('crm.lead_phone_numbers') }}</h2>
                    </div>
                    <div class="info-list">
                        <div class="info-row is-primary-phone">
                            <div>
                                <span class="badge active" style="margin-inline-end:6px">{{ __('crm.primary_badge') }}</span>
                                @if ($lead->phone)
                                    @if (!empty($maskPhones))
                                        <span style="font-weight:900;color:inherit;text-decoration:none" dir="ltr">{{ \App\Support\PhoneMask::mask($lead->phone) }}</span>
                                    @else
                                        @can('leads.followups.view')
                                            @if ($callPhone)
                                                <a
                                                    class="js-call-followup"
                                                    href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
                                                    data-call-href="tel:{{ $callPhone }}"
                                                    title="{{ __('crm.open_microsip_followup') }}"
                                                    style="font-weight:900;color:inherit;text-decoration:none"
                                                    dir="ltr"
                                                >
                                                    {{ $lead->phone }}
                                                </a>
                                            @else
                                                <a href="tel:{{ $lead->phone }}" style="font-weight:900;color:inherit;text-decoration:none" dir="ltr">
                                                    {{ $lead->phone }}
                                                </a>
                                            @endif
                                        @else
                                            <a href="tel:{{ $lead->phone }}" style="font-weight:900;color:inherit;text-decoration:none" dir="ltr">
                                                {{ $lead->phone }}
                                            </a>
                                        @endcan
                                    @endif
                                @else
                                    <span style="color:var(--muted)">—</span>
                                @endif
                            </div>
                            <div style="display:flex;gap:6px">
                                @if ($callPhone && empty($maskPhones))
                                    @can('leads.followups.view')
                                        <a
                                            class="btn small soft js-call-followup"
                                            href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
                                            data-call-href="tel:{{ $callPhone }}"
                                            title="{{ __('crm.open_microsip_followup') }}"
                                        >
                                            <i class="bi bi-telephone"></i>
                                        </a>
                                    @else
                                        <a class="btn small soft" href="tel:{{ $callPhone }}" title="{{ __('crm.call_action') }}">
                                            <i class="bi bi-telephone"></i>
                                        </a>
                                    @endcan
                                @endif
                                @if ($whatsappPhone && empty($maskPhones))
                                    <a href="https://wa.me/{{ $whatsappPhone }}" target="_blank" rel="noopener noreferrer" class="btn small success" title="{{ __('crm.whatsapp') }}">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if ($lead->email)
                            <div class="info-row">
                                <div>
                                    <span class="badge" style="margin-inline-end:6px">{{ __('crm.email') }}</span>
                                    <span style="font-weight:700">{{ $lead->email }}</span>
                                </div>
                                <a href="mailto:{{ $lead->email }}" class="btn small soft" title="إرسال بريد">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </section>

                <!-- QUOTATION SECTION -->
                @if ($hasQuotationFile && auth()->user()?->can('quotations.view'))
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-file-earmark-pdf"></i> {{ __('crm.price_quotation') }}</h2>
                            <span class="badge active">{{ $lead->quotation_sent ? 'تم الإرسال' : 'جاهز' }}</span>
                        </div>
                        <div class="info-list">
                            <div class="info-row">
                                <div>
                                    <strong style="display:block;font-size:13px">{{ $quotationFileName ?: 'ملف عرض السعر' }}</strong>
                                    <small style="color:var(--muted)">ملف عرض السعر الرسمي المرفق للعميل</small>
                                </div>
                                <div style="display:flex;gap:6px">
                                    <a
                                        class="btn small soft"
                                        href="{{ route('v2.leads.quotation.preview', $lead) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="معاينة الملف"
                                    >
                                        <i class="bi bi-eye"></i> معاينة
                                    </a>
                                    <a
                                        class="btn small soft"
                                        href="{{ route('v2.leads.quotation.download', $lead) }}"
                                        title="تحميل الملف"
                                    >
                                        <i class="bi bi-download"></i> تحميل
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($lead->notes)
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-chat-left-text"></i> {{ __('crm.notes') }}</h2>
                        </div>
                        <div style="background:var(--bg); border:1px solid var(--line); border-radius:10px; padding:14px 16px; line-height:1.7; color:var(--dark); white-space:pre-line;">
                            {{ $lead->notes }}
                        </div>
                    </section>
                @endif
            </div>

            <!-- RIGHT COLUMN: VOIP, STAGE ANSWERS & TIMELINE -->
            <div>
                @can('voip.view')
                    <section class="panel" id="leadCallInsights" data-endpoint="{{ route('v2.leads.calls', $lead) }}">
                        <div class="panel-head">
                            <div>
                                <h2><i class="bi bi-soundwave"></i> {{ __('crm.lead_call_insights') }}</h2>
                                <small style="color:var(--muted)">مكالمات مسجلة ومربوطة مباشرة من خادم الاتصالات VoIP</small>
                            </div>
                            <span class="badge active">{{ __('crm.live_from_voip') }}</span>
                        </div>
                        <form class="call-filters" id="leadCallFilters">
                            <div><label for="callStartDate">{{ __('crm.from_date') }}</label><input id="callStartDate" name="start_date" type="date"></div>
                            <div><label for="callEndDate">{{ __('crm.to_date') }}</label><input id="callEndDate" name="end_date" type="date"></div>
                            <div><label for="callDirection">{{ __('crm.direction') }}</label><select id="callDirection" name="direction"><option value="">{{ __('crm.all_directions') }}</option><option value="inbound">{{ __('crm.incoming') }}</option><option value="outbound">{{ __('crm.outgoing') }}</option><option value="internal">{{ __('crm.internal') }}</option></select></div>
                            <button class="btn soft" type="submit"><i class="bi bi-funnel"></i> {{ __('crm.apply_filter') }}</button>
                        </form>
                        <div class="call-metrics" hidden data-call-metrics>
                            <div class="call-metric"><span>{{ __('crm.total_calls') }}</span><strong data-metric="total_calls">0</strong></div>
                            <div class="call-metric"><span>{{ __('crm.answer_rate') }}</span><strong data-metric="answer_rate_percent">0%</strong></div>
                            <div class="call-metric"><span>{{ __('crm.total_talk_time') }}</span><strong data-metric="total_talk_seconds">0:00</strong></div>
                            <div class="call-metric"><span>{{ __('crm.missed_calls') }}</span><strong data-metric="missed_calls">0</strong></div>
                        </div>
                        <div class="call-bars" hidden data-call-bars>
                            @foreach ([['inbound', __('crm.incoming'), '#16a34a'], ['outbound', __('crm.outgoing'), '#0284c7'], ['internal', __('crm.internal'), '#64748b']] as [$key, $label, $color])
                                <div class="call-bar">
                                    <span>{{ $label }}</span>
                                    <span class="call-bar-track"><span class="call-bar-fill" data-direction="{{ $key }}" style="width:0;background:{{ $color }}"></span></span>
                                    <b data-direction-count="{{ $key }}">0</b>
                                </div>
                            @endforeach
                        </div>
                        <div class="call-state" data-call-state><i class="bi bi-arrow-repeat"></i>{{ __('crm.loading_call_history') }}</div>
                        <div class="call-list" hidden data-call-list></div>
                        <div class="call-pagination" hidden data-call-pagination></div>
                    </section>
                @endcan

                <!-- STAGE QUESTIONS ANSWERS HISTORY -->
                @if (!empty($stageHistoryGroups) && $stageHistoryGroups->isNotEmpty())
                    <section class="panel">
                        <div class="panel-head">
                            <h2><i class="bi bi-ui-checks"></i> {{ __('crm.stage_history_section_title') }}</h2>
                        </div>
                        <div class="info-list" style="display:flex; flex-direction:column; gap:10px;">
                            @foreach ($stageHistoryGroups as $group)
                                <div style="background:var(--bg); border:1px solid var(--line); border-radius:10px; padding:12px;">
                                    <div style="display:flex; justify-content:space-between; width:100%; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:6px;">
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            <span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:{{ $group['stage']?->color ?? '#64748b' }};"></span>
                                            <strong>{{ $group['stage']?->localizedName() ?? ($group['stage']?->name_ar ?? '—') }}</strong>
                                        </div>
                                        <small style="color:var(--muted); font-size:11px;">
                                            <i class="bi bi-person"></i> {{ $group['actor'] }} • {{ $group['date']?->format('Y-m-d H:i') ?? '—' }}
                                        </small>
                                    </div>
                                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:6px 12px; width:100%; font-size:12px;">
                                        @foreach ($group['values'] as $val)
                                            <div>
                                                <span style="color:var(--muted)">{{ $val['label'] }}:</span>
                                                <strong style="color:var(--dark)">{{ $val['value'] }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <!-- CUSTOMER ACTIVITY TIMELINE -->
                <section class="panel">
                    <div class="panel-head">
                        <div>
                            <h2><i class="bi bi-clock-history"></i> {{ __('crm.lead_activity_timeline') }}</h2>
                            <small style="color:var(--muted)">سجل زمني لجميع المتابعات وتغييرات الحالات</small>
                        </div>
                        @can('leads.followups.view')
                            <a
                             href="{{ route('v2.leads.followups.index', $lead) }}"
                             class="btn primary small"
                            >
                                <i class="bi bi-plus-lg"></i> {{ __('crm.add_followup') }}
                            </a>
                        @endcan
                    </div>

                    @if ($timelineEvents->isNotEmpty())
                        <div class="timeline">
                            @foreach ($timelineEvents as $event)
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-card">
                                        <div class="timeline-head">
                                            <div>
                                                <span class="timeline-employee">
                                                    <i class="bi bi-person"></i> {{ $event['employee'] }}
                                                </span>
                                                @if ($event['type'] === 'followup')
                                                    @php
                                                        $commType = $event['communication_type'] ?? 'other';
                                                        $commLabel = $followupCommunicationTypes[$commType] ?? $commType;
                                                    @endphp
                                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; margin-inline-start:6px">
                                                        <i class="bi bi-telephone"></i> {{ $commLabel }}
                                                    </span>
                                                @else
                                                    <span class="badge" style="background:#fef3c7; color:#92400e; margin-inline-start:6px">
                                                        <i class="bi bi-arrow-left-right"></i> {{ __('crm.status_change') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="timeline-date">
                                                {{ $event['timestamp'] ? $event['timestamp']->format('Y-m-d h:i A') : '—' }}
                                            </span>
                                        </div>

                                        @if ($event['from_status'] && $event['to_status'] && $event['from_status'] !== $event['to_status'])
                                            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:var(--muted)">
                                                {{ __('crm.status_changed_to') }} <span style="color:#64748b">{{ $event['from_status'] }}</span> {{ app()->getLocale() === 'ar' ? '←' : '→' }} <strong style="color:var(--dark)">{{ $event['to_status'] }}</strong>
                                            </div>
                                        @elseif ($event['to_status'])
                                            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:var(--muted)">
                                                {{ __('crm.status') }} <strong style="color:var(--dark)">{{ $event['to_status'] }}</strong>
                                            </div>
                                        @endif

                                        @if ($event['details'])
                                            <div class="timeline-body">
                                                {{ $event['details'] }}
                                            </div>
                                        @endif

                                        @if (!empty($event['field_changes']))
                                            <div class="followup-field-changes">
                                                <strong>التعديلات التي قام بها الموظف</strong>
                                                <ul class="followup-change-list">
                                                    @foreach ($event['field_changes'] as $change)
                                                        <li class="followup-change-item">
                                                            <span class="followup-change-label">{{ $change['label'] ?? 'تعديل' }}</span>
                                                            <div>
                                                                <span class="followup-change-old">{{ $change['old'] ?? '----' }}</span>
                                                                →
                                                                <span class="followup-change-new">{{ $change['new'] ?? '----' }}</span>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @if (!empty($event['stage_values']))
                                            <div style="margin-top:10px; padding:10px 12px; background:var(--card); border:1px solid var(--line); border-radius:8px;">
                                                <span style="display:block; font-size:11px; font-weight:800; color:#64748b; margin-bottom:6px;">
                                                    <i class="bi bi-ui-checks"></i> إجابات أسئلة المرحلة
                                                </span>
                                                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:6px 12px;">
                                                    @foreach ($event['stage_values'] as $sVal)
                                                        <div style="font-size:12px;">
                                                            <span style="color:var(--muted)">{{ $sVal['label'] }}:</span>
                                                            <strong style="color:var(--dark)">{{ $sVal['value'] }}</strong>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if ($event['next_follow_up'])
                                            <div style="margin-top:10px; padding-top:8px; border-top:1px dashed var(--line); font-size:12px; color:var(--muted)">
                                                <i class="bi bi-calendar-event"></i> المتابعة القادمة: <strong>{{ $event['next_follow_up']->format('Y-m-d h:i A') }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center; padding:30px 20px; color:var(--muted); background:var(--bg); border-radius:12px;">
                            <i class="bi bi-chat-square-dots" style="font-size:30px; display:block; margin-bottom:8px"></i>
                            <p style="margin:0; font-weight:700">لا توجد متابعات أو نشاطات مسجلة لهذا العميل حتى الآن.</p>
                            @can('leads.followups.view')
                                <a
                                 href="{{ route('v2.leads.followups.index', $lead) }}"
                                 class="btn primary small"
                                 style="margin-top:12px;"
                                >
                                    تسجيل أول متابعة الآن
                                </a>
                            @endcan
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </main>
</div>

@can('voip.view')
<script>
    (() => {
        const panel = document.getElementById('leadCallInsights');
        if (!panel) return;

        const form = document.getElementById('leadCallFilters');
        const state = panel.querySelector('[data-call-state]');
        const list = panel.querySelector('[data-call-list]');
        const pagination = panel.querySelector('[data-call-pagination]');
        const metrics = panel.querySelector('[data-call-metrics]');
        const bars = panel.querySelector('[data-call-bars]');
        const copy = {
            loading: @json(__('crm.loading_call_history')),
            empty: 'لا توجد مكالمات مسجلة تطابق التصفية.',
            error: 'تعذر جلب سجل المكالمات من خادم VoIP.',
            unassigned: @json(__('crm.unassigned')),
            extension: 'تحويلة',
            recording: 'تسجيل صوتي',
            directions: {
                inbound: @json(__('crm.incoming')),
                outbound: @json(__('crm.outgoing')),
                internal: @json(__('crm.internal')),
                unknown: 'غير محدد',
            },
            statuses: {
                answered: 'تم الرد',
                missed: 'لم يُرد عليها',
                failed: 'فشلت',
                busy: 'مشغول',
                no_answer: 'لا يوجد رد',
                unknown: 'غير معروف',
            },
            prev: 'السابق',
            next: 'التالي',
            showing: 'عرض :from - :to من أصل :total مكالمة',
            download: 'تحميل التسجيل الصوتي',
            speed: 'سرعة التشغيل',
        };
        const showState = (message, icon = 'bi-info-circle') => {
            const symbol = document.createElement('i');
            symbol.className = `bi ${icon}`;
            state.replaceChildren(symbol, document.createTextNode(message));
            state.hidden = false;
        };

        const formatDuration = (seconds) => {
            const total = Math.max(0, Number(seconds) || 0);
            return `${Math.floor(total / 60)}:${String(total % 60).padStart(2, '0')}`;
        };

        let allCalls = [];
        let currentPage = 1;
        const pageSize = 5;
        let activeAudio = null;

        const createAudioPlayer = (recordingUrl, callDurationSec) => {
            const player = document.createElement('div');
            player.className = 'crm-audio-player';

            const audio = document.createElement('audio');
            audio.preload = 'none';
            audio.src = recordingUrl;

            const playBtn = document.createElement('button');
            playBtn.type = 'button';
            playBtn.className = 'audio-play-btn';
            playBtn.title = 'تشغيل / إيقاف';
            playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';

            const trackWrap = document.createElement('div');
            trackWrap.className = 'audio-track-wrap';

            const seek = document.createElement('input');
            seek.type = 'range';
            seek.className = 'audio-seek';
            seek.min = '0';
            seek.max = '100';
            seek.value = '0';

            const timeRow = document.createElement('div');
            timeRow.className = 'audio-time-row';

            const currTime = document.createElement('span');
            currTime.textContent = '0:00';
            const durTime = document.createElement('span');
            durTime.textContent = callDurationSec ? formatDuration(callDurationSec) : '0:00';

            timeRow.appendChild(currTime);
            timeRow.appendChild(durTime);
            trackWrap.appendChild(seek);
            trackWrap.appendChild(timeRow);

            const speedBtn = document.createElement('button');
            speedBtn.type = 'button';
            speedBtn.className = 'audio-speed-btn';
            speedBtn.textContent = '1x';
            speedBtn.title = copy.speed;
            const speeds = [1, 1.25, 1.5, 2];
            let speedIdx = 0;
            speedBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                speedIdx = (speedIdx + 1) % speeds.length;
                audio.playbackRate = speeds[speedIdx];
                speedBtn.textContent = `${speeds[speedIdx]}x`;
            });

            const dlBtn = document.createElement('a');
            dlBtn.href = recordingUrl;
            dlBtn.download = '';
            dlBtn.className = 'audio-download-btn';
            dlBtn.title = copy.download;
            dlBtn.innerHTML = '<i class="bi bi-download"></i>';

            playBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (audio.paused) {
                    if (activeAudio && activeAudio !== audio) {
                        activeAudio.pause();
                    }
                    audio.play();
                } else {
                    audio.pause();
                }
            });

            audio.addEventListener('play', () => {
                activeAudio = audio;
                playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                player.classList.add('is-playing');
            });

            audio.addEventListener('pause', () => {
                playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                player.classList.remove('is-playing');
            });

            audio.addEventListener('ended', () => {
                playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                player.classList.remove('is-playing');
                seek.value = '0';
                currTime.textContent = '0:00';
            });

            audio.addEventListener('timeupdate', () => {
                if (!audio.duration || isNaN(audio.duration)) return;
                const pct = (audio.currentTime / audio.duration) * 100;
                seek.value = String(pct);
                currTime.textContent = formatDuration(audio.currentTime);
            });

            audio.addEventListener('loadedmetadata', () => {
                if (audio.duration && !isNaN(audio.duration)) {
                    durTime.textContent = formatDuration(audio.duration);
                }
            });

            seek.addEventListener('input', () => {
                if (!audio.duration || isNaN(audio.duration)) return;
                audio.currentTime = (Number(seek.value) / 100) * audio.duration;
            });

            player.appendChild(playBtn);
            player.appendChild(trackWrap);
            player.appendChild(speedBtn);
            player.appendChild(dlBtn);
            player.appendChild(audio);

            return player;
        };

        const renderCallsPage = () => {
            list.replaceChildren();
            const total = allCalls.length;
            const totalPages = Math.ceil(total / pageSize) || 1;
            currentPage = Math.min(Math.max(1, currentPage), totalPages);

            const startIdx = (currentPage - 1) * pageSize;
            const pageCalls = allCalls.slice(startIdx, startIdx + pageSize);

            pageCalls.forEach((call) => {
                const row = document.createElement('article');
                row.className = 'call-row';

                const dir = call.direction || 'unknown';
                const dirLabel = copy.directions[dir] || dir;

                let dirColor = '#64748b';
                let dirIcon = 'bi-arrow-left-right';
                if (dir === 'inbound') {
                    dirColor = '#16a34a';
                    dirIcon = 'bi-telephone-inbound-fill';
                } else if (dir === 'outbound') {
                    dirColor = '#0284c7';
                    dirIcon = 'bi-telephone-outbound-fill';
                }

                const partyCol = document.createElement('div');
                partyCol.innerHTML = `<strong style="display:flex;align-items:center;gap:5px"><i class="bi ${dirIcon}" style="color:${dirColor}"></i> ${dirLabel}</strong><small>${call.call_date || '—'}</small>`;

                const agentCol = document.createElement('div');
                const agentText = call.agent_name ? `${call.agent_name}` : (call.agent_extension ? `تحويلة ${call.agent_extension}` : '—');
                agentCol.innerHTML = `<strong>${agentText}</strong><small dir="ltr">${call.customer_number || call.src || call.dst || ''}</small>`;

                const durCol = document.createElement('div');
                durCol.innerHTML = `<strong>${call.duration_formatted || '0 ثانية'}</strong>`;

                const statusCol = document.createElement('div');
                const disp = (call.disposition || '').toLowerCase();
                const isAnswered = disp.includes('answer') || disp.includes('up');
                statusCol.innerHTML = `<span class="badge ${isAnswered ? 'active' : 'inactive'}">${isAnswered ? 'تم الرد' : 'لم يرد'}</span>`;

                row.appendChild(partyCol);
                row.appendChild(agentCol);
                row.appendChild(durCol);
                row.appendChild(statusCol);

                if (call.has_recording && call.media_id) {
                    const recUrl = `/voip/recordings/${encodeURIComponent(call.media_id)}`;
                    const player = createAudioPlayer(recUrl, call.duration_seconds || call.billsec || 0);
                    row.appendChild(player);
                }

                list.appendChild(row);
            });

            pagination.replaceChildren();
            if (totalPages > 1) {
                const info = document.createElement('span');
                info.className = 'call-pagination-info';
                info.textContent = copy.showing
                    .replace(':from', String(startIdx + 1))
                    .replace(':to', String(Math.min(startIdx + pageSize, total)))
                    .replace(':total', String(total));

                const controls = document.createElement('div');
                controls.className = 'call-pagination-controls';

                const prevBtn = document.createElement('button');
                prevBtn.type = 'button';
                prevBtn.className = 'btn-call-page';
                prevBtn.textContent = '‹';
                prevBtn.title = copy.prev;
                prevBtn.disabled = currentPage <= 1;
                prevBtn.addEventListener('click', () => {
                    currentPage--;
                    renderCallsPage();
                });
                controls.appendChild(prevBtn);

                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.type = 'button';
                    pageBtn.className = `btn-call-page ${i === currentPage ? 'active' : ''}`;
                    pageBtn.textContent = String(i);
                    pageBtn.addEventListener('click', () => {
                        currentPage = i;
                        renderCallsPage();
                    });
                    controls.appendChild(pageBtn);
                }

                const nextBtn = document.createElement('button');
                nextBtn.type = 'button';
                nextBtn.className = 'btn-call-page';
                nextBtn.textContent = '›';
                nextBtn.title = copy.next;
                nextBtn.disabled = currentPage >= totalPages;
                nextBtn.addEventListener('click', () => {
                    currentPage++;
                    renderCallsPage();
                });
                controls.appendChild(nextBtn);

                pagination.appendChild(info);
                pagination.appendChild(controls);
                pagination.hidden = false;
            } else {
                pagination.hidden = true;
            }
        };

        const render = (payload) => {
            allCalls = payload.calls || payload.data || [];
            currentPage = 1;

            const total = allCalls.length;
            let answered = 0;
            let totalSeconds = 0;
            const counts = { inbound: 0, outbound: 0, internal: 0 };

            allCalls.forEach((call) => {
                const disp = (call.disposition || '').toLowerCase();
                const isAns = disp.includes('answer') || disp.includes('up');
                if (isAns) answered++;
                const sec = Number(call.duration_seconds || call.billsec || 0);
                totalSeconds += sec;

                const dir = call.direction || 'unknown';
                if (counts[dir] !== undefined) counts[dir]++;
            });

            const answerRate = total > 0 ? Math.round((answered / total) * 100) : 0;
            const missed = total - answered;

            panel.querySelector('[data-metric="total_calls"]').textContent = String(total);
            panel.querySelector('[data-metric="answer_rate_percent"]').textContent = `${answerRate}%`;
            panel.querySelector('[data-metric="total_talk_seconds"]').textContent = formatDuration(totalSeconds);
            panel.querySelector('[data-metric="missed_calls"]').textContent = String(missed);
            metrics.hidden = false;

            Object.entries(counts).forEach(([dir, count]) => {
                const fill = bars.querySelector(`[data-direction="${dir}"]`);
                const cnt = bars.querySelector(`[data-direction-count="${dir}"]`);
                if (fill) fill.style.width = total > 0 ? `${(count / total) * 100}%` : '0%';
                if (cnt) cnt.textContent = String(count);
            });
            bars.hidden = false;

            if (allCalls.length === 0) {
                list.hidden = true;
                pagination.hidden = true;
                showState(copy.empty);
            } else {
                state.hidden = true;
                list.hidden = false;
                renderCallsPage();
            }
        };

        const loadCalls = async () => {
            showState(copy.loading, 'bi-arrow-repeat');
            list.hidden = true;
            pagination.hidden = true;
            metrics.hidden = true;
            bars.hidden = true;

            const query = new URLSearchParams();
            new FormData(form).forEach((value, key) => {
                if (value) query.set(key, String(value));
            });
            query.set('limit', '100');

            try {
                const response = await fetch(`${panel.dataset.endpoint}?${query}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok || payload.success === false) throw new Error(payload.error || copy.error);
                render(payload);
            } catch (error) {
                showState(error instanceof Error ? error.message : copy.error, 'bi-exclamation-triangle');
            }
        };
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            loadCalls();
        });
        loadCalls();
    })();
</script>
@endcan

<script>
(() => {
 document
  .querySelectorAll('.js-call-followup')
  .forEach((link) => {
   link.addEventListener(
    'click',
    () => {
     const callHref = link.dataset.callHref;
     if (!callHref || link.target !== '_blank') {
      return;
     }
     window.setTimeout(() => {
       window.location.href = callHref;
     }, 120);
    }
   );
  });
})();
</script>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
</body>
</html>
