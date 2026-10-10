<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida CPF (11 dígitos) e/ou CNPJ (14 dígitos) pelos dígitos verificadores.
 */
class CpfCnpj implements ValidationRule
{
  public function __construct(protected bool $allowCpf = true, protected bool $allowCnpj = true) {}

  public static function cpf(): self
  {
    return new self(true, false);
  }

  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $digits = only_digits((string) $value);
    $valid = match (strlen($digits)) {
      11 => $this->allowCpf && self::validCpf($digits),
      14 => $this->allowCnpj && self::validCnpj($digits),
      default => false,
    };

    if (!$valid) {
      $fail($this->allowCnpj ? 'O :attribute informado não é um CPF/CNPJ válido.' : 'O :attribute informado não é um CPF válido.');
    }
  }

  public static function validCpf(string $cpf): bool
  {
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
      return false;
    }
    for ($t = 9; $t < 11; $t++) {
      $sum = 0;
      for ($i = 0; $i < $t; $i++) {
        $sum += (int) $cpf[$i] * (($t + 1) - $i);
      }
      if ((int) $cpf[$t] !== ((10 * $sum) % 11) % 10) {
        return false;
      }
    }

    return true;
  }

  public static function validCnpj(string $cnpj): bool
  {
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
      return false;
    }
    $weights = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];
    foreach ([12, 13] as $k => $len) {
      $sum = 0;
      for ($i = 0; $i < $len; $i++) {
        $sum += (int) $cnpj[$i] * $weights[$k][$i];
      }
      $digit = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
      if ((int) $cnpj[$len] !== $digit) {
        return false;
      }
    }

    return true;
  }
}
