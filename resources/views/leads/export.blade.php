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
    <a class="btn soft" href="{{ route('v2.leads') }}">
        <i class="bi bi-people"></i> {{ __('crm.view_leads') }}
    </a>
@endsection

@section('content')
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

        @if ($errors->has('export'))
            <div class="notice error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>{{ $errors->first('export') }}</div>
            </div>
        @endif

        <form id="leadExportForm" method="POST" action="{{ route('v2.leads.export.download') }}">
            @csrf

            <div class="grid three">
                <div class="field">
                    <label for="stageId"><i class="bi bi-diagram-3"></i> {{ __('المرحلة') }}</label>
                    <select class="control" id="stageId" name="stage_id">
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
                            <option value="{{ $status->id }}" @selected((string) old('status_id') === (string) $status->id)>
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
                        <option value="latest" @selected(old('sort') === 'latest')>{{ __('crm.newest_first') }}</option>
                        <option value="oldest" @selected(old('sort') === 'oldest')>{{ __('crm.oldest_first') }}</option>
                        <option value="name" @selected(old('sort') === 'name')>{{ __('crm.by_name') }}</option>
                        <option value="followup" @selected(old('sort') === 'followup')>{{ __('crm.by_followup') }}</option>
                    </select>
                </div>

                <div class="field">
                    <label for="fromDate"><i class="bi bi-calendar-date"></i> {{ __('crm.from_date') }}</label>
                    <input class="control" id="fromDate" type="date" name="from_date" value="{{ old('from_date') }}">
                </div>

                <div class="field">
                    <label for="toDate"><i class="bi bi-calendar-date"></i> {{ __('crm.to_date') }}</label>
                    <input class="control" id="toDate" type="date" name="to_date" value="{{ old('to_date') }}">
                </div>

                <div class="field">
                    <label for="searchQuery"><i class="bi bi-search"></i> {{ __('البحث') }}</label>
                    <input class="control" id="searchQuery" type="text" name="q" value="{{ old('q') }}" placeholder="{{ __('crm.lead_search_placeholder') }}">
                </div>
            </div>

            <!-- COLUMNS SELECTION -->
            <div style="margin-top:24px;padding-top:20px;border-top:1px dashed var(--line)">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;flex-wrap:wrap">
                    <div>
                        <strong style="display:block;font-size:14px;color:var(--dark)"><i class="bi bi-layout-three-columns"></i> الأعمدة المطلوب تصديرها</strong>
                        <small style="color:var(--muted)">اختر الأعمدة التي تود تضمينها في ملف Excel</small>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button type="button" class="btn soft small" id="selectAllColumnsBtn">تحديد الكل</button>
                        <button type="button" class="btn soft small" id="deselectAllColumnsBtn">إلغاء الكل</button>
                    </div>
                </div>

                <div class="columns-grid">
                    @php
                        $columns = [
                            'name' => 'الاسم',
                            'phone' => 'رقم الهاتف',
                            'email' => 'البريد الإلكتروني',
                            'company_name' => 'اسم الشركة',
                            'activity' => 'النشاط',
                            'governorate' => 'المحافظة',
                            'address' => 'العنوان',
                            'status' => 'الحالة',
                            'stage' => 'المرحلة',
                            'source' => 'المصدر',
                            'assigned_employee' => 'الموظف المسؤول',
                            'created_at' => 'تاريخ الإضافة',
                            'next_follow_up_at' => 'المتابعة القادمة',
                        ];
                    @endphp

                    @foreach ($columns as $colKey => $colLabel)
                        <label class="column-card">
                            <input type="checkbox" name="columns[]" value="{{ $colKey }}" checked class="col-checkbox">
                            <span>{{ $colLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-top:24px;display:flex;align-items:center;gap:12px">
                <button class="btn primary" type="submit" style="height:44px;padding:0 24px">
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
    const deselectAllBtn = document.getElementById('deselectAllColumnsBtn');
    const checkboxes = document.querySelectorAll('.col-checkbox');

    selectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => { cb.checked = true; });
    });

    deselectAllBtn?.addEventListener('click', () => {
        checkboxes.forEach(cb => { cb.checked = false; });
    });
})();
</script>
@endsection
