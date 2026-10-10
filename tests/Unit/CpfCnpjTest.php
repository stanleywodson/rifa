<?php

namespace Tests\Unit;

use App\Rules\CpfCnpj;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CpfCnpjTest extends TestCase
{
  #[DataProvider('documents')]
  public function test_validates_check_digits(string $document, bool $valid): void
  {
    $digits = preg_replace('/\D/', '', $document);
    $result = strlen($digits) === 11 ? CpfCnpj::validCpf($digits) : CpfCnpj::validCnpj($digits);

    $this->assertSame($valid, $result);
  }

  public static function documents(): array
  {
    return [
      'cpf válido' => ['529.982.247-25', true],
      'cpf inválido' => ['529.982.247-24', false],
      'cpf repetido' => ['111.111.111-11', false],
      'cnpj válido' => ['11.222.333/0001-81', true],
      'cnpj inválido' => ['11.222.333/0001-80', false],
    ];
  }
}
