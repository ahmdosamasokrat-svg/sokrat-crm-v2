@extends('leads.transfer-layout')

@section('title', __('crm.lead_trash_title'))
@section('page-title', __('crm.lead_trash_title'))
@section('page-description', __('crm.lead_trash_desc'))


@push('styles')
<style>
    .trash-panel {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .trash-panel-head {
        padding: 18px 24px;
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
    }
    .trash-filters {
        padding: 16px 24px;
        background: rgba(0,0,0,0.015);
        border-bottom: 1px solid var(--line);
    }
    .trash-filters form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        align-items: end;
    }
    .trash-bulk-bar {
        padding: 10px 24px;
        background: #f8fafc;
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 13px;
    }
    html.dark-mode .trash-bulk-bar {
        background: #1e293b;
    }
    .table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    table.trash-table {
        width: 100%;
        border-collapse: collapse;
        text-align: start;
        font-size: 13px;
    }
    table.trash-table th {
        background: rgba(0,0,0,0.02);
        color: var(--muted);
        font-weight: 700;
        padding: 12px 16px;
        border-bottom: 1px solid var(--line);
        white-space: nowrap;
    }
    table.trash-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--line);
        vertical-align: middle;
    }
    table.trash-table tbody tr:hover {
        background: rgba(0,0,0,0.015);
    }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1;
    }
    .badge-stage {
        border: 1px solid rgba(0,0,0,0.1);
    }
    .badge-deleted {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .crm-modal-shell {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .crm-modal-dialog {
        background: var(--card);
        color: var(--dark);
        border: 1px solid var(--line);
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 520px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes modalScaleIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .crm-modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .crm-modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(85vh - 120px);
    }
    .crm-modal-footer {
        padding: 14px 20px;
        border-top: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        background: rgba(0,0,0,0.015);
    }
    .crm-modal-close-btn {
        background: none;
        border: none;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        color: var(--muted);
        padding: 0;
    }
    .form-group {
        margin-bottom: 14px;
    }
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--dark);
    }
    .form-control {
        width: 100%;
        height: 38px;
        padding: 0 10px;
        border: 1px solid var(--line);
        border-radius: 8px;
        background: var(--card);
        color: var(--dark);
        box-sizing: border-box;
    }
</style>
@endpush

@section('content')
<section class="grid stats-grid" style="margin-bottom: 20px;">
    <article class="stat-card" style="border-inline-start: 4px solid #ef4444;">
        <span>{{ __('crm.lead_trash_title') }}</span>
        <b style="color: #ef4444;">{{ number_format($trashCount) }}</b>
    </article>
</section>

@if(session('success'))
    <div class="notice success" style="margin-bottom: 20px; display:flex; align-items:center; gap:8px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:10px;">
        <i class="bi bi-check-circle-fill" style="font-size:18px;"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="notice error" style="margin-bottom: 20px; display:flex; align-items:center; gap:8px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:18px;"></i>
        <div>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

