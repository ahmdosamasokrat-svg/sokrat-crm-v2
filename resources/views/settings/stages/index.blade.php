@extends('settings.layout')

@section('title', __('crm.stages_settings_title'))
@section('heading', __('crm.stages_settings_heading'))
@section('subheading', __('crm.stages_settings_subheading'))
@section('page-icon', 'bi-diagram-3')

@section('top-actions')
    <button type="button" class="btn primary" onclick="openAddStageModal()">
        <i class="bi bi-plus-lg"></i> {{ __('crm.add_additional_stage') }}
    </button>
@endsection

@section('content')
<section class="grid stats-grid" style="margin-bottom: 20px;">
    <article class="stat-card">
        <span>{{ __('crm.total_current_stages') }}</span>
        <b>{{ $totalStagesCount }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.primary_stages_count') }}</span>
        <b>{{ $primaryStagesCount }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.custom_stages_count') }}</span>
        <b>{{ $customStagesCount }}</b>
    </article>
</section>

<!-- STAGES PANEL -->
<section class="panel">
    <div class="panel-head">
        <div>
            <h2><i class="bi bi-diagram-3"></i> {{ __('crm.customer_pipeline_stages') }}</h2>
            <p>{{ __('crm.pipeline_stages_rule_desc') }}</p>
        </div>
        <div>
            <button type="button" class="btn primary" onclick="openAddStageModal()">
                <i class="bi bi-plus-lg"></i> {{ __('crm.add_additional_stage') }}
            </button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:60px">{{ __('crm.order_col') }}</th>
                    <th>{{ __('crm.stage_name_col') }}</th>
                    <th>{{ __('crm.type_col') }}</th>
                    <th>{{ __('crm.color_col') }}</th>
                    <th>{{ __('crm.leads_count_col') }}</th>
                    <th>{{ __('crm.status_th') }}</th>
                    <th style="width:160px">{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stages as $stage)
                    <tr>
                        <td>
                            <span class="badge" style="font-size:14px; font-weight:bold">{{ $stage->position }}</span>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                @if ($stage->icon)
                                    <span style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; background:#f1f5f9; color:#475569; font-size:15px;">
                                        <i class="bi {{ $stage->icon }}"></i>
                                    </span>
                                @endif
                                <div>
                                    <strong style="font-size:15px">{{ $stage->localizedName() }}</strong>
                                    @if ($stage->description_ar)
                                        <small style="display:block; color:var(--muted); margin-top:3px">{{ $stage->description_ar }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($stage->isPrimary())
                                <span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd">
                                    <i class="bi bi-shield-check"></i> {{ __('crm.primary_stage_badge') }}
                                </span>
                            @else
                                <span class="badge" style="background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff">
                                    <i class="bi bi-plus-circle"></i> {{ __('crm.additional_stage_badge') }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px">
                                <span style="display:inline-block; width:22px; height:22px; border-radius:6px; background:{{ $stage->color ?? '#64748b' }}; border:1px solid rgba(0,0,0,0.1)"></span>
                                <code style="font-size:13px">{{ $stage->color ?? '—' }}</code>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $stage->leads_count > 0 ? 'active' : '' }}" style="font-size:13px">
                                {{ number_format($stage->leads_count) }} {{ __('crm.lead_unit') }}
                            </span>
                        </td>
                        <td>
                            @if ($stage->is_active)
                                <span class="badge active">{{ __('crm.stage_active_badge') }}</span>
                            @else
                                <span class="badge inactive">{{ __('crm.stage_inactive_badge') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions" style="display:inline-flex; gap:6px; align-items:center; flex-direction:row;">
                                <a href="{{ route('v2.settings.stages.fields.index', $stage) }}" class="btn small soft" style="color:#4f46e5; border-color:#c7d2fe; background:#eef2ff; padding:0 8px;" title="{{ __('crm.manage_stage_fields') }}" aria-label="{{ __('crm.manage_stage_fields') }}">
                                    <i class="bi bi-ui-checks"></i>
                                    @if (($stage->fields_count ?? 0) > 0)
                                        <span class="badge" style="background:#6366f1; color:#fff; font-size:10px; padding:1px 5px; border-radius:10px; margin-inline-start:2px;">{{ (int) $stage->fields_count }}</span>
                                    @endif
                                </a>
                                <button type="button" class="btn small soft" style="padding:0 8px;" onclick="openEditModal({{ json_encode($stage) }})" title="{{ __('crm.edit') }}" aria-label="{{ __('crm.edit') }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                @if ($stage->leads_count === 0)
                                    <form method="POST" action="{{ route('v2.settings.stages.destroy', $stage) }}" onsubmit="return confirm(@json(__('crm.confirm_delete_stage')))" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn small danger" style="padding:0 8px;" title="{{ __('crm.delete') }}" aria-label="{{ __('crm.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn small danger" style="padding:0 8px;" onclick="openSafeDeleteStageModal({{ json_encode($stage) }}, {{ (int) $stage->leads_count }})" title="حذف المرحلة ونقل/أرشفة العملاء" aria-label="{{ __('crm.delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

@php
$crmStageIcons = [
    ['icon' => 'bi-person', 'name' => 'عميل (شخص)'],
    ['icon' => 'bi-person-plus', 'name' => 'عميل جديد'],
    ['icon' => 'bi-person-check', 'name' => 'عميل مؤكد'],
    ['icon' => 'bi-person-x', 'name' => 'عميل ملغى'],
    ['icon' => 'bi-people', 'name' => 'مجموعة عملاء'],
    ['icon' => 'bi-telephone', 'name' => 'هاتف'],
    ['icon' => 'bi-telephone-outbound', 'name' => 'اتصال صادر'],
    ['icon' => 'bi-telephone-inbound', 'name' => 'اتصال وارد'],
    ['icon' => 'bi-telephone-x', 'name' => 'لم يتم الرد'],
    ['icon' => 'bi-chat-dots', 'name' => 'محادثة'],
    ['icon' => 'bi-whatsapp', 'name' => 'واتساب'],
    ['icon' => 'bi-envelope', 'name' => 'بريد إلكتروني'],
    ['icon' => 'bi-check-circle', 'name' => 'مكتمل ومؤكد'],
    ['icon' => 'bi-x-circle', 'name' => 'غير مهتم'],
    ['icon' => 'bi-clock-history', 'name' => 'متابعة لاحقة'],
    ['icon' => 'bi-calendar-check', 'name' => 'موعد محدد'],
    ['icon' => 'bi-calendar-event', 'name' => 'حدث ومقابلة'],
    ['icon' => 'bi-arrow-repeat', 'name' => 'متابعة دورية'],
    ['icon' => 'bi-hourglass-split', 'name' => 'قيد الانتظار'],
    ['icon' => 'bi-star', 'name' => 'مميز'],
    ['icon' => 'bi-star-fill', 'name' => 'نجم'],
    ['icon' => 'bi-flag', 'name' => 'راية ومرحلة'],
    ['icon' => 'bi-bookmark', 'name' => 'علامة'],
    ['icon' => 'bi-pin-angle', 'name' => 'تثبيت'],
    ['icon' => 'bi-bell', 'name' => 'تنبيه'],
    ['icon' => 'bi-hand-thumbs-up', 'name' => 'موافقة'],
    ['icon' => 'bi-briefcase', 'name' => 'أعمال وشركات'],
    ['icon' => 'bi-building', 'name' => 'مؤسسة'],
    ['icon' => 'bi-patch-check', 'name' => 'معتمد'],
    ['icon' => 'bi-tag', 'name' => 'تصنيف'],
    ['icon' => 'bi-sliders', 'name' => 'تخصيص'],
];
@endphp

<!-- MODAL: ADD STAGE -->
<div id="addStageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:520px; width:100%; max-height:calc(100dvh - 36px); overflow-y:auto; padding:20px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;">{{ __('crm.add_stage_modal_title') }}</h3>
            <button type="button" onclick="document.getElementById('addStageModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="{{ route('v2.settings.stages.store') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.stage_name_label') }} <span style="color:var(--red)">*</span></label>
                <input type="text" name="name_ar" required placeholder="{{ __('crm.stage_name_label') }}" autofocus>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.stage_color_hex') }}</label>
                    <div style="display:flex; gap:8px;">
                        <input type="color" id="stageColorPicker" value="#7b61df" style="width:48px; height:42px; padding:2px;" onchange="document.getElementById('stageColorInput').value=this.value">
                        <input type="text" id="stageColorInput" name="color" value="#7b61df" placeholder="#7b61df" pattern="^#[0-9a-fA-F]{6}$" onchange="document.getElementById('stageColorPicker').value=this.value">
                    </div>
                </div>
                <div>
                    <label>{{ __('crm.icon_optional') }}</label>
                    <div class="icon-picker-wrap" id="addStageIconWrap" style="position:relative;">
                        <input type="hidden" id="addStageIconInput" name="icon" value="">
                        <button type="button" class="icon-picker-btn" id="addStageIconBtn" onclick="toggleIconDropdown('addStage')" style="width:100%; height:42px; display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid #dbe1e9; border-radius:10px; padding:0 12px; background:#fff; color:var(--ink); cursor:pointer; text-align:start;">
                            <span style="display:flex; align-items:center; gap:8px; min-width:0; overflow:hidden;">
                                <span class="icon-preview-box" id="addStageIconPreview" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#475569; font-size:15px; flex-shrink:0;">
                                    <i class="bi bi-slash-circle" style="font-size:13px; color:#94a3b8;"></i>
                                </span>
                                <span class="icon-selected-label" id="addStageIconLabel" style="font-size:13px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ __('crm.choose_icon_optional') }}
                                </span>
                            </span>
                            <span style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                                <span class="icon-clear-btn" id="addStageIconClear" onclick="event.stopPropagation(); clearIconSelection('addStage')" style="display:none; color:#94a3b8; font-size:14px; padding:2px 4px; border-radius:4px; cursor:pointer;" title="{{ __('crm.no_icon') }}">&times;</span>
                                <i class="bi bi-chevron-down" style="font-size:12px; color:#94a3b8;"></i>
                            </span>
                        </button>
                        <div class="icon-picker-dropdown" id="addStageIconDropdown" style="display:none; position:absolute; top:calc(100% + 4px); inset-inline-start:0; width:340px; max-width:min(340px, calc(100vw - 60px)); background:#fff; border:1px solid #dbe1e9; border-radius:12px; box-shadow:0 12px 32px rgba(0,0,0,0.15); padding:10px; z-index:1000;">
                            <div style="display:grid; grid-template-columns:repeat(6, 1fr); gap:6px; max-height:210px; overflow-y:auto; padding:2px;">
                                @foreach($crmStageIcons as $ico)
                                    <button type="button" class="icon-option-btn" onclick="selectIcon('addStage', '{{ $ico['icon'] }}', '{{ $ico['name'] }}')" title="{{ $ico['name'] }}" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; color:#334155; font-size:16px; cursor:pointer; transition:all 0.15s ease;">
                                        <i class="bi {{ $ico['icon'] }}"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label>{{ __('crm.description_optional') }}</label>
                <textarea name="description_ar" rows="2" placeholder="{{ __('crm.description_optional') }}"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('addStageModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.save_stage') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT STAGE -->
<div id="editStageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:520px; width:100%; max-height:calc(100dvh - 36px); overflow-y:auto; padding:20px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;">{{ __('crm.edit_stage_modal_title') }}</h3>
            <button type="button" onclick="document.getElementById('editStageModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>
        <form id="editStageForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.stage_name_label') }} <span style="color:var(--red)">*</span></label>
                <input type="text" id="editStageName" name="name_ar" required>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.order_col') }} <span style="color:var(--red)">*</span></label>
                    <input type="number" id="editStagePosition" name="position" min="1" max="255" required>
                </div>
                <div>
                    <label>{{ __('crm.stage_color_hex') }}</label>
                    <div style="display:flex; gap:8px;">
                        <input type="color" id="editStageColorPicker" value="#7b61df" style="width:48px; height:42px; padding:2px;" onchange="document.getElementById('editStageColorInput').value=this.value">
                        <input type="text" id="editStageColorInput" name="color" placeholder="#7b61df" pattern="^#[0-9a-fA-F]{6}$" onchange="document.getElementById('editStageColorPicker').value=this.value">
                    </div>
                </div>
            </div>
            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.icon_optional') }}</label>
                    <div class="icon-picker-wrap" id="editStageIconWrap" style="position:relative;">
                        <input type="hidden" id="editStageIconInput" name="icon" value="">
                        <button type="button" class="icon-picker-btn" id="editStageIconBtn" onclick="toggleIconDropdown('editStage')" style="width:100%; height:42px; display:flex; align-items:center; justify-content:space-between; gap:8px; border:1px solid #dbe1e9; border-radius:10px; padding:0 12px; background:#fff; color:var(--ink); cursor:pointer; text-align:start;">
                            <span style="display:flex; align-items:center; gap:8px; min-width:0; overflow:hidden;">
                                <span class="icon-preview-box" id="editStageIconPreview" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#f1f5f9; color:#475569; font-size:15px; flex-shrink:0;">
                                    <i class="bi bi-slash-circle" style="font-size:13px; color:#94a3b8;"></i>
                                </span>
                                <span class="icon-selected-label" id="editStageIconLabel" style="font-size:13px; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    {{ __('crm.choose_icon_optional') }}
                                </span>
                            </span>
                            <span style="display:flex; align-items:center; gap:4px; flex-shrink:0;">
                                <span class="icon-clear-btn" id="editStageIconClear" onclick="event.stopPropagation(); clearIconSelection('editStage')" style="display:none; color:#94a3b8; font-size:14px; padding:2px 4px; border-radius:4px; cursor:pointer;" title="{{ __('crm.no_icon') }}">&times;</span>
                                <i class="bi bi-chevron-down" style="font-size:12px; color:#94a3b8;"></i>
                            </span>
                        </button>
                        <div class="icon-picker-dropdown" id="editStageIconDropdown" style="display:none; position:absolute; top:calc(100% + 4px); inset-inline-start:0; width:340px; max-width:min(340px, calc(100vw - 60px)); background:#fff; border:1px solid #dbe1e9; border-radius:12px; box-shadow:0 12px 32px rgba(0,0,0,0.15); padding:10px; z-index:1000;">
                            <div style="display:grid; grid-template-columns:repeat(6, 1fr); gap:6px; max-height:210px; overflow-y:auto; padding:2px;">
                                @foreach($crmStageIcons as $ico)
                                    <button type="button" class="icon-option-btn" onclick="selectIcon('editStage', '{{ $ico['icon'] }}', '{{ $ico['name'] }}')" title="{{ $ico['name'] }}" style="width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; color:#334155; font-size:16px; cursor:pointer; transition:all 0.15s ease;">
                                        <i class="bi {{ $ico['icon'] }}"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div id="editStageActiveWrap" style="display:flex; align-items:center; margin-top:24px;">
                    <label class="check-card" style="margin:0; width:100%; cursor:pointer;">
                        <input type="checkbox" id="editStageIsActive" name="is_active" value="1">
                        <div>
                            <strong>{{ __('crm.stage_active_label') }}</strong>
                            <small>{{ __('crm.stage_active_desc') }}</small>
                        </div>
                    </label>
                </div>
            </div>
            <div style="margin-bottom:18px;">
                <label>{{ __('crm.description_optional') }}</label>
                <textarea id="editStageDescription" name="description_ar" rows="2"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn soft" onclick="document.getElementById('editStageModal').style.display='none'">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
<!-- MODAL: SAFE DELETE STAGE WITH LEADS -->
<div id="safeDeleteStageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:520px; width:100%; max-height:calc(100dvh - 36px); overflow-y:auto; padding:20px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px; color:var(--red);"><i class="bi bi-exclamation-triangle"></i> حذف المرحلة: <span id="safeDeleteStageName"></span></h3>
            <button type="button" onclick="closeSafeDeleteStageModal()" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <form id="safeDeleteStageForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px; margin-bottom:16px; color:#92400e; font-size:13px; line-height:1.6;">
                تحتوي هذه المرحلة حالياً على <strong><span id="safeDeleteLeadsCount"></span> عميل</strong>. لحذف المرحلة بأمان دون فقدان البيانات، يرجى اختيار الإجراء المطلوب:
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:700; margin-bottom:8px;">
                    <input type="radio" name="lead_action" value="move" checked onchange="onSafeDeleteActionChange(this)">
                    <span>أ) نقل العملاء إلى مرحلة نشطة أخرى</span>
                </label>
                <div id="safeDeleteMoveGroup" style="margin-inline-start:24px; margin-top:6px;">
                    <select name="destination_stage_id" id="safeDeleteDestinationStage" style="width:100%; height:38px; border-radius:8px; border:1px solid var(--line); padding:0 10px;">
                        @foreach ($stages as $stgOption)
                            @if (! $stgOption->isPrimary())
                                <option value="{{ $stgOption->id }}">{{ $stgOption->localizedName() }} ({{ $stgOption->leads_count }} عميل)</option>
                            @else
                                <option value="{{ $stgOption->id }}">{{ $stgOption->localizedName() }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:700;">
                    <input type="radio" name="lead_action" value="trash" onchange="onSafeDeleteActionChange(this)">
                    <span style="color:#b91c1c;">ب) نقل جميع عملاء المرحلة إلى سلة المهملات (Trash)</span>
                </label>
                <small style="display:block; margin-inline-start:24px; color:var(--muted); font-size:11px;">يمكنك استعادة العملاء لاحقاً من صفحة سلة المهملات.</small>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--line); padding-top:14px;">
                <button type="button" class="btn soft" onclick="closeSafeDeleteStageModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn danger" onclick="return confirm('هل أنت متأكد من تنفيذ هذا الإجراء وحذف المرحلة؟')">تأكيد الحذف</button>
            </div>
        </form>
    </div>
</div>

<script>
const crmIconsMap = @json(collect($crmStageIcons)->keyBy('icon'));

function toggleIconDropdown(prefix) {
    const dd = document.getElementById(prefix + 'IconDropdown');
    if (!dd) return;
    const isShown = dd.style.display === 'block';
    // Close any other open dropdowns
    document.querySelectorAll('.icon-picker-dropdown').forEach(d => d.style.display = 'none');
    dd.style.display = isShown ? 'none' : 'block';
}

function selectIcon(prefix, iconClass, iconName) {
    const input = document.getElementById(prefix + 'IconInput');
    const preview = document.getElementById(prefix + 'IconPreview');
    const label = document.getElementById(prefix + 'IconLabel');
    const clear = document.getElementById(prefix + 'IconClear');
    const dd = document.getElementById(prefix + 'IconDropdown');

    if (input) input.value = iconClass;
    if (preview) preview.innerHTML = `<i class="bi ${iconClass}" style="color:var(--red);"></i>`;
    if (label) {
        label.textContent = iconName || iconClass;
        label.style.color = 'var(--ink)';
        label.style.fontWeight = 'bold';
    }
    if (clear) clear.style.display = 'inline-block';
    if (dd) dd.style.display = 'none';
}

function clearIconSelection(prefix) {
    const input = document.getElementById(prefix + 'IconInput');
    const preview = document.getElementById(prefix + 'IconPreview');
    const label = document.getElementById(prefix + 'IconLabel');
    const clear = document.getElementById(prefix + 'IconClear');

    if (input) input.value = '';
    if (preview) preview.innerHTML = '<i class="bi bi-slash-circle" style="font-size:13px; color:#94a3b8;"></i>';
    if (label) {
        label.textContent = @json(__('crm.choose_icon_optional'));
        label.style.color = '#64748b';
        label.style.fontWeight = 'normal';
    }
    if (clear) clear.style.display = 'none';
}

// Close icon picker dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.icon-picker-wrap')) {
        document.querySelectorAll('.icon-picker-dropdown').forEach(d => d.style.display = 'none');
    }
});

function openAddStageModal() {
    clearIconSelection('addStage');
    document.getElementById('stageColorPicker').value = '#7b61df';
    document.getElementById('stageColorInput').value = '#7b61df';
    document.getElementById('addStageModal').style.display = 'flex';
}

function openEditModal(stage) {
    const form = document.getElementById('editStageForm');
    form.action = `/settings/stages/${stage.id}`;
    document.getElementById('editStageName').value = stage.name_ar || '';
    document.getElementById('editStagePosition').value = stage.position || 1;
    document.getElementById('editStageColorInput').value = stage.color || '#3478f6';
    document.getElementById('editStageColorPicker').value = stage.color || '#3478f6';
    document.getElementById('editStageDescription').value = stage.description_ar || '';

    // Handle Icon in edit modal
    if (stage.icon && stage.icon.trim() !== '') {
        const icoInfo = crmIconsMap[stage.icon];
        selectIcon('editStage', stage.icon, icoInfo ? icoInfo.name : stage.icon);
    } else {
        clearIconSelection('editStage');
    }

    const activeWrap = document.getElementById('editStageActiveWrap');
    const activeCb = document.getElementById('editStageIsActive');

    if (stage.is_primary) {
        activeWrap.style.display = 'none';
        activeCb.checked = true;
    } else {
        activeWrap.style.display = 'flex';
        activeCb.checked = !!stage.is_active;
    }

    document.getElementById('editStageModal').style.display = 'flex';
}

function openSafeDeleteStageModal(stage, count) {
    const form = document.getElementById('safeDeleteStageForm');
    form.action = `/settings/stages/${stage.id}`;
    document.getElementById('safeDeleteStageName').textContent = stage.name_ar;
    document.getElementById('safeDeleteLeadsCount').textContent = count;

    const select = document.getElementById('safeDeleteDestinationStage');
    if (select) {
        Array.from(select.options).forEach(opt => {
            const isSelf = String(opt.value) === String(stage.id);
            opt.disabled = isSelf;
            opt.hidden = isSelf;
        });
        const firstValid = Array.from(select.options).find(opt => !opt.disabled);
        if (firstValid) select.value = firstValid.value;
    }

    document.getElementById('safeDeleteStageModal').style.display = 'flex';
}

function onSafeDeleteActionChange(radio) {
    const moveGroup = document.getElementById('safeDeleteMoveGroup');
    if (radio.value === 'move') {
        moveGroup.style.display = 'block';
        document.getElementById('safeDeleteDestinationStage').required = true;
    } else {
        moveGroup.style.display = 'none';
        document.getElementById('safeDeleteDestinationStage').required = false;
    }
}
</script>
@endsection
