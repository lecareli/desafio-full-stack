<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'amount'        => ['required', 'string', 'max:30', 'regex:/^\s*(R\$)?\s*\d{1,3}(\.\d{3})*(,\d{1,2})?\s*$|^\s*(R\$)?\s*\d+([.,]\d{1,2})?\s*$/i'],
            'description'   => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'   => 'Informe o valor para retirada.',
            'amount.string'     => 'Informe um valor válido.',
            'description.max'   => 'A descrição deve ter no máximo 160 caracteres.',
        ];
    }
}
