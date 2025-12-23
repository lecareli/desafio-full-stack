<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:120', 'unique:users,email'],
            'password'  => ['required', 'string', 'confirmed']
        ];
    }

    public function messages(): array
    {

    }
}
