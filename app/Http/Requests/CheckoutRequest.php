<?php

namespace App\Http\Requests;

use App\Rules\CpfCnpj;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  protected function prepareForValidation(): void
  {
    $numbers = $this->input('numbers');
    if (is_string($numbers)) {
      $numbers = array_filter(array_map('trim', explode(',', $numbers)), fn($n) => $n !== '');
    }

    $this->merge([
      'numbers' => array_values(array_map('intval', (array) $numbers)),
      'cpf' => only_digits($this->input('cpf')),
      'phone' => only_digits($this->input('phone')),
    ]);
  }

  public function rules(): array
  {
    $max = config('saas.max_numbers_per_order');

    return [
      'numbers' => ['array', "max:{$max}"],
      'numbers.*' => ['integer', 'min:0', 'distinct'],
      'quantity' => ['nullable', 'required_without:numbers', 'integer', 'min:1', "max:{$max}"],
      'name' => ['required', 'string', 'min:3', 'max:120'],
      'cpf' => ['required', CpfCnpj::cpf()],
      'phone' => ['required', 'digits_between:10,11'],
      'email' => ['nullable', 'email:rfc', 'max:150'],
      'birthdate' => ['required', 'date', 'before_or_equal:' . now()->subYears(config('saas.minimum_age'))->toDateString()],
      'accept_terms' => ['accepted'],
      'adult' => ['accepted'],
    ];
  }

  public function messages(): array
  {
    return [
      'birthdate.before_or_equal' => 'É necessário ter ao menos ' . config('saas.minimum_age') . ' anos para participar.',
      'adult.accepted' => 'Confirme que você é maior de idade.',
      'accept_terms.accepted' => 'É necessário aceitar o regulamento e os termos de uso.',
      'quantity.required_without' => 'Escolha os números ou informe a quantidade de números aleatórios.',
    ];
  }

  public function attributes(): array
  {
    return [
      'name' => 'nome',
      'cpf' => 'CPF',
      'phone' => 'WhatsApp',
      'email' => 'e-mail',
      'birthdate' => 'data de nascimento',
      'numbers' => 'números',
      'quantity' => 'quantidade',
    ];
  }
}
