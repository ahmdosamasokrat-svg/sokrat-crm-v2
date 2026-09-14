<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::USERS_CREATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:100',
                'alpha_dash:ascii',
                'unique:users,username',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'voip_extension' => ['nullable', 'string', 'max:50'],
            'password' => [
                'required',
                'string',
                'confirmed',
            ],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => [
                'integer',
                'distinct',
                'exists:groups,id',
            ],
            'pipeline_stage_access_mode' => ['required', Rule::in(['all', 'selected'])],
            'pipeline_stage_category_ids' => ['nullable', 'array'],
            'pipeline_stage_category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('pipeline_stage_categories', 'id')->where(
                    static fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ],
            'pipeline_stage_ids' => [
                Rule::requiredIf(fn () => $this->input('pipeline_stage_access_mode') === 'selected' && empty($this->input('pipeline_stage_category_ids'))),
                'nullable',
                'array',
            ],
            'pipeline_stage_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('pipeline_stages', 'id')->where(
                    static fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ],
        ];
    }
}
