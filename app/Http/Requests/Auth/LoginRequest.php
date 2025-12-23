<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email'     => 'e-mail',
            'password'  => 'senha',
            'remember'  => 'lembrar-me',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Por favor, informe seu e-mail.',
            'email.email'       => 'Informe um e-mail válido.',

            'password.required' => 'Por favor, informe sua senha.',
            'password.string'   => 'A senha informada é inválida.',

            'remember.boolean'  => 'Valor inválido no campo lembrar-me.',
        ];
    }
}
