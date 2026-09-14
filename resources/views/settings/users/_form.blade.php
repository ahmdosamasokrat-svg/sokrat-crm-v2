@php
    $isEdit = isset($managedUser);
    $selectedGroupIds = collect(old(
        'group_ids',
        $isEdit ? $managedUser->groups->pluck('id')->all() : [],
    ))->map(static fn ($id) => (int) $id);
    $stageAccessMode = old(
        'pipeline_stage_access_mode',
        $isEdit ? $managedUser->pipeline_stage_access_mode : 'all',
    );
    $selectedPipelineStageIds = collect(old(
        'pipeline_stage_ids',
        $isEdit ? $managedUser->pipelineStages->pluck('id')->all() : [],
    ))->map(static fn ($id) => (int) $id);
    $selectedPipelineStageCategoryIds = collect(old(
        'pipeline_stage_category_ids',
        $isEdit ? ($managedUser->pipelineStageCategories->pluck('id')->all() ?? []) : [],
    ))->map(static fn ($id) => (int) $id);
@endphp

<div class="form-grid">
    <div class="field">
        <label for="name">{{ __('crm.display_name') }}</label>
        <input id="name" name="name" required maxlength="150" value="{{ old('name', $managedUser->name ?? '') }}">
    </div>
    <div class="field">
        <label for="username">{{ __('crm.username_label') }}</label>
        <input id="username" name="username" required maxlength="100" dir="ltr" value="{{ old('username', $managedUser->username ?? '') }}">
        <div class="hint">{{ __('crm.username_rules') }}</div>
    </div>
    <div class="field">
        <label for="email">{{ __('crm.email_optional') }}</label>
        <input id="email" name="email" type="email" dir="ltr" value="{{ old('email', $managedUser->email ?? '') }}">
    </div>
    @if(!empty($isVoipConnected))
    <div class="field">
        <label for="voip_extension">{{ __('crm.extension_label') }}</label>
        @php
            $currentExt = (string) old('voip_extension', $managedUser->voip_extension ?? '');
            $foundCurrent = false;
        @endphp
        <select id="voip_extension" name="voip_extension" style="width:100%;padding:10px;border-radius:10px;border:1px solid var(--line)">
            <option value="">{{ __('crm.no_extension_option') }}</option>
            @if(!empty($voipExtensions))
                @foreach($voipExtensions as $ext)
                    @php
                        $extNum = (string) (is_array($ext) ? ($ext['extension'] ?? '') : (is_object($ext) ? ($ext->extension ?? '') : $ext));
                        $extName = is_array($ext) ? ($ext['name'] ?? '') : (is_object($ext) ? ($ext->name ?? '') : '');
                        $isOnline = is_array($ext) ? (!empty($ext['online'])) : (is_object($ext) ? (!empty($ext->online)) : false);

                        if ($currentExt !== '' && $currentExt === $extNum) {
                            $foundCurrent = true;
                        }

                        $statusBadge = $isOnline ? ' [متصل]' : '';
                        $displayName = $extName ? "{$extNum} - {$extName}{$statusBadge}" : $extNum;
                    @endphp
                    <option value="{{ $extNum }}" @selected($currentExt === $extNum)>
                        {{ $displayName }}
                    </option>
                @endforeach
            @endif
            @if($currentExt !== '' && !$foundCurrent)
                <option value="{{ $currentExt }}" selected>
                    {{ $currentExt }} (امتداد مخصص)
                </option>
            @endif
        </select>
    </div>
    @endif
    @unless ($isEdit)
        <div class="field">
            <label for="password">كلمة المرور</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="password_confirmation">{{ __('crm.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
    @endunless

    <div class="field full">
        <label>{{ __('crm.groups') }}</label>
        <div class="checkbox-grid">
            @foreach ($groups as $group)
                <label class="check-card">
                    <input
                        type="checkbox"
                        name="group_ids[]"
                        value="{{ $group->id }}"
                        @checked($selectedGroupIds->contains((int) $group->id))
                    >
                    <span>
                        <strong>{{ $group->name }}</strong>
                        <small>{{ $group->description ?: $group->code }}</small>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="field full">
        <label>{{ __('crm.allowed_pipeline_stages') }}</label>
        <div class="checkbox-grid" style="margin-bottom:12px">
            <label class="check-card">
                <input type="radio" name="pipeline_stage_access_mode" value="all" @checked($stageAccessMode === 'all')>
                <span>
                    <strong>{{ __('crm.all_pipeline_stages') }}</strong>
                    <small>{{ __('crm.all_pipeline_stages_hint') }}</small>
                </span>
            </label>
            <label class="check-card">
                <input type="radio" name="pipeline_stage_access_mode" value="selected" @checked($stageAccessMode === 'selected')>
                <span>
                    <strong>{{ __('crm.selected_pipeline_stages') }}</strong>
                    <small>{{ __('crm.selected_pipeline_stages_hint') }}</small>
                </span>
            </label>
        </div>
        <div id="pipeline-stage-options" style="display:flex; flex-direction:column; gap:16px;">
            @if (isset($pipelineStageCategories) && $pipelineStageCategories->isNotEmpty())
                <div style="background:var(--bg); border:1px solid var(--line); border-radius:12px; padding:16px;">
                    <div style="margin-bottom:10px; font-weight:800; font-size:13px; color:var(--dark); display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-collection-fill" style="color:var(--red);"></i>
                        <span>{{ __('crm.category_level_permissions') }}</span>
                    </div>
                    <div class="checkbox-grid" style="margin-bottom:6px;">
                        @foreach ($pipelineStageCategories as $cat)
                            @php
                                $isCatChecked = $selectedPipelineStageCategoryIds->contains((int) $cat->id);
                            @endphp
                            <label class="check-card" style="border-inline-start:4px solid {{ $cat->color ?: '#3478f6' }}; cursor:pointer;">
                                <input
                                    type="checkbox"
                                    name="pipeline_stage_category_ids[]"
                                    value="{{ $cat->id }}"
                                    class="category-master-checkbox"
                                    data-cat-id="{{ $cat->id }}"
                                    @checked($isCatChecked)
                                    onchange="handleCategoryMasterChange(this)"
                                >
                                <span>
                                    <strong style="display:flex; align-items:center; gap:6px;">
                                        <i class="bi {{ $cat->icon ?: 'bi-collection' }}" style="color:{{ $cat->color }};"></i>
                                        {{ $cat->name_ar }}
                                    </strong>
                                    <small>{{ $cat->activeStages->count() }} {{ __('crm.stages_unit') }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="hint" style="margin-top:6px;">تحديد أي فئة يمنح المستخدم تلقائياً صلاحية الوصول لكافة مراحل تلك الفئة.</div>
                </div>
            @endif

            <div>
                <div style="margin-bottom:10px; font-weight:800; font-size:13px; color:var(--dark); display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-diagram-3-fill" style="color:#0284c7;"></i>
                    <span>{{ __('crm.detailed_stage_permissions') }}</span>
                </div>

                @php
                    $stagesGroupedByCategory = $pipelineStages->groupBy(fn ($s) => $s->pipeline_stage_category_id ?: 'uncategorized');
                @endphp

                <div style="display:flex; flex-direction:column; gap:12px;">
                    @if (isset($pipelineStageCategories))
                        @foreach ($pipelineStageCategories as $cat)
                            @php
                                $catStages = $stagesGroupedByCategory->get($cat->id, collect());
                            @endphp
                            @if ($catStages->isNotEmpty())
                                <div style="background:#fff; border:1px solid var(--line); border-radius:10px; padding:12px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--line);">
                                        <span style="font-weight:700; font-size:13px; display:flex; align-items:center; gap:6px;">
                                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:{{ $cat->color }};"></span>
                                            {{ $cat->name_ar }}
                                        </span>
                                        <button type="button" class="btn small soft" onclick="toggleCategoryStages({{ $cat->id }})" style="font-size:11px; padding:2px 8px;">
                                            تحديد/إلغاء مراحل الفئة
                                        </button>
                                    </div>
                                    <div class="checkbox-grid">
                                        @foreach ($catStages as $stage)
                                            <label class="check-card">
                                                <input
                                                    type="checkbox"
                                                    name="pipeline_stage_ids[]"
                                                    value="{{ $stage->id }}"
                                                    class="stage-checkbox cat-stage-{{ $cat->id }}"
                                                    data-cat-id="{{ $cat->id }}"
                                                    @checked($selectedPipelineStageIds->contains((int) $stage->id) || $selectedPipelineStageCategoryIds->contains((int) $cat->id))
                                                >
                                                <span>
                                                    <strong>{{ $stage->name_ar }}</strong>
                                                    <small style="color:{{ $stage->color }}">{{ $stage->color }}</small>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @php
                        $uncategorizedStages = $stagesGroupedByCategory->get('uncategorized', collect());
                    @endphp
                    @if ($uncategorizedStages->isNotEmpty())
                        <div style="background:#fff; border:1px solid var(--line); border-radius:10px; padding:12px;">
                            <div style="margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid var(--line); font-weight:700; font-size:13px; color:var(--muted);">
                                {{ __('crm.no_category_direct') }}
                            </div>
                            <div class="checkbox-grid">
                                @foreach ($uncategorizedStages as $stage)
                                    <label class="check-card">
                                        <input
                                            type="checkbox"
                                            name="pipeline_stage_ids[]"
                                            value="{{ $stage->id }}"
                                            class="stage-checkbox"
                                            @checked($selectedPipelineStageIds->contains((int) $stage->id))
                                        >
                                        <span>
                                            <strong>{{ $stage->name_ar }}</strong>
                                            <small style="color:{{ $stage->color }}">{{ $stage->color }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @if ($pipelineStages->isEmpty())
            <div class="hint">{{ __('crm.no_active_pipeline_stages') }}</div>
        @else
            <div class="hint">{{ __('crm.dynamic_pipeline_stages_hint') }}</div>
        @endif
    </div>
</div>

<script>
    (() => {
        const modes = document.querySelectorAll('input[name="pipeline_stage_access_mode"]');
        const options = document.getElementById('pipeline-stage-options');
        if (!options) return;

        const syncStageOptions = () => {
            const selectedMode = document.querySelector('input[name="pipeline_stage_access_mode"]:checked')?.value;
            const disabled = selectedMode !== 'selected';
            options.style.opacity = disabled ? '0.55' : '1';
            options.querySelectorAll('input, button').forEach((el) => el.disabled = disabled);
        };

        modes.forEach((mode) => mode.addEventListener('change', syncStageOptions));
        syncStageOptions();
    })();

    function handleCategoryMasterChange(masterCheckbox) {
        const catId = masterCheckbox.dataset.catId;
        const isChecked = masterCheckbox.checked;
        document.querySelectorAll(`.cat-stage-${catId}`).forEach(cb => {
            cb.checked = isChecked;
        });
    }

    function toggleCategoryStages(catId) {
        const checkboxes = document.querySelectorAll(`.cat-stage-${catId}`);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        const master = document.querySelector(`.category-master-checkbox[data-cat-id="${catId}"]`);
        if (master) {
            master.checked = !allChecked;
        }
    }
</script>

<div class="actions" style="margin-top:20px">
    <button type="submit" class="btn primary">{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء المستخدم' }}</button>
    <a class="btn soft" href="{{ route('v2.settings.users.index') }}">{{ __('crm.cancel') }}</a>
</div>
