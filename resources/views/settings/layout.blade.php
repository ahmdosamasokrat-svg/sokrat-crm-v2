<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('crm.settings')) — {{ config('app.name', 'SokratCRM') }}</title>
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
            --ink: #182033;
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
            --ink: #f1f5f9;
            --muted: #94a3b8;
            --line: #334155;
            --bg: #0f172a;
            --card: #1e293b;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-width: 320px;
            background: var(--bg);
            color: var(--dark);
            font-family: var(--font-primary);
            font-size: 14px;
            line-height: 1.5;
        }
        button, input, select, textarea { font: inherit; }
        a { color: inherit; text-decoration: none; }

        .settings-shell { display: flex; min-height: 100vh; max-width: 100vw; overflow-x: hidden; }
        .settings-main { flex: 1; min-width: 0; max-width: 100%; padding: 24px 32px 60px; }
        /* Topbar Unified */
        .crm-topbar, .settings-top, .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
            flex-wrap: wrap;
            min-height: 56px;
        }
        .crm-topbar-left, .settings-heading, .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .crm-topbar-title h1, .settings-heading h1, .topbar h1 {
            font-size: 24px;
            font-weight: 900;
            margin: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .crm-topbar-title p, .settings-heading p, .topbar p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }
        .crm-topbar-right, .settings-user-tools, .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .crm-topbar-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .crm-topbar-menu-btn, .menu-button {
            display: none;
            width: 44px;
            height: 44px;
            min-height: 44px;
            min-width: 44px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--dark);
            font-size: 20px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
        }
        .crm-topbar-back-btn {
            width: 44px;
            height: 44px;
            min-height: 44px;
            min-width: 44px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: var(--card);
            color: var(--dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            touch-action: manipulation;
            text-decoration: none;
        }

        /* Settings Navigation Tabs */
        .settings-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            background: var(--card);
            border: 1px solid var(--line);
            padding: 6px;
            border-radius: 14px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
        }
        .settings-tabs a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            padding: 9px 16px;
            border-radius: 10px;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .settings-tabs a:hover {
            background: var(--bg);
            color: var(--dark);
        }
        .settings-tabs a.active {
            background: var(--red);
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(220, 38, 55, 0.25);
        }

        /* Panels & Cards */
        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 22px;
            margin-bottom: 20px;
        }
        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .panel-head h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-head p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* Stats & Grids */
        .grid { display: grid; gap: 16px; }
        .stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 16px 18px;
            box-shadow: var(--shadow);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .stat-card b {
            display: block;
            font-size: 24px;
            font-weight: 900;
            margin-top: 6px;
            color: var(--dark);
        }
        .stat-card span {
            color: var(--muted);
            font-weight: 700;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .action-card {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .action-card:hover {
            border-color: var(--red);
            transform: translateY(-1px);
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.08);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--card);
            color: var(--dark);
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
            transition: all 0.15s ease;
            white-space: nowrap;
            touch-action: manipulation;
        }
        .btn:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
            transform: translateY(-1px);
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
        .btn.danger {
            color: #b42332;
            border-color: #fecaca;
            background: #fef2f2;
        }
        .btn.danger:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }
        .btn.soft {
            background: var(--bg);
            border-color: var(--line);
            color: var(--dark);
        }
        .btn.soft:hover {
            background: #fff5f6;
            border-color: rgba(220, 38, 55, 0.35);
            color: var(--red);
            transform: translateY(-1px);
        }
        .btn.ghost {
            background: transparent;
            border-color: transparent;
            color: var(--muted);
        }
        .btn.ghost:hover {
            background: #fff5f6;
            border-color: rgba(220, 38, 55, 0.25);
            color: var(--red);
            transform: translateY(-1px);
        }
        .btn.small {
            min-height: 38px;
            padding: 0 12px;
            font-size: 12.5px;
            border-radius: 8px;
        }
        /* Form Controls */
        input, textarea, select {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            background: var(--card);
            color: var(--dark);
            font: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        input:focus, textarea:focus, select:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.12);
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        label {
            display: block;
            font-weight: 800;
            margin-bottom: 6px;
            font-size: 13px;
            color: var(--dark);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .field.full { grid-column: 1 / -1; }
        .hint {
            color: var(--muted);
            font-size: 12px;
            margin-top: 5px;
        }

        /* Checkbox Grids */
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .check-card {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            border: 1px solid var(--line);
            border-radius: 11px;
            padding: 12px;
            background: var(--card);
            color: var(--dark);
            cursor: pointer;
            transition: border-color 0.15s ease;
        }
        .check-card:hover { border-color: var(--red); }
        .check-card input { width: auto; margin-top: 3px; accent-color: var(--red); }
        .check-card strong, .check-card small { display: block; }
        .check-card small { color: var(--muted); margin-top: 3px; }

        /* Tables */
        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }
        th, td {
            text-align: start;
            padding: 12px 14px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }
        th {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            background: var(--bg);
            white-space: nowrap;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg); }
        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: wrap;
        }
        .actions form { margin: 0; }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            background: #eef2f7;
            color: #536078;
            white-space: nowrap;
        }
        .badge.active { background: #dcfce7; color: #166534; }
        .badge.inactive { background: #fee2e2; color: #991b1b; }
        .badge.system { background: #fef3c7; color: #92400e; }

        /* Alerts */
        .flash {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 13px;
        }
        .flash.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .flash.error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .flash ul { margin: 4px 0 0; padding-inline-start: 20px; }

        /* Permissions Matrix Table */
        .permission-table { min-width: 580px; }
        .permission-table th, .permission-table td {
            text-align: center;
            white-space: nowrap;
            padding: 12px 14px;
        }
        .permission-table th:first-child, .permission-table td:first-child {
            text-align: start;
            position: sticky;
            inset-inline-start: 0;
            background: var(--card);
            z-index: 2;
            box-shadow: 2px 0 6px rgba(0,0,0,0.04);
        }
        [dir="rtl"] .permission-table th:first-child, [dir="rtl"] .permission-table td:first-child {
            box-shadow: -2px 0 6px rgba(0,0,0,0.04);
        }
        .permission-table th:first-child { background: var(--bg); }
        .permission-table tr:hover td:first-child { background: var(--bg); }
        .permission-table .module-row td {
            background: var(--bg) !important;
            color: var(--dark);
            font-weight: 900;
            text-align: start;
        }
        .permission-table input {
            width: 22px;
            height: 22px;
            accent-color: var(--red);
            cursor: pointer;
            touch-action: manipulation;
        }
        /* Dark Mode Overrides */
        html.dark-mode .settings-tabs {
            background: rgba(30, 41, 59, 0.6);
        }
        html.dark-mode input:not([type="checkbox"]):not([type="radio"]),
        html.dark-mode textarea,
        html.dark-mode select {
            background: rgba(30, 41, 59, 0.7);
            border-color: var(--line);
            color: var(--dark);
        }
        html.dark-mode .btn:not(.primary):not(.danger) {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--line);
            color: var(--dark);
        }
        html.dark-mode .btn.danger {
            background: rgba(220, 38, 55, 0.15);
            border-color: rgba(220, 38, 55, 0.3);
            color: #fca5a5;
        }
        html.dark-mode th {
            background: rgba(255, 255, 255, 0.03);
        }
        html.dark-mode tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        @media(max-width: 1024px) {
            .settings-main { padding: 20px 16px 40px; }
            .form-grid { grid-template-columns: 1fr; }
        }
        @media(max-width: 900px) {
            .crm-topbar-menu-btn, .menu-button { display: inline-flex; }
        }
        @media(max-width: 768px) {
            .settings-main { padding: 14px 12px 36px; min-width: 0; width: 100%; max-width: 100vw; }
            .crm-topbar, .settings-top, .topbar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                margin-bottom: 16px;
            }
            .crm-topbar-left, .topbar-left, .settings-heading {
                width: 100%;
                justify-content: flex-start;
            }
            .crm-topbar-right, .top-actions, .settings-user-tools {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 8px;
            }
            .crm-topbar-actions {
                display: flex;
                flex: 1 1 auto;
                gap: 8px;
                flex-wrap: wrap;
            }
            .crm-topbar-actions .btn {
                flex: 1 1 auto;
            }
            .settings-tabs {
                display: flex;
                flex-direction: row;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
                padding: 4px;
                gap: 6px;
                scrollbar-width: none;
                max-width: 100%;
            }
            .settings-tabs::-webkit-scrollbar { display: none; }
            .settings-tabs a { min-height: 44px; flex-shrink: 0; padding: 10px 14px; }
            .btn { min-height: 44px; }
            .btn.small { min-height: 40px; }
            .checkbox-grid { grid-template-columns: 1fr; }
            .permission-table input {
                width: 24px;
                height: 24px;
                min-width: 24px;
                min-height: 24px;
            }
        }
        @media(max-width: 430px) {
            .settings-main { padding: 12px 8px 30px; }
            .panel { padding: 16px 12px; border-radius: 12px; }
            .crm-topbar-title h1 { font-size: 20px; }
            .btn { width: 100%; }
            .actions .btn { width: auto; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card { padding: 12px 14px; }
            .stat-card b { font-size: 20px; }
        }
        @media(max-width: 375px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
    @stack('head')
</head>
<body>
@include('partials.page-loader')
<div class="settings-shell">
    @include('partials.crm-sidebar')
    <button class="crm-overlay" id="crmSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>

    <main class="settings-main">
        @include('partials.topbar')

        <nav class="settings-tabs">
            <a class="{{ request()->routeIs('v2.settings') ? 'active' : '' }}" href="{{ route('v2.settings') }}">
                <i class="bi bi-grid-1x2"></i> {{ __('crm.overview') }}
            </a>
            <a class="{{ request()->routeIs('v2.settings.stages.*') ? 'active' : '' }}" href="{{ route('v2.settings.stages.index') }}">
                <i class="bi bi-diagram-3"></i> {{ __('crm.stages_and_statuses') }}
            </a>
            <a class="{{ request()->routeIs('v2.settings.followup-customer-fields.*') ? 'active' : '' }}" href="{{ route('v2.settings.followup-customer-fields.index') }}">
                <i class="bi bi-card-checklist"></i> {{ __('crm.followup_customer_fields_nav') }}
            </a>
            @can('users.view')
                <a class="{{ request()->routeIs('v2.settings.users.*') ? 'active' : '' }}" href="{{ route('v2.settings.users.index') }}">
                    <i class="bi bi-people"></i> {{ __('crm.users') }}
                </a>
            @endcan
            @can('groups.view')
                <a class="{{ request()->routeIs('v2.settings.groups.*') ? 'active' : '' }}" href="{{ route('v2.settings.groups.index') }}">
                    <i class="bi bi-diagram-2"></i> {{ __('crm.groups') }}
                </a>
                <a class="{{ request()->routeIs('v2.settings.permissions.*') ? 'active' : '' }}" href="{{ route('v2.settings.permissions.index') }}">
                    <i class="bi bi-shield-lock"></i> {{ __('crm.permissions') }}
                </a>
            @endcan
            @can('notifications.manage')
                <a class="{{ request()->routeIs('v2.settings.notifications.*') ? 'active' : '' }}" href="{{ route('v2.settings.notifications.index') }}">
                    <i class="bi bi-bell"></i> {{ __('crm.notifications') }}
                </a>
            @endcan
            @can('voip.settings')
                <a class="{{ request()->routeIs('v2.settings.voip') ? 'active' : '' }}" href="{{ route('v2.settings.voip') }}">
                    <i class="bi bi-telephone"></i> {{ __('crm.voip_link') }}
                </a>
            @endcan
        </nav>

        @if (session('success'))
            <div class="flash success">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="flash error">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ __('crm.save_failed') }}
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
@stack('scripts')
</body>
</html>
