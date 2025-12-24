<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Aceita: 150 | 150,00 | 150.00 | 1.500,00
            'amount'        => ['required', 'string', 'max:30', 'regex:/^\s*(R\$)?\s*\d{1,3}(\.\d{3})*(,\d{1,2})?\s*$|^\s*(R\$)?\s*\d+([.,]\d{1,2})?\s*$/i'],
            'description'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount'        => 'valor',
            'description'   => 'descrição',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'   => 'Por favor, informe o valor do depósito.',
            'amount.regex'      => 'Informe um valor válido. Ex.: 150,00',
            'amount.max'        => 'O valor informado é muito longo.',
            'description.max'   => 'A descrição deve ter no máximo :max caracteres.',
        ];
    }
}
