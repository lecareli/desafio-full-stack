<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                  => 'nome',
            'email'                 => 'e-mail',
            'password'              => 'senha',
            'password_confirmation' => 'confirmar senha',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Por favor, informe seu nome.',
            'name.string'           => 'O nome informado é inválido.',
            'name.max'              => 'O nome deve ter no máximo :max caracteres.',

            'email.required'        => 'Por favor, informe seu e-mail.',
            'email.email'           => 'Informe um e-mail válido.',
            'email.max'             => 'O e-mail deve ter no máximo :max caracteres.',
            'email.unique'          => 'Este e-mail já está cadastrado. Tente entrar na sua conta.',

            'password.required'     => 'Por favor, informe uma senha.',
            'password.string'       => 'A senha informada é inválida.',
            'password.min'          => 'A senha deve ter no mínimo :min caracteres.',
            'password.confirmed'    => 'As senhas não conferem. Verifique e tente novamente.',
        ];
    }
}
