@php
    $visibleColumns = $visibleColumns ?? [
        ['key' => 'core:customer'],
        ['key' => 'core:contact'],
        ['key' => 'core:company_source'],
        ['key' => 'core:status'],
        ['key' => 'core:employee'],
        ['key' => 'core:next_followup'],
        ['key' => 'core:created_at'],
        ['key' => 'core:actions'],
    ];
@endphp
<div id="bulkActionsBar" class="bulk-actions-bar" style="display:none; padding:12px 18px; margin-bottom:14px; background:var(--card); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow); align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div style="display:flex; align-items:center; gap:10px;">
        <span style="font-size:13px; font-weight:800; color:var(--dark);">
            تم تحديد <strong id="bulkSelectedCount" style="color:var(--red);">0</strong> عميل
        </span>
        <button type="button" id="bulkClearSelection" class="btn small soft" style="height:32px; font-size:11.5px; border-radius:8px;">
            <i class="bi bi-x-circle"></i> إلغاء التحديد
        </button>
    </div>

    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        @can('leads.assign')
            @if(isset($assignableUsers) && $assignableUsers->isNotEmpty())
                <form id="bulkAssignForm" method="POST" action="{{ route('v2.leads.bulk-assign') }}" style="display:inline-flex; align-items:center; gap:6px;">
                    @csrf
                    <div id="bulkAssignHiddenInputs"></div>
                    <select id="bulkAssignUserSelect" name="assigned_user_id" required style="height:36px; padding:0 10px; border-radius:8px; font-size:12px; font-weight:700; border:1px solid var(--line); background:var(--card); color:var(--dark);">
                        <option value="">إعادة تعيين إلى...</option>
                        @foreach($assignableUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" id="bulkAssignButton" class="btn small primary" style="height:36px; font-size:12px; display:inline-flex; align-items:center; gap:4px; border-radius:8px;">
                        <i class="bi bi-person-check"></i> إعادة تعيين
                    </button>
                </form>
            @endif
        @endcan

        @can('leads.delete')
            <form id="bulkDeleteForm" method="POST" action="{{ route('v2.leads.bulk-destroy') }}" style="display:inline-flex;" onsubmit="return confirm('هل أنت متأكد من مسح العملاء المحددين ونقلهم إلى سلة المهملات؟')">
                @csrf
                <div id="bulkDeleteHiddenInputs"></div>
                <button type="submit" id="bulkDeleteButton" class="btn small danger" style="height:36px; font-size:12px; display:inline-flex; align-items:center; gap:4px; border-radius:8px; background:#fee2e2; color:#dc2626; border-color:#fca5a5;">
                    <i class="bi bi-trash"></i> مسح المحدد
                </button>
            </form>
        @endcan

        @can('leads.export')
            <form id="bulkExportForm" method="POST" action="{{ route('v2.leads.export-selected') }}" style="display:inline-flex;">
                @csrf
                <div id="bulkExportHiddenInputs"></div>
                <button type="submit" id="bulkExportButton" class="btn small soft" style="height:36px; font-size:12px; display:inline-flex; align-items:center; gap:5px; border-radius:8px; color:#059669; border-color:#a7f3d0; background:#ecfdf5;">
                    <i class="bi bi-file-earmark-excel"></i> تصدير إكسيل
                </button>
            </form>
        @endcan
    </div>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:40px;text-align:center">
                    <input type="checkbox" id="selectAllLeads" class="lead-select-all" aria-label="{{ __('crm.select_all') }}">
                </th>
                @foreach ($visibleColumns as $col)
                    @switch ($col['key'])
                        @case ('core:customer')
                            <th style="min-width:200px">{{ __('crm.client') }}</th>
                            @break
                        @case ('core:contact')
                            <th style="min-width:140px">{{ __('crm.contact_data') }}</th>
                            @break
                        @case ('core:company_source')
                            <th style="min-width:140px">{{ __('crm.company_source') }}</th>
                            @break
                        @case ('core:status')
                            <th style="min-width:140px">{{ __('crm.current_status') }}</th>
                            @break
                        @case ('core:employee')
                            <th style="min-width:130px">{{ __('crm.responsible_employee') }}</th>
                            @break
                        @case ('core:next_followup')
                            <th style="min-width:140px">{{ __('crm.next_followup') }}</th>
                            @break
                        @case ('core:created_at')
                            <th style="min-width:110px">{{ __('crm.created_date') }}</th>
                            @break
                        @case ('core:actions')
                            <th style="min-width:140px;text-align:center">{{ __('crm.actions') }}</th>
                            @break
                        @default
                            <th style="min-width:130px">
                                {{ app()->getLocale() === 'en' ? ($col['label_en'] ?? $col['label_ar']) : ($col['label_ar'] ?? $col['label_en']) }}
                            </th>
                    @endswitch
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($leads as $lead)
                @php
                    $leadStatusColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $lead->status?->color) ? $lead->status->color : '#64748b';
                    $leadPhoneRaw = trim((string) $lead->phone);
                    $leadPhoneDigits = preg_replace('/\D+/', '', $leadPhoneRaw) ?? '';
                    $callPhone = preg_match('/^[0-9]{2,20}$/', $leadPhoneDigits) === 1 ? $leadPhoneDigits : null;
                    $whatsappPhone = null;

                    if (str_starts_with($leadPhoneDigits, '0020')) {
                        $whatsappPhone = substr($leadPhoneDigits, 2);
                    } elseif (preg_match('/^01[0125][0-9]{8}$/', $leadPhoneDigits) === 1) {
                        $whatsappPhone = '20'.substr($leadPhoneDigits, 1);
                    } elseif (preg_match('/^20[0-9]{10}$/', $leadPhoneDigits) === 1 || preg_match('/^[1-9][0-9]{7,14}$/', $leadPhoneDigits) === 1) {
                        $whatsappPhone = $leadPhoneDigits;
                    }
                @endphp
                <tr class="lead-row">
                    <td style="width:40px;text-align:center" onclick="event.stopPropagation()">
                        <input type="checkbox" class="lead-select-checkbox" name="lead_ids[]" value="{{ $lead->id }}" aria-label="{{ $lead->name }}">
                    </td>
                    @foreach ($visibleColumns as $col)
                        @switch ($col['key'])
                            @case ('core:customer')
                                <td>
                                    <div class="customer-name-cell">
                                        <div class="customer-avatar">
                                            {{ mb_substr((string) $lead->name, 0, 1) }}
                                        </div>
                                        <div class="customer-info">
                                            <a href="{{ route('v2.leads.show', array_merge(request()->query(), ['lead' => $lead->id])) }}">
                                                <strong>{{ $lead->name }}</strong>
                                            </a>
                                            <small>{{ $lead->email ?: __('crm.no_email') }}</small>
                                        </div>
                                    </div>
                                </td>
                                @break

                            @case ('core:contact')
                                <td>
                                    @if ($lead->phone)
                                        @can('leads.followups.view')
                                            @if ($callPhone)
                                                <a
                                                    class="js-call-followup"
                                                    href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
                                                    data-call-href="tel:{{ $callPhone }}"
                                                    title="{{ __('crm.open_microsip_followup') }}"
                                                    style="font-weight:700;color:inherit"
                                                    dir="ltr"
                                                >
                                                    {{ $lead->phone }}
                                                </a>
                                            @else
                                                <a href="tel:{{ $lead->phone }}" style="font-weight:700;color:inherit" dir="ltr">
                                                    {{ $lead->phone }}
                                                </a>
                                            @endif
                                        @else
                                            <a href="tel:{{ $lead->phone }}" style="font-weight:700;color:inherit" dir="ltr">
                                                {{ $lead->phone }}
                                            </a>
                                        @endcan
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>
                                @break

                            @case ('core:company_source')
                                <td>
                                    <strong>{{ $lead->company_name ?: __('crm.no_company') }}</strong>
                                    <span class="stage-name">{{ $lead->source ? __($lead->source) : __('غير محدد') }}</span>
                                </td>
                                @break

                            @case ('core:status')
                                <td>
                                    <span class="status-badge" style="--status-color:{{ $leadStatusColor }}">
                                        <i class="status-dot"></i>
                                        {{ $lead->status?->name_ar ? __($lead->status->name_ar) : __('crm.no_status') }}
                                    </span>
                                    <span class="stage-name">
                                        {{ $lead->status?->stage?->name_ar ? __($lead->status->stage->name_ar) : __('بدون مرحلة') }}
                                    </span>
                                </td>
                                @break

                            @case ('core:employee')
                                <td>
                                    {{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
                                </td>
                                @break

                            @case ('core:next_followup')
                                <td>
                                    @if ($lead->next_follow_up_at)
                                        <span class="badge {{ $lead->next_follow_up_at->isPast() ? 'overdue' : ($lead->next_follow_up_at->isToday() ? 'today' : '') }}">
                                            <i class="bi bi-clock"></i> {{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}
                                        </span>
                                    @else
                                        <span style="color:var(--muted)">—</span>
                                    @endif
                                </td>
                                @break

                            @case ('core:created_at')
                                <td>
                                    {{ $lead->created_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                @break

                            @case ('core:actions')
                                <td>
                                    <div class="actions-cell" style="justify-content:center">
                                        <a
                                            class="btn-action"
                                            href="{{ route('v2.leads.show', array_merge(request()->query(), ['lead' => $lead->id])) }}"
                                            title="{{ __('crm.view_lead') }}"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if ($callPhone)
                                            @can('leads.followups.view')
                                                <a
                                                    class="btn-action call js-call-followup"
                                                    href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
                                                    data-call-href="tel:{{ $callPhone }}"
                                                    title="{{ __('crm.call_action') }}"
                                                >
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                            @else
                                                <a class="btn-action call" href="tel:{{ $callPhone }}" title="{{ __('crm.call_action') }}">
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                            @endcan
                                        @endif

                                        @can('leads.followups.view')
                                            <a
                                                class="btn-action"
                                                href="{{ route('v2.leads.followups.index', $lead) }}"
                                                title="{{ __('crm.log_new_followup') }}"
                                            >
                                                <i class="bi bi-clock-history"></i>
                                            </a>
                                        @endcan

                                        @if ($whatsappPhone)
                                            <a
                                                class="btn-action whatsapp"
                                                href="https://wa.me/{{ $whatsappPhone }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="{{ __('crm.whatsapp') }}"
                                            >
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        @endif

                                        @can('leads.update')
                                            <a
                                                class="btn-action"
                                                href="{{ route('v2.leads.edit', $lead) }}"
                                                title="{{ __('crm.edit_data') }}"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                                @break

                            @default
                                @php
                                    $fieldObj = $col['stage_field'] ?? null;
                                    $customVal = ($fieldObj && isset($stageValuesMap[$lead->id][$col['field_id']]))
                                        ? $stageValuesMap[$lead->id][$col['field_id']]->value
                                        : null;
                                    $displayVal = $fieldObj
                                        ? \App\Support\LeadColumnConfig::formatDynamicCellValue($lead, $fieldObj, $customVal)
                                        : '—';
                                @endphp
                                <td>
                                    <span style="font-weight:600;font-size:12px;{{ $displayVal === '—' ? 'color:var(--muted);' : '' }}">
                                        {{ $displayVal }}
                                    </span>
                                </td>
                        @endswitch
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($visibleColumns) + 1 }}" style="text-align:center;padding:40px 20px;color:var(--muted)">
                        <i class="bi bi-people" style="font-size:32px;display:block;margin-bottom:8px"></i>
                        <strong>{{ empty($activeQuery) ? __('لا يوجد عملاء حتى الآن') : __('لا توجد نتائج مطابقة') }}</strong>
                        <p style="margin:4px 0 0;font-size:12px">
                            @if (empty($activeQuery))
                                لم تتم إضافة أي عميل إلى قاعدة CRM حتى الآن.
                            @else
                                جرّب تغيير كلمات البحث أو إزالة بعض الفلاتر الحالية.
                            @endif
                        </p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- PAGINATION -->
@if ($leads->hasPages())
    {{ $leads->links() }}
@endif