<section class="trash-panel">
    <div class="trash-panel-head">
        <div>
            <h2 style="margin:0; font-size:18px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-trash3 text-danger"></i> {{ __('crm.lead_trash_title') }}
            </h2>
            <p style="margin:4px 0 0; color:var(--muted); font-size:13px;">
                {{ __('crm.lead_trash_desc') }}
            </p>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="trash-filters">
        <form method="GET" action="{{ route('v2.leads.trash.index') }}">
            <div>
                <label style="font-size:12px; color:var(--muted); margin-bottom:4px; display:block;">{{ __('crm.search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('crm.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>

            <div>
                <label style="font-size:12px; color:var(--muted); margin-bottom:4px; display:block;">{{ __('crm.previous_stage') }}</label>
                <select name="stage_id" class="form-control">
                    <option value="">{{ __('crm.all') }}</option>
                    @foreach($allStages as $st)
                        <option value="{{ $st->id }}" @selected((string) ($filters['stage_id'] ?? '') === (string) $st->id)>
                            {{ $st->localizedName() }} {{ $st->trashed() ? '(' . __('crm.stage_inactive_badge') . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="font-size:12px; color:var(--muted); margin-bottom:4px; display:block;">{{ __('crm.deleted_by') }}</label>
                <select name="deleted_by" class="form-control">
                    <option value="">{{ __('crm.all') }}</option>
                    @foreach($deleters as $d)
                        <option value="{{ $d->id }}" @selected((string) ($filters['deleted_by'] ?? '') === (string) $d->id)>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="font-size:12px; color:var(--muted); margin-bottom:4px; display:block;">{{ __('crm.deleted_at') }} (من)</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>

            <div>
                <label style="font-size:12px; color:var(--muted); margin-bottom:4px; display:block;">{{ __('crm.deleted_at') }} (إلى)</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn primary" style="height:38px; padding:0 14px;">
                    <i class="bi bi-funnel"></i> {{ __('crm.filter') }}
                </button>
                @if(array_filter($filters))
                    <a href="{{ route('v2.leads.trash.index') }}" class="btn soft" style="height:38px; display:inline-flex; align-items:center; padding:0 12px;" title="{{ __('crm.clear_filters') }}">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- BULK ACTIONS TOOLBAR -->
    <div class="trash-bulk-bar" id="trashBulkBar">
        <div style="display:flex; align-items:center; gap:10px;">
            <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:700;">
                <input type="checkbox" id="selectAllTrash" onchange="toggleSelectAllTrash(this)">
                <span>{{ __('تحديد الكل') }}</span>
            </label>
            <span id="selectedTrashCount" style="color:var(--muted);"></span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            @can('leads.trash.restore')
                <button type="button" class="btn small soft" id="bulkRestoreBtn" onclick="openBulkRestoreModal()" style="display:none;">
                    <i class="bi bi-arrow-counterclockwise"></i> {{ __('crm.restore_lead') }}
                </button>
            @endcan
            @can('leads.trash.force_delete')
                <button type="button" class="btn small danger" id="bulkForceDeleteBtn" onclick="openBulkForceDeleteModal()" style="display:none;">
                    <i class="bi bi-x-circle"></i> {{ __('crm.permanent_delete') }}
                </button>
            @endcan
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <table class="trash-table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>{{ __('crm.customer') }}</th>
                    <th>{{ __('crm.previous_stage') }}</th>
                    <th>{{ __('crm.assigned_employee') }}</th>
                    <th>{{ __('crm.deleted_by') }}</th>
                    <th>{{ __('crm.deleted_at') }}</th>
                    <th>{{ __('crm.deletion_reason') }}</th>
                    <th style="width:180px; text-align:center;">{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    @php
                        $prevStage = $lead->deletedFromStage ?: $lead->status?->stage;
                        $prevStageDeleted = $prevStage === null || $prevStage->trashed() || ! $prevStage->is_active;
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="trash-item-cb" value="{{ $lead->id }}" onchange="onTrashItemCheckChange()">
                        </td>
                        <td>
                            <div>
                                <strong style="font-size:14px;">{{ $lead->name }}</strong>
                                @if($lead->company_name)
                                    <div style="color:var(--muted); font-size:12px;">{{ $lead->company_name }}</div>
                                @endif
                                @if($lead->phone)
                                    <div style="color:var(--muted); font-size:12px;" dir="ltr"><i class="bi bi-telephone"></i> {{ $lead->phone }}</div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($prevStage)
                                <span class="badge badge-stage" style="background:{{ $prevStage->color ? $prevStage->color . '18' : '#f1f5f9' }}; color:{{ $prevStage->color ?: '#334155' }}; border-color:{{ $prevStage->color ? $prevStage->color . '40' : '#e2e8f0' }}">
                                    @if($prevStage->icon)
                                        <i class="bi {{ $prevStage->icon }}"></i>
                                    @endif
                                    {{ $prevStage->localizedName() }}
                                </span>
                                @if($prevStageDeleted)
                                    <span class="badge badge-deleted" title="{{ __('المرحلة السابقة محذوفة أو غير نشطة') }}">
                                        {{ __('محذوفة') }}
                                    </span>
                                @endif
                            @else
                                <span style="color:var(--muted);">—</span>
                            @endif
                        </td>
                        <td>
                            <span>{{ $lead->assignedUser?->name ?: ($lead->assigned_employee ?: '—') }}</span>
                        </td>
                        <td>
                            <span>{{ $lead->deletedByUser?->name ?: '—' }}</span>
                        </td>
                        <td>
                            <div style="white-space:nowrap;">{{ $lead->deleted_at ? $lead->deleted_at->format('Y-m-d H:i') : '—' }}</div>
                            @if($lead->deleted_at)
                                <small style="color:var(--muted); display:block;">{{ $lead->deleted_at->diffForHumans() }}</small>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:12px; color:var(--muted);">{{ $lead->deleted_reason ?: '—' }}</span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                                @can('leads.trash.restore')
                                    <button type="button" class="btn small soft" style="color:#059669; border-color:#a7f3d0; background:#ecfdf5;" onclick="openSingleRestoreModal({{ json_encode($lead) }}, {{ json_encode($prevStage) }}, {{ $prevStageDeleted ? 'true' : 'false' }})" title="{{ __('crm.restore_lead') }}">
                                        <i class="bi bi-arrow-counterclockwise"></i> {{ __('crm.restore_lead') }}
                                    </button>
                                @endcan

                                @can('leads.trash.force_delete')
                                    <button type="button" class="btn small danger" onclick="openSingleForceDeleteModal({{ json_encode($lead) }})" title="{{ __('crm.permanent_delete') }}">
                                        <i class="bi bi-x-circle"></i> {{ __('crm.permanent_delete') }}
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:48px 16px;">
                            <div style="max-width:320px; margin:0 auto; color:var(--muted);">
                                <i class="bi bi-trash3" style="font-size:44px; display:block; margin-bottom:12px; opacity:0.5;"></i>
                                <strong style="display:block; font-size:16px; margin-bottom:4px; color:var(--dark);">{{ __('سلة المهملات فارغة') }}</strong>
                                <p style="margin:0; font-size:13px;">{{ __('لا يوجد أي عملاء محذوفين في سلة المهملات حالياً.') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leads->hasPages())
        <div style="padding:16px 24px; border-top:1px solid var(--line);">
            {{ $leads->links() }}
        </div>
    @endif
</section>

<!-- MODAL: SINGLE RESTORE -->
<div id="singleRestoreModal" class="crm-modal-shell" role="dialog" aria-modal="true" style="display:none;">
    <div class="crm-modal-dialog">
        <div class="crm-modal-header">
            <h3 style="margin:0; font-size:16px; font-weight:800;">
                {{ __('crm.restore_lead') }}: <span id="singleRestoreLeadName" style="color:var(--primary);"></span>
            </h3>
            <button type="button" class="crm-modal-close-btn" onclick="closeSingleRestoreModal()">&times;</button>
        </div>
        <form id="singleRestoreForm" method="POST" action="">
            @csrf
            <div class="crm-modal-body">
                <div id="singleRestorePrevValidBox" style="display:none; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; padding:12px; margin-bottom:14px;">
                    <p style="margin:0; font-size:13px; color:#065f46;">
                        {{ __('سيتم استعادة العميل إلى مرحلته السابقة:') }} <strong id="singleRestorePrevStageName"></strong>.
                    </p>
                </div>

                <div id="singleRestorePrevDeletedBox" style="display:none; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px; margin-bottom:14px;">
                    <strong style="color:#92400e; font-size:13px; display:block; margin-bottom:4px;">
                        <i class="bi bi-exclamation-circle-fill"></i> {{ __('المرحلة السابقة لهذا العميل تم حذفها.') }}
                    </strong>
                    <p style="margin:0; font-size:12px; color:#78350f;">
                        {{ __('يرجى اختيار مرحلة نشطة جديدة لاستعادة العميل إليها:') }}
                    </p>
                </div>

                <div class="form-group" id="singleRestoreStageSelectGroup">
                    <label>{{ __('crm.restore_to_stage') }}</label>
                    <select name="destination_stage_id" id="singleRestoreDestinationStage" class="form-control">
                        @foreach($activeStages as $st)
                            <option value="{{ $st->id }}">{{ $st->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="crm-modal-footer">
                <button type="button" class="btn soft" onclick="closeSingleRestoreModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.restore_lead') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: SINGLE FORCE DELETE -->
<div id="singleForceDeleteModal" class="crm-modal-shell" role="dialog" aria-modal="true" style="display:none;">
    <div class="crm-modal-dialog">
        <div class="crm-modal-header" style="background:#fef2f2; border-bottom-color:#fecaca;">
            <h3 style="margin:0; font-size:16px; font-weight:800; color:#991b1b;">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ __('crm.permanent_delete') }}
            </h3>
            <button type="button" class="crm-modal-close-btn" onclick="closeSingleForceDeleteModal()">&times;</button>
        </div>
        <form id="singleForceDeleteForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="crm-modal-body">
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:14px; margin-bottom:14px; color:#991b1b;">
                    <strong style="font-size:14px; display:block; margin-bottom:4px;">
                        {{ __('crm.permanent_delete_warning') }}
                    </strong>
                    <p style="margin:0; font-size:13px; line-height:1.5;">
                        {{ __('سيتم حذف العميل ومستنداته وسجلاته بالكامل وبشكل نهائي من النظام.') }}
                    </p>
                </div>
                <p style="margin:0; font-size:14px;">
                    {{ __('هل أنت متأكد من الحذف النهائي للعميل:') }} <strong id="singleForceDeleteLeadName" style="color:var(--dark);"></strong>؟
                </p>
            </div>
            <div class="crm-modal-footer">
                <button type="button" class="btn soft" onclick="closeSingleForceDeleteModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn danger">{{ __('crm.permanent_delete') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: BULK RESTORE -->
<div id="bulkRestoreModal" class="crm-modal-shell" role="dialog" aria-modal="true" style="display:none;">
    <div class="crm-modal-dialog">
        <div class="crm-modal-header">
            <h3 style="margin:0; font-size:16px; font-weight:800;">{{ __('استعادة العملاء المحددين') }}</h3>
            <button type="button" class="crm-modal-close-btn" onclick="closeBulkRestoreModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('v2.leads.trash.bulk-restore') }}" id="bulkRestoreForm">
            @csrf
            <div id="bulkRestoreHiddenInputs"></div>
            <div class="crm-modal-body">
                <p style="margin:0 0 14px; font-size:14px;">
                    {{ __('سيتم استعادة') }} <strong id="bulkRestoreCount"></strong> {{ __('عميل.') }}
                </p>
                <div class="form-group">
                    <label>{{ __('crm.restore_to_stage') }} ({{ __('اختياري، اتركه فارغاً للاستعادة إلى المرحلة السابقة') }}):</label>
                    <select name="destination_stage_id" class="form-control">
                        <option value="">{{ __('المرحلة السابقة لكل عميل (تلقائي)') }}</option>
                        @foreach($activeStages as $st)
                            <option value="{{ $st->id }}">{{ $st->localizedName() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="crm-modal-footer">
                <button type="button" class="btn soft" onclick="closeBulkRestoreModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.restore_lead') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: BULK FORCE DELETE -->
<div id="bulkForceDeleteModal" class="crm-modal-shell" role="dialog" aria-modal="true" style="display:none;">
    <div class="crm-modal-dialog">
        <div class="crm-modal-header" style="background:#fef2f2; border-bottom-color:#fecaca;">
            <h3 style="margin:0; font-size:16px; font-weight:800; color:#991b1b;">
                <i class="bi bi-exclamation-triangle-fill"></i> {{ __('الحذف النهائي للعملاء المحددين') }}
            </h3>
            <button type="button" class="crm-modal-close-btn" onclick="closeBulkForceDeleteModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('v2.leads.trash.bulk-force-delete') }}" id="bulkForceDeleteForm">
            @csrf
            <div id="bulkForceDeleteHiddenInputs"></div>
            <div class="crm-modal-body">
                <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:14px; margin-bottom:14px; color:#991b1b;">
                    <strong style="font-size:14px; display:block; margin-bottom:4px;">
                        {{ __('crm.permanent_delete_warning') }}
                    </strong>
                    <p style="margin:0; font-size:13px;">
                        {{ __('سيتم مسح بيانات جميع العملاء المحددين نهائياً من قاعدة البيانات.') }}
                    </p>
                </div>
                <p style="margin:0; font-size:14px;">
                    {{ __('هل أنت متأكد من الحذف النهائي لـ') }} <strong id="bulkForceDeleteCount"></strong> {{ __('عميل؟') }}
                </p>
            </div>
            <div class="crm-modal-footer">
                <button type="button" class="btn soft" onclick="closeBulkForceDeleteModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn danger">{{ __('crm.permanent_delete') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function getSelectedTrashIds() {
    return Array.from(document.querySelectorAll('.trash-item-cb:checked')).map(cb => cb.value);
}

function onTrashItemCheckChange() {
    const selected = getSelectedTrashIds();
    const countSpan = document.getElementById('selectedTrashCount');
    const restoreBtn = document.getElementById('bulkRestoreBtn');
    const forceBtn = document.getElementById('bulkForceDeleteBtn');
    const selectAll = document.getElementById('selectAllTrash');

    const total = document.querySelectorAll('.trash-item-cb').length;
    if (selectAll) {
        selectAll.checked = total > 0 && selected.length === total;
        selectAll.indeterminate = selected.length > 0 && selected.length < total;
    }

    if (countSpan) {
        countSpan.textContent = selected.length > 0 ? `(${selected.length} محدد)` : '';
    }

    if (restoreBtn) restoreBtn.style.display = selected.length > 0 ? 'inline-flex' : 'none';
    if (forceBtn) forceBtn.style.display = selected.length > 0 ? 'inline-flex' : 'none';
}

function toggleSelectAllTrash(master) {
    document.querySelectorAll('.trash-item-cb').forEach(cb => {
        cb.checked = master.checked;
    });
    onTrashItemCheckChange();
}

function openSingleRestoreModal(lead, prevStage, prevStageDeleted) {
    const form = document.getElementById('singleRestoreForm');
    form.action = `/leads/trash/${lead.id}/restore`;

    document.getElementById('singleRestoreLeadName').textContent = lead.name;

    const validBox = document.getElementById('singleRestorePrevValidBox');
    const delBox = document.getElementById('singleRestorePrevDeletedBox');
    const stageSelect = document.getElementById('singleRestoreDestinationStage');

    if (prevStageDeleted) {
        validBox.style.display = 'none';
        delBox.style.display = 'block';
        stageSelect.required = true;
    } else {
        validBox.style.display = 'block';
        delBox.style.display = 'none';
        document.getElementById('singleRestorePrevStageName').textContent = prevStage ? (prevStage.name_ar || prevStage.code) : '';
        if (prevStage) {
            stageSelect.value = prevStage.id;
        }
        stageSelect.required = false;
    }

    const modal = document.getElementById('singleRestoreModal');
    if (modal) modal.style.display = 'flex';
}

function closeSingleRestoreModal() {
    const modal = document.getElementById('singleRestoreModal');
    if (modal) modal.style.display = 'none';
}

function openSingleForceDeleteModal(lead) {
    const form = document.getElementById('singleForceDeleteForm');
    form.action = `/leads/trash/${lead.id}/force-delete`;
    document.getElementById('singleForceDeleteLeadName').textContent = lead.name;
    const modal = document.getElementById('singleForceDeleteModal');
    if (modal) modal.style.display = 'flex';
}

function closeSingleForceDeleteModal() {
    const modal = document.getElementById('singleForceDeleteModal');
    if (modal) modal.style.display = 'none';
}

function openBulkRestoreModal() {
    const selected = getSelectedTrashIds();
    if (selected.length === 0) return;

    document.getElementById('bulkRestoreCount').textContent = selected.length;
    const container = document.getElementById('bulkRestoreHiddenInputs');
    container.innerHTML = '';
    selected.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'lead_ids[]';
        inp.value = id;
        container.appendChild(inp);
    });

    const modal = document.getElementById('bulkRestoreModal');
    if (modal) modal.style.display = 'flex';
}

function closeBulkRestoreModal() {
    const modal = document.getElementById('bulkRestoreModal');
    if (modal) modal.style.display = 'none';
}

function openBulkForceDeleteModal() {
    const selected = getSelectedTrashIds();
    if (selected.length === 0) return;

    document.getElementById('bulkForceDeleteCount').textContent = selected.length;
    const container = document.getElementById('bulkForceDeleteHiddenInputs');
    container.innerHTML = '';
    selected.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'lead_ids[]';
        inp.value = id;
        container.appendChild(inp);
    });

    const modal = document.getElementById('bulkForceDeleteModal');
    if (modal) modal.style.display = 'flex';
}

function closeBulkForceDeleteModal() {
    const modal = document.getElementById('bulkForceDeleteModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('click', (e) => {
    if (e.target && e.target.classList.contains('crm-modal-shell')) {
        closeSingleRestoreModal();
        closeSingleForceDeleteModal();
        closeBulkRestoreModal();
        closeBulkForceDeleteModal();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeSingleRestoreModal();
        closeSingleForceDeleteModal();
        closeBulkRestoreModal();
        closeBulkForceDeleteModal();
    }
});
</script>
@endpush
