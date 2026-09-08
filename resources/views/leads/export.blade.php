@extends('leads.transfer-layout')

@section('title', __('crm.export_leads'))
@section('page-title', __('crm.export_leads'))
@section('page-description', __('حدد نطاق العملاء والأعمدة المطلوبة ثم نزّل ملف Excel بشكل مباشر.'))

@section('top-actions')
    @can('leads.import')
        <a class="btn soft" href="{{ route('v2.leads.import') }}">
            <i class="bi bi-file-earmark-arrow-up"></i> {{ __('crm.import') }}
        </a>
    @endcan
@endsection

@section('content')
<style>
    .column-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--card);
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        color: var(--dark);
        transition: all 0.15s ease;
        user-select: none;
    }
    .column-card:hover {
        border-color: #cbd5e1;
        background: color-mix(in srgb, var(--card) 97%, #000);
    }
    html.dark-mode .column-card:hover {
        border-color: #475569;
        background: color-mix(in srgb, var(--card) 93%, #fff);
    }
    .column-card.checked {
        border-color: color-mix(in srgb, var(--red) 45%, var(--line));
        background: color-mix(in srgb, var(--red) 3%, var(--card));
    }
    .column-card input[type="checkbox"] {
        width: 17px;
        height: 17px;
        accent-color: var(--red);
        cursor: pointer;
        margin: 0;
    }
    .columns-badge {
        font-size: 11px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 12px;
        background: rgba(220, 38, 55, 0.08);
        color: var(--red);
    }
</style>

<article class="transfer-card">
    <div class="transfer-hero">
        <small><i class="bi bi-file-earmark-excel"></i> {{ __('crm.export_excel_title') }}</small>
        <h2>{{ __('crm.prepare_leads_file') }}</h2>
        <p>{{ __('اختر الفلاتر المطلوبة والأعمدة التي تريد ظهورها في الملف. التصدير المباشر يدعم حتى 5000 عميل في العملية الواحدة.') }}</p>
    </div>

    <div class="card-body">
        <div class="notice info">
            <i class="bi bi-info-circle-fill"></i>
            <div>
                {{ __('إجمالي العملاء المتاحين لديك حاليًا:') }}
                <strong>{{ number_format($totalLeads) }}</strong> {{ __('عميل') }}.
            </div>
        </div>

        @if (isset($errors) && $errors->has('export'))
            <div class="notice error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>{{ $errors->first('export') }}</div>
            </div>
        @endif

        @if (isset($errors) && $errors->has('columns'))
            <div class="notice error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>{{ $errors->first('columns') }}</div>
            </div>
        @endif

        <form id="leadExportForm" method="POST" action="{{ route('v2.leads.export.download') }}">
            @csrf

            <div class="grid three">
                <div class="field">
                    <label for="stageId"><i class="bi bi-diagram-3"></i> {{ __('المرحلة') }}</label>
                    <select class="control" id="stageId" name="stage_id" onchange="filterStatusesByStage(this.value)">
                        <option value="">{{ __('كل المراحل') }}</option>
                        @foreach ($stages as $stage)
                            <option value="{{ $stage->id }}" @selected((string) old('stage_id') === (string) $stage->id)>
                                {{ $stage->localizedName() ?? ($stage->name_ar ? __($stage->name_ar) : $stage->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="statusId"><i class="bi bi-tag"></i> {{ __('crm.status') }}</label>
                    <select class="control" id="statusId" name="status_id">
                        <option value="">{{ __('crm.all_states') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}"
                                    data-stage-id="{{ $status->pipeline_stage_id }}"
                                    @selected((string) old('status_id') === (string) $status->id)>
                                {{ $status->stage?->name_ar ? __($status->stage->name_ar) : '----' }} — {{ __($status->name_ar) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="employee"><i class="bi bi-person-check"></i> {{ __('crm.assigned_employee') }}</label>
                    <select class="control" id="employee" name="employee">
                        <option value="">{{ __('كل الموظفين') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee }}" @selected(old('employee') === $employee)>
                                {{ $employee }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="source"><i class="bi bi-diagram-2"></i> {{ __('crm.source') }}</label>
                    <select class="control" id="source" name="source">
                        <option value="">{{ __('crm.all_sources') }}</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source }}" @selected(old('source') === $source)>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="followUp"><i class="bi bi-calendar-event"></i> {{ __('crm.next_followup') }}</label>
                    <select class="control" id="followUp" name="follow_up">
                        <option value="">{{ __('crm.all_appointments') }}</option>
                        <option value="today" @selected(old('follow_up') === 'today')>{{ __('اليوم') }}</option>
                        <option value="upcoming" @selected(old('follow_up') === 'upcoming')>{{ __('crm.upcoming') }}</option>
                        <option value="overdue" @selected(old('follow_up') === 'overdue')>{{ __('crm.overdue') }}</option>
                        <option value="none" @selected(old('follow_up') === 'none')>{{ __('crm.no_date') }}</option>
                    </select>
                </div>

                <div class="field">
                    <label for="sort"><i class="bi bi-sort-down"></i> {{ __('crm.sort') }}</label>
                    <select class="control" id="sort" name="sort">
                        <option value="latest" @selected(old('sort', 'latest') === 'latest')>{{ __('crm.newest_first') }}</option>
                        <option value="oldest" @selected(old('sort') === 'oldest')>{{ __('crm.oldest_first') }}</option>
                        <option value="name" @selected(old('sort') === 'name')>{{ __('crm.by_name') }}</option>
                        <option value="followup" @selected(old('sort') === 'followup')>{{ __('crm.by_followup') }}</option>
                    </select>
                </div>

                <div class="field">
                    <label for="fromDate"><i class="bi bi-calendar-date"></i> {{ __('crm.from_date') }}</label>
                    <input class="control" id="fromDate" type="date" name="from_date" value="{{ old('from_date', old('date_from')) }}">
                </div>

                <div class="field">
                    <label for="toDate"><i class="bi bi-calendar-date"></i> {{ __('crm.to_date') }}</label>
                    <input class="control" id="toDate" type="date" name="to_date" value="{{ old('to_date', old('date_to')) }}">
                </div>

                <div class="field">
                    <label for="searchQuery"><i class="bi bi-search"></i> {{ __('البحث') }}</label>
                    <input class="control" id="searchQuery" type="text" name="q" value="{{ old('q') }}" placeholder="{{ __('crm.lead_search_placeholder') }}">
                </div>
            </div>

            <!-- COLUMNS SELECTION -->
            <div style="margin-top:24px;padding-top:20px;border-top:1px dashed var(--line)">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap">
                    <div>
                        <strong style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--dark)">
                            <i class="bi bi-layout-three-columns" style="color:var(--red)"></i>
                            {{ __('الأعمدة المطلوب تصديرها في ملف Excel') }}
                            <span class="columns-badge" id="selectedColsBadge">11 {{ __('محدد') }}</span>
                        </strong>
                        <small style="color:var(--muted)">{{ __('حدد الأعمدة التي تود ظهورها في الملف المُصدَّر.') }}</small>
                    </div>

                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button type="button" class="btn soft small" id="selectAllColumnsBtn">
                            <i class="bi bi-check-all"></i> {{ __('تحديد الكل') }}
                        </button>
                        <button type="button" class="btn soft small" id="selectDefaultColumnsBtn">
                            <i class="bi bi-star"></i> {{ __('الأعمدة الأساسية') }}
                        </button>
                        <button type="button" class="btn soft small" id="deselectAllColumnsBtn">
                            <i class="bi bi-dash"></i> {{ __('إلغاء الكل') }}
                        </button>
                    </div>
                </div>

                @php
                    $essentialKeys = [
                        'name',
                        'phone',
                        'company_name',
                        'activity',
                        'email',
                        'status',
                        'stage',
                        'source',
                        'assigned_employee',
                        'created_at',
                        'next_follow_up_at',
                    ];
                    $selectedCols = old('columns', $essentialKeys);
                    if (!is_array($selectedCols)) {
                        $selectedCols = $essentialKeys;
                    }
                @endphp

                <div class="columns-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:10px;">
                    @foreach ($columns as $colKey => $colLabel)
                        @php
                            $isColChecked = in_array($colKey, $selectedCols, true);
                            $isEssential = in_array($colKey, $essentialKeys, true);
                        @endphp
                        <label class="column-card {{ $isColChecked ? 'checked' : '' }}" id="colLabel_{{ $colKey }}">
                            <input type="checkbox"
                                   name="columns[]"
                                   value="{{ $colKey }}"
                                   data-essential="{{ $isEssential ? '1' : '0' }}"
                                   class="col-checkbox"
                                   {{ $isColChecked ? 'checked' : '' }}
                                   onchange="toggleColCard(this)">
                            <span>{{ $colLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <button class="btn primary" type="submit" style="height:44px;padding:0 26px">
                    <i class="bi bi-file-earmark-excel"></i> {{ __('crm.download_excel') }}
                </button>
                <a class="btn soft" href="{{ route('v2.leads') }}">
                    {{ __('crm.cancel') }}
                </a>
            </div>
        </form>
    </div>
</article>

<script>
(() => {
    const selectAllBtn = document.getElementById('selectAllColumnsBtn');
    const selectDefaultBtn = document.getElementById('selectDefaultColumnsBtn');
    const deselectAllBtn = document.getElementById('deselectAllColumnsBtn');
    const checkboxes = document.querySelectorAll('.col-checkbox');

    function updateBadge() {
        const checkedCount = document.querySelectorAll('.col-checkbox:checked').length;
        const badge = document.getElementById('selectedColsBadge');
        if (badge) {
            badge.innerText = checkedCount + ' محدد';
        }
    }

    selectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            cb.checked = true;
            cb.closest('.column-card')?.classList.add('checked');
        });
        updateBadge();
    });

    selectDefaultBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            const isEss = cb.getAttribute('data-essential') === '1';
            cb.checked = isEss;
            if (isEss) {
                cb.closest('.column-card')?.classList.add('checked');
            } else {
                cb.closest('.column-card')?.classList.remove('checked');
            }
        });
        updateBadge();
    });

    deselectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => {
            cb.checked = false;
            cb.closest('.column-card')?.classList.remove('checked');
        });
        updateBadge();
    });

    updateBadge();
})();

function toggleColCard(cb) {
    if (cb.checked) {
        cb.closest('.column-card')?.classList.add('checked');
    } else {
        cb.closest('.column-card')?.classList.remove('checked');
    }
    const checkedCount = document.querySelectorAll('.col-checkbox:checked').length;
    const badge = document.getElementById('selectedColsBadge');
    if (badge) {
        badge.innerText = checkedCount + ' محدد';
    }
}

function filterStatusesByStage(selectedStageId) {
    const statusSelect = document.getElementById('statusId');
    if (!statusSelect) return;

    const options = statusSelect.querySelectorAll('option');
    let hasCurrentSelection = false;

    options.forEach(opt => {
        if (!opt.value) {
            opt.hidden = false;
            return;
        }

        const stageId = opt.getAttribute('data-stage-id');
        if (!selectedStageId || stageId === selectedStageId) {
            opt.hidden = false;
            if (opt.selected) hasCurrentSelection = true;
        } else {
            opt.hidden = true;
            if (opt.selected) opt.selected = false;
        }
    });

    if (!hasCurrentSelection && selectedStageId) {
        // Keep placeholder selected if current selection was hidden
        statusSelect.value = '';
    }
}
</script>
@endsection
