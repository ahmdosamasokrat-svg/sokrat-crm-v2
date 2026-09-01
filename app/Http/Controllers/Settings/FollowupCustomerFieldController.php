<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\FollowupCustomerField;
use App\Models\Lead;
use App\Support\CrmDatabaseGuard;
use App\Support\FollowupCustomerFieldSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FollowupCustomerFieldController extends Controller
{
    public function index(Request $request): View
    {
        CrmDatabaseGuard::ensureConnected();

        $fields = FollowupCustomerField::query()->ordered()->get();
        $editField = $request->filled('edit')
            ? $fields->firstWhere('id', $request->integer('edit'))
            : null;

        return view('settings.followup-customer-fields.index', [
            'fields' => $fields,
            'editField' => $editField,
            'fieldTypes' => FollowupCustomerField::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateField($request);
        $key = $this->uniqueKey((string) ($validated['label_en'] ?? ''));
        $position = (int) (FollowupCustomerField::query()->max('position') ?? 0) + 1;

        FollowupCustomerField::query()->create([
            ...$this->attributes($validated),
            'key' => $key,
            'is_system' => false,
            'is_active' => true,
            'position' => $position,
        ]);

        FollowupCustomerFieldSchema::flushCache();

        return to_route('v2.settings.followup-customer-fields.index')
            ->with('success', __('crm.followup_customer_field_added'));
    }

    public function update(Request $request, FollowupCustomerField $field): RedirectResponse
    {
        $validated = $this->validateField($request, $field);
        $attributes = $this->attributes($validated);

        $attributes['type'] = $field->type;

        if ($field->is_system) {
            $attributes['options'] = $field->options;
        } elseif (in_array($field->type, ['select', 'multiselect'], true)) {
            $newValues = array_column($attributes['options'] ?? [], 'value');
            $removedUsedValues = array_diff($this->storedOptionValues($field), $newValues);

            if ($removedUsedValues !== []) {
                throw ValidationException::withMessages([
                    'options_raw' => __('crm.followup_customer_options_in_use'),
                ]);
            }
        }

        $field->update($attributes);
        FollowupCustomerFieldSchema::flushCache();

        return to_route('v2.settings.followup-customer-fields.index')
            ->with('success', __('crm.followup_customer_field_updated'));
    }

    public function toggle(FollowupCustomerField $field): RedirectResponse
    {
        $field->update(['is_active' => ! $field->is_active]);
        FollowupCustomerFieldSchema::flushCache();

        return back()->with('success', $field->is_active
            ? __('crm.followup_customer_field_enabled')
            : __('crm.followup_customer_field_disabled'));
    }

    public function destroy(FollowupCustomerField $field): RedirectResponse
    {
        if ($field->is_system) {
            return back()->withErrors(['field' => __('crm.followup_customer_system_field_delete_error')]);
        }

        $field->delete();
        FollowupCustomerFieldSchema::flushCache();

        return to_route('v2.settings.followup-customer-fields.index')
            ->with('success', __('crm.followup_customer_field_deleted'));
    }

    public function move(Request $request, FollowupCustomerField $field): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction'];
        $fields = FollowupCustomerField::query()->ordered()->get();
        $index = $fields->search(fn (FollowupCustomerField $item): bool => $item->is($field));
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && isset($fields[$swapIndex])) {
            $other = $fields[$swapIndex];
            DB::transaction(function () use ($field, $other): void {
                $position = $field->position;
                $field->update(['position' => $other->position]);
                $other->update(['position' => $position]);
            });
            FollowupCustomerFieldSchema::flushCache();
        }

        return back();
    }

    /** @throws ValidationException */
    private function validateField(Request $request, ?FollowupCustomerField $field = null): array
    {
        $validated = $request->validate([
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(FollowupCustomerField::TYPES))],
            'placeholder_ar' => ['nullable', 'string', 'max:255'],
            'placeholder_en' => ['nullable', 'string', 'max:255'],
            'help_text_ar' => ['nullable', 'string', 'max:1000'],
            'help_text_en' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'options_raw' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($field?->is_system !== true && in_array($validated['type'], ['select', 'multiselect'], true)) {
            $options = $this->parseOptions((string) ($validated['options_raw'] ?? ''));
            if ($options === []) {
                throw ValidationException::withMessages([
                    'options_raw' => __('crm.followup_customer_options_required'),
                ]);
            }
            $validated['options'] = $options;
        }

        return $validated;
    }

    private function attributes(array $validated): array
    {
        return [
            'label_ar' => trim($validated['label_ar']),
            'label_en' => trim((string) ($validated['label_en'] ?? '')) ?: null,
            'type' => $validated['type'],
            'placeholder_ar' => trim((string) ($validated['placeholder_ar'] ?? '')) ?: null,
            'placeholder_en' => trim((string) ($validated['placeholder_en'] ?? '')) ?: null,
            'help_text_ar' => trim((string) ($validated['help_text_ar'] ?? '')) ?: null,
            'help_text_en' => trim((string) ($validated['help_text_en'] ?? '')) ?: null,
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'options' => $validated['options'] ?? null,
        ];
    }

    private function uniqueKey(string $labelEn): string
    {
        $base = str_replace('-', '_', Str::slug($labelEn));
        $base = $base !== '' ? mb_substr($base, 0, 80) : 'field_'.Str::lower(Str::random(8));
        $key = $base;
        $suffix = 1;

        while (FollowupCustomerField::withTrashed()->where('key', $key)->exists()) {
            $key = $base.'_'.($suffix++);
        }

        return $key;
    }

    private function parseOptions(string $raw): array
    {
        $options = [];
        $values = [];

        foreach (preg_split('/\R+/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 3));
            $labelAr = $parts[0] ?? '';
            if ($labelAr === '') {
                continue;
            }

            $labelEn = $parts[1] ?? $labelAr;
            $value = $parts[2] ?? '';
            $value = $value !== '' ? str_replace('-', '_', Str::slug($value)) : '';
            $value = $value !== '' ? $value : 'option_'.substr(sha1($labelAr), 0, 10);

            if (isset($values[$value])) {
                throw ValidationException::withMessages([
                    'options_raw' => __('crm.followup_customer_options_duplicate'),
                ]);
            }

            $values[$value] = true;
            $options[] = ['value' => $value, 'label_ar' => $labelAr, 'label_en' => $labelEn ?: $labelAr];
        }

        return $options;
    }

    private function storedOptionValues(FollowupCustomerField $field): array
    {
        $values = [];

        foreach (Lead::query()->whereNotNull('custom_fields')->select(['id', 'custom_fields'])->lazyById() as $lead) {
            $customFields = is_array($lead->custom_fields) ? $lead->custom_fields : [];
            if (! array_key_exists($field->key, $customFields)) {
                continue;
            }

            foreach ((array) $customFields[$field->key] as $value) {
                if ($value !== null && $value !== '') {
                    $values[(string) $value] = true;
                }
            }
        }

        return array_keys($values);
    }
}
