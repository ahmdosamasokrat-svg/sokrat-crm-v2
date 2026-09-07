<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __('crm.app_name', ['default' => 'SokratCRM']) }} — {{ __('crm.followup_for_lead', ['name' => $lead->name]) }}</title>
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

.crm-app { display: flex; min-height: 100vh; }
.crm-main { flex: 1; min-width: 0; padding: 24px 32px 60px; }

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
  font-size: 22px;
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

/* Client Identity Card */
.client-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 20px;
  margin-bottom: 24px;
}
.client-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--line);
}
.client-identity {
  display: flex;
  align-items: center;
  gap: 16px;
}
.client-avatar {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  background: #fef2f2;
  color: var(--red);
  display: grid;
  place-items: center;
  font-size: 22px;
  font-weight: 900;
  flex-shrink: 0;
}
.client-copy h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 900;
  color: var(--dark);
}
.client-copy p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.client-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* Client Quick Data Grid */
.client-data {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 12px;
  margin-top: 18px;
}
.client-data-item {
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 10px 14px;
}
.client-data-item small {
  display: block;
  font-size: 11px;
  font-weight: 800;
  color: var(--muted);
  margin-bottom: 3px;
}
.client-data-item strong {
  display: block;
  font-size: 13px;
  font-weight: 900;
  color: var(--dark);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Workspace Layout (2 Columns) */
.workspace {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 24px;
  align-items: start;
}

/* Panels */
.panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  margin-bottom: 20px;
}
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--line);
  background: var(--bg);
}
.panel-head h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 900;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 8px;
}
.panel-head p {
  margin: 0;
  font-size: 12px;
  color: var(--muted);
  font-weight: 700;
}
.panel-body {
  padding: 20px;
}

/* Form Elements */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.field.full {
  grid-column: 1 / -1;
}
.field label {
  font-size: 12px;
  font-weight: 800;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 4px;
}
.field label .required {
  color: var(--red);
}
.control {
  width: 100%;
  min-height: 40px;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 8px 12px;
  background: var(--card);
  color: var(--dark);
  font-size: 13px;
  font-weight: 600;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
textarea.control {
  min-height: 100px;
  resize: vertical;
  line-height: 1.6;
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
.field small {
  color: var(--muted);
  font-size: 11px;
}

/* Communication Channel Selector */
.channel-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
  gap: 8px;
}
.channel-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 8px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--bg);
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;
  font-size: 12px;
  font-weight: 800;
  color: var(--dark);
  text-align: center;
}
.channel-card i {
  font-size: 18px;
  color: var(--muted);
  transition: color 0.15s ease;
}
.channel-card:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}
.channel-card.active {
  border-color: var(--red);
  background: #fef2f2;
  color: var(--red);
}
.channel-card.active i {
  color: var(--red);
}
html.dark-mode .channel-card.active {
  background: rgba(220, 38, 55, 0.15);
  border-color: rgba(220, 38, 55, 0.4);
}

/* Sub-sections / Accordions */
.followup-stage-editor {
  margin-top: 18px;
  padding-top: 18px;
  border-top: 1px dashed var(--line);
}
.followup-stage-editor.is-hidden {
  display: none !important;
}
.followup-editor-head {
  margin-bottom: 12px;
}
.followup-editor-head h4 {
  margin: 0;
  font-size: 14px;
  font-weight: 900;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 6px;
}
.followup-editor-head p {
  margin: 2px 0 0;
  font-size: 11px;
  color: var(--muted);
}
.followup-editor-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.followup-detail-panel.is-hidden {
  display: none !important;
}

/* Quick presets for next followup */
.presets-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  flex-wrap: wrap;
}
.preset-chip {
  padding: 3px 8px;
  border-radius: 6px;
  border: 1px solid var(--line);
  background: var(--bg);
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}
.preset-chip:hover {
  border-color: var(--dark);
  color: var(--dark);
}

