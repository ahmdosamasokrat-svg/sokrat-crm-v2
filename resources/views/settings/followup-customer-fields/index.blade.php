@extends('settings.layout')

@section('title', __('crm.followup_customer_fields_title'))
@section('heading', __('crm.followup_customer_fields_heading'))
@section('subheading', __('crm.followup_customer_fields_subheading'))
@section('page-icon', 'bi-card-checklist')
@section('back-url', route('v2.settings'))
@section('back-title', __('crm.settings'))

@section('top-actions')
    <a class="btn primary" href="{{ route('v2.settings.followup-customer-fields.index') }}#fieldEditor">
        <i class="bi bi-plus-lg"></i> {{ __('crm.add_followup_customer_field') }}
    </a>
@endsection

@section('content')
<style>
.followup-fields-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr);gap:20px;align-items:start}
.followup-fields-list{display:flex;flex-direction:column;gap:10px}
.followup-field-card{display:flex;align-items:center;gap:14px;padding:15px 16px;border:1px solid var(--line);border-radius:14px;background:var(--card);transition:border-color .16s ease,opacity .16s ease}
.followup-field-card:hover{border-color:var(--muted)}
.followup-field-card.inactive{opacity:.58;background:var(--bg)}
.followup-field-handle{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;flex:0 0 auto;background:var(--bg);color:var(--muted);font-size:15px}
.followup-field-copy{min-width:0;flex:1}
.followup-field-copy h3{margin:0 0 5px;color:var(--dark);font-size:15px;font-weight:800}
.followup-field-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;color:var(--muted);font-size:11px;font-weight:700}
.followup-field-actions{display:flex;align-items:center;gap:5px;flex-wrap:wrap;justify-content:flex-end}
.followup-field-actions form{margin:0}
.followup-field-actions .btn{min-width:40px;min-height:40px;padding:0 9px}
.followup-field-editor{position:sticky;top:18px}
.followup-field-editor .panel{padding:0;overflow:hidden}
.field-editor-head{padding:18px 20px;border-bottom:1px solid var(--line);background:var(--bg)}
.field-editor-head h2{margin:0 0 4px;font-size:15px;color:var(--dark)}
.field-editor-head p{margin:0;color:var(--muted);font-size:11px}
.field-editor-body{padding:20px}
.editor-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px}
.editor-field{display:flex;flex-direction:column;gap:6px}
.editor-field.full{grid-column:1/-1}
.editor-field label{font-size:11px;font-weight:800;color:var(--dark)}
.editor-field input,.editor-field select,.editor-field textarea{width:100%}
.editor-actions{display:flex;gap:8px;padding-top:16px;margin-top:4px;border-top:1px solid var(--line)}
.employee-preview{margin-top:16px;padding:16px;border:1px solid var(--line);border-radius:14px;background:var(--bg)}
.employee-preview h3{margin:0 0 4px;font-size:14px;color:var(--dark)}
.employee-preview>p{margin:0 0 14px;color:var(--muted);font-size:11px}
@media(max-width:1050px){.followup-fields-layout{grid-template-columns:1fr}.followup-field-editor{position:static}}
@media(max-width:640px){
    .followup-field-card{align-items:flex-start;flex-wrap:wrap;padding:13px}
    .followup-field-actions{width:100%;padding-top:10px;border-top:1px solid var(--line)}
    .editor-grid{grid-template-columns:1fr}
    .editor-field.full{grid-column:auto}
}
</style>

@php
    $editing = $editField !== null;
    $optionsRaw = $editing
        ? collect($editField->normalizedOptions())->map(
            fn (array $option): string => implode(' | ', [$option['label_ar'], $option['label_en'], $option['value']])
        )->implode("\n")
        : '';
@endphp

