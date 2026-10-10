<?php

namespace Tests\Unit;

use App\Domain\Draws\DrawService;
use PHPUnit\Framework\TestCase;

class DrawMathTest extends TestCase
{
  public function test_modulo_handles_large_numbers_and_leading_zeros(): void
  {
    $this->assertSame(45, DrawService::modulo('12345', 100));
    $this->assertSame(7, DrawService::modulo('007', 1000));
    $this->assertSame((int) bcmod('98765432109876543210', '997'), DrawService::modulo('98765432109876543210', 997));
    $this->assertSame(0, DrawService::modulo('0', 10));
  }

  public function test_seed_base_is_deterministic(): void
  {
    $a = DrawService::seedBase('semente', '12345');
    $this->assertSame($a, DrawService::seedBase('semente', '12345'));
    $this->assertNotSame($a, DrawService::seedBase('semente', '12346'));
    $this->assertMatchesRegularExpression('/^\d+$/', $a);
  }
}
