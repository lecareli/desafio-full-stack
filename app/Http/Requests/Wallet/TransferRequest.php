<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_email'      => ['required', 'email', 'max:190'],
            'amount'        => ['required', 'string', 'max:30', 'regex:/^\s*(R\$)?\s*\d{1,3}(\.\d{3})*(,\d{1,2})?\s*$|^\s*(R\$)?\s*\d+([.,]\d{1,2})?\s*$/i'],
            'description'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'to_email'      => 'e-mail do destinatário',
            'amount'        => 'valor',
            'description'   => 'descrição',
        ];
    }

    public function messages(): array
    {
        return [
            'to_email.required' => 'Por favor, informe o e-mail do destinatário.',
            'to_email.email'    => 'Informe um e-mail válido para o destinatário.',
            'to_email.max'      => 'O e-mail do destinatário deve ter no máximo :max caracteres.',

            'amount.required'   => 'Por favor, informe o valor da transferência.',
            'amount.regex'      => 'Informe um valor válido. Ex.: 50,00',
            'amount.max'        => 'O valor informado é muito longo.',

            'description.max'   => 'A descrição deve ter no máximo :max caracteres.',
        ];
    }
}
