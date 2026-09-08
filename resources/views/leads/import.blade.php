@extends('leads.transfer-layout')

@section('title', isset($campaign) ? __('استيراد عملاء - ') . $campaign->name : __('crm.import_leads'))
@section('page-title', isset($campaign) ? __('استيراد عملاء حملة ') . $campaign->name : __('crm.import_leads'))
@section('page-description', isset($campaign) ? __('ارفع ملف الحملة وفق قواعد استيراد العملاء، راجع المعاينة الذكية، ثم اختر طريقة توزيع العملاء وأكد الاستيراد.') : __('ارفع ملف Excel أو CSV، راجع المعاينة الذكية، ثم اختر طريقة توزيع العملاء على الموظفين وأكد الاستيراد.'))
@isset($campaign)
    @section('back-url', route('v2.campaigns.show', $campaign))
    @section('back-title', __('العودة للحملة'))
@endisset

@section('top-actions')
    @isset($campaign)
        <a class="btn soft" href="{{ route('v2.campaigns.show', $campaign) }}">
            <i class="bi bi-arrow-right"></i> {{ __('العودة للحملة') }}
        </a>
    @endisset
    @can('leads.export')
        <a class="btn soft" href="{{ route('v2.leads.export') }}">
            <i class="bi bi-file-earmark-arrow-down"></i> {{ __('crm.export') }}
        </a>
    @endcan
@endsection

