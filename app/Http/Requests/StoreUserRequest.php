<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'portal' => ['required', Rule::in(['admin', 'shop', 'salesman'])],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'scope_type' => ['required', Rule::in(['global', 'territory', 'warehouse', 'shop'])],
            'scope_id' => ['nullable', 'integer', 'required_unless:scope_type,global', ...self::scopeExistsRule($this->input('scope_type'))],
            'scope_label' => ['nullable', 'string', 'max:190'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return ['scope_id.required_unless' => 'Pick which territory, warehouse or shop this user is limited to.'];
    }

    /** @return list<string> */
    public static function scopeExistsRule(?string $type): array
    {
        return match ($type) {
            'territory' => ['exists:territories,id'],
            'warehouse' => ['exists:warehouses,id'],
            'shop' => ['exists:shops,id'],
            default => [],
        };
    }
}
