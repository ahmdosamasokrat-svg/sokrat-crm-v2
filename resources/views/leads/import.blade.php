@extends('leads.transfer-layout')

@section('title', isset($campaign) ? __('استيراد عملاء - ') . $campaign->name : __('crm.import_leads'))
@section('page-title', isset($campaign) ? __('استيراد عملاء حملة ') . $campaign->name : __('crm.import_leads'))
@section('page-description', isset($campaign) ? __('ارفع ملف الحملة وفق قواعد استيراد العملاء، ثم راجع المعاينة وأكدها.') : __('ارفع ملف Excel أو CSV، راجع المعاينة الذكية، ثم أكد الاستيراد.'))
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
    <a class="btn soft" href="{{ route('v2.leads') }}">
        <i class="bi bi-people"></i> {{ __('crm.view_leads') }}
    </a>
@endsection

@section('content')
<!-- UPLOAD & TEMPLATE CARD -->
<article class="transfer-card">
    <div class="transfer-hero">
        <small><i class="bi bi-shield-check"></i> {{ __('crm.safe_import') }}</small>
        <h2>{{ __('crm.add_leads_from_file') }}</h2>
        <p>{{ __('لن يتم إضافة أي عميل بمجرد رفع الملف. ستظهر معاينة كاملة أولًا، ويتم الاستيراد فقط بعد الضغط على تأكيد الاستيراد. رقم الهاتف هو معيار منع التكرار.') }}</p>
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

<!-- IMPORT PREVIEW SECTION -->
@if ($preview)
<section class="transfer-card" data-import-preview>
    <div class="card-head">
        <div>
            <h3><i class="bi bi-table"></i> {{ __('crm.import_preview') }}</h3>
            <p>{{ __('راجع الصفوف المحللة قبل أي كتابة في قاعدة البيانات.') }}</p>
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
            <div class="notice info">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>
                    {{ __('تم تجاهل الأعمدة غير المعروفة:') }}
                    {{ implode(app()->getLocale() == 'ar' ? '، ' : ', ', $preview['ignored_headers']) }}
                </div>
            </div>
        @endif

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:60px">{{ __('crm.row') }}</th>
                        <th>{{ __('crm.client') }}</th>
                        <th>{{ __('crm.phone') }}</th>
                        <th>{{ __('crm.status') }}</th>
                        <th>{{ __('المرحلة') }}</th>
                        <th>{{ __('crm.result') }}</th>
                        <th>{{ __('crm.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($preview['rows'] as $row)
                        <tr>
                            <td><strong>{{ $row['row_number'] }}</strong></td>
                            <td>{{ $row['name'] }}</td>
                            <td dir="ltr">{{ $row['phone'] }}</td>
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

        @if ($preview['valid_count'] > 0)
            <form method="POST" action="{{ route('v2.leads.import.confirm') }}" style="margin-top:24px">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $preview['token'] }}">
                <div style="display:flex;align-items:center;gap:12px">
                    <button class="btn primary" type="submit" style="height:44px;padding:0 24px">
                        <i class="bi bi-check2-all"></i> {{ __('تأكيد استيراد') }} {{ $preview['valid_count'] }} {{ __('عميل') }}
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
@endsection
