@php
    $isPopup = (bool) ($isPopup ?? (request()->boolean('kanban_popup') || request()->boolean('popup')));
@endphp

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
                            @can('leads.followups.view')
                                @if ($callPhone)
                                    <a
                                        class="js-call-followup"
                                        href="{{ route('v2.leads.followups.index', array_merge(['lead' => $lead, 'channel' => 'call'], $isPopup ? ['kanban_popup' => 1] : [])) }}"
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
                        @else
                            <span style="color:var(--muted)">—</span>
                        @endif
                    </div>
                    <div style="display:flex;gap:6px">
                        @if ($callPhone)
                            @can('leads.followups.view')
                                <a
                                    class="btn small soft js-call-followup"
                                    href="{{ route('v2.leads.followups.index', array_merge(['lead' => $lead, 'channel' => 'call'], $isPopup ? ['kanban_popup' => 1] : [])) }}"
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
                        @if ($whatsappPhone)
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

        <!-- DEDICATED QUOTATIONS SECTION -->
        <!-- COMPREHENSIVE DOCUMENTS & QUOTATIONS SECTION -->
        @php
            $userCanQuotations = auth()->user()?->hasPermission(\App\Security\CrmPermission::QUOTATIONS_VIEW) ?? false;
            $allDocsCollection = $allLeadDocuments ?? collect();
            $visibleDocs = $allDocsCollection->filter(fn ($d) => $userCanQuotations || $d->category !== 'quotation');
            $hasAnyDocs = $visibleDocs->isNotEmpty() || ($hasQuotationFile && $userCanQuotations);
            $quotationsCount = ($quotationDocuments ?? collect())->count() + ($hasQuotationFile && ($quotationDocuments ?? collect())->isEmpty() ? 1 : 0);
            $pdfCount = $visibleDocs->where('category', 'pdf')->count();
            $imageCount = $visibleDocs->where('category', 'image')->count();
            $attachmentCount = $visibleDocs->where('category', 'attachment')->count();
            $totalCount = $visibleDocs->count() + ($hasQuotationFile && ($quotationDocuments ?? collect())->isEmpty() && $userCanQuotations ? 1 : 0);
        @endphp

        <section class="panel" id="leadDocumentsPanel">
            <div class="panel-head" style="flex-wrap:wrap; gap:8px;">
                <div>
                    <h2><i class="bi bi-folder2-open" style="color:var(--primary, #3478f6);"></i> {{ __('crm.documents_and_quotations') ?? 'المستندات وعروض الأسعار' }}</h2>
                    <small style="color:var(--muted)">عروض الأسعار والمستندات المرفقة بالعميل</small>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    @if ($quotationsCount > 0 && $userCanQuotations)
                        <span class="badge active">{{ $lead->quotation_sent ? 'عرض سعر معتمد' : 'عرض سعر متاح' }}</span>
                    @endif
                    <span class="badge" style="background:#f1f5f9; color:#475569;">
                        {{ $totalCount }} {{ $totalCount === 1 ? 'مستند' : 'مستندات' }}
                    </span>
                </div>
            </div>

            @if ($hasAnyDocs)
                <!-- Category Filter Pills -->
                <div class="lead-doc-filter-pills" style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px;">
                    <button type="button" class="btn small soft doc-filter-pill active" onclick="filterLeadDocs('all', this)" style="border-radius:999px; padding:3px 12px; font-size:11px; background:var(--primary, #3478f6); color:#fff; border-color:var(--primary, #3478f6);">
                        الكل ({{ $totalCount }})
                    </button>
                    @if ($userCanQuotations && $quotationsCount > 0)
                        <button type="button" class="btn small soft doc-filter-pill" onclick="filterLeadDocs('quotation', this)" style="border-radius:999px; padding:3px 12px; font-size:11px;">
                            <i class="bi bi-file-earmark-pdf"></i> عروض الأسعار ({{ $quotationsCount }})
                        </button>
                    @endif
                    @if ($pdfCount > 0)
                        <button type="button" class="btn small soft doc-filter-pill" onclick="filterLeadDocs('pdf', this)" style="border-radius:999px; padding:3px 12px; font-size:11px;">
                            <i class="bi bi-file-earmark-text"></i> ملفات PDF ({{ $pdfCount }})
                        </button>
                    @endif
                    @if ($imageCount > 0)
                        <button type="button" class="btn small soft doc-filter-pill" onclick="filterLeadDocs('image', this)" style="border-radius:999px; padding:3px 12px; font-size:11px;">
                            <i class="bi bi-file-earmark-image"></i> الصور ({{ $imageCount }})
                        </button>
                    @endif
                    @if ($attachmentCount > 0)
                        <button type="button" class="btn small soft doc-filter-pill" onclick="filterLeadDocs('attachment', this)" style="border-radius:999px; padding:3px 12px; font-size:11px;">
                            <i class="bi bi-paperclip"></i> المرفقات ({{ $attachmentCount }})
                        </button>
                    @endif
                </div>

                <div class="info-list" id="leadDocsList" style="display:flex; flex-direction:column; gap:10px;">
                    @foreach ($visibleDocs as $doc)
                        @php
                            $docFileName = $doc->original_name ?: basename($doc->path);
                            $docStageName = $doc->stage?->localizedName() ?? ($doc->stage?->name_ar ?? '—');
                            $docUploader = $doc->uploader?->name ?? 'System';
                            $docDate = $doc->created_at ? $doc->created_at->format('Y-m-d h:i A') : '—';
                            $docFormattedSize = $doc->formattedSize();
                            $catConfig = \App\Models\PipelineStageField::DOCUMENT_CATEGORIES[$doc->category] ?? [
                                'label_ar' => 'مستند',
                                'label_en' => 'Document',
                            ];
                            $docCatLabel = app()->getLocale() === 'en' ? ($catConfig['label_en'] ?? $doc->category) : ($catConfig['label_ar'] ?? $doc->category);
                            $isImg = $doc->isImage();
                            $isPdf = $doc->isPdf();
                            $previewUrl = route('v2.leads.documents.preview', [$lead, $doc]);
                            $downloadUrl = route('v2.leads.documents.download', [$lead, $doc]);

                            $iconClass = match($doc->category) {
                                'quotation' => 'bi-file-earmark-pdf',
                                'pdf' => 'bi-file-earmark-text',
                                'image' => 'bi-file-earmark-image',
                                default => 'bi-paperclip',
                            };
                            $iconColor = match($doc->category) {
                                'quotation' => '#dc2626',
                                'pdf' => '#2563eb',
                                'image' => '#7c3aed',
                                default => '#475569',
                            };
                            $iconBg = match($doc->category) {
                                'quotation' => '#fef2f2',
                                'pdf' => '#eff6ff',
                                'image' => '#f5f3ff',
                                default => '#f8fafc',
                            };
                        @endphp
                        <div class="info-row lead-doc-card" data-doc-category="{{ $doc->category }}" style="padding:12px 14px; border:1px solid var(--line); border-radius:10px; background:var(--bg); flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; transition:border-color .15s ease;">
                            <div style="display:flex; align-items:center; gap:12px; min-width:0; flex:1 1 240px;">
                                @if ($isImg)
                                    <div style="width:44px; height:44px; border-radius:8px; overflow:hidden; border:1px solid var(--line); flex-shrink:0; background:#f1f5f9; cursor:pointer;"
                                         onclick="openDocumentViewer('{{ $previewUrl }}', '{{ $downloadUrl }}', '{{ addslashes($docFileName) }}', '{{ $docCatLabel }}', true, false, '{{ $docFormattedSize }}')">
                                        <img src="{{ $previewUrl }}" alt="{{ $docFileName }}" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                @else
                                    <div style="width:44px; height:44px; border-radius:8px; background:{{ $iconBg }}; color:{{ $iconColor }}; display:grid; place-items:center; font-size:22px; flex-shrink:0;">
                                        <i class="bi {{ $iconClass }}"></i>
                                    </div>
                                @endif

                                <div style="min-width:0;">
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                                        <strong style="font-size:13px; color:var(--dark); word-break:break-all;">{{ $docFileName }}</strong>
                                        <span class="badge" style="font-size:10px; background:{{ $iconBg }}; color:{{ $iconColor }}; border:1px solid {{ $iconColor }}33;">
                                            {{ $docCatLabel }}
                                        </span>
                                        @if ($docFormattedSize !== '—')
                                            <span style="font-size:11px; color:var(--muted);">({{ $docFormattedSize }})</span>
                                        @endif
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:11px; color:var(--muted);">
                                        <span><i class="bi bi-diagram-3"></i> {{ __('crm.stage') ?? 'مرحلة' }}: <strong style="color:var(--dark)">{{ $docStageName }}</strong></span>
                                        <span>•</span>
                                        <span><i class="bi bi-person"></i> رفع بواسطة: <strong style="color:var(--dark)">{{ $docUploader }}</strong></span>
                                        <span>•</span>
                                        <span><i class="bi bi-calendar3"></i> {{ $docDate }}</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; gap:6px; flex-shrink:0;">
                                <button
                                    type="button"
                                    class="btn small soft"
                                    onclick="openDocumentViewer('{{ $previewUrl }}', '{{ $downloadUrl }}', '{{ addslashes($docFileName) }}', '{{ $docCatLabel }}', {{ $isImg ? 'true' : 'false' }}, {{ $isPdf ? 'true' : 'false' }}, '{{ $docFormattedSize }}')"
                                    title="معاينة الملف"
                                >
                                    <i class="bi bi-eye"></i> {{ __('crm.preview') ?? 'عرض' }}
                                </button>
                                <a
                                    class="btn small soft"
                                    href="{{ $downloadUrl }}"
                                    title="تحميل الملف"
                                >
                                    <i class="bi bi-download"></i> {{ __('crm.download') ?? 'تنزيل' }}
                                </a>
                            </div>
                        </div>
                    @endforeach

                    @if ($hasQuotationFile && $userCanQuotations && ($quotationDocuments ?? collect())->isEmpty())
                        <div class="info-row lead-doc-card" data-doc-category="quotation" style="padding:12px 14px; border:1px solid var(--line); border-radius:10px; background:var(--bg); flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:12px; min-width:0; flex:1 1 240px;">
                                <div style="width:44px; height:44px; border-radius:8px; background:#fef2f2; color:#dc2626; display:grid; place-items:center; font-size:22px; flex-shrink:0;">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </div>
                                <div style="min-width:0;">
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                                        <strong style="font-size:13px; color:var(--dark); word-break:break-all;">{{ $quotationFileName ?: 'ملف عرض السعر' }}</strong>
                                        <span class="badge" style="font-size:10px; background:#fef2f2; color:#dc2626; border:1px solid #dc262633;">عرض سعر</span>
                                    </div>
                                    <div style="font-size:11px; color:var(--muted);">
                                        ملف عرض السعر الرسمي المرفق للعميل
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; gap:6px; flex-shrink:0;">
                                <button
                                    type="button"
                                    class="btn small soft"
                                    onclick="openDocumentViewer('{{ route('v2.leads.quotation.preview', $lead) }}', '{{ route('v2.leads.quotation.download', $lead) }}', '{{ addslashes($quotationFileName ?: 'ملف عرض السعر') }}', 'عرض سعر', false, true, '—')"
                                    title="معاينة الملف"
                                >
                                    <i class="bi bi-eye"></i> {{ __('crm.preview') ?? 'عرض' }}
                                </button>
                                <a
                                    class="btn small soft"
                                    href="{{ route('v2.leads.quotation.download', $lead) }}"
                                    title="تحميل الملف"
                                >
                                    <i class="bi bi-download"></i> {{ __('crm.download') ?? 'تنزيل' }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div id="leadDocsEmptyCategoryMsg" style="display:none; text-align:center; padding:24px; color:var(--muted); font-size:13px;">
                    <i class="bi bi-folder-x" style="font-size:28px; display:block; margin-bottom:6px; opacity:0.5;"></i>
                    لا توجد ملفات ضمن هذه الفئة.
                </div>
            @else
                <div style="text-align:center; padding:24px; color:var(--muted); font-size:13px;">
                    <i class="bi bi-folder-x" style="font-size:32px; display:block; margin-bottom:6px; opacity:0.5;"></i>
                    لا توجد مستندات أو عروض أسعار مرفقة بهذا العميل حتى الآن.
                </div>
            @endif
        </section>

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

        <!-- CUSTOMER ACTIVITY TIMELINE -->
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2><i class="bi bi-clock-history"></i> {{ __('crm.lead_activity_timeline') }}</h2>
                    <small style="color:var(--muted)">سجل زمني لجميع المتابعات وتغييرات الحالات</small>
                </div>
                @can('leads.followups.view')
                    <a
                     href="{{ route('v2.leads.followups.index', array_merge(['lead' => $lead], $isPopup ? ['kanban_popup' => 1] : [])) }}"
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
                         href="{{ route('v2.leads.followups.index', array_merge(['lead' => $lead], $isPopup ? ['kanban_popup' => 1] : [])) }}"
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

<!-- MODAL: DOCUMENT PREVIEW VIEWER -->
<div id="documentViewerModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.85); z-index:99999; backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:16px; width:100%; max-width:960px; height:88vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <!-- Viewer Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid var(--line); background:#f8fafc; flex-shrink:0;">
            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                <div id="viewerFileIcon" style="width:36px; height:36px; border-radius:10px; background:#eef2ff; color:#4f46e5; display:grid; place-items:center; font-size:18px; flex-shrink:0;">
                    <i class="bi bi-file-earmark"></i>
                </div>
                <div style="min-width:0;">
                    <h3 id="viewerFileName" style="margin:0; font-size:15px; font-weight:800; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        معاينة المستند
                    </h3>
                    <div style="display:flex; align-items:center; gap:8px; font-size:11px; color:#64748b; margin-top:2px;">
                        <span id="viewerCategoryBadge" class="badge" style="background:#e2e8f0; color:#334155;"></span>
                        <span id="viewerFileSize"></span>
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                <a id="viewerOpenTabBtn" href="#" target="_blank" rel="noopener noreferrer" class="btn small soft" title="فتح في نافذة جديدة">
                    <i class="bi bi-box-arrow-up-right"></i> <span style="font-size:11px;">نافذة جديدة</span>
                </a>
                <a id="viewerDownloadBtn" href="#" class="btn small soft" title="تنزيل الملف">
                    <i class="bi bi-download"></i> <span style="font-size:11px;">تنزيل</span>
                </a>
                <button type="button" onclick="closeDocumentViewer()" class="btn small soft" style="font-size:18px; min-width:36px; padding:0 8px;" title="إغلاق">
                    &times;
                </button>
            </div>
        </div>

        <!-- Viewer Body -->
        <div id="viewerContentArea" style="flex:1; background:#0f172a; display:flex; align-items:center; justify-content:center; overflow:auto; position:relative; min-height:0;">
            <iframe id="viewerIframe" src="about:blank" style="display:none; width:100%; height:100%; border:0;"></iframe>
            <div id="viewerImgContainer" style="display:none; width:100%; height:100%; align-items:center; justify-content:center; padding:16px; overflow:auto;">
                <img id="viewerImage" src="" alt="Document Preview" style="max-width:100%; max-height:100%; object-fit:contain; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.3);">
            </div>
            <div id="viewerFallback" style="display:none; text-align:center; padding:40px; color:#94a3b8;">
                <i class="bi bi-file-earmark-arrow-down" style="font-size:48px; display:block; margin-bottom:12px; color:#64748b;"></i>
                <p style="font-size:14px; margin:0 0 16px; color:#cbd5e1;">هذا النوع من الملفات لا يدعم المعاينة المباشرة داخل المتصفح.</p>
                <a id="viewerFallbackDownloadBtn" href="#" class="btn primary">
                    <i class="bi bi-download"></i> تنزيل الملف للمعاينة
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function openDocumentViewer(previewUrl, downloadUrl, fileName, category, isImage, isPdf, size) {
    const modal = document.getElementById('documentViewerModal');
    const iframe = document.getElementById('viewerIframe');
    const imgContainer = document.getElementById('viewerImgContainer');
    const img = document.getElementById('viewerImage');
    const fallback = document.getElementById('viewerFallback');
    const nameEl = document.getElementById('viewerFileName');
    const catBadge = document.getElementById('viewerCategoryBadge');
    const sizeEl = document.getElementById('viewerFileSize');
    const openTabBtn = document.getElementById('viewerOpenTabBtn');
    const dlBtn = document.getElementById('viewerDownloadBtn');
    const fallbackDlBtn = document.getElementById('viewerFallbackDownloadBtn');

    if (!modal) return;

    nameEl.textContent = fileName || 'مستند';
    catBadge.textContent = category || 'مستند';
    sizeEl.textContent = size || '';
    openTabBtn.href = previewUrl;
    dlBtn.href = downloadUrl;
    fallbackDlBtn.href = downloadUrl;

    if (isImage) {
        iframe.style.display = 'none';
        iframe.src = 'about:blank';
        fallback.style.display = 'none';
        imgContainer.style.display = 'flex';
        img.src = previewUrl;
    } else if (isPdf) {
        imgContainer.style.display = 'none';
        img.src = '';
        fallback.style.display = 'none';
        iframe.style.display = 'block';
        iframe.src = previewUrl;
    } else {
        iframe.style.display = 'none';
        iframe.src = 'about:blank';
        imgContainer.style.display = 'none';
        img.src = '';
        fallback.style.display = 'block';
    }

    modal.style.display = 'flex';
}

function closeDocumentViewer() {
    const modal = document.getElementById('documentViewerModal');
    if (!modal) return;
    const iframe = document.getElementById('viewerIframe');
    const img = document.getElementById('viewerImage');
    if (iframe) iframe.src = 'about:blank';
    if (img) img.src = '';
    modal.style.display = 'none';
}

function filterLeadDocs(category, btnEl) {
    const pills = document.querySelectorAll('.doc-filter-pill');
    pills.forEach(p => {
        p.style.background = 'var(--card, #fff)';
        p.style.color = 'var(--dark, #1e293b)';
        p.style.borderColor = 'var(--line, #e2e8f0)';
    });
    if (btnEl) {
        btnEl.style.background = 'var(--primary, #3478f6)';
        btnEl.style.color = '#fff';
        btnEl.style.borderColor = 'var(--primary, #3478f6)';
    }

    const cards = document.querySelectorAll('.lead-doc-card');
    let visibleCount = 0;
    cards.forEach(card => {
        const docCat = card.getAttribute('data-doc-category');
        if (category === 'all' || docCat === category) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const emptyMsg = document.getElementById('leadDocsEmptyCategoryMsg');
    if (emptyMsg) {
        emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}
</script>
