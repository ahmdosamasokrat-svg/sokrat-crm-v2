<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::USERS_UPDATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'required',
                'string',
                'max:100',
                'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($managedUser),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($managedUser),
            ],
            'voip_extension' => ['nullable', 'string', 'max:50'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => [
                'integer',
                'distinct',
                'exists:groups,id',
            ],
            'pipeline_stage_access_mode' => ['required', Rule::in(['all', 'selected'])],
            'pipeline_stage_ids' => ['required_if:pipeline_stage_access_mode,selected', 'array', 'min:1'],
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
