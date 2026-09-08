<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __('crm.add_lead') }} — {{ config('app.name', 'SokratCRM') }}</title>
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
<link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
<link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v=1.0.0">
<script>
(() => {
    try {
        const theme = localStorage.getItem('sokrat.crm.theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (theme === 'dark' || (theme !== 'light' && prefersDark)) {
            document.documentElement.classList.add('dark-mode');
        }
    } catch (e) {}
})();
</script>

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

/* Cards & Sections */
.form-card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 24px;
  margin-bottom: 24px;
}
.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--line);
  flex-wrap: wrap;
  gap: 10px;
}
.section-head h2 {
  margin: 0;
  font-size: 17px;
  font-weight: 900;
  color: var(--dark);
  display: flex;
  align-items: center;
  gap: 8px;
}
.section-head h2 i {
  color: var(--red);
  font-size: 19px;
}
.section-head p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 12px;
}

/* Grid & Fields */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
}
.form-grid.two-cols {
  grid-template-columns: repeat(2, minmax(0, 1fr));
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
label .required {
  color: var(--red);
}
label .optional {
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
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
textarea.control {
  min-height: 90px;
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
.field small, .field .help {
  color: var(--muted);
  font-size: 11px;
  margin-top: 3px;
}

/* Stage Preview Pill */
.stage-badge-preview {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  background: var(--bg);
  border: 1px solid var(--line);
  color: var(--dark);
}

/* Quick Presets for Follow-up */
.presets-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  flex-wrap: wrap;
}
.preset-chip {
  padding: 3px 9px;
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

/* Conditional display */
.is-hidden {
  display: none !important;
}

/* Alerts */
.error-box {
  padding: 14px 18px;
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 12px;
  margin-bottom: 24px;
}
.error-box strong { display: block; margin-bottom: 6px; }
.error-box ul { margin: 0; padding-inline-start: 20px; }

@media(max-width: 1024px) {
  .form-grid.two-cols { grid-template-columns: 1fr; }
}
@media(max-width: 768px) {
  .crm-main { padding: 16px 12px 60px; min-width: 0; width: 100%; max-width: 100%; }
  .topbar { flex-direction: column; align-items: stretch; gap: 12px; }
  .top-actions { width: 100%; flex-wrap: wrap; gap: 8px; }
  .top-actions .btn { flex: 1 1 auto; min-height: 44px; }
  .form-grid { grid-template-columns: 1fr; }
  .form-card { padding: 16px; }
  .presets-wrap .preset-chip { min-height: 36px; padding: 6px 12px; }
}
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
body.kanban-followup-popup .crm-side {
  display: none !important;
}
</style>
</head>
<body class="{{ request()->boolean('kanban_popup') ? 'kanban-followup-popup' : '' }}">
@include('partials.page-loader')
<div class="crm-app lead-create-page">
    @include('partials.crm-sidebar')

    <main class="crm-main">
        @include('partials.topbar', [
            'title' => __('crm.add_new_lead'),
            'subtitle' => __('crm.enter_lead_basics'),
            'icon' => 'bi-person-plus-fill',
            'backUrl' => route('v2.leads'),
            'backTitle' => __('crm.back_to_leads_list'),
            'actions' => '<a href="' . route('v2.leads') . '" class="btn soft"><i class="bi bi-x-lg"></i> ' . __('crm.cancel') . '</a>',
        ])

        @if ($errors->any())
            <div class="error-box">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> يرجى مراجعة البيانات التالية:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('v2.leads.store', [], false) }}" enctype="multipart/form-data" id="createLeadForm">
            @csrf

            <!-- CARD 1: PRIMARY CONTACT & IDENTITY -->
            <section class="form-card">
                <div class="section-head">
                    <h2><i class="bi bi-person-badge"></i> بيانات العميل والتواصل الأساسية</h2>
                    <p>الاسم ورقم الهاتف والمصدر وموظف المبيعات المسؤول</p>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="firstName">
                            {{ __('crm.first_name') ?: 'الاسم الأول' }} <span class="required">*</span>
                        </label>
                        <input
                            class="control"
                            id="firstName"
                            type="text"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            required
                            maxlength="75"
                            placeholder="مثال: أحمد"
                            autocomplete="off"
                        >
                    </div>

                    <div class="field">
                        <label for="lastName">
                            {{ __('crm.last_name') ?: 'الاسم الأخير / العائلة' }}
                        </label>
                        <input
                            class="control"
                            id="lastName"
                            type="text"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            maxlength="75"
                            placeholder="مثال: المنصور"
                            autocomplete="off"
                        >
                    </div>

                    <div class="field">
                        <label for="phone">
                            {{ __('crm.phone') }} <span class="required">*</span>
                        </label>
                        <input
                            class="control"
                            id="phone"
                            type="tel"
                            name="phone"
                            value="{{ old('phone') }}"
                            required
                            maxlength="50"
                            placeholder="مثال: 0501234567 أو 01012345678"
                            dir="ltr"
                        >
                    </div>

                    <div class="field">
                        <label for="email">
                            {{ __('crm.email') }} <span class="optional">({{ __('crm.optional') ?: 'اختياري' }})</span>
                        </label>
                        <input
                            class="control"
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            maxlength="150"
                            placeholder="name@company.com"
                            dir="ltr"
                        >
                    </div>

                    <div class="field">
                        <label for="source">
                            {{ __('crm.source') }} <span class="required">*</span>
                        </label>
                        <input
                            class="control"
                            id="source"
                            type="text"
                            name="source"
                            value="{{ old('source') }}"
                            maxlength="100"
                            list="sourceOptions"
                            required
                            placeholder="اختر أو اكتب مصدر العميل..."
                        >
                        <datalist id="sourceOptions">
                            @foreach ($sources as $source)
                                <option value="{{ $source }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="field">
                        <label for="campaignId">
                            {{ __('crm.campaign') }} <span class="optional">({{ __('crm.optional') ?: 'اختياري' }})</span>
                        </label>
                        <select
                            class="control"
                            id="campaignId"
                            name="campaign_id"
                            data-current-user-id="{{ auth()->id() }}"
                        >
                            <option value="">{{ __('crm.no_campaign') ?: 'بدون حملة إعلانية' }}</option>
                            @foreach ($campaigns as $campaignOption)
                                <option
                                    value="{{ $campaignOption->id }}"
                                    data-user-ids="{{ implode(',', $campaignOption->users->modelKeys()) }}"
                                    @selected((int) old('campaign_id', $campaign?->id) === (int) $campaignOption->id)
                                >
                                    {{ $campaignOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="assignedUserId">
                            {{ __('crm.responsible_employee') }} <span class="required">*</span>
                        </label>
                        @if ($canAssignLead)
                            <select class="control" id="assignedUserId" name="assigned_user_id" required>
                                @foreach ($assignableUsers as $assignableUser)
                                    <option
                                        value="{{ $assignableUser->id }}"
                                        data-user-id="{{ $assignableUser->id }}"
                                        @selected((int) old('assigned_user_id', auth()->id()) === (int) $assignableUser->id)
                                    >
                                        {{ $assignableUser->name }}
                                        @if ($assignableUser->username) ({{ $assignableUser->username }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input class="control" type="text" value="{{ $assignedEmployee }}" readonly style="background:var(--bg)">
                            <input type="hidden" name="assigned_user_id" value="{{ auth()->id() }}">
                        @endif
                    </div>
                </div>
            </section>

            <!-- CARD 2: PIPELINE STAGE & STATUS SELECTION -->
            <section class="form-card">
                <div class="section-head">
                    <h2><i class="bi bi-diagram-3"></i> حالة ومرحلة العميل في المسار</h2>
                    <div id="stageBadgePreviewWrap">
                        <span class="stage-badge-preview" id="stageBadgePreview">
                            <span id="stageDot" style="width:8px;height:8px;border-radius:50%;background:#3478f6"></span>
                            <span id="stageText">{{ __('crm.select_lead_status') }}</span>
                        </span>
                    </div>
                </div>

                <div class="form-grid two-cols">
                    <div class="field full">
                        <label for="leadStatus">
                            {{ __('حالة العميل المبدئية') }} <span class="required">*</span>
                        </label>
                        <select class="control" id="leadStatus" name="lead_status_id" required>
                            <option value="">{{ __('crm.select_lead_status') }}</option>
                            @foreach ($statusGroups as $stageName => $stageStatuses)
                                <optgroup label="{{ $stageName }}">
                                    @foreach ($stageStatuses as $status)
                                        <option
                                            value="{{ $status->id }}"
                                            data-code="{{ $status->code }}"
                                            data-stage-id="{{ $status->pipeline_stage_id }}"
                                            data-stage-name="{{ $status->stage?->localizedName() ?? ($status->stage?->name_ar ?? $stageName) }}"
                                            data-stage-color="{{ $status->stage?->color ?? '#3478f6' }}"
                                            data-status-name="{{ $status->name_ar }}"
                                            data-status-color="{{ $status->color ?? '#3478f6' }}"
                                            @selected((string) old('lead_status_id') === (string) $status->id)
                                        >
                                            {{ $status->name_ar }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- DYNAMIC STAGE QUESTIONS & FIELDS SECTION -->
                @php
                    $initStatusId = (int) old('lead_status_id', $defaultStatusId ?? ($statuses->firstWhere('code', 'new')?->id ?? ($statuses->first()?->id ?? 1)));
                    $initStatus = $statuses->firstWhere('id', $initStatusId);
                    $initStageId = $initStatus?->pipeline_stage_id;
                @endphp
                <div id="dynamicStageQuestionsSection" style="margin-top:20px;">
                    @foreach ($activeStages as $astage)
                        @if ($astage->activeFields->isNotEmpty())
                            @php
                                $isInitStage = $initStageId && ((int) $astage->id === (int) $initStageId);
                            @endphp
                            <div
                                class="stage-questions-block"
                                id="stage_q_block_{{ $astage->id }}"
                                data-stage-id="{{ $astage->id }}"
                                style="{{ $isInitStage ? 'display:block;' : 'display:none;' }} background:var(--bg); border:1px solid var(--line); border-radius:var(--radius); padding:20px; margin-bottom:16px;"
                            >
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; font-weight:900; font-size:14px; color:var(--dark);">
                                    <i class="bi bi-ui-checks" style="color:var(--red);font-size:18px"></i>
                                    <span>أسئلة ومحددات مرحلة: <strong>{{ $astage->localizedName() }}</strong></span>
                                </div>
                                @include('partials.stage-field-inputs', [
                                    'fields' => $astage->activeFields,
                                    'recordValues' => old('stage_fields', []),
                                    'prefix' => 'stage_fields',
                                    'scope' => 'create_' . $astage->id,
                                    'disabled' => ! $isInitStage,
                                ])
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>

            <!-- CARD 3: NEXT FOLLOW-UP SCHEDULING (CONDITIONAL) -->
            <section class="form-card is-hidden" id="createNextFollowupSection">
                <div class="section-head">
                    <h2><i class="bi bi-calendar-event"></i> {{ __('crm.next_followup') }}</h2>
                    <p>{{ __('crm.next_followup_hint') }}</p>
                </div>

                <div class="form-grid two-cols">
                    <div class="field full">
                        <label for="createNextFollowupAt">
                            {{ __('crm.next_followup_date') }} <span class="required">*</span>
                        </label>
                        <input
                            class="control"
                            id="createNextFollowupAt"
                            type="datetime-local"
                            name="next_follow_up_at"
                            value="{{ old('next_follow_up_at') }}"
                        >
                        <div class="presets-wrap">
                            <span style="font-size:11px;color:var(--muted);font-weight:700">اقتراحات سريعة:</span>
                            <button type="button" class="preset-chip" onclick="setNextDate(1, 10)">غداً 10:00 ص</button>
                            <button type="button" class="preset-chip" onclick="setNextDate(3, 11)">بعد 3 أيام</button>
                            <button type="button" class="preset-chip" onclick="setNextDate(7, 10)">بعد أسبوع</button>
                        </div>
                    </div>
                </div>
            </section>


            <!-- SUBMIT ACTIONS BAR -->
            <div style="display:flex;align-items:center;gap:12px;margin-top:24px;flex-wrap:wrap">
                <button type="submit" class="btn primary" style="height:46px;min-height:46px;padding:0 28px;font-size:14px">
                    <i class="bi bi-check-lg"></i> {{ __('حفظ العميل') }}
                </button>
                <button type="submit" name="after_save" value="followup" class="btn soft" style="height:46px;min-height:46px;padding:0 20px;font-size:14px">
                    <i class="bi bi-telephone-forward"></i> {{ __('حفظ والبدء بتسجيل متابعة') }}
                </button>
                @if (request()->boolean('kanban_popup'))
                    <button type="button" class="btn soft" style="height:46px;min-height:46px" onclick="cancelKanbanPopup()">
                        {{ __('crm.cancel') }}
                    </button>
                @else
                    <a href="{{ route('v2.leads') }}" class="btn soft" style="height:46px;min-height:46px">
                        {{ __('crm.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </main>
</div>

<script>
function cancelKanbanPopup() {
    try {
        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ type: 'crm-kanban-popup-close' }, window.location.origin);
            return;
        }
    } catch (e) {}
}

function setNextDate(daysAhead, hour) {
    const d = new Date();
    d.setDate(d.getDate() + daysAhead);
    d.setHours(hour, 0, 0, 0);
    const pad = n => String(n).padStart(2, '0');
    const val = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const input = document.getElementById('createNextFollowupAt');
    if (input) input.value = val;
}

(() => {
    const statusSelect = document.getElementById('leadStatus');
    const nextFollowupSection = document.getElementById('createNextFollowupSection');
    const nextFollowupInput = document.getElementById('createNextFollowupAt');
    const questionBlocks = document.querySelectorAll('.stage-questions-block');

    const stageDot = document.getElementById('stageDot');
    const stageText = document.getElementById('stageText');

    const noFollowupStatuses = ['new', 'no_answer', 'not_interested', 'execution'];

    function getSelectedOption() {
        return statusSelect?.selectedOptions?.[0];
    }

    function updateFormVisibility() {
        const opt = getSelectedOption();
        if (!opt || !opt.value) {
            nextFollowupSection?.classList.add('is-hidden');
            if (stageText) stageText.textContent = 'اختر حالة العميل';
            if (stageDot) stageDot.style.background = '#64748b';
            hideAllStageQuestions();
            return;
        }

        const code = opt.dataset.code || '';
        const stageId = opt.dataset.stageId || '';
        const stageName = opt.dataset.stageName || '';
        const stageColor = opt.dataset.stageColor || '#3478f6';

        // Update Stage Badge Preview
        if (stageText) stageText.textContent = `${stageName} (${opt.textContent.trim()})`;
        if (stageDot) stageDot.style.background = stageColor;

        // Dynamic Stage Questions Sync
        syncStageQuestions(stageId);

        // Next Followup Date
        const needsFollowup = !noFollowupStatuses.includes(code);
        if (nextFollowupSection) {
            nextFollowupSection.classList.toggle('is-hidden', !needsFollowup);
        }
        if (nextFollowupInput) {
            nextFollowupInput.required = needsFollowup;
        }
    }


    function syncStageQuestions(stageId) {
        questionBlocks.forEach(block => {
            const blockStageId = block.getAttribute('data-stage-id');
            const isMatch = blockStageId && stageId && String(blockStageId) === String(stageId);
            block.style.display = isMatch ? 'block' : 'none';
            block.querySelectorAll('input, select, textarea').forEach(input => {
                if (isMatch) {
                    input.removeAttribute('disabled');
                    const req = input.closest('.stage-field-item')?.getAttribute('data-sf-required') === '1';
                    if (req) {
                        input.setAttribute('required', 'required');
                    }
                } else {
                    input.setAttribute('disabled', 'disabled');
                    input.removeAttribute('required');
                }
            });
        });
    }

    function hideAllStageQuestions() {
        questionBlocks.forEach(block => {
            block.style.display = 'none';
            block.querySelectorAll('input, select, textarea').forEach(input => {
                input.setAttribute('disabled', 'disabled');
                input.removeAttribute('required');
            });
        });
    }

    statusSelect?.addEventListener('change', updateFormVisibility);

    updateFormVisibility();
})();
</script>
<script src="{{ asset('crm-notifications.js') }}?v=1.0.0"></script>
</body>
</html>
