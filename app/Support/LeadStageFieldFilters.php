<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PipelineStageField;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class LeadStageFieldFilters
{
    /**
     * @return array{
     *     0: Collection<int, PipelineStageField>,
     *     1: Collection<int, PipelineStageField>,
     *     2: array<int, string>
     * }
     */
    public static function resolve(
        mixed $requestedFieldIds,
        mixed $requestedValues,
    ): array {
        $availableFields = PipelineStageField::query()
            ->select('pipeline_stage_fields.*')
            ->join(
                'pipeline_stages',
                'pipeline_stages.id',
                '=',
                'pipeline_stage_fields.pipeline_stage_id',
            )
            ->with('stage')
            ->where('pipeline_stage_fields.is_active', true)
            ->where('pipeline_stage_fields.show_on_stage_view', true)
            ->whereNull('pipeline_stage_fields.deleted_at')
            ->where('pipeline_stages.is_active', true)
            ->orderBy('pipeline_stages.position')
            ->orderBy('pipeline_stages.id')
            ->orderBy('pipeline_stage_fields.position')
            ->orderBy('pipeline_stage_fields.id')
            ->get();
        $rawFieldIds = is_array($requestedFieldIds)
            ? $requestedFieldIds
            : explode(',', is_scalar($requestedFieldIds) ? (string) $requestedFieldIds : '');
        $fieldIds = collect($rawFieldIds)
            ->filter(static fn (mixed $id): bool => is_scalar($id) && ctype_digit((string) $id))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $selectedFields = $availableFields
            ->whereIn('id', $fieldIds)
            ->values();
        $rawValues = is_array($requestedValues) ? $requestedValues : [];
        $values = [];

        foreach ($selectedFields as $field) {
            $values[(int) $field->id] = self::normalizeValue(
                $field,
                $rawValues[$field->id] ?? '',
            );
        }

        return [$availableFields, $selectedFields, $values];
    }

    /**
     * Apply AND filters against the latest saved value for each selected field.
     *
     * @param  Collection<int, PipelineStageField>  $fields
     * @param  array<int, string>  $values
     */
    public static function apply(Builder $query, Collection $fields, array $values): void
    {
        foreach ($fields as $field) {
            $value = $values[(int) $field->id] ?? '';

            if ($value === '') {
                continue;
            }

            $query->whereExists(
                static function ($valueQuery) use ($field, $value): void {
                    $valueQuery
                        ->selectRaw('1')
                        ->from('lead_stage_field_values as stage_field_values')
                        ->whereColumn('stage_field_values.lead_id', 'leads.id')
                        ->where('stage_field_values.pipeline_stage_field_id', $field->id)
                        ->whereRaw(
                            'stage_field_values.id = ('
                            .'SELECT MAX(latest_stage_field_values.id) '
                            .'FROM lead_stage_field_values AS latest_stage_field_values '
                            .'WHERE latest_stage_field_values.lead_id = leads.id '
                            .'AND latest_stage_field_values.pipeline_stage_field_id = ?'
                            .')',
                            [$field->id],
                        );

                    match ($field->type) {
                        'text', 'textarea', 'email', 'tel', 'url' => $valueQuery->whereRaw(
                            'INSTR(LOWER(stage_field_values.value), LOWER(?)) > 0',
                            [$value],
                        ),
                        'number' => $valueQuery->whereRaw(
                            "stage_field_values.value REGEXP '^-?[0-9]+([.][0-9]+)?([eE][+-]?[0-9]+)?$' "
                            .'AND CAST(stage_field_values.value AS DECIMAL(65, 30)) = CAST(? AS DECIMAL(65, 30))',
                            [$value],
                        ),
                        'datetime' => $valueQuery->whereRaw(
                            "REPLACE(SUBSTR(stage_field_values.value, 1, 16), ' ', 'T') = ?",
                            [$value],
                        ),
                        'multiselect' => $valueQuery->whereRaw(
                            'INSTR(stage_field_values.value, ?) > 0',
                            [json_encode($value)],
                        ),
                        default => $valueQuery->where('stage_field_values.value', $value),
                    };
                },
            );
        }
    }

    private static function normalizeValue(PipelineStageField $field, mixed $rawValue): string
    {
        if (! is_scalar($rawValue)) {
            return '';
        }

        $value = mb_substr(trim((string) $rawValue), 0, 250);

        if ($value === '') {
            return '';
        }

        if (in_array($field->type, ['select', 'multiselect'], true)) {
            $allowedValues = array_map(
                static fn (array $option): string => (string) $option['value'],
                $field->normalizedOptions(),
            );

            return in_array($value, $allowedValues, true) ? $value : '';
        }

        if ($field->type === 'checkbox') {
            return in_array($value, ['0', '1'], true) ? $value : '';
        }

        if ($field->type === 'number') {
            return is_numeric($value) && is_finite((float) $value) ? $value : '';
        }

        if ($field->type === 'date') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
        }

        if ($field->type === 'datetime') {
            $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

            return $dateTime !== false && $dateTime->format('Y-m-d\TH:i') === $value
                ? $value
                : '';
        }

        return $value;
    }
}
