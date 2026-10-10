<?php

namespace App\Domain\Tickets\Exceptions;

use DomainException;

class NumbersUnavailable extends DomainException
{
  /** @param  int[]  $numbers */
  public function __construct(public readonly array $numbers, string $message = '')
  {
    parent::__construct($message ?: 'Alguns números já foram reservados por outra pessoa: ' . implode(', ', $numbers));
  }
}
