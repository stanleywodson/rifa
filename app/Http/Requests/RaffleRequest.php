<?php

namespace App\Http\Requests;

use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\UnsoldRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RaffleRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // autorização feita no controller via policy
  }

  protected function prepareForValidation(): void
  {
    // Aceita "1.234,56" (máscara pt-BR) ou "1234.56"
    foreach (['price', 'prize_value'] as $field) {
      if (is_string($value = $this->input($field)) && str_contains($value, ',')) {
        $this->merge([$field => str_replace(['.', ','], ['', '.'], $value)]);
      }
    }
  }

  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'min:5', 'max:150'],
      'summary' => ['nullable', 'string', 'max:300'],
      'description' => ['nullable', 'string', 'max:20000'],
      'prize_title' => ['required', 'string', 'max:150'],
      'prize_description' => ['nullable', 'string', 'max:2000'],
      'prize_value' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
      'total_numbers' => ['required', 'integer', 'min:10', 'max:' . config('saas.max_numbers')],
      'number_start' => ['required', Rule::in([0, 1])],
      'price' => ['required', 'numeric', 'min:0.5', 'max:100000'],
      'min_numbers_per_order' => ['required', 'integer', 'min:1', 'max:' . config('saas.max_numbers_per_order')],
      'sales_end_at' => ['required', 'date', 'after:now'],
      'draw_at' => ['required', 'date', 'after_or_equal:sales_end_at'],
      'draw_method' => ['required', Rule::enum(DrawMethod::class)],
      'unsold_rule' => ['required', Rule::enum(UnsoldRule::class)],
      'regulation' => ['required', 'string', 'min:50', 'max:50000'],
      'authorization_number' => ['nullable', 'string', 'max:80'],
      'authorization_doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'images' => ['nullable', 'array', 'max:8'],
      'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ];
  }

  public function attributes(): array
  {
    return [
      'title' => 'título',
      'summary' => 'resumo',
      'description' => 'descrição',
      'prize_title' => 'prêmio',
      'prize_value' => 'valor estimado do prêmio',
      'total_numbers' => 'quantidade de números',
      'number_start' => 'numeração inicial',
      'price' => 'preço por número',
      'min_numbers_per_order' => 'mínimo de números por compra',
      'sales_end_at' => 'encerramento das vendas',
      'draw_at' => 'data do sorteio',
      'draw_method' => 'método de sorteio',
      'unsold_rule' => 'regra para número não vendido',
      'regulation' => 'regulamento',
      'authorization_number' => 'número da autorização',
      'authorization_doc' => 'documento de autorização',
      'images' => 'imagens',
      'images.*' => 'imagem',
    ];
  }

  /**
   * Atributos do model a partir do formulário.
   */
  public function raffleAttributes(): array
  {
    return [
      'title' => $this->input('title'),
      'summary' => $this->input('summary'),
      'description' => clean($this->input('description')),
      'total_numbers' => (int) $this->input('total_numbers'),
      'number_start' => (int) $this->input('number_start'),
      'price_cents' => (int) round((float) $this->input('price') * 100),
      'min_numbers_per_order' => (int) $this->input('min_numbers_per_order'),
      'sales_end_at' => $this->date('sales_end_at'),
      'draw_at' => $this->date('draw_at'),
      'draw_method' => $this->input('draw_method'),
      'unsold_rule' => $this->input('unsold_rule'),
      'regulation' => $this->input('regulation'),
      'authorization_number' => $this->input('authorization_number'),
    ];
  }
}