<div class="followup-fields-layout">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2><i class="bi bi-layout-text-window-reverse"></i> {{ __('crm.followup_customer_fields_list') }}</h2>
                <p>{{ __('crm.followup_customer_fields_list_desc') }}</p>
            </div>
            <span class="badge active">{{ $fields->where('is_active', true)->count() }} {{ __('crm.active_fields_count') }}</span>
        </div>

        <div class="followup-fields-list">
            @foreach ($fields as $field)
                @php
                    $typeIcon = match($field->type) {
                        'textarea' => 'bi-textarea-t',
                        'number' => 'bi-hash',
                        'email' => 'bi-envelope',
                        'tel' => 'bi-telephone',
                        'date', 'datetime' => 'bi-calendar-event',
                        'select', 'multiselect' => 'bi-menu-button-wide',
                        'checkbox' => 'bi-check-square',
                        default => 'bi-input-cursor-text',
                    };
                @endphp
                <article class="followup-field-card {{ $field->is_active ? '' : 'inactive' }}">
                    <span class="followup-field-handle"><i class="bi {{ $typeIcon }}"></i></span>
                    <div class="followup-field-copy">
                        <h3>{{ $field->localizedLabel() }}</h3>
                        <div class="followup-field-meta">
                            <span>{{ __('crm.field_type_'.$field->type) }}</span>
                            <span>•</span>
                            <span>{{ $field->is_required ? __('crm.required') : __('crm.optional') }}</span>
                            @if ($field->is_system)
                                <span class="badge system">{{ __('crm.system_field') }}</span>
                            @endif
                            @unless ($field->is_active)
                                <span class="badge">{{ __('crm.hidden_from_employees') }}</span>
                            @endunless
                        </div>
                    </div>
                    <div class="followup-field-actions">
                        <form method="POST" action="{{ route('v2.settings.followup-customer-fields.move', $field) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="direction" value="up">
                            <button class="btn small soft" type="submit" title="{{ __('crm.move_up') }}"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form method="POST" action="{{ route('v2.settings.followup-customer-fields.move', $field) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="direction" value="down">
                            <button class="btn small soft" type="submit" title="{{ __('crm.move_down') }}"><i class="bi bi-arrow-down"></i></button>
                        </form>
                        <a class="btn small soft" href="{{ route('v2.settings.followup-customer-fields.index', ['edit' => $field->id]) }}#fieldEditor" title="{{ __('crm.edit') }}">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('v2.settings.followup-customer-fields.toggle', $field) }}">
                            @csrf @method('PATCH')
                            <button class="btn small soft" type="submit" title="{{ $field->is_active ? __('crm.deactivate_action') : __('crm.activate_action') }}">
                                <i class="bi {{ $field->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                            </button>
                        </form>
                        @unless ($field->is_system)
                            <form method="POST" action="{{ route('v2.settings.followup-customer-fields.destroy', $field) }}" onsubmit="return confirm(@json(__('crm.followup_customer_field_delete_confirm')))">
                                @csrf @method('DELETE')
                                <button class="btn small danger" type="submit" title="{{ __('crm.delete') }}"><i class="bi bi-trash"></i></button>
                            </form>
                        @endunless
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <aside class="followup-field-editor" id="fieldEditor">
        <section class="panel">
            <div class="field-editor-head">
                <h2>{{ $editing ? __('crm.edit_followup_customer_field') : __('crm.add_followup_customer_field') }}</h2>
                <p>{{ $editing ? __('crm.existing_field_edit_hint') : __('crm.followup_customer_field_editor_desc') }}</p>
            </div>
            <div class="field-editor-body">
                <form method="POST" action="{{ $editing ? route('v2.settings.followup-customer-fields.update', $editField) : route('v2.settings.followup-customer-fields.store') }}">
                    @csrf
                    @if ($editing) @method('PATCH') @endif

                    <div class="editor-grid">
                        <div class="editor-field full">
                            <label for="fieldLabelAr">{{ __('crm.field_label_ar') }} <span class="required">*</span></label>
                            <input id="fieldLabelAr" name="label_ar" type="text" required value="{{ old('label_ar', $editField?->label_ar) }}">
                        </div>
                        <div class="editor-field">
                            <label for="fieldLabelEn">{{ __('crm.field_label_en') }}</label>
                            <input id="fieldLabelEn" name="label_en" type="text" value="{{ old('label_en', $editField?->label_en) }}">
                        </div>
                        <div class="editor-field">
                            <label for="fieldType">{{ __('crm.answer_type_label') }} <span class="required">*</span></label>
                            <select id="fieldType" name="type" required {{ $editing ? 'disabled' : '' }}>
                                @foreach ($fieldTypes as $type => $label)
                                    <option value="{{ $type }}" @selected(old('type', $editField?->type ?? 'text') === $type)>{{ __('crm.field_type_'.$type) }}</option>
                                @endforeach
                            </select>
                            @if ($editing)
                                <input type="hidden" name="type" value="{{ $editField->type }}">
                            @endif
                        </div>
                        <div class="editor-field">
                            <label for="fieldPlaceholderAr">{{ __('crm.placeholder_ar') }}</label>
                            <input id="fieldPlaceholderAr" name="placeholder_ar" type="text" value="{{ old('placeholder_ar', $editField?->placeholder_ar) }}">
                        </div>
                        <div class="editor-field">
                            <label for="fieldPlaceholderEn">{{ __('crm.placeholder_en') }}</label>
                            <input id="fieldPlaceholderEn" name="placeholder_en" type="text" value="{{ old('placeholder_en', $editField?->placeholder_en) }}">
                        </div>
                        <div class="editor-field full">
                            <label for="fieldHelpAr">{{ __('crm.help_text_ar') }}</label>
                            <input id="fieldHelpAr" name="help_text_ar" type="text" value="{{ old('help_text_ar', $editField?->help_text_ar) }}">
                        </div>
                        <div class="editor-field full" id="optionsField" hidden>
                            <label for="fieldOptions">{{ __('crm.followup_customer_options') }}</label>
                            <textarea id="fieldOptions" name="options_raw" rows="5" dir="auto">{{ old('options_raw', $optionsRaw) }}</textarea>
                            <small>{{ __('crm.followup_customer_options_hint') }}</small>
                        </div>
                        <div class="editor-field full">
                            <label class="check-card" style="margin:0;cursor:pointer">
                                <input name="is_required" type="checkbox" value="1" @checked(old('is_required', $editField?->is_required))>
                                <span>
                                    <strong>{{ __('crm.make_field_required') }}</strong>
                                    <small>{{ __('crm.make_field_required_hint') }}</small>
                                </span>
                            </label>
                        </div>
                        <div class="editor-field full">
                            <div class="editor-actions">
                                <button class="btn primary" type="submit"><i class="bi bi-check-lg"></i> {{ __('crm.save') }}</button>
                                @if ($editing)
                                    <a class="btn soft" href="{{ route('v2.settings.followup-customer-fields.index') }}#fieldEditor">{{ __('crm.cancel') }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                <div class="employee-preview">
                    <h3><i class="bi bi-eye"></i> {{ __('crm.employee_followup_preview') }}</h3>
                    <p>{{ __('crm.employee_followup_preview_desc') }}</p>
                    @include('partials.stage-field-inputs', [
                        'fields' => $fields->where('is_active', true)->values(),
                        'recordValues' => [],
                        'prefix' => 'preview_customer_fields',
                        'scope' => 'followup_customer_preview',
                    ])
                </div>
            </div>
        </section>
    </aside>
</div>

<script>
(() => {
    const type = document.getElementById('fieldType');
    const options = document.getElementById('optionsField');
    if (!type || !options) return;

    const syncOptions = () => {
        options.hidden = !['select', 'multiselect'].includes(type.value);
    };

    type.addEventListener('change', syncOptions);
    syncOptions();
})();
</script>
@endsection
