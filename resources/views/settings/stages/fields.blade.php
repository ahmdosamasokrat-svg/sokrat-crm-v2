@extends('settings.layout')

@section('title', __('crm.stage_fields_title', ['stage' => $stage->localizedName()]))
@section('heading', __('crm.stage_fields_heading', ['stage' => $stage->localizedName()]))
@section('subheading', __('crm.stage_fields_subheading'))
@section('page-icon', 'bi-ui-checks')
@section('back-url', route('v2.settings.stages.index'))
@section('back-title', __('crm.back_to_stages'))

@section('top-actions')
    <button type="button" class="btn soft" onclick="openPresetsModal()">
        <i class="bi bi-magic"></i> {{ __('crm.use_preset_template') }}
    </button>
    <button type="button" class="btn primary" onclick="openAddQuestionModal()">
        <i class="bi bi-plus-lg"></i> {{ __('crm.add_question_btn') }}
    </button>
@endsection

@section('content')
<style>
.stage-q-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.stage-q-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:10px;background:#fff;border:1px solid var(--line);min-height:44px}
.stage-q-grid{display:grid;grid-template-columns:1.4fr 1fr;gap:20px}
.question-cards{display:flex;flex-direction:column;gap:12px}
.q-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:14px;box-shadow:0 2px 6px rgba(0,0,0,0.02);transition:all .15s ease}
.q-card:hover{border-color:#cbd5e1;box-shadow:0 4px 12px rgba(0,0,0,0.04)}
.q-card.inactive{opacity:0.6;background:#f8fafc}
.q-info{display:flex;align-items:flex-start;gap:14px;min-width:0;flex:1}
.q-type-icon{width:40px;height:40px;border-radius:10px;background:#f1f5f9;color:#475569;display:grid;place-items:center;font-size:18px;flex-shrink:0}
.q-details h4{margin:0 0 4px;font-size:15px;color:var(--dark);font-weight:800}
.q-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;color:var(--muted)}
.q-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}
.q-actions .btn{min-height:40px;min-width:40px;padding:0 10px;touch-action:manipulation}
.preview-panel{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;position:sticky;top:20px}
.preview-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line)}
.preview-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px}
.accordion-toggle{background:none;border:none;padding:8px 0;min-height:44px;color:#4f46e5;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;touch-action:manipulation}
.options-builder-table{width:100%;margin-top:8px}
.options-builder-table input{padding:8px 10px;font-size:13px;min-height:40px}
.option-row-item{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:center}
.preset-card{border:1px solid var(--line);border-radius:12px;padding:16px;cursor:pointer;background:#fff;transition:all .15s ease}
.preset-card:hover{border-color:#4f46e5;background:#f5f3ff}
@media(max-width:1024px){.stage-q-grid{grid-template-columns:1fr}.preview-panel{position:static}}
@media(max-width:640px){
    .q-card{flex-direction:column;align-items:stretch;gap:12px;padding:14px}
    .q-actions{justify-content:flex-end;width:100%;border-top:1px solid var(--line);padding-top:10px}
    #conditionInputsRow{grid-template-columns:1fr !important}
    .option-row-item{grid-template-columns:1fr;background:#fff;padding:10px;border-radius:8px;border:1px solid #e2e8f0}
    .stage-q-header>div:last-child{width:100%;display:flex;gap:8px}
    .stage-q-header>div:last-child .btn{flex:1;justify-content:center}
}
</style>



<div class="stage-q-grid">
    <!-- LEFT: QUESTIONS LIST -->
    <div>
        <section class="panel">
            <div class="panel-head">
                <div>
                    <h2 style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <i class="bi bi-ui-checks"></i>
                        <span>{{ __('crm.stage_questions_heading') }}</span>
                        <span class="badge" style="font-size:12px; border:1px solid var(--line); background:var(--card); color:var(--dark);">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:{{ $stage->color ?? '#64748b' }}; margin-inline-end:4px;"></span>
                            {{ $stage->localizedName() }}
                            <span class="badge {{ $stage->isPrimary() ? 'system' : '' }}" style="font-size:10px; margin-inline-start:4px;">
                                {{ $stage->isPrimary() ? __('crm.primary_stage_badge') : __('crm.additional_stage_badge') }}
                            </span>
                        </span>
                    </h2>
                    <p>{{ __('crm.stage_questions_subheading') }}</p>
                </div>
            </div>

            @if ($fields->isEmpty())
                <div style="text-align:center; padding: 48px 20px; color:var(--muted);">
                    <i class="bi bi-chat-square-text" style="font-size:42px; display:block; margin-bottom:12px; color:#cbd5e1;"></i>
                    <h3 style="margin:0 0 6px; font-size:16px; color:#334155;">{{ __('crm.no_stage_questions_title') }}</h3>
                    <p style="font-size:13px; margin:0 0 18px;">{{ __('crm.no_stage_questions_desc') }}</p>
                    <div style="display:flex; justify-content:center; gap:10px;">
                        <button type="button" class="btn primary small" onclick="openAddQuestionModal()">
                            <i class="bi bi-plus-lg"></i> {{ __('crm.add_first_question') }}
                        </button>
                        <button type="button" class="btn soft small" onclick="openPresetsModal()">
                            <i class="bi bi-magic"></i> {{ __('crm.use_preset_template') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="question-cards" id="questionsContainer">
                    @foreach ($fields as $field)
                        @php
                            $typeIcon = match($field->type) {
                                'datetime', 'date' => 'bi-calendar-event',
                                'select', 'multiselect' => 'bi-menu-button-wide',
                                'checkbox' => 'bi-check-square',
                                'number' => 'bi-hash',
                                'textarea' => 'bi-textarea-t',
                                'tel' => 'bi-telephone',
                                'email' => 'bi-envelope',
                                default => 'bi-fonts',
                            };
                            $typeLabel = __('crm.field_type_' . $field->type) ?? $field->type;
                            $hasCondition = !empty($field->conditions) && !empty($field->conditions['field']);
                            $condField = $hasCondition ? $fields->firstWhere('key', $field->conditions['field']) : null;
                        @endphp

                        <article class="q-card {{ $field->is_active ? '' : 'inactive' }}" data-field-id="{{ $field->id }}">
                            <div class="q-info">
                                <div class="q-type-icon">
                                    <i class="bi {{ $typeIcon }}"></i>
                                </div>
                                <div class="q-details">
                                    <h4>{{ $field->localizedLabel() }}</h4>
                                    <div class="q-meta">
                                        <span><i class="bi bi-tag"></i> {{ $typeLabel }}</span>
                                        <span>•</span>
                                        @if ($field->is_required)
                                            <span style="color:#b91c1c; font-weight:800;"><i class="bi bi-asterisk" style="font-size:9px"></i> {{ __('crm.required') }}</span>
                                        @else
                                            <span>{{ __('crm.optional') }}</span>
                                        @endif

                                        @if ($hasCondition)
                                            <span>•</span>
                                            <span class="badge" style="background:#fdf4ff; color:#a21caf; border:1px solid #f5d0fe;">
                                                <i class="bi bi-diagram-2"></i> {{ __('crm.conditional_rule_summary', ['field' => $condField?->localizedLabel() ?? $field->conditions['field']]) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="q-actions">
                                <button type="button" class="btn small soft" onclick="openEditQuestionModal({{ json_encode($field) }})" title="{{ __('crm.edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('v2.settings.stages.fields.toggle', [$stage, $field]) }}" style="margin:0;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn small soft" title="{{ $field->is_active ? __('crm.deactivate_action') : __('crm.activate_action') }}">
                                        <i class="bi {{ $field->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('v2.settings.stages.fields.destroy', [$stage, $field]) }}" style="margin:0;" onsubmit="return confirm(@json($field->values_count > 0 ? __('crm.stage_field_archive_confirm') : __('crm.confirm_delete_stage_field')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn small danger" title="{{ __('crm.delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <!-- RIGHT: LIVE EMPLOYEE PREVIEW -->
    <div>
        <aside class="preview-panel">
            <div class="preview-head">
                <div>
                    <h3 style="margin:0; font-size:15px; color:#1e293b;"><i class="bi bi-eye"></i> {{ __('crm.employee_transition_preview_title') }}</h3>
                    <small style="color:var(--muted)">{{ __('crm.employee_transition_preview_desc') }}</small>
                </div>
                <span class="badge active" style="font-size:10px;">{{ __('crm.live_preview') }}</span>
            </div>

            <div class="preview-box">
                <div style="margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:{{ $stage->color ?? '#3478f6' }};"></span>
                    <strong style="font-size:13px;">{{ __('crm.move_to_stage_preview', ['stage' => $stage->localizedName()]) }}</strong>
                </div>

                @if ($fields->where('is_active', true)->isEmpty())
                    <p style="color:var(--muted); font-size:12px; text-align:center; margin:16px 0;">
                        <i class="bi bi-check-circle" style="font-size:20px; display:block; margin-bottom:6px; color:#94a3b8;"></i>
                        {{ __('crm.no_extra_questions_for_stage') }}
                    </p>
                @else
                    @include('partials.stage-field-inputs', [
                        'fields' => $fields->where('is_active', true)->values(),
                        'recordValues' => [],
                        'prefix' => 'preview_fields',
                        'scope' => 'admin_live_preview',
                    ])
                @endif
            </div>
        </aside>
    </div>
</div>

<!-- MODAL: ADD / EDIT QUESTION -->
<div id="questionModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:620px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="margin:0; font-size:18px;" id="questionModalTitle">{{ __('crm.add_question_modal_title') }}</h3>
            <button type="button" onclick="closeQuestionModal()" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>

        <form id="questionForm" method="POST" action="">
            @csrf
            <div id="methodContainer"></div>

            <!-- CORE QUESTION DETAILS -->
            <div style="margin-bottom:14px;">
                <label>{{ __('crm.question_label_ar_field') }} <span style="color:var(--red)">*</span></label>
                <input type="text" id="qLabelAr" name="label_ar" required placeholder="{{ __('crm.question_label_ar_hint') }}" autofocus>
            </div>

            <div class="form-grid" style="margin-bottom:14px;">
                <div>
                    <label>{{ __('crm.answer_type_label') }} <span style="color:var(--red)">*</span></label>
                    <select name="type" id="qType" required onchange="handleAnswerTypeChange(this.value)">
                        <option value="text">{{ __('crm.type_short_text') }}</option>
                        <option value="textarea">{{ __('crm.type_long_text') }}</option>
                        <option value="datetime">{{ __('crm.type_datetime') }}</option>
                        <option value="date">{{ __('crm.type_date') }}</option>
                        <option value="select">{{ __('crm.type_dropdown') }}</option>
                        <option value="multiselect">{{ __('crm.type_multiselect') }}</option>
                        <option value="checkbox">{{ __('crm.type_yes_no') }}</option>
                        <option value="number">{{ __('crm.type_number') }}</option>
                        <option value="tel">{{ __('crm.type_phone') }}</option>
                        <option value="email">{{ __('crm.type_email') }}</option>
                        <option value="url">{{ __('crm.type_url') }}</option>
                    </select>
                </div>
                <div>
                    <label style="margin-bottom:8px;">{{ __('crm.requirement_label') }}</label>
                    <label class="check-card" style="cursor:pointer; padding:10px; margin:0;">
                        <input type="checkbox" id="qIsRequired" name="is_required" value="1">
                        <div>
                            <strong style="font-size:13px;">{{ __('crm.is_required_question') }}</strong>
                            <small>{{ __('crm.is_required_question_hint') }}</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- INTERACTIVE OPTIONS BUILDER -->
            <div id="optionsBuilderWrap" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label style="margin:0; font-size:13px; font-weight:800;">{{ __('crm.options_list_title') }}</label>
                    <button type="button" class="btn small soft" onclick="addOptionRow()" style="background:#fff;">
                        <i class="bi bi-plus"></i> {{ __('crm.add_option_btn') }}
                    </button>
                </div>
                <div id="optionsListContainer" style="display:flex; flex-direction:column; gap:8px;"></div>
                <small class="hint">{{ __('crm.options_builder_hint') }}</small>
            </div>

            <!-- ADVANCED OPTIONS TOGGLE -->
            <div style="margin-top:16px; margin-bottom:16px; border-top:1px solid var(--line); padding-top:12px;">
                <button type="button" class="accordion-toggle" onclick="toggleAdvancedOptions()">
                    <i class="bi bi-sliders"></i> <span id="advancedOptionsToggleText">{{ __('crm.show_advanced_options') }}</span>
                    <i class="bi bi-chevron-down" id="advancedOptionsChevron"></i>
                </button>

                <div id="advancedOptionsContent" style="display:none; margin-top:14px;">
                    <div class="form-grid" style="margin-bottom:14px;">
                        <div>
                            <label style="font-size:12px;">{{ __('crm.question_label_en_field') }}</label>
                            <input type="text" id="qLabelEn" name="label_en" placeholder="e.g. Next Callback Date">
                        </div>
                        <div>
                            <label style="font-size:12px;">{{ __('crm.question_placeholder_field') }}</label>
                            <input type="text" id="qPlaceholder" name="placeholder_ar" placeholder="{{ __('crm.optional_placeholder_hint') }}">
                        </div>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="font-size:12px;">{{ __('crm.question_help_text_field') }}</label>
                        <input type="text" id="qHelpText" name="help_text_ar" placeholder="{{ __('crm.optional_help_text_hint') }}">
                    </div>

                    <!-- NATURAL SENTENCE CONDITION BUILDER -->
                    <div style="background:#f1f5f9; border-radius:10px; padding:12px; margin-bottom:14px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-bottom:0; font-size:13px; font-weight:700;">
                            <input type="checkbox" id="qHasCondition" onchange="toggleConditionInputs(this.checked)">
                            <span><i class="bi bi-diagram-2"></i> {{ __('crm.show_this_question_when') }}</span>
                        </label>

                        <div id="conditionInputsRow" style="display:none; margin-top:10px; grid-template-columns:1.5fr 1fr 1.5fr; gap:8px;">
                            <div>
                                <select id="qCondField" name="condition_field">
                                    <option value="">{{ __('crm.select_previous_question') }}</option>
                                    @foreach($otherFields as $of)
                                        <option value="{{ $of->key }}">{{ $of->localizedLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select id="qCondOperator" name="condition_operator" onchange="handleOperatorChange(this.value)">
                                    <option value="equals">{{ __('crm.operator_equals') }}</option>
                                    <option value="not_equals">{{ __('crm.operator_not_equals') }}</option>
                                    <option value="is_checked">{{ __('crm.operator_is_checked') }}</option>
                                    <option value="is_not_checked">{{ __('crm.operator_is_not_checked') }}</option>
                                    <option value="is_empty">{{ __('crm.operator_is_empty') }}</option>
                                    <option value="is_not_empty">{{ __('crm.operator_is_not_empty') }}</option>
                                </select>
                            </div>
                            <div id="qCondValueWrap">
                                <input type="text" id="qCondValue" name="condition_value" placeholder="{{ __('crm.expected_value_placeholder') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px; border-top:1px solid var(--line); padding-top:16px;">
                <button type="button" class="btn soft" onclick="closeQuestionModal()">{{ __('crm.cancel') }}</button>
                <button type="submit" class="btn primary" id="questionSubmitBtn">{{ __('crm.save_question_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: PRESETS -->
<div id="presetsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center; padding:18px;">
    <div style="background:#fff; border-radius:16px; max-width:680px; width:100%; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <div>
                <h3 style="margin:0; font-size:18px;">{{ __('crm.preset_modal_title') }}</h3>
                <small style="color:var(--muted)">{{ __('crm.preset_modal_desc') }}</small>
            </div>
            <button type="button" onclick="document.getElementById('presetsModal').style.display='none'" style="border:0; background:transparent; font-size:22px; cursor:pointer">&times;</button>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:14px; margin-bottom:20px;">
            @foreach($presets as $pKey => $pData)
                <div class="preset-card" onclick="selectPreset('{{ $pKey }}')">
                    <h4 style="margin:0 0 6px; font-size:14px; color:#1e293b; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-collection-play" style="color:#4f46e5;"></i>
                        {{ $pData['name_ar'] }}
                    </h4>
                    <p style="margin:0 0 10px; font-size:12px; color:var(--muted); line-height:1.4;">
                        {{ $pData['description_ar'] }}
                    </p>
                    <div style="display:flex; flex-wrap:wrap; gap:4px;">
                        @foreach($pData['fields'] as $pf)
                            <span class="badge" style="font-size:10px; background:#f1f5f9; color:#475569;">
                                {{ $pf['label_ar'] }} ({{ __('crm.field_type_' . $pf['type']) ?? $pf['type'] }})
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <form id="presetForm" method="POST" action="{{ route('v2.settings.stages.fields.preset', $stage) }}">
            @csrf
            <input type="hidden" name="preset_key" id="selectedPresetKey" value="">
            <input type="hidden" name="confirm_overwrite" id="confirmOverwriteInput" value="0">
        </form>
    </div>
</div>

<script>
let optionRowIndex = 0;

function handleAnswerTypeChange(type) {
    const wrap = document.getElementById('optionsBuilderWrap');
    if (type === 'select' || type === 'multiselect') {
        wrap.style.display = 'block';
        if (document.querySelectorAll('.option-row-item').length === 0) {
            addOptionRow();
            addOptionRow();
        }
    } else {
        wrap.style.display = 'none';
    }
}

function addOptionRow(labelAr = '', labelEn = '', val = '') {
    const container = document.getElementById('optionsListContainer');
    const row = document.createElement('div');
    row.className = 'option-row-item';
    row.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr auto; gap:8px; align-items:center;';

    const idx = optionRowIndex++;

    row.innerHTML = `
        <input type="text" name="options_list[${idx}][label_ar]" value="${escapeHtml(labelAr)}" placeholder="{{ __('crm.option_label_ar_placeholder') }}" required style="font-size:13px; padding:7px 10px;">
        <input type="text" name="options_list[${idx}][label_en]" value="${escapeHtml(labelEn)}" placeholder="{{ __('crm.option_label_en_placeholder') }}" style="font-size:13px; padding:7px 10px;">
        <input type="hidden" name="options_list[${idx}][value]" value="${escapeHtml(val)}">
        <button type="button" class="btn small danger" onclick="this.closest('.option-row-item').remove()" style="padding:4px 8px; min-height:30px;">
            <i class="bi bi-x"></i>
        </button>
    `;

    container.appendChild(row);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function toggleAdvancedOptions() {
    const content = document.getElementById('advancedOptionsContent');
    const chevron = document.getElementById('advancedOptionsChevron');
    const txt = document.getElementById('advancedOptionsToggleText');

    if (content.style.display === 'none') {
        content.style.display = 'block';
        chevron.className = 'bi bi-chevron-up';
        txt.textContent = @json(__('crm.hide_advanced_options'));
    } else {
        content.style.display = 'none';
        chevron.className = 'bi bi-chevron-down';
        txt.textContent = @json(__('crm.show_advanced_options'));
    }
}

function toggleConditionInputs(isChecked) {
    const row = document.getElementById('conditionInputsRow');
    row.style.display = isChecked ? 'grid' : 'none';
}

function handleOperatorChange(op) {
    const valWrap = document.getElementById('qCondValueWrap');
    if (op === 'is_checked' || op === 'is_not_checked' || op === 'is_empty' || op === 'is_not_empty') {
        valWrap.style.display = 'none';
    } else {
        valWrap.style.display = 'block';
    }
}

function openAddQuestionModal() {
    const form = document.getElementById('questionForm');
    form.action = @json(route('v2.settings.stages.fields.store', $stage));
    document.getElementById('methodContainer').innerHTML = '';
    document.getElementById('questionModalTitle').textContent = @json(__('crm.add_question_modal_title'));

    document.getElementById('qLabelAr').value = '';
    document.getElementById('qLabelEn').value = '';
    document.getElementById('qType').value = 'text';
    document.getElementById('qIsRequired').checked = false;
    document.getElementById('qPlaceholder').value = '';
    document.getElementById('qHelpText').value = '';

    document.getElementById('optionsListContainer').innerHTML = '';
    handleAnswerTypeChange('text');

    document.getElementById('qHasCondition').checked = false;
    toggleConditionInputs(false);
    document.getElementById('qCondField').value = '';
    document.getElementById('qCondOperator').value = 'equals';
    document.getElementById('qCondValue').value = '';

    document.getElementById('questionModal').style.display = 'flex';
}

function openEditQuestionModal(field) {
    const form = document.getElementById('questionForm');
    form.action = `/settings/stages/{{ $stage->id }}/fields/${field.id}`;
    document.getElementById('methodContainer').innerHTML = '<input type="hidden" name="_method" value="PATCH">';
    document.getElementById('questionModalTitle').textContent = @json(__('crm.edit_question_modal_title'));

    document.getElementById('qLabelAr').value = field.label_ar || '';
    document.getElementById('qLabelEn').value = field.label_en || '';
    document.getElementById('qType').value = field.type || 'text';
    document.getElementById('qIsRequired').checked = !!field.is_required;
    document.getElementById('qPlaceholder').value = field.placeholder_ar || '';
    document.getElementById('qHelpText').value = field.help_text_ar || '';

    document.getElementById('optionsListContainer').innerHTML = '';
    handleAnswerTypeChange(field.type);

    if (field.type === 'select' || field.type === 'multiselect') {
        let opts = [];
        if (field.options) {
            if (Array.isArray(field.options)) {
                opts = field.options;
            } else if (typeof field.options === 'string') {
                try { opts = JSON.parse(field.options); } catch(e) {}
            }
        }
        if (opts.length > 0) {
            document.getElementById('optionsListContainer').innerHTML = '';
            opts.forEach(opt => {
                if (typeof opt === 'string') {
                    addOptionRow(opt, opt, opt);
                } else if (typeof opt === 'object') {
                    addOptionRow(opt.label_ar || opt.value, opt.label_en || opt.value, opt.value || '');
                }
            });
        }
    }

    if (field.conditions && field.conditions.field) {
        document.getElementById('qHasCondition').checked = true;
        toggleConditionInputs(true);
        document.getElementById('qCondField').value = field.conditions.field || '';
        document.getElementById('qCondOperator').value = field.conditions.operator || 'equals';
        document.getElementById('qCondValue').value = field.conditions.value || '';
        handleOperatorChange(field.conditions.operator || 'equals');
    } else {
        document.getElementById('qHasCondition').checked = false;
        toggleConditionInputs(false);
        document.getElementById('qCondField').value = '';
        document.getElementById('qCondOperator').value = 'equals';
        document.getElementById('qCondValue').value = '';
    }

    document.getElementById('questionModal').style.display = 'flex';
}

function closeQuestionModal() {
    document.getElementById('questionModal').style.display = 'none';
}

function openPresetsModal() {
    document.getElementById('presetsModal').style.display = 'flex';
}

function selectPreset(key) {
    document.getElementById('selectedPresetKey').value = key;
    @if ($fields->count() > 0)
        if (confirm(@json(__('crm.preset_overwrite_warning')))) {
            document.getElementById('confirmOverwriteInput').value = '1';
            document.getElementById('presetForm').submit();
        }
    @else
        document.getElementById('confirmOverwriteInput').value = '1';
        document.getElementById('presetForm').submit();
    @endif
}
</script>
@endsection
