<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FollowupCustomerField;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FollowupCustomerFieldSchema
{
    /** @return Collection<int, FollowupCustomerField> */
    public static function fields(bool $onlyActive = true): Collection
    {
        return FollowupCustomerField::query()
            ->when($onlyActive, static fn ($query) => $query->active())
            ->ordered()
            ->get();
    }

    public static function flushCache(): void
    {
        // Kept as a compatibility hook; this schema intentionally avoids persistent object caching.
    }

    public static function currentValues(Lead $lead, ?Collection $fields = null): array
    {
        $customValues = is_array($lead->custom_fields) ? $lead->custom_fields : [];
        $values = [];

        foreach ($fields ?? self::fields() as $field) {
            $values[$field->key] = $field->lead_attribute
                ? $lead->getAttribute($field->lead_attribute)
                : ($customValues[$field->key] ?? null);
        }

        return $values;
    }

    /** @throws ValidationException */
    public static function validateAndExtract(array $rawInput): array
    {
        $fields = self::fields();
        $submitted = isset($rawInput['customer_fields']) && is_array($rawInput['customer_fields'])
            ? $rawInput['customer_fields']
            : [];
        $presence = isset($rawInput['customer_field_presence']) && is_array($rawInput['customer_field_presence'])
            ? array_values(array_unique(array_map('strval', $rawInput['customer_field_presence'])))
            : [];
        $activeKeys = $fields->pluck('key')->all();
        $unknownKeys = array_diff(array_unique([...array_keys($submitted), ...$presence]), $activeKeys);

        if ($unknownKeys !== []) {
            throw ValidationException::withMessages([
                'customer_fields' => __('crm.followup_customer_field_unknown_error'),
            ]);
        }

        $rules = [];
        $attributes = [];
        foreach ($fields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            $canonicalRules = match ($field->lead_attribute) {
                'company_name', 'activity', 'job_title' => ['string', 'max:150'],
                'governorate' => ['string', 'max:100'],
                'address' => ['string', 'max:255'],
                'users_count', 'branches_count' => ['integer', 'min:0', 'max:1000000'],
                default => null,
            };

            if ($canonicalRules !== null) {
                $rules[$field->key] = [...$fieldRules, ...$canonicalRules];
                $attributes[$field->key] = $field->localizedLabel();
                continue;
            }

            switch ($field->type) {
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;
                case 'tel':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:50';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    $fieldRules[] = 'max:500';
                    break;
                case 'date':
                    $fieldRules[] = 'date_format:Y-m-d';
                    break;
                case 'datetime':
                    $fieldRules[] = 'date';
                    break;
                case 'select':
                    $fieldRules[] = Rule::in(array_column($field->normalizedOptions(), 'value'));
                    break;
                case 'multiselect':
                    $fieldRules[] = 'array';
                    $fieldRules[] = 'max:50';
                    $rules[$field->key.'.*'] = [Rule::in(array_column($field->normalizedOptions(), 'value'))];
                    break;
                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
            }

            $rules[$field->key] = $fieldRules;
            $attributes[$field->key] = $field->localizedLabel();
        }

        $validated = Validator::make($submitted, $rules, [], $attributes)->validate();
        $normalized = [];

        foreach ($fields as $field) {
            if (! in_array($field->key, $presence, true) && ! array_key_exists($field->key, $submitted)) {
                continue;
            }

            $value = $validated[$field->key] ?? null;
            if ($value === null || $value === '') {
                $normalized[$field->key] = null;
            } elseif ($field->type === 'checkbox') {
                $normalized[$field->key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($field->type === 'multiselect') {
                $normalized[$field->key] = array_values((array) $value);
            } elseif ($field->type === 'number') {
                $normalized[$field->key] = is_numeric($value) ? $value + 0 : null;
            } elseif ($field->type === 'datetime') {
                $normalized[$field->key] = Carbon::parse((string) $value)->toDateTimeString();
            } else {
                $normalized[$field->key] = trim((string) $value);
            }
        }

        return $normalized;
    }

    /**
     * @return array{attributes: array<string, mixed>, changes: array<int, array<string, string>>}
     */
    public static function prepareUpdates(Lead $lead, array $values): array
    {
        $fields = self::fields()->keyBy('key');
        $customValues = is_array($lead->custom_fields) ? $lead->custom_fields : [];
        $attributes = [];
        $changes = [];
        $customChanged = false;

        foreach ($values as $key => $newValue) {
            $field = $fields->get($key);
            if (! $field) {
                continue;
            }

            $oldValue = $field->lead_attribute
                ? $lead->getAttribute($field->lead_attribute)
                : ($customValues[$key] ?? null);

            if (self::comparable($oldValue) === self::comparable($newValue)) {
                continue;
            }

            if ($field->lead_attribute) {
                $attributes[$field->lead_attribute] = $newValue;
            } else {
                $customChanged = true;
                if ($newValue === null) {
                    unset($customValues[$key]);
                } else {
                    $customValues[$key] = $newValue;
                }
            }

            $changes[] = [
                'field' => (string) $key,
                'label' => $field->localizedLabel(),
                'old' => self::displayValue($field, $oldValue),
                'new' => self::displayValue($field, $newValue),
            ];
        }

        if ($customChanged) {
            $attributes['custom_fields'] = $customValues === [] ? null : $customValues;
        }

        return ['attributes' => $attributes, 'changes' => $changes];
    }

    private static function comparable(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE) ?: '';
        }

        return $value === null ? '' : trim((string) $value);
    }

    private static function displayValue(FollowupCustomerField $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '----';
        }

        if ($field->type === 'checkbox') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? __('crm.yes') : __('crm.no');
        }

        if (in_array($field->type, ['select', 'multiselect'], true)) {
            $selected = array_map('strval', is_array($value) ? $value : [$value]);
            $options = collect($field->normalizedOptions())->keyBy('value');

            return implode('، ', array_map(
                static function (string $item) use ($options): string {
                    $option = $options->get($item);

                    return $option
                        ? (app()->getLocale() === 'en' ? $option['label_en'] : $option['label_ar'])
                        : $item;
                },
                $selected,
            ));
        }

        return trim((string) $value);
    }
}