@section('content')
<style>
    /* Distribution Selector Styles */
    .dist-section {
        margin-top: 24px;
        margin-bottom: 24px;
        padding: 22px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    .dist-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .dist-section-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 900;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dist-section-header .dist-badge {
        font-size: 11px;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 20px;
        background: rgba(220, 38, 55, 0.1);
        color: var(--red);
    }
    .dist-strategies-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .dist-strat-card {
        border: 2px solid var(--line);
        border-radius: 12px;
        padding: 14px 16px;
        background: var(--bg);
        cursor: pointer;
        transition: all 0.18s ease;
        display: flex;
        flex-direction: column;
        gap: 6px;
        position: relative;
        text-align: right;
    }
    .dist-strat-card:hover {
        border-color: #cbd5e1;
        background: color-mix(in srgb, var(--card) 96%, #000);
        transform: translateY(-1px);
    }
    html.dark-mode .dist-strat-card:hover {
        background: color-mix(in srgb, var(--card) 92%, #fff);
        border-color: #475569;
    }
    .dist-strat-card.active {
        border-color: var(--red);
        background: color-mix(in srgb, var(--red) 4%, var(--card));
        box-shadow: 0 4px 14px rgba(220, 38, 55, 0.08);
    }
    .dist-strat-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .dist-strat-title {
        font-size: 13px;
        font-weight: 900;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .dist-strat-title i {
        font-size: 16px;
        color: var(--red);
    }
    .dist-strat-tag {
        font-size: 10px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 8px;
        background: var(--card);
        color: var(--muted);
        border: 1px solid var(--line);
    }
    .dist-strat-card.active .dist-strat-tag {
        background: rgba(220, 38, 55, 0.12);
        color: var(--red);
        border-color: transparent;
    }
    .dist-strat-desc {
        font-size: 11px;
        color: var(--muted);
        line-height: 1.5;
        margin: 0;
    }

    /* Users Selection Box */
    .dist-users-panel {
        background: var(--bg);
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 16px;
        margin-top: 14px;
    }
    .dist-users-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .dist-users-toolbar-title {
        font-size: 13px;
        font-weight: 800;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .dist-users-quick-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .dist-users-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 10px;
        max-height: 320px;
        overflow-y: auto;
        padding-left: 4px;
    }
    .dist-user-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 10px;
        transition: all 0.15s ease;
        gap: 10px;
    }
    .dist-user-item:hover {
        border-color: #cbd5e1;
    }
    html.dark-mode .dist-user-item:hover {
        border-color: #475569;
    }
    .dist-user-item.selected {
        border-color: color-mix(in srgb, var(--red) 50%, var(--line));
        background: color-mix(in srgb, var(--red) 3%, var(--card));
    }
    .dist-user-item-info {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .dist-user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: color-mix(in srgb, var(--red) 10%, var(--bg));
        color: var(--red);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 12px;
        flex-shrink: 0;
    }
    .dist-user-text {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .dist-user-name {
        font-size: 12px;
        font-weight: 800;
        color: var(--dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dist-user-meta {
        font-size: 10px;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .dist-user-meta span.lead-count {
        color: #0369a1;
        background: #e0f2fe;
        padding: 1px 6px;
        border-radius: 6px;
        font-weight: 700;
    }
    html.dark-mode .dist-user-meta span.lead-count {
        color: #38bdf8;
        background: rgba(56, 189, 248, 0.15);
    }
    .dist-user-weight-input {
        width: 70px;
        height: 32px;
        min-height: 32px;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 12px;
        text-align: center;
    }

    /* Distribution Summary Cards in Preview */
    .dist-summary-box {
        margin-top: 20px;
        margin-bottom: 20px;
        padding: 18px 20px;
        background: color-mix(in srgb, var(--red) 3%, var(--card));
        border: 1px solid var(--line);
        border-radius: 14px;
    }
    .dist-summary-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .dist-summary-header strong {
        font-size: 14px;
        font-weight: 900;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dist-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 10px;
    }
    .dist-summary-item {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .dist-summary-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
    }
    .dist-summary-item-name {
        font-size: 12px;
        font-weight: 800;
        color: var(--dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .dist-summary-item-pct {
        font-size: 11px;
        font-weight: 800;
        color: var(--red);
        background: rgba(220, 38, 55, 0.08);
        padding: 1px 6px;
        border-radius: 6px;
    }
    .dist-summary-item-count {
        font-size: 15px;
        font-weight: 900;
        color: var(--dark);
    }
    .dist-summary-item-count small {
        font-size: 11px;
        font-weight: 600;
        color: var(--muted);
    }
    .dist-bar-track {
        width: 100%;
        height: 5px;
        background: var(--line);
        border-radius: 4px;
        overflow: hidden;
    }
    .dist-bar-fill {
        height: 100%;
        background: var(--red);
        border-radius: 4px;
    }
</style>

@if (session('success'))
    <div class="notice success" style="margin-bottom:20px;">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (isset($errors) && $errors->any())
    <div class="notice error" style="margin-bottom:20px;">
        <i class="bi bi-x-octagon-fill"></i>
        <div>
            <strong>{{ __('يرجى تصحيح الأخطاء التالية:') }}</strong>
            <ul style="margin:6px 0 0 18px;padding:0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- UPLOAD & TEMPLATE CARD -->
<article class="transfer-card">
    <div class="transfer-hero">
        <small><i class="bi bi-shield-check"></i> {{ __('crm.safe_import') }}</small>
        <h2>{{ __('crm.add_leads_from_file') }}</h2>
        <p>{{ __('لن يتم إضافة أي عميل بمجرد رفع الملف. ستظهر معاينة كاملة أولًا لفحص البيانات والتحقق منها، ومن ثم يمكنك اختيار طريقة توزيع العملاء على الموظفين وتأكيد الاستيراد.') }}</p>
    </div>

    <div class="card-body">
        <form id="leadImportForm" method="POST" action="{{ route('v2.leads.import.preview') }}" enctype="multipart/form-data">
            @csrf

            @isset($campaign)
                <input type="hidden" name="campaign_id" value="{{ $campaign->id }}">
            @endisset

            <div class="grid">
                <div class="field">
                    <label for="importFile">
                        <i class="bi bi-file-earmark-arrow-up"></i> {{ __('crm.lead_file') }} <span style="color:var(--red)">*</span>
                    </label>
                    <input class="control" id="importFile" type="file" name="import_file" accept=".xlsx,.csv" required>
                    <span class="help">الملفات المدعومة: XLSX أو CSV بحد أقصى 5MB، وحتى 1000 صف في العملية الواحدة.</span>
                </div>

                <div class="field">
                    <label><i class="bi bi-file-earmark-excel"></i> {{ __('crm.ready_template') }}</label>
                    <a class="btn soft" href="{{ route('v2.leads.import.template') }}" style="width:100%;height:44px;min-height:44px">
                        <i class="bi bi-download"></i> {{ __('crm.download_template') }}
                    </a>
                    <span class="help">استخدم النموذج المعتمد لضمان مطابقة أسماء الأعمدة وصيغة أرقام الهواتف.</span>
                </div>
            </div>

            <div style="margin-top:20px;display:flex;align-items:center;gap:12px">
                <button class="btn primary" type="submit">
                    <i class="bi bi-eye"></i> {{ __('crm.preview_before_import') }}
                </button>
            </div>
        </form>
    </div>
</article>

<!-- DYNAMIC STAGES & STATUSES GUIDE -->
<article class="transfer-card">
    <div class="card-head">
        <div>
            <h3><i class="bi bi-diagram-3"></i> {{ __('crm.available_statuses') }}</h3>
            <p>{{ __('يمكنك كتابة اسم أو كود المرحلة، أو اسم أو كود الحالة في ملف الاستيراد. القائمة تتحدث ديناميكياً عند إضافة المراحل أو تعديلها.') }}</p>
        </div>
    </div>

    <div class="card-body">
        <div class="status-guide">
            @foreach ($statuses as $status)
                <div class="status-guide-item">
                    <strong>{{ $status->name_ar }}</strong>
                    <small>
                        {{ __('المرحلة:') }} {{ $status->stage?->name_ar ? __($status->stage->name_ar) : '----' }}
                        @if ($status->stage?->code) ({{ $status->stage->code }}) @endif
                        ·
                        {{ __('الكود:') }} <code>{{ $status->code }}</code>
                    </small>
                </div>
            @endforeach
        </div>
    </div>
</article>

<!-- IMPORT PREVIEW SECTION (VISIBLE AFTER FILE UPLOAD) -->
@if ($preview)
<section class="transfer-card" data-import-preview id="previewSection">
    <div class="card-head">
        <div>
            <h3><i class="bi bi-table"></i> {{ __('crm.import_preview') }}</h3>
            <p>{{ __('راجع الصفوف المحللة وتأكد من سلامة بيانات العملاء، ثم حدد طريقة توزيعهم على فريق العمل وأكد الاستيراد.') }}</p>
        </div>
    </div>

    <div class="card-body">
        <div class="stat-grid">
            <div class="stat">
                <span>{{ __('crm.total_rows') }}</span>
                <strong>{{ $preview['total_count'] }}</strong>
            </div>

            <div class="stat" style="border-color:#bbf7d0">
                <span style="color:#16a34a">{{ __('crm.valid_for_import') }}</span>
                <strong style="color:#16a34a">{{ $preview['valid_count'] }}</strong>
            </div>

            <div class="stat" style="border-color:#fecaca">
                <span style="color:#dc2637">{{ __('crm.rows_with_errors') }}</span>
                <strong style="color:#dc2637">{{ $preview['error_count'] }}</strong>
            </div>

            <div class="stat" style="border-color:#fde68a">
                <span style="color:#d97706">{{ __('crm.duplicate_numbers') }}</span>
                <strong style="color:#d97706">{{ $preview['duplicate_count'] }}</strong>
            </div>
        </div>

        @if (!empty($preview['ignored_headers']))
            <div class="notice info" style="margin-top:16px;">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>
                    {{ __('تم تجاهل الأعمدة غير المعروفة:') }}
                    {{ implode(app()->getLocale() == 'ar' ? '، ' : ', ', $preview['ignored_headers']) }}
                </div>
            </div>
        @endif

        @if ($preview['valid_count'] > 0)
            <!-- LEAD DISTRIBUTION OPTIONS & ASSIGNMENT SECTION -->
            <div class="dist-section">
                <div class="dist-section-header">
                    <h4>
                        <i class="bi bi-diagram-2" style="color:var(--red)"></i>
                        {{ __('خيارات توزيع العملاء على الموظفين') }}
                        <span style="font-size:13px;font-weight:700;color:var(--muted)">({{ $preview['valid_count'] }} {{ __('عميل جاهز للتوزيع') }})</span>
                    </h4>
                    <span class="dist-badge">
                        <i class="bi bi-people-fill"></i>
                        {{ count($assignableUsers ?? []) }} {{ __('موظف متاح للإسناد') }}
                    </span>
                </div>

                <p class="help" style="margin-bottom:16px;font-size:12px;">
                    {{ __('اختر طريقة التوزيع المناسبة. سيتم تحديث جدول المعاينة والإحصائيات أدناه تلقائياً لمراجعة الحصص قبل التأكيد.') }}
                </p>

                @php
                    $selectedStrategy = $preview['distribution']['strategy'] ?? \App\Services\LeadDistributionService::STRATEGY_EQUAL;
                    $selectedUserIds = $preview['distribution_config']['user_ids'] ?? ($assignableUsers ? $assignableUsers->pluck('id')->all() : []);
                    if (!is_array($selectedUserIds)) {
                        $selectedUserIds = [];
                    }
                    $selectedSingleId = $preview['distribution_config']['single_user_id'] ?? null;
                    $selectedFallbackId = $preview['distribution_config']['fallback_user_id'] ?? null;
                    $weightsMap = $preview['distribution_config']['weights'] ?? [];
                @endphp

                <!-- STRATEGIES CARDS GRID -->
                <div class="dist-strategies-grid" id="strategiesGrid">
                    @foreach ($distributionStrategies ?? \App\Services\LeadDistributionService::strategies() as $key => $meta)
                        <div class="dist-strat-card {{ $selectedStrategy === $key ? 'active' : '' }}"
                             data-strategy="{{ $key }}"
                             onclick="selectStrategy('{{ $key }}')">
                            <div class="dist-strat-card-head">
                                <span class="dist-strat-title">
                                    <i class="bi {{ $meta['icon'] }}"></i>
                                    {{ $meta['name'] }}
                                </span>
                                <span class="dist-strat-tag">{{ $meta['badge'] }}</span>
                            </div>
                            <p class="dist-strat-desc">{{ $meta['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- SUBSECTION: SINGLE USER SELECTOR -->
                <div id="distSingleUserBox" style="display: {{ $selectedStrategy === \App\Services\LeadDistributionService::STRATEGY_SINGLE ? 'block' : 'none' }}; margin-bottom: 16px;">
                    <div class="field" style="max-width: 460px;">
                        <label for="distSingleUserSelect">
                            <i class="bi bi-person-check-fill"></i> {{ __('الموظف المسؤول المختار لاستلام جميع العملاء:') }} <span style="color:var(--red)">*</span>
                        </label>
                        <select class="control" id="distSingleUserSelect" onchange="recalculateDistribution()">
                            @foreach ($assignableUsers ?? [] as $u)
                                <option value="{{ $u->id }}" {{ (int)$selectedSingleId === (int)$u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->username }}) · {{ $activeLeadCounts[$u->id] ?? 0 }} {{ __('عميل حالي') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- SUBSECTION: FALLBACK USER FOR FROM_FILE -->
                <div id="distFallbackUserBox" style="display: {{ $selectedStrategy === \App\Services\LeadDistributionService::STRATEGY_FROM_FILE ? 'block' : 'none' }}; margin-bottom: 16px;">
                    <div class="field" style="max-width: 460px;">
                        <label for="distFallbackUserSelect">
                            <i class="bi bi-person-exclamation"></i> {{ __('الموظف البديل (في حال كانت خانة الموظف بالملف فارغة أو غير معروفة):') }}
                        </label>
                        <select class="control" id="distFallbackUserSelect" onchange="recalculateDistribution()">
                            <option value="">{{ __('مدير النظام / المستخدم الحالي الافتراضي') }}</option>
                            @foreach ($assignableUsers ?? [] as $u)
                                <option value="{{ $u->id }}" {{ (int)$selectedFallbackId === (int)$u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- SUBSECTION: PARTICIPATING USERS SELECTION -->
                <div id="distUsersBox" class="dist-users-panel" style="display: {{ in_array($selectedStrategy, [\App\Services\LeadDistributionService::STRATEGY_EQUAL, \App\Services\LeadDistributionService::STRATEGY_LEAST_LOADED, \App\Services\LeadDistributionService::STRATEGY_WEIGHTED, \App\Services\LeadDistributionService::STRATEGY_RANDOM]) ? 'block' : 'none' }};">
                    <div class="dist-users-toolbar">
                        <span class="dist-users-toolbar-title">
                            <i class="bi bi-people"></i>
                            {{ __('الموظفون المشمولون في التوزيع:') }}
                            <span id="selectedUsersCountBadge" style="font-weight:700;color:var(--red);">({{ count($selectedUserIds) }})</span>
                        </span>

                        <div class="dist-users-quick-actions">
                            <button type="button" class="btn soft small" onclick="setAllUsers(true)">
                                <i class="bi bi-check-all"></i> {{ __('تحديد الكل') }}
                            </button>
                            <button type="button" class="btn soft small" onclick="setAllUsers(false)">
                                <i class="bi bi-dash"></i> {{ __('إلغاء التحديد') }}
                            </button>
                            <button type="button" class="btn soft small" onclick="selectSalesOnly()">
                                <i class="bi bi-funnel"></i> {{ __('موظفو المبيعات فقط') }}
                            </button>
                        </div>
                    </div>

                    <div class="dist-users-grid">
                        @foreach ($assignableUsers ?? [] as $u)
                            @php
                                $isChecked = in_array((int)$u->id, array_map('intval', $selectedUserIds), true);
                                $initials = mb_substr($u->name, 0, 2);
                                $leadCount = $activeLeadCounts[$u->id] ?? 0;
                                $weightVal = $weightsMap[$u->id] ?? 1;
                                $isSales = $u->groups->contains(fn($g) => str_contains($g->name, 'مبيعات') || str_contains(strtolower($g->name), 'sales'));
                            @endphp
                            <div class="dist-user-item {{ $isChecked ? 'selected' : '' }}" id="userItemRow_{{ $u->id }}" data-is-sales="{{ $isSales ? '1' : '0' }}">
                                <div class="dist-user-item-info">
                                    <input type="checkbox"
                                           id="userCheckbox_{{ $u->id }}"
                                           value="{{ $u->id }}"
                                           class="dist-user-checkbox"
                                           {{ $isChecked ? 'checked' : '' }}
                                           onchange="toggleUserRow({{ $u->id }})">

                                    <div class="dist-user-avatar">{{ $initials }}</div>

                                    <div class="dist-user-text">
                                        <span class="dist-user-name" title="{{ $u->name }}">{{ $u->name }}</span>
                                        <div class="dist-user-meta">
                                            <span>{{ $u->groups->pluck('name')->first() ?? $u->username }}</span>
                                            <span class="lead-count">{{ $leadCount }} {{ __('عميل') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Weight input (shown when weighted strategy is selected) -->
                                <div class="dist-weight-box" style="display: {{ $selectedStrategy === \App\Services\LeadDistributionService::STRATEGY_WEIGHTED ? 'flex' : 'none' }}; align-items: center; gap: 4px;">
                                    <input type="number"
                                           min="1"
                                           max="100"
                                           class="control dist-user-weight-input"
                                           id="weightInput_{{ $u->id }}"
                                           value="{{ $weightVal }}"
                                           onchange="recalculateDistribution()"
                                           oninput="recalculateDistribution()"
                                           placeholder="{{ __('الوزن') }}"
                                           title="{{ __('وزن / نسبة الموظف في التوزيع') }}">
                                    <small style="color:var(--muted);font-size:10px;">{{ __('وزن') }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- LIVE DISTRIBUTION BREAKDOWN SUMMARY -->
                <div class="dist-summary-box" id="distSummaryBox">
                    <div class="dist-summary-header">
                        <strong>
                            <i class="bi bi-pie-chart-fill" id="summaryStrategyIcon" style="color:var(--red);font-size:17px;"></i>
                            {{ __('ملخص توزيع العملاء:') }}
                            <span id="summaryStrategyName" style="color:var(--red);">{{ $preview['distribution']['strategy_name'] ?? 'توزيع عادل بالتساوي' }}</span>
                        </strong>
                        <div style="display:flex;align-items:center;gap:12px;font-size:12px;color:var(--muted)">
                            <span>
                                {{ __('إجمالي العملاء:') }}
                                <strong style="color:var(--dark)" id="summaryTotalCount">{{ $preview['valid_count'] }}</strong>
                            </span>
                            <span>·</span>
                            <span>
                                {{ __('عدد المستلمين:') }}
                                <strong style="color:var(--dark)" id="summaryRecipientsCount">{{ count($preview['distribution']['user_stats'] ?? []) }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="dist-summary-grid" id="distSummaryGrid">
                        @foreach ($preview['distribution']['user_stats'] ?? [] as $stat)
                            <div class="dist-summary-item">
                                <div class="dist-summary-item-top">
                                    <span class="dist-summary-item-name" title="{{ $stat['name'] }}">
                                        <i class="bi bi-person-fill" style="color:var(--muted)"></i>
                                        {{ $stat['name'] }}
                                    </span>
                                    <span class="dist-summary-item-pct">{{ $stat['percentage'] }}%</span>
                                </div>
                                <div class="dist-summary-item-count">
                                    {{ $stat['count'] }}
                                    <small>{{ __('عميل') }}</small>
                                </div>
                                <div class="dist-bar-track">
                                    <div class="dist-bar-fill" style="width: {{ min(100, max(4, $stat['percentage'])) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- PREVIEW DATA TABLE -->
            <div class="table-wrap">
                <table id="previewTable">
                    <thead>
                        <tr>
                            <th style="width:50px">{{ __('crm.row') }}</th>
                            <th>{{ __('crm.client') }}</th>
                            <th>{{ __('crm.phone') }}</th>
                            <th style="min-width:140px">{{ __('الموظف المسند إليه') }}</th>
                            <th>{{ __('crm.status') }}</th>
                            <th>{{ __('المرحلة') }}</th>
                            <th>{{ __('crm.result') }}</th>
                            <th>{{ __('crm.details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $index => $row)
                            <tr data-row-valid="{{ $row['state'] === 'valid' ? '1' : '0' }}" data-row-num="{{ $row['row_number'] }}">
                                <td><strong>{{ $row['row_number'] }}</strong></td>
                                <td>{{ $row['name'] }}</td>
                                <td dir="ltr">{{ $row['phone'] }}</td>
                                <td class="assigned-emp-cell">
                                    @if (!empty($row['assigned_employee']) && $row['assigned_employee'] !== '----')
                                        <span style="display:inline-flex;align-items:center;gap:5px;font-weight:700;color:var(--dark)">
                                            <i class="bi bi-person-badge" style="color:var(--red)"></i>
                                            <span class="emp-name-text">{{ $row['assigned_employee'] }}</span>
                                        </span>
                                    @else
                                        <span style="color:var(--muted)">----</span>
                                    @endif
                                </td>
                                <td>{{ $row['status'] }}</td>
                                <td>{{ $row['stage'] }}</td>
                                <td>
                                    @if ($row['state'] === 'valid')
                                        <span class="badge valid"><i class="bi bi-check-circle"></i> {{ __('crm.valid') ?: 'صالح' }}</span>
                                    @elseif ($row['state'] === 'duplicate')
                                        <span class="badge duplicate"><i class="bi bi-exclamation-triangle"></i> {{ __('crm.duplicate') ?: 'مكرر' }}</span>
                                    @else
                                        <span class="badge error"><i class="bi bi-x-circle"></i> {{ __('crm.error') ?: 'خطأ' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($row['errors']))
                                        <small style="color:var(--red);font-weight:700">{{ implode(' · ', $row['errors']) }}</small>
                                    @elseif (!empty($row['duplicate_reason']))
                                        <small style="color:#d97706;font-weight:700">{{ $row['duplicate_reason'] }}</small>
                                    @elseif (!empty($row['warnings']))
                                        <small style="color:#d97706">{{ implode(' · ', $row['warnings']) }}</small>
                                    @else
                                        <small style="color:#16a34a;font-weight:700">{{ __('جاهز للاستيراد') }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- CONFIRMATION FORM -->
            <form method="POST" action="{{ route('v2.leads.import.confirm') }}" id="confirmImportForm" style="margin-top:24px;">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                <input type="hidden" name="distribution_strategy" id="formDistributionStrategy" value="{{ $selectedStrategy }}">
                <input type="hidden" name="distribution_single_user_id" id="formSingleUserId" value="{{ $selectedSingleId }}">
                <input type="hidden" name="distribution_fallback_user_id" id="formFallbackUserId" value="{{ $selectedFallbackId }}">
                <div id="formUsersContainer"></div>
                <div id="formWeightsContainer"></div>

                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 20px;background:var(--bg);border:1px solid var(--line);border-radius:12px;">
                    <button class="btn primary" type="submit" id="btnConfirmSubmit" style="height:46px;padding:0 28px;font-size:14px;">
                        <i class="bi bi-check2-all"></i>
                        {{ __('تأكيد استيراد') }} {{ $preview['valid_count'] }} {{ __('عميل وتوزيعهم على الموظفين') }}
                    </button>
                    <a class="btn soft" href="{{ route('v2.leads.import') }}">
                        {{ __('إلغاء') }}
                    </a>
                </div>
            </form>
        @else
            <div class="notice error" style="margin-top:20px;margin-bottom:0">
                <i class="bi bi-x-octagon-fill"></i>
                <div>{{ __('crm.no_valid_import_rows') }}</div>
            </div>
        @endif
    </div>
</section>
@endif

@php
    $assignableUsersJson = [];
    if (!empty($assignableUsers)) {
        foreach ($assignableUsers as $u) {
            $isSales = false;
            if ($u->groups) {
                foreach ($u->groups as $g) {
                    if (str_contains($g->name, 'مبيعات') || str_contains(strtolower($g->name), 'sales')) {
                        $isSales = true;
                        break;
                    }
                }
            }
            $assignableUsersJson[] = [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'username' => (string) $u->username,
                'lead_count' => (int) ($activeLeadCounts[$u->id] ?? 0),
                'is_sales' => $isSales,
            ];
        }
    }
@endphp
<script>
    // All assignable users data for client-side live distribution
    const assignableUsersData = {!! json_encode($assignableUsersJson, JSON_UNESCAPED_UNICODE) !!};

    const strategyMetaMap = {
        'equal': { name: 'توزيع عادل بالتساوي (Round-Robin)', icon: 'bi-arrow-repeat' },
        'least_loaded': { name: 'موازنة عبء العمل (الأقل عملاء حالياً)', icon: 'bi-bar-chart-steps' },
        'weighted': { name: 'توزيع بنسب وأوزان مئوية', icon: 'bi-pie-chart' },
        'single': { name: 'إسناد جماعي لموظف واحد', icon: 'bi-person-check' },
        'random': { name: 'توزيع عشوائي متوازن', icon: 'bi-shuffle' },
        'from_file': { name: 'الاعتماد على الموظف من الملف', icon: 'bi-file-earmark-person' }
    };

    let currentStrategy = '{{ $selectedStrategy ?? "equal" }}';

    function selectStrategy(strategyKey) {
        currentStrategy = strategyKey;

        // Update active class on strategy cards
        document.querySelectorAll('#strategiesGrid .dist-strat-card').forEach(card => {
            if (card.getAttribute('data-strategy') === strategyKey) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

        const singleBox = document.getElementById('distSingleUserBox');
        const fallbackBox = document.getElementById('distFallbackUserBox');
        const usersBox = document.getElementById('distUsersBox');
        const weightBoxes = document.querySelectorAll('.dist-weight-box');

        if (strategyKey === 'single') {
            if (singleBox) singleBox.style.display = 'block';
            if (fallbackBox) fallbackBox.style.display = 'none';
            if (usersBox) usersBox.style.display = 'none';
        } else if (strategyKey === 'from_file') {
            if (singleBox) singleBox.style.display = 'none';
            if (fallbackBox) fallbackBox.style.display = 'block';
            if (usersBox) usersBox.style.display = 'none';
        } else {
            if (singleBox) singleBox.style.display = 'none';
            if (fallbackBox) fallbackBox.style.display = 'none';
            if (usersBox) usersBox.style.display = 'block';

            const showWeights = (strategyKey === 'weighted');
            weightBoxes.forEach(box => {
                box.style.display = showWeights ? 'flex' : 'none';
            });
        }

        recalculateDistribution();
    }

    function toggleUserRow(userId) {
        const row = document.getElementById('userItemRow_' + userId);
        const checkbox = document.getElementById('userCheckbox_' + userId);
        if (checkbox && row) {
            if (checkbox.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        }
        updateSelectedUsersCount();
        recalculateDistribution();
    }

    function setAllUsers(checked) {
        document.querySelectorAll('.dist-user-checkbox').forEach(cb => {
            cb.checked = checked;
            const row = document.getElementById('userItemRow_' + cb.value);
            if (row) {
                if (checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }
        });
        updateSelectedUsersCount();
        recalculateDistribution();
    }

    function selectSalesOnly() {
        document.querySelectorAll('.dist-user-checkbox').forEach(cb => {
            const row = document.getElementById('userItemRow_' + cb.value);
            const isSales = row && row.getAttribute('data-is-sales') === '1';
            cb.checked = isSales;
            if (row) {
                if (isSales) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }
        });
        updateSelectedUsersCount();
        recalculateDistribution();
    }

    function updateSelectedUsersCount() {
        const totalChecked = document.querySelectorAll('.dist-user-checkbox:checked').length;
        const badge = document.getElementById('selectedUsersCountBadge');
        if (badge) {
            badge.innerText = '(' + totalChecked + ')';
        }
    }

    // Live distribution recalculation across table rows and summary pills
    function recalculateDistribution() {
        const validRows = document.querySelectorAll('#previewTable tr[data-row-valid="1"]');
        if (!validRows || validRows.length === 0) return;
        const totalValid = validRows.length;

        // Get selected user IDs
        let selectedUserIds = [];
        document.querySelectorAll('.dist-user-checkbox:checked').forEach(cb => {
            selectedUserIds.push(parseInt(cb.value, 10));
        });

        // Filter user objects
        let selectedUsers = assignableUsersData.filter(u => selectedUserIds.includes(u.id));
        if (selectedUsers.length === 0 && assignableUsersData.length > 0) {
            selectedUsers = [assignableUsersData[0]];
        }

        const singleSelect = document.getElementById('distSingleUserSelect');
        const fallbackSelect = document.getElementById('distFallbackUserSelect');
        const singleUserId = singleSelect ? parseInt(singleSelect.value, 10) : (selectedUsers[0] ? selectedUsers[0].id : null);
        const singleUser = assignableUsersData.find(u => u.id === singleUserId) || selectedUsers[0];

        const fallbackUserId = fallbackSelect && fallbackSelect.value ? parseInt(fallbackSelect.value, 10) : null;
        const fallbackUser = assignableUsersData.find(u => u.id === fallbackUserId) || assignableUsersData[0];

        // Compute assignments for each valid row
        let assignments = []; // array of User objects length = totalValid

        if (currentStrategy === 'single') {
            for (let i = 0; i < totalValid; i++) {
                assignments.push(singleUser);
            }
        } else if (currentStrategy === 'least_loaded') {
            // Simulated counts starting from existing database lead count
            let simCounts = {};
            selectedUsers.forEach(u => {
                simCounts[u.id] = u.lead_count || 0;
            });

            for (let i = 0; i < totalValid; i++) {
                let best = selectedUsers[0];
                let minC = simCounts[best.id] || 0;
                for (let j = 1; j < selectedUsers.length; j++) {
                    const candidate = selectedUsers[j];
                    const c = simCounts[candidate.id] || 0;
                    if (c < minC) {
                        minC = c;
                        best = candidate;
                    }
                }
                assignments.push(best);
                simCounts[best.id] = (simCounts[best.id] || 0) + 1;
            }
        } else if (currentStrategy === 'weighted') {
            // Quota calculation using Largest Remainder
            let weights = {};
            let totalWeight = 0;
            selectedUsers.forEach(u => {
                const input = document.getElementById('weightInput_' + u.id);
                let w = input ? parseFloat(input.value) : 1;
                if (isNaN(w) || w <= 0) w = 1;
                weights[u.id] = w;
                totalWeight += w;
            });

            let quotas = {};
            let remainders = [];
            let allocated = 0;

            selectedUsers.forEach(u => {
                const exact = (weights[u.id] / totalWeight) * totalValid;
                const base = Math.floor(exact);
                quotas[u.id] = base;
                allocated += base;
                remainders.push({ id: u.id, remainder: exact - base });
            });

            let remSlots = totalValid - allocated;
            remainders.sort((a, b) => b.remainder - a.remainder);
            for (let k = 0; k < remSlots && k < remainders.length; k++) {
                quotas[remainders[k].id]++;
            }

            // Build slots and interleave
            let slots = [];
            selectedUsers.forEach(u => {
                const count = quotas[u.id] || 0;
                for (let k = 0; k < count; k++) {
                    slots.push(u);
                }
            });

            for (let i = 0; i < totalValid; i++) {
                assignments.push(slots[i] || selectedUsers[i % selectedUsers.length]);
            }
        } else if (currentStrategy === 'random') {
            for (let i = 0; i < totalValid; i++) {
                const randIdx = Math.floor(Math.random() * selectedUsers.length);
                assignments.push(selectedUsers[randIdx]);
            }
        } else if (currentStrategy === 'from_file') {
            for (let i = 0; i < totalValid; i++) {
                assignments.push(fallbackUser || selectedUsers[0]);
            }
        } else {
            // Equal / Round-Robin
            for (let i = 0; i < totalValid; i++) {
                assignments.push(selectedUsers[i % selectedUsers.length]);
            }
        }

        // Apply to table rows and calculate user tally
        let userTally = {};
        validRows.forEach((rowEl, i) => {
            const assigned = assignments[i] || fallbackUser;
            const empCell = rowEl.querySelector('.assigned-emp-cell');
            if (empCell && assigned) {
                empCell.innerHTML = `<span style="display:inline-flex;align-items:center;gap:5px;font-weight:700;color:var(--dark)"><i class="bi bi-person-badge" style="color:var(--red)"></i> <span class="emp-name-text">${assigned.name}</span></span>`;
            }

            if (assigned) {
                if (!userTally[assigned.id]) {
                    userTally[assigned.id] = {
                        id: assigned.id,
                        name: assigned.name,
                        count: 0,
                        percentage: 0
                    };
                }
                userTally[assigned.id].count++;
            }
        });

        // Calculate percentages
        const tallyList = Object.values(userTally);
        tallyList.forEach(item => {
            item.percentage = totalValid > 0 ? ((item.count / totalValid) * 100).toFixed(1) : 0;
        });
        tallyList.sort((a, b) => b.count - a.count);

        // Update distribution summary card
        const summaryName = document.getElementById('summaryStrategyName');
        const summaryIcon = document.getElementById('summaryStrategyIcon');
        const summaryTotal = document.getElementById('summaryTotalCount');
        const summaryRecipients = document.getElementById('summaryRecipientsCount');
        const summaryGrid = document.getElementById('distSummaryGrid');

        const meta = strategyMetaMap[currentStrategy] || strategyMetaMap['equal'];
        if (summaryName) summaryName.innerText = meta.name;
        if (summaryIcon) summaryIcon.className = `bi ${meta.icon}`;
        if (summaryTotal) summaryTotal.innerText = totalValid;
        if (summaryRecipients) summaryRecipients.innerText = tallyList.length;

        if (summaryGrid) {
            summaryGrid.innerHTML = '';
            tallyList.forEach(stat => {
                const fillWidth = Math.min(100, Math.max(4, stat.percentage));
                const itemDiv = document.createElement('div');
                itemDiv.className = 'dist-summary-item';
                itemDiv.innerHTML = `
                    <div class="dist-summary-item-top">
                        <span class="dist-summary-item-name" title="${stat.name}">
                            <i class="bi bi-person-fill" style="color:var(--muted)"></i>
                            ${stat.name}
                        </span>
                        <span class="dist-summary-item-pct">${stat.percentage}%</span>
                    </div>
                    <div class="dist-summary-item-count">
                        ${stat.count}
                        <small>عميل</small>
                    </div>
                    <div class="dist-bar-track">
                        <div class="dist-bar-fill" style="width: ${fillWidth}%"></div>
                    </div>
                `;
                summaryGrid.appendChild(itemDiv);
            });
        }

        // Sync hidden inputs in the confirmation form
        const formStrat = document.getElementById('formDistributionStrategy');
        const formSingle = document.getElementById('formSingleUserId');
        const formFallback = document.getElementById('formFallbackUserId');
        const usersContainer = document.getElementById('formUsersContainer');
        const weightsContainer = document.getElementById('formWeightsContainer');

        if (formStrat) formStrat.value = currentStrategy;
        if (formSingle) formSingle.value = singleUserId || '';
        if (formFallback) formFallback.value = fallbackUserId || '';

        if (usersContainer) {
            usersContainer.innerHTML = '';
            selectedUserIds.forEach(uid => {
                const hiddenInp = document.createElement('input');
                hiddenInp.type = 'hidden';
                hiddenInp.name = 'distribution_users[]';
                hiddenInp.value = uid;
                usersContainer.appendChild(hiddenInp);
            });
        }

        if (weightsContainer) {
            weightsContainer.innerHTML = '';
            selectedUserIds.forEach(uid => {
                const weightInp = document.getElementById('weightInput_' + uid);
                const w = weightInp ? weightInp.value : 1;
                const hiddenInp = document.createElement('input');
                hiddenInp.type = 'hidden';
                hiddenInp.name = `distribution_weights[${uid}]`;
                hiddenInp.value = w;
                weightsContainer.appendChild(hiddenInp);
            });
        }
    }

    // Initialize on page load if preview exists
    document.addEventListener('DOMContentLoaded', () => {
        const previewEl = document.getElementById('previewSection');
        if (previewEl) {
            previewEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            recalculateDistribution();
        }
    });
</script>
@endsection
