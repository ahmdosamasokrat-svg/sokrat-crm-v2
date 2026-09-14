@extends('settings.layout')

@section('title', __('crm.stage_categories_title'))
@section('heading', __('crm.stage_categories_heading'))
@section('subheading', __('crm.stage_categories_subheading'))
@section('page-icon', 'bi-collection')

@section('top-actions')
    <button type="button" class="btn primary" onclick="openAddCategoryModal()">
        <i class="bi bi-plus-lg"></i> {{ __('crm.add_stage_category') }}
    </button>
@endsection

@section('content')
<section class="grid stats-grid" style="margin-bottom: 20px;">
    <article class="stat-card">
        <span>{{ __('crm.total_categories') }}</span>
        <b>{{ $totalCategoriesCount }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.active_categories') }}</span>
        <b style="color: #16a34a">{{ $activeCategoriesCount }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.assigned_stages_count') }}</span>
        <b style="color: #0284c7">{{ $assignedStagesCount }}</b>
    </article>
    <article class="stat-card">
        <span>{{ __('crm.unassigned_stages_count') }}</span>
        <b style="color: {{ $unassignedStagesCount > 0 ? '#f59e0b' : '#64748b' }}">{{ $unassignedStagesCount }}</b>
    </article>
</section>

<!-- CATEGORIES PANEL -->
<section class="panel">
    <div class="panel-head">
        <div>
            <h2><i class="bi bi-collection"></i> {{ __('crm.stage_categories_list') }}</h2>
            <p>{{ __('crm.stage_categories_desc') }}</p>
        </div>
        <div>
            <button type="button" class="btn primary" onclick="openAddCategoryModal()">
                <i class="bi bi-plus-lg"></i> {{ __('crm.add_stage_category') }}
            </button>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:60px">{{ __('crm.order_col') }}</th>
                    <th>{{ __('crm.category_name_col') }}</th>
                    <th>{{ __('crm.color_col') }}</th>
                    <th>{{ __('crm.assigned_stages') }}</th>
                    <th>{{ __('crm.stages_count_col') }}</th>
                    <th>{{ __('crm.status_th') }}</th>
                    <th style="width:160px">{{ __('crm.actions_th') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <span class="badge" style="font-size:14px; font-weight:bold">{{ $category->position }}</span>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; background:{{ $category->color }}1a; color:{{ $category->color }}; font-size:17px; border:1px solid {{ $category->color }}33;">
                                    <i class="bi {{ $category->icon ?: 'bi-collection' }}"></i>
                                </span>
                                <div>
                                    <strong style="font-size:15px">{{ $category->name_ar }}</strong>
                                    @if ($category->name_en)
                                        <small style="color:var(--muted); margin-inline-start:6px">({{ $category->name_en }})</small>
                                    @endif
                                    @if ($category->description_ar)
                                        <small style="display:block; color:var(--muted); margin-top:3px">{{ $category->description_ar }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px">
                                <span style="display:inline-block; width:22px; height:22px; border-radius:6px; background:{{ $category->color }}; border:1px solid rgba(0,0,0,0.1)"></span>
                                <code style="font-size:13px">{{ $category->color }}</code>
                            </div>
                        </td>
                        <td>
                            @if ($category->stages->isNotEmpty())
                                <div style="display:flex; flex-wrap:wrap; gap:5px; max-width:400px;">
                                    @foreach ($category->stages as $stage)
                                        <span class="badge" style="background:{{ $stage->color ? $stage->color.'1a' : '#f1f5f9' }}; color:{{ $stage->color ?: '#475569' }}; border:1px solid {{ $stage->color ? $stage->color.'40' : '#cbd5e1' }}; font-size:12px; font-weight:600;">
                                            @if ($stage->icon)<i class="bi {{ $stage->icon }}"></i>@endif
                                            {{ $stage->localizedName() }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--muted); font-size:13px;">{{ __('crm.no_stages_assigned') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $category->stages_count > 0 ? 'active' : '' }}" style="font-size:13px">
                                {{ $category->stages_count }} {{ __('crm.stages_unit') }}
                            </span>
                        </td>
                        <td>
                            @if ($category->is_active)
                                <span class="badge active">{{ __('crm.active') }}</span>
                            @else
                                <span class="badge inactive">{{ __('crm.inactive') }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:6px; align-items:center">
                                <button type="button" class="btn light icon-btn" title="{{ __('crm.edit') }}"
                                    onclick='openEditCategoryModal(@json($category), @json($category->stages->pluck("id")))'
                                    style="padding: 6px 10px; font-size: 13px;">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button type="button" class="btn light icon-btn" title="{{ __('crm.delete') }}"
                                    onclick='openDeleteCategoryModal(@json($category))'
                                    style="padding: 6px 10px; font-size: 13px; color: var(--red);">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 40px 20px; color: var(--muted);">
                            <i class="bi bi-collection" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            {{ __('crm.no_stage_categories_yet') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<!-- ADD CATEGORY MODAL -->
<div id="addCategoryModal" class="crm-body-modal-shell" style="display:none;" onclick="handleBackdropClick(event, 'addCategoryModal')">
    <div class="crm-body-modal-dialog" style="max-width: 620px;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--line); padding-bottom:12px;">
            <h3 style="margin:0; font-size:18px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-collection-fill" style="color:var(--red);"></i> {{ __('crm.add_stage_category') }}
            </h3>
            <button type="button" onclick="closeModal('addCategoryModal')" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--muted);">&times;</button>
        </div>

        <form action="{{ route('v2.settings.stage_categories.store') }}" method="POST">
            @csrf
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.category_name_ar') }} <span style="color:var(--red)">*</span>
                    </label>
                    <input type="text" name="name_ar" required placeholder="{{ __('crm.category_name_placeholder') }}"
                           style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.category_name_en') }} <small style="color:var(--muted)">({{ __('crm.optional') }})</small>
                    </label>
                    <input type="text" name="name_en" placeholder="e.g. Sales Pipeline"
                           style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                    {{ __('crm.description') }} <small style="color:var(--muted)">({{ __('crm.optional') }})</small>
                </label>
                <textarea name="description_ar" rows="2" placeholder="{{ __('crm.category_description_placeholder') }}"
                          style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark); resize:vertical;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.color_col') }}
                    </label>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="color" id="addCategoryColorPicker" value="#3478f6"
                               onchange="document.getElementById('addCategoryColorInput').value = this.value;"
                               style="width:44px; height:42px; border:1px solid var(--line); border-radius:8px; cursor:pointer; padding:2px; background:none;">
                        <input type="text" name="color" id="addCategoryColorInput" value="#3478f6"
                               oninput="document.getElementById('addCategoryColorPicker').value = this.value;"
                               placeholder="#3478f6"
                               style="flex:1; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark); font-family:monospace;">
                    </div>
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.icon_class') }}
                    </label>
                    <select name="icon" style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                        <option value="bi-collection">{{ __('crm.icon_collection') }} (bi-collection)</option>
                        <option value="bi-folder2-open">{{ __('crm.icon_folder') }} (bi-folder2-open)</option>
                        <option value="bi-tags">{{ __('crm.icon_tags') }} (bi-tags)</option>
                        <option value="bi-funnel">{{ __('crm.icon_funnel') }} (bi-funnel)</option>
                        <option value="bi-briefcase">{{ __('crm.icon_briefcase') }} (bi-briefcase)</option>
                        <option value="bi-diagram-3">{{ __('crm.icon_diagram') }} (bi-diagram-3)</option>
                        <option value="bi-kanban">{{ __('crm.icon_kanban') }} (bi-kanban)</option>
                        <option value="bi-telephone">{{ __('crm.icon_phone') }} (bi-telephone)</option>
                        <option value="bi-headset">{{ __('crm.icon_support') }} (bi-headset)</option>
                        <option value="bi-check2-all">{{ __('crm.icon_check') }} (bi-check2-all)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; font-size:13px;">
                    {{ __('crm.assign_stages_to_category') }}
                </label>
                <div style="border:1px solid var(--line); border-radius:10px; padding:12px; max-height:180px; overflow-y:auto; background:var(--bg); display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    @foreach ($allStages as $stage)
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; padding:4px 8px; border-radius:6px; background:#fff; border:1px solid #e2e8f0;">
                            <input type="checkbox" name="stage_ids[]" value="{{ $stage->id }}">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $stage->color ?: '#94a3b8' }}"></span>
                            <span>{{ $stage->localizedName() }}</span>
                            @if ($stage->category)
                                <small style="color:var(--muted); font-size:11px; margin-inline-start:auto;">({{ $stage->category->name_ar }})</small>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--line); padding-top:16px;">
                <button type="button" class="btn light" onclick="closeModal('addCategoryModal')">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.save_category') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div id="editCategoryModal" class="crm-body-modal-shell" style="display:none;" onclick="handleBackdropClick(event, 'editCategoryModal')">
    <div class="crm-body-modal-dialog" style="max-width: 620px;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid var(--line); padding-bottom:12px;">
            <h3 style="margin:0; font-size:18px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-pencil-square" style="color:var(--red);"></i> {{ __('crm.edit_stage_category') }}
            </h3>
            <button type="button" onclick="closeModal('editCategoryModal')" style="background:none; border:none; font-size:20px; cursor:pointer; color:var(--muted);">&times;</button>
        </div>

        <form id="editCategoryForm" method="POST">
            @csrf
            @method('PATCH')
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.category_name_ar') }} <span style="color:var(--red)">*</span>
                    </label>
                    <input type="text" name="name_ar" id="editCategoryNameAr" required
                           style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.category_name_en') }} <small style="color:var(--muted)">({{ __('crm.optional') }})</small>
                    </label>
                    <input type="text" name="name_en" id="editCategoryNameEn"
                           style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                    {{ __('crm.description') }} <small style="color:var(--muted)">({{ __('crm.optional') }})</small>
                </label>
                <textarea name="description_ar" id="editCategoryDescriptionAr" rows="2"
                          style="width:100%; padding:10px 14px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark); resize:vertical;"></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:14px; margin-bottom:16px;">
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.color_col') }}
                    </label>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="color" id="editCategoryColorPicker" value="#3478f6"
                               onchange="document.getElementById('editCategoryColorInput').value = this.value;"
                               style="width:40px; height:42px; border:1px solid var(--line); border-radius:8px; cursor:pointer; padding:2px; background:none;">
                        <input type="text" name="color" id="editCategoryColorInput" value="#3478f6"
                               oninput="document.getElementById('editCategoryColorPicker').value = this.value;"
                               style="flex:1; padding:10px 10px; border:1px solid var(--line); border-radius:10px; font-size:13px; background:var(--bg); color:var(--dark); font-family:monospace;">
                    </div>
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.icon_class') }}
                    </label>
                    <select name="icon" id="editCategoryIcon" style="width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                        <option value="bi-collection">{{ __('crm.icon_collection') }}</option>
                        <option value="bi-folder2-open">{{ __('crm.icon_folder') }}</option>
                        <option value="bi-tags">{{ __('crm.icon_tags') }}</option>
                        <option value="bi-funnel">{{ __('crm.icon_funnel') }}</option>
                        <option value="bi-briefcase">{{ __('crm.icon_briefcase') }}</option>
                        <option value="bi-diagram-3">{{ __('crm.icon_diagram') }}</option>
                        <option value="bi-kanban">{{ __('crm.icon_kanban') }}</option>
                        <option value="bi-telephone">{{ __('crm.icon_phone') }}</option>
                        <option value="bi-headset">{{ __('crm.icon_support') }}</option>
                        <option value="bi-check2-all">{{ __('crm.icon_check') }}</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:700; font-size:13px;">
                        {{ __('crm.order_col') }}
                    </label>
                    <input type="number" name="position" id="editCategoryPosition" min="1" max="255" required
                           style="width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:10px; font-size:14px; background:var(--bg); color:var(--dark);">
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:flex; align-items:center; gap:8px; font-weight:700; font-size:14px; cursor:pointer;">
                    <input type="checkbox" name="is_active" id="editCategoryIsActive" value="1">
                    <span>{{ __('crm.active_category_label') }}</span>
                </label>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; font-size:13px;">
                    {{ __('crm.assign_stages_to_category') }}
                </label>
                <div style="border:1px solid var(--line); border-radius:10px; padding:12px; max-height:180px; overflow-y:auto; background:var(--bg); display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    @foreach ($allStages as $stage)
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; padding:4px 8px; border-radius:6px; background:#fff; border:1px solid #e2e8f0;">
                            <input type="checkbox" name="stage_ids[]" value="{{ $stage->id }}" class="edit-category-stage-checkbox" data-stage-id="{{ $stage->id }}">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $stage->color ?: '#94a3b8' }}"></span>
                            <span>{{ $stage->localizedName() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--line); padding-top:16px;">
                <button type="button" class="btn light" onclick="closeModal('editCategoryModal')">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary">{{ __('crm.update_category') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CATEGORY MODAL -->
<div id="deleteCategoryModal" class="crm-body-modal-shell" style="display:none;" onclick="handleBackdropClick(event, 'deleteCategoryModal')">
    <div class="crm-body-modal-dialog" style="max-width: 480px;" onclick="event.stopPropagation()">
        <div style="text-align:center; padding: 10px 0 20px;">
            <div style="width:56px; height:56px; border-radius:50%; background:#fee2e2; color:#dc2626; display:inline-flex; align-items:center; justify-content:center; font-size:26px; margin-bottom:14px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h3 style="margin:0 0 8px; font-size:18px; font-weight:800;">{{ __('crm.delete_category_confirm_title') }}</h3>
            <p style="color:var(--muted); font-size:14px; margin:0 0 16px;">
                {{ __('crm.delete_category_confirm_desc') }}: <strong id="deleteCategoryName"></strong>؟
            </p>
            <p style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; font-size:12px; color:#64748b; margin:0;">
                <i class="bi bi-info-circle"></i> {{ __('crm.delete_category_stages_notice') }}
            </p>
        </div>

        <form id="deleteCategoryForm" method="POST">
            @csrf
            @method('DELETE')
            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" class="btn light" onclick="closeModal('deleteCategoryModal')">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn" style="background:#dc2626; color:#fff; border-color:#dc2626;">{{ __('crm.confirm_delete') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'flex';
    document.body.classList.add('modal-open');
}

function openEditCategoryModal(category, assignedStageIds) {
    const form = document.getElementById('editCategoryForm');
    form.action = '{{ url("settings/stage-categories") }}/' + category.id;
    document.getElementById('editCategoryNameAr').value = category.name_ar || '';
    document.getElementById('editCategoryNameEn').value = category.name_en || '';
    document.getElementById('editCategoryDescriptionAr').value = category.description_ar || '';
    document.getElementById('editCategoryColorInput').value = category.color || '#3478f6';
    document.getElementById('editCategoryColorPicker').value = category.color || '#3478f6';
    document.getElementById('editCategoryPosition').value = category.position || 1;
    document.getElementById('editCategoryIcon').value = category.icon || 'bi-collection';
    document.getElementById('editCategoryIsActive').checked = !!category.is_active;

    const assignedSet = new Set((assignedStageIds || []).map(Number));
    document.querySelectorAll('.edit-category-stage-checkbox').forEach(cb => {
        cb.checked = assignedSet.has(Number(cb.dataset.stageId));
    });

    document.getElementById('editCategoryModal').style.display = 'flex';
    document.body.classList.add('modal-open');
}

function openDeleteCategoryModal(category) {
    const form = document.getElementById('deleteCategoryForm');
    form.action = '{{ url("settings/stage-categories") }}/' + category.id;
    document.getElementById('deleteCategoryName').textContent = category.name_ar;
    document.getElementById('deleteCategoryModal').style.display = 'flex';
    document.body.classList.add('modal-open');
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.classList.remove('modal-open');
}

function handleBackdropClick(event, modalId) {
    if (event.target.id === modalId) {
        closeModal(modalId);
    }
}
</script>
@endsection