/* Timeline */
.timeline {
  position: relative;
  padding-inline-start: 24px;
  margin: 0;
}
.timeline::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  inset-inline-start: 7px;
  width: 2px;
  background: var(--line);
}
.timeline-item {
  position: relative;
  margin-bottom: 18px;
}
.timeline-dot {
  position: absolute;
  inset-inline-start: -24px;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--red);
  border: 3px solid #fff;
  box-shadow: 0 0 0 2px var(--line);
}
.timeline-card {
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 14px 16px;
}
.timeline-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}
.timeline-employee {
  font-weight: 800;
  color: var(--dark);
  font-size: 13px;
}
.timeline-date {
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
}
.timeline-body {
  color: #334155;
  font-size: 13px;
  line-height: 1.6;
}
html.dark-mode .timeline-body { color: #d4d4d8; }
.timeline-badge {
  display: inline-flex;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 800;
  background: #e0f2fe;
  color: #0369a1;
  margin-inline-start: 6px;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  background: #f1f5f9;
  color: #475569;
}
.badge.active { background: #dcfce7; color: #166534; }

/* Alerts */
.flash-success {
  padding: 12px 16px;
  background: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  margin-bottom: 20px;
  font-weight: 700;
}
.error-box {
  padding: 14px 18px;
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 12px;
  margin-bottom: 20px;
}
.error-box strong { display: block; margin-bottom: 6px; }
.error-box ul { margin: 0; padding-inline-start: 20px; }

/* Kanban popup embedded mode */
body.kanban-followup-popup {
  background: transparent !important;
}
body.kanban-followup-popup .crm-app {
  min-height: auto;
  display: block;
}
body.kanban-followup-popup .crm-main {
  padding: 14px;
}
body.kanban-followup-popup .crm-topbar,
body.kanban-followup-popup .topbar,
body.kanban-followup-popup .latest-followups-panel,
body.kanban-followup-popup .crm-side {
  display: none !important;
}
body.kanban-followup-popup .workspace {
  grid-template-columns: 1fr;
}
body.kanban-followup-popup .client-card {
  margin-bottom: 14px;
  box-shadow: none;
}
body.kanban-followup-popup .client-actions {
  display: none !important;
}

@media(max-width: 1024px) {
  .workspace { grid-template-columns: 1fr; }
}
@media(max-width: 768px) {
  .crm-main { padding: 16px 12px 60px; min-width: 0; width: 100%; max-width: 100%; }
  .topbar { flex-direction: column; align-items: stretch; gap: 12px; }
  .top-actions { width: 100%; flex-wrap: wrap; gap: 8px; }
  .top-actions .btn { flex: 1 1 auto; min-height: 44px; }
  .form-grid, .followup-editor-grid { grid-template-columns: 1fr; }
  .client-head { flex-direction: column; align-items: stretch; gap: 14px; }
  .client-actions { width: 100%; justify-content: stretch; flex-wrap: wrap; gap: 8px; }
  .client-actions .btn { flex: 1 1 auto; min-height: 44px; }
}
</style>
</head>
<body class="{{ request()->boolean('kanban_popup') ? 'kanban-followup-popup' : '' }}">
@include('partials.page-loader')
<div class="crm-app lead-followups-page">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        @php
            $followupTopActions = '<a href="' . route('v2.leads.show', $lead) . '" class="btn soft"><i class="bi bi-eye"></i> ' . __('crm.view_lead_data') . '</a>';
            if ($callPhone && empty($maskPhones)) {
                $followupTopActions .= '<a href="tel:' . $callPhone . '" class="btn primary"><i class="bi bi-telephone-outbound"></i> ' . __('crm.call_action') . '</a>';
            }
            $visibleCustomerAttributes = ($customerFields ?? collect())->keyBy('lead_attribute');
        @endphp

        @include('partials.topbar', [
            'title' => __('crm.followup_for_lead', ['name' => $lead->name]),
            'subtitle' => '<span>' . __('crm.lead_code_label') . ': #' . $lead->id . '</span> <span style="margin:0 6px">•</span> <span>' . __('crm.registered_at') . ': ' . ($lead->created_at?->format('Y-m-d') ?? '—') . '</span>',
            'icon' => 'bi-chat-dots-fill',
            'backUrl' => route('v2.leads.show', $lead),
            'backTitle' => __('crm.lead_details'),
            'actions' => $followupTopActions,
        ])

        <!-- CLIENT IDENTITY & OVERVIEW CARD -->
        <section class="client-card">
            <div class="client-head">
                <div class="client-identity">
                    <div class="client-avatar">
                        {{ mb_substr((string) $lead->name, 0, 1) }}
                    </div>
                    <div class="client-copy">
                        <h2>{{ $lead->name }}</h2>
                        <p>
                            @if ($visibleCustomerAttributes->has('company_name') && $lead->company_name)
                                <span><i class="bi bi-building"></i> {{ $lead->company_name }}</span>
                                <span>•</span>
                            @endif
                            @if ($visibleCustomerAttributes->has('governorate') && $lead->governorate)
                                <span><i class="bi bi-geo-alt"></i> {{ $lead->governorate }}</span>
                                <span>•</span>
                            @endif
                            <span><i class="bi bi-person-badge"></i> {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?? __('crm.unassigned') }}</span>
                        </p>
                    </div>
                </div>

                <div class="client-actions">
                    <a href="{{ route('v2.leads.show', $lead) }}" class="btn soft small">
                        <i class="bi bi-person"></i> {{ __('crm.view_lead_data') }}
                    </a>
                    @if ($callPhone && empty($maskPhones))
                        <a href="tel:{{ $callPhone }}" class="btn soft small" style="color:#2563eb">
                            <i class="bi bi-telephone"></i> {{ __('crm.call_action') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="client-data">
                <div class="client-data-item">
                    <small>{{ __('crm.phone') }}</small>
                    <strong dir="ltr">
                        @if (!empty($maskPhones))
                            {{ \App\Support\PhoneMask::mask($lead->phone) ?: '—' }}
                        @elseif ($callPhone)
                            <a href="tel:{{ $callPhone }}" style="color:inherit">{{ $lead->phone }}</a>
                        @else
                            {{ $lead->phone ?: '—' }}
                        @endif
                    </strong>
                </div>
                <div class="client-data-item">
                    <small>{{ __('crm.email') }}</small>
                    <strong>{{ $lead->email ?: '—' }}</strong>
                </div>
                @if ($activityField = $visibleCustomerAttributes->get('activity'))
                    <div class="client-data-item">
                        <small>{{ $activityField->localizedLabel() }}</small>
                        <strong>{{ $lead->activity ?: '—' }}</strong>
                    </div>
                @endif
                <div class="client-data-item">
                    <small>{{ __('crm.current_stage') }}</small>
                    <strong>{{ $lead->status?->stage?->localizedName() ?? ($lead->status?->stage?->name_ar ?? '—') }}</strong>
                </div>
                <div class="client-data-item">
                    <small>{{ __('crm.current_status') }}</small>
                    <strong style="color:{{ $lead->status?->color ?? 'inherit' }}">{{ $lead->status?->name_ar ?? '—' }}</strong>
                </div>
                <div class="client-data-item">
                    <small>{{ __('crm.responsible_employee') }}</small>
                    <strong>{{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}</strong>
                </div>
                <div class="client-data-item">
                    <small>{{ __('crm.next_followup') }}</small>
                    <strong>{{ $lead->next_follow_up_at ? $lead->next_follow_up_at->format('d/m/Y - h:i A') : '—' }}</strong>
                </div>
            </div>
        </section>

        @if (request()->boolean('saved') || session('success'))
            <script>
            (() => {
                const isKanbanPopup = @json(request()->boolean('kanban_popup') || request()->boolean('popup'));
                const successRedirectUrl = isKanbanPopup ? @json(route('v2.leads.kanban')) : @json(route('v2.leads'));
                const successMsg = @json(session('success') ?? 'تم حفظ بيانات المرحلة وتسجيل المتابعة بنجاح.');

                try {
                    if (window.parent && window.self !== window.top) {
                        window.parent.postMessage({
                            type: 'crm-kanban-followup-saved',
                            action: 'lead-transition-saved',
                            redirectUrl: successRedirectUrl,
                            message: successMsg
                        }, window.location.origin);
                    }
                } catch (e) {}

                try {
                    if (window.parent && window.self !== window.top && window.parent.location) {
                        try {
                            window.parent.closePopup?.();
                            window.parent.closeModal?.();
                            const openModals = window.parent.document?.querySelectorAll('.kanban-followup-modal.open, #crmKanbanFollowupModal, #crmKanbanActionModal, #crmKanbanUtilityModal');
                            openModals?.forEach(m => {
                                m.classList.remove('open');
                                m.setAttribute('aria-hidden', 'true');
                            });
                            window.parent.document?.body?.classList.remove('kanban-modal-open');
                        } catch (e) {}

                        window.parent.location.href = successRedirectUrl;
                        return;
                    }
                } catch (e) {}

                window.location.href = successRedirectUrl;
            })();
            </script>
            <div class="flash-success">
                <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> {{ __('crm.review_followup_data') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="workspace">
            <!-- MAIN FORM COLUMN -->
            @can('leads.followups.create')
            <section class="panel">
                <div class="panel-head">
                    <h3><i class="bi bi-pencil-square"></i> {{ __('crm.new_followup_data') }}</h3>
                    <p><i class="bi bi-person-check"></i> الموظف المسجل: <strong>{{ $currentEmployee }}</strong></p>
                </div>

                <div class="panel-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('v2.leads.followups.store', $lead) }}" id="followupForm">
                        @csrf

                        @if (request()->boolean('kanban_popup'))
                            <input type="hidden" name="kanban_popup" value="1">
                        @endif

                        @php
                            $selectedStatusId = (int) old('lead_status_id', $defaultStatusId ?? $lead->lead_status_id);
                            $selectedCommunicationType = (string) old('communication_type', $defaultCommunicationType);
                        @endphp

                        <div class="form-grid">
                            <!-- STATUS & STAGE -->
                            <div class="field full">
                                <label for="lead_status_id">
                                    {{ __('crm.status_stage') }} <span class="required">*</span>
                                </label>
                                <select
                                    class="control"
                                    id="lead_status_id"
                                    name="lead_status_id"
                                    required
                                    @if (request()->boolean('kanban_popup')) disabled @endif
                                >
                                    @foreach ($statusGroups as $stageName => $stageStatuses)
                                        <optgroup label="{{ $stageName }}">
                                            @foreach ($stageStatuses as $status)
                                                <option
                                                    value="{{ $status->id }}"
                                                    data-stage-id="{{ $status->pipeline_stage_id }}"
                                                    data-stage-name="{{ $status->stage?->name_ar ?? '----' }}"
                                                    data-stage-description="{{ $status->stage?->description_ar ?? '----' }}"
                                                    data-stage-code="{{ $status->stage?->code ?? '----' }}"
                                                    data-stage-position="{{ $status->stage?->position ?? '----' }}"
                                                    data-stage-color="{{ $status->stage?->color ?? '#64748b' }}"
                                                    data-status-name="{{ $status->name_ar }}"
                                                    data-status-code="{{ $status->code }}"
                                                    data-status-color="{{ $status->color ?? '#64748b' }}"
                                                    data-terminal="{{ $status->is_terminal ? '1' : '0' }}"
                                                    @selected($selectedStatusId === (int) $status->id)
                                                >
                                                    {{ $status->name_ar }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>

                                @if (request()->boolean('kanban_popup'))
                                    <input type="hidden" name="lead_status_id" value="{{ $selectedStatusId }}">
                                @endif
                                <small>
                                    @if (request()->boolean('kanban_popup'))
                                        الحالة الجديدة محددة تلقائيًا من العمود الذي تم نقل العميل إليه.
                                    @else
                                        اختيار الحالة يحدد المرحلة الجديدة للعميل.
                                    @endif
                                </small>
                            </div>

                            <!-- DYNAMIC STAGE QUESTIONS CONTAINER -->
                            <div id="dynamicStageQuestionsSection" class="field full" style="margin-top:4px;">
                                @foreach (($activeStages ?? []) as $astage)
                                    @if ($astage->activeFields->isNotEmpty())
                                        <div class="stage-questions-block" id="stage_q_block_{{ $astage->id }}" data-stage-id="{{ $astage->id }}" style="display:none; background:var(--bg); border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:14px;">
                                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; font-weight:800; font-size:14px; color:var(--dark);">
                                                <i class="bi bi-ui-checks" style="color:var(--red);"></i>
                                                <span>{{ __('crm.stage_questions') }} ({{ $astage->localizedName() }})</span>
                                            </div>
                                            @include('partials.stage-field-inputs', [
                                                'fields' => $astage->activeFields,
                                                'recordValues' => old('stage_fields', []),
                                                'prefix' => 'stage_fields',
                                                'scope' => 'followup_' . $astage->id,
                                            ])
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <!-- CAMPAIGN SELECTION (IF APPLICABLE) -->
                            @if ($manageableCampaigns->isNotEmpty())
                                <div class="field">
                                    <label for="followupCampaign"><i class="bi bi-megaphone"></i> {{ __('crm.campaign') }}</label>
                                    <select class="control" id="followupCampaign" name="campaign_id">
                                        <option value="">{{ __('crm.no_campaign_change') }}</option>
                                        @foreach ($manageableCampaigns as $campaignOption)
                                            <option
                                                value="{{ $campaignOption->id }}"
                                                data-user-ids="{{ implode(',', $campaignOption->users->modelKeys()) }}"
                                                @selected(old('campaign_id', $currentCampaign?->id) === $campaignOption->id)
                                            >
                                                {{ $campaignOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field">
                                    <label for="followupCampaignAssignee"><i class="bi bi-person-check"></i> {{ __('crm.assigned_employee') }}</label>
                                    <select class="control" id="followupCampaignAssignee" name="assigned_user_id">
                                        <option value="">{{ __('crm.keep_current_assignee') }}</option>
                                        @foreach ($campaignAssignees as $campaignAssignee)
                                            <option
                                                value="{{ $campaignAssignee->id }}"
                                                @selected((int) old('assigned_user_id', $lead->assigned_user_id) === (int) $campaignAssignee->id)
                                            >
                                                {{ $campaignAssignee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- COMMUNICATION TYPE SELECTOR -->
                            <div class="field full">
                                <label>{{ __('crm.communication_type') }} <span class="required">*</span></label>
                                <input type="hidden" name="communication_type" id="communicationTypeInput" value="{{ $selectedCommunicationType }}">
                                <div class="channel-grid">
                                    @foreach ($communicationTypes as $commKey => $commLabel)
                                        @php
                                            $commIcon = match($commKey) {
                                                'call' => 'bi-telephone',
                                                'whatsapp' => 'bi-whatsapp',
                                                'meeting' => 'bi-people',
                                                'email' => 'bi-envelope',
                                                default => 'bi-clock-history',
                                            };
                                        @endphp
                                        <div
                                            class="channel-card {{ $selectedCommunicationType === $commKey ? 'active' : '' }}"
                                            data-channel="{{ $commKey }}"
                                            onclick="selectChannel('{{ $commKey }}')"
                                        >
                                            <i class="bi {{ $commIcon }}"></i>
                                            <span>{{ $commLabel }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- OUTCOME NOTES -->
                            <div class="field full">
                                <label for="followupOutcome">
                                    {{ __('crm.followup_notes') }} <span class="required">*</span>
                                </label>
                                <textarea
                                    class="control"
                                    id="followupOutcome"
                                    name="outcome"
                                    rows="4"
                                    required
                                    placeholder="{{ __('crm.followup_notes_placeholder') }}"
                                >{{ old('outcome') }}</textarea>
                            </div>

                            <!-- NEXT FOLLOWUP DATE -->
                            <div class="field full">
                                <label for="nextFollowUpAt">
                                    {{ __('crm.next_followup_date') }}
                                </label>
                                <input
                                    class="control"
                                    id="nextFollowUpAt"
                                    type="datetime-local"
                                    name="next_follow_up_at"
                                    value="{{ old('next_follow_up_at', $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d\TH:i') : '') }}"
                                >
                                <div class="presets-wrap">
                                    <span style="font-size:11px;color:var(--muted);font-weight:700">اقتراحات سريعة:</span>
                                    <button type="button" class="preset-chip" onclick="setNextDate(1, 10)">غداً 10:00 ص</button>
                                    <button type="button" class="preset-chip" onclick="setNextDate(3, 11)">بعد 3 أيام</button>
                                    <button type="button" class="preset-chip" onclick="setNextDate(7, 10)">بعد أسبوع</button>
                                </div>
                                <small>{{ __('crm.next_followup_hint') }}</small>
                            </div>
                        </div>

                        <!-- CONDITIONAL: QUOTATION DATA -->
                        <section class="followup-stage-editor is-hidden" id="quotationSection">
                            <div class="followup-editor-head">
                                <h4><i class="bi bi-file-earmark-text"></i> {{ __('crm.quotation_data') }}</h4>
                                <p>بيانات عرض السعر والحل المطلوب لهذا العميل.</p>
                            </div>
                            <div class="followup-editor-grid">
                                <div class="field">
                                    <label for="solutionType">{{ __('crm.system_type') }} <span class="required">*</span></label>
                                    <select class="control" id="solutionType" name="solution_type">
                                        <option value="">{{ __('crm.select_system_type') }}</option>
                                        <option value="call_center" @selected(old('solution_type', $lead->solution_type) === 'call_center')>Call Center</option>
                                        <option value="erp" @selected(old('solution_type', $lead->solution_type) === 'erp')>ERP</option>
                                    </select>
                                </div>

                                <div class="field followup-detail-panel is-hidden" id="callCenterFields">
                                    <label for="linesCount">{{ __('crm.lines_count') }}</label>
                                    <input class="control" id="linesCount" type="number" min="0" name="lines_count" value="{{ old('lines_count', $lead->lines_count) }}" placeholder="مثال: 4">
                                </div>

                                <div class="field followup-detail-panel is-hidden" id="erpFields">
                                    <label for="departments">{{ __('crm.departments') }}</label>
                                    <input class="control" id="departments" type="text" name="departments" value="{{ old('departments', $lead->departments) }}" placeholder="مثال: الحسابات، المبيعات">
                                </div>

                                <div class="field">
                                    <label for="quotationFile">{{ __('crm.quotation_file') }}</label>
                                    <input class="control" id="quotationFile" type="file" name="quotation_file">
                                    <small id="quotationFileHelp">{{ $quotationFileHelpText }}</small>
                                </div>
                            </div>
                        </section>

                        <!-- CONDITIONAL: NOT INTERESTED REASON -->
                        <section class="followup-stage-editor is-hidden" id="notInterestedSection">
                            <div class="followup-editor-head">
                                <h4><i class="bi bi-x-circle"></i> {{ __('crm.not_interested_reason') }}</h4>
                                <p>سجّل سبب عدم اهتمام العميل بالخدمة.</p>
                            </div>
                            <div class="field full">
                                <textarea class="control" id="disinterestReason" name="disinterest_reason" rows="3" placeholder="اكتب سبب الرفض أو عدم الاهتمام بالتفصيل...">{{ old('disinterest_reason', $lead->disinterest_reason) }}</textarea>
                            </div>
                        </section>

                        @if (($customerFields ?? collect())->isNotEmpty())
                            <!-- CONFIGURABLE CUSTOMER & COMPANY DATA -->
                            <section class="followup-stage-editor" id="businessDataSection">
                                <div class="followup-editor-head">
                                    <h4><i class="bi bi-building"></i> {{ __('crm.company_lead_data') }}</h4>
                                    <p>{{ __('crm.followup_customer_fields_employee_desc') }}</p>
                                </div>
                                @foreach ($customerFields as $customerField)
                                    <input type="hidden" name="customer_field_presence[]" value="{{ $customerField->key }}">
                                @endforeach
                                @include('partials.stage-field-inputs', [
                                    'fields' => $customerFields,
                                    'recordValues' => $customerFieldValues ?? [],
                                    'prefix' => 'customer_fields',
                                    'scope' => 'followup_customer_fields',
                                ])
                            </section>
                        @endif

                        <!-- FORM ACTION BUTTONS -->
                        <div style="display:flex;align-items:center;gap:12px;margin-top:24px;padding-top:18px;border-top:1px solid var(--line)">
                            <button type="submit" class="btn primary" style="height:44px;min-height:44px;padding:0 24px;font-size:14px">
                                <i class="bi bi-check-lg"></i> {{ __('crm.save_followup') }}
                            </button>
                            <a href="{{ route('v2.leads.show', $lead) }}" class="btn soft" style="height:44px;min-height:44px">
                                {{ __('crm.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </section>
            @endcan

            <!-- PREVIOUS FOLLOWUPS TIMELINE -->
            <section class="panel latest-followups-panel">
                <div class="panel-head">
                    <h3><i class="bi bi-clock-history"></i> {{ __('crm.latest_followups') }}</h3>
                    <span class="badge">{{ $followups->count() }}</span>
                </div>

                <div class="panel-body">
                    @if ($followups->isNotEmpty())
                        <div class="timeline">
                            @foreach ($followups as $item)
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-card">
                                        <div class="timeline-head">
                                            <span class="timeline-employee">
                                                <i class="bi bi-person"></i> {{ $item->user?->name ?? $item->employee_name }}
                                                @php
                                                    $commLabel = $communicationTypes[$item->communication_type] ?? $item->communication_type;
                                                @endphp
                                                <span class="timeline-badge">{{ $commLabel }}</span>
                                            </span>
                                            <span class="timeline-date">
                                                {{ $item->followed_up_at ? $item->followed_up_at->format('d/m/Y - h:i A') : '—' }}
                                            </span>
                                        </div>

                                        @if ($item->fromStatus || $item->toStatus)
                                            <div style="font-size:12px;font-weight:800;color:var(--muted);margin-bottom:6px">
                                                المرحلة: <strong style="color:var(--dark)">{{ $item->toStatus?->stage?->name_ar ?? $item->toStatus?->name_ar ?? '—' }}</strong>
                                                @if ($item->toStatus)
                                                    • الحالة: <strong style="color:{{ $item->toStatus->color ?? 'var(--dark)' }}">{{ $item->toStatus->name_ar }}</strong>
                                                @endif
                                            </div>
                                        @endif

                                        <p class="timeline-body" style="margin:0;white-space:pre-line">{{ $item->outcome }}</p>

                                        @if (!empty($item->field_changes))
                                            <div style="margin-top:8px;padding:8px 10px;background:var(--card);border:1px solid var(--line);border-radius:8px;font-size:11px">
                                                <strong style="display:block;margin-bottom:4px;color:var(--dark)">تعديلات البيانات:</strong>
                                                @foreach ($item->field_changes as $chg)
                                                    <div style="color:var(--muted)">{{ $chg['label'] ?? 'حقل' }}: <span style="text-decoration:line-through;color:#b42332">{{ $chg['old'] ?? '—' }}</span> → <strong style="color:#15803d">{{ $chg['new'] ?? '—' }}</strong></div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($item->next_follow_up_at)
                                            <div style="margin-top:8px;font-size:11px;color:var(--muted);font-weight:700">
                                                <i class="bi bi-calendar-event"></i> المتابعة القادمة: <strong style="color:var(--dark)">{{ $item->next_follow_up_at->format('d/m/Y - h:i A') }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center;padding:30px 16px;color:var(--muted)">
                            <i class="bi bi-chat-left-dots" style="font-size:28px;display:block;margin-bottom:6px"></i>
                            {{ __('crm.no_lead_followups') }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </main>
</div>

<script>
function selectChannel(key) {
    document.getElementById('communicationTypeInput').value = key;
    document.querySelectorAll('.channel-card').forEach(card => {
        card.classList.toggle('active', card.dataset.channel === key);
    });
}

function setNextDate(daysAhead, hour) {
    const d = new Date();
    d.setDate(d.getDate() + daysAhead);
    d.setHours(hour, 0, 0, 0);
    const pad = n => String(n).padStart(2, '0');
    const val = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const input = document.getElementById('nextFollowUpAt');
    if (input) input.value = val;
}

(() => {
    const statusSelect = document.getElementById('lead_status_id');
    const notInterestedSection = document.getElementById('notInterestedSection');
    const quotationSection = document.getElementById('quotationSection');
    const solutionTypeSelect = document.getElementById('solutionType');
    const callCenterFields = document.getElementById('callCenterFields');
    const erpFields = document.getElementById('erpFields');
    const disinterestReason = document.getElementById('disinterestReason');
    const linesCount = document.getElementById('linesCount');
    const questionBlocks = document.querySelectorAll('.stage-questions-block');

    const quotationStatuses = ['quotation', 'discussion', 'contract_closed', 'execution'];

    function getSelectedStatusCode() {
        const opt = statusSelect?.selectedOptions?.[0];
        return opt?.dataset?.statusCode || '';
    }

    function updateStageSections() {
        const code = getSelectedStatusCode();
        const isNotInterested = code === 'not_interested';
        const isQuotation = quotationStatuses.includes(code);

        if (notInterestedSection) notInterestedSection.classList.toggle('is-hidden', !isNotInterested);
        if (quotationSection) quotationSection.classList.toggle('is-hidden', !isQuotation);

        if (disinterestReason) disinterestReason.required = isNotInterested;
        if (solutionTypeSelect) solutionTypeSelect.required = isQuotation;

        updateSolutionFields();
    }

    function updateSolutionFields() {
        const code = getSelectedStatusCode();
        const isQuotation = quotationStatuses.includes(code);
        const sol = isQuotation ? solutionTypeSelect?.value : '';
        const isCallCenter = sol === 'call_center';
        const isErp = sol === 'erp';

        if (callCenterFields) callCenterFields.classList.toggle('is-hidden', !isCallCenter);
        if (erpFields) erpFields.classList.toggle('is-hidden', !isErp);
        if (linesCount) linesCount.required = isCallCenter;
    }

    function syncStageQuestions() {
        const selectedOpt = statusSelect?.options?.[statusSelect.selectedIndex];
        const stageId = selectedOpt ? selectedOpt.getAttribute('data-stage-id') : null;

        questionBlocks.forEach(block => {
            const blockStageId = block.getAttribute('data-stage-id');
            const isMatch = blockStageId && stageId && String(blockStageId) === String(stageId);
            block.style.display = isMatch ? 'block' : 'none';
            block.querySelectorAll('input, select, textarea').forEach(input => {
                if (isMatch) {
                    input.removeAttribute('disabled');
                } else {
                    input.setAttribute('disabled', 'disabled');
                }
            });
        });
    }

    statusSelect?.addEventListener('change', () => {
        updateStageSections();
        syncStageQuestions();
    });

    solutionTypeSelect?.addEventListener('change', updateSolutionFields);

    updateStageSections();
    syncStageQuestions();
})();
</script>

@if ($callPhone && empty($maskPhones) && ($defaultCommunicationType === 'call' || request('channel') === 'call'))
<script>
(() => {
    const callPhone = @json($callPhone);
    if (!callPhone) return;

    const initiateCall = () => {
        const telUri = 'tel:' + encodeURIComponent(callPhone);
        try {
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.setAttribute('src', telUri);
            document.body.appendChild(iframe);
            setTimeout(() => { iframe.remove(); }, 3000);
        } catch (e) {}

        setTimeout(() => {
            try { window.location.href = telUri; } catch (e) {}
        }, 150);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => setTimeout(initiateCall, 300));
    } else {
        setTimeout(initiateCall, 300);
    }
})();
</script>
@endif
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
</body>
</html>
