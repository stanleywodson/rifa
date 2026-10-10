<?php

if (!function_exists('money_br')) {
  /** Formata centavos como R$ 1.234,56 */
  function money_br(?int $cents): string
  {
    return 'R$' . "\u{00A0}" . number_format(($cents ?? 0) / 100, 2, ',', '.');
  }
}

if (!function_exists('only_digits')) {
  function only_digits(?string $value): string
  {
    return preg_replace('/\D+/', '', (string) $value);
  }
}

if (!function_exists('mask_cpf')) {
  /** 123.***.***-09 — nunca exibir CPF completo em telas/logs */
  function mask_cpf(?string $cpf): string
  {
    $d = only_digits($cpf);
    if (strlen($d) !== 11) {
      return '***';
    }

    return substr($d, 0, 3) . '.***.***-' . substr($d, -2);
  }
}

if (!function_exists('mask_phone')) {
  function mask_phone(?string $phone): string
  {
    $d = only_digits($phone);

    return strlen($d) >= 4 ? '(**) *****-' . substr($d, -4) : '***';
  }
}

if (!function_exists('canonical_value')) {
  /**
   * Ordena chaves recursivamente (o tipo JSON do MySQL reordena as chaves),
   * garantindo hashes reproduzíveis após ida e volta ao banco.
   */
  function canonical_value(mixed $value): mixed
  {
    if (!is_array($value)) {
      return is_scalar($value) || $value === null ? $value : (string) $value;
    }
    if (!array_is_list($value)) {
      ksort($value);
    }

    return array_map('canonical_value', $value);
  }
}
