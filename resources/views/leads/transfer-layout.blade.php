<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — {{ config('app.name', 'SokratCRM') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
    <link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v=1.0.0">
    <style>
        :root {
            --red: #dc2637;
            --red-hover: #b81829;
            --dark: #182033;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --card: #ffffff;
            --shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
            --radius: 16px;
            --font-primary: var(--font-primary, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif);
        }

        html.dark-mode {
            --dark: #f1f5f9;
            --muted: #94a3b8;
            --line: #334155;
            --bg: #0f172a;
            --card: #1e293b;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-width: 320px;
            width: 100%;
            max-width: 100vw;
            background: var(--bg);
            color: var(--dark);
            font-family: var(--font-primary);
            font-size: 14px;
            line-height: 1.5;
            overflow-x: clip;
        }
        button, input, select, textarea { font: inherit; }
        a { color: inherit; text-decoration: none; }

        .crm-app { display: flex; min-height: 100vh; width: 100%; max-width: 100vw; overflow-x: clip; min-width: 0; }
        .crm-main { flex: 1; min-width: 0; max-width: 100%; padding: 24px 32px 60px; box-sizing: border-box; }
        /* Topbar */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .topbar h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            color: var(--dark);
        }
        .topbar p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }
        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            min-height: 44px;
            padding: 0 18px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--card);
            color: var(--dark);
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            font-size: 13px;
            white-space: nowrap;
        }
        .btn:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
            transform: translateY(-1px);
        }
        html.dark-mode .btn {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--line);
            color: var(--dark);
        }
        html.dark-mode .btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #475569;
        }
        .btn.primary {
            background: var(--red);
            border-color: var(--red);
            color: #fff;
            box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25);
        }
        .btn.primary:hover {
            background: var(--red-hover);
            border-color: var(--red-hover);
            color: #fff;
        }
        .btn.soft {
            background: #f1f5f9;
            border-color: transparent;
            color: #334155;
        }
        html.dark-mode .btn.soft {
            background: rgba(255, 255, 255, 0.08);
            color: #f1f5f9;
        }
        .btn.soft:hover {
            background: #e2e8f0;
        }
        .btn.small {
            height: 38px;
            min-height: 38px;
            padding: 0 12px;
            font-size: 12px;
            border-radius: 10px;
        }
        .topbar-left .btn.small {
            width: 44px;
            height: 44px;
            min-height: 44px;
            padding: 0;
            display: inline-grid;
            place-items: center;
            border-radius: 12px;
        }
        /* Cards */
        .transfer-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .card-head {
            padding: 20px 24px;
            border-bottom: 1px solid var(--line);
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .card-head h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 900;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-head p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 12px;
        }
        .card-body {
            padding: 24px;
        }
        .transfer-hero {
            padding: 20px 24px;
            background: color-mix(in srgb, var(--red) 4%, var(--card));
            border-bottom: 1px solid var(--line);
        }
        .transfer-hero small {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(220, 38, 55, 0.1);
            color: var(--red);
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .transfer-hero h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            color: var(--dark);
        }
        .transfer-hero p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        /* Form Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .field.full {
            grid-column: 1 / -1;
        }
        label {
            font-size: 13px;
            font-weight: 800;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .control {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px 13px;
            background: var(--card);
            color: var(--dark);
            font-size: 13px;
            font-weight: 600;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .control:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.12);
        }
        html.dark-mode .control {
            background: rgba(30, 41, 59, 0.6);
            border-color: var(--line);
            color: var(--dark);
        }
        .help {
            color: var(--muted);
            font-size: 11px;
            margin-top: 3px;
        }

        /* Notices & Alerts */
        .notice {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.6;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notice.info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        html.dark-mode .notice.info {
            background: rgba(30, 64, 175, 0.2);
            color: #93c5fd;
            border-color: rgba(30, 64, 175, 0.4);
        }
        .notice.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        html.dark-mode .notice.error {
            background: rgba(153, 27, 27, 0.2);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.3);
        }
        .notice.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Stat Grid for Preview */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat {
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }
        .stat span {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .stat strong {
            display: block;
            font-size: 24px;
            font-weight: 900;
            color: var(--dark);
        }

        /* Status Guide Grid */
        .status-guide {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .status-guide-item {
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 12px 14px;
            transition: all 0.15s ease;
        }
        .status-guide-item:hover {
            border-color: var(--red);
            background: color-mix(in srgb, var(--red) 3%, var(--bg));
        }
        .status-guide-item strong {
            display: block;
            font-size: 13px;
            font-weight: 900;
            color: var(--dark);
        }
        .status-guide-item small {
            display: block;
            color: var(--muted);
            font-size: 11px;
            margin-top: 4px;
        }

        /* Checkbox Grid for Column Selection */
        .columns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .column-card {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--bg);
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            font-size: 13px;
            font-weight: 700;
        }
        .column-card:hover {
            border-color: var(--red);
        }
        .column-card input {
            width: 16px;
            height: 16px;
            accent-color: var(--red);
            cursor: pointer;
        }

        /* Tables */
        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        th, td {
            text-align: start;
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
            font-size: 13px;
        }
        th {
            background: var(--bg);
            color: var(--muted);
            font-weight: 800;
            font-size: 12px;
            white-space: nowrap;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg); }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            background: #f1f5f9;
            color: #475569;
            white-space: nowrap;
        }
        .badge.valid { background: #dcfce7; color: #166534; }
        .badge.duplicate { background: #fef3c7; color: #92400e; }
        .badge.error { background: #fee2e2; color: #991b1b; }

        @media(max-width: 1024px) {
            .grid.three { grid-template-columns: repeat(2, 1fr); }
        }
        @media(max-width: 768px) {
            .crm-main { padding: 16px 12px 60px; min-width: 0; width: 100%; max-width: 100%; }
            .topbar { flex-direction: column; align-items: stretch; gap: 12px; }
            .top-actions { width: 100%; flex-wrap: wrap; gap: 8px; }
            .top-actions .btn { flex: 1 1 auto; min-height: 44px; }
            .grid, .grid.three { grid-template-columns: 1fr; }
            .card-body, .transfer-hero, .card-head { padding: 16px; }
            .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
        }
        @media(max-width: 600px) {
            .columns-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media(max-width: 380px) {
            .columns-grid { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
    @stack('head')
</head>
<body>
@include('partials.page-loader')
<div class="crm-app transfer-page">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        @php
            $transferBackUrl = trim($__env->yieldContent('back-url'));
            $transferBackRoute = trim($__env->yieldContent('back-route'));
            $hasBackSection = $__env->hasSection('back-url') || $__env->hasSection('back-route');
            $finalTransferBack = $hasBackSection ? ($transferBackUrl ?: ($transferBackRoute ?: null)) : route('v2.leads');
            $finalTransferBackTitle = trim($__env->yieldContent('back-title')) ?: __('crm.back_to_leads_list');
        @endphp
        @include('partials.topbar', [
            'backUrl' => $finalTransferBack,
            'backTitle' => $finalTransferBackTitle,
        ])

        @yield('content')
    </main>
</div>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
@stack('scripts')
</body>
</html>
