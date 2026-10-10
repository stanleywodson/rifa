<?php

namespace App\Domain\Tickets;

use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tickets\Enums\TicketStatus;
use App\Domain\Tickets\Exceptions\NumbersUnavailable;
use App\Domain\Tickets\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reserva atômica de números.
 *
 * A garantia contra dupla venda é o UPDATE condicional (status = free):
 * o MySQL trava as linhas e só uma transação consegue trocar o status.
 * Se menos linhas forem afetadas que o solicitado, nada é reservado.
 */
class TicketReservationService
{
  /**
   * Deve ser chamado dentro de uma transação (o rollback desfaz a reserva parcial).
   *
   * @param  int[]  $numbers
   * @return int[] números reservados
   */
  public function reserve(Raffle $raffle, Order $order, array $numbers, Carbon $until): array
  {
    $numbers = array_values(array_unique(array_map('intval', $numbers)));
    sort($numbers);

    $invalid = array_filter($numbers, fn($n) => $n < $raffle->number_start || $n > $raffle->lastNumber());
    if ($invalid) {
      throw new NumbersUnavailable(array_values($invalid), 'Números fora da faixa da rifa: ' . implode(', ', $invalid));
    }

    $affected = Ticket::query()
      ->where('raffle_id', $raffle->id)
      ->whereIn('number', $numbers)
      ->where('status', TicketStatus::Free)
      ->update([
        'status' => TicketStatus::Reserved,
        'order_id' => $order->id,
        'reserved_until' => $until,
      ]);

    if ($affected !== count($numbers)) {
      $taken = Ticket::query()
        ->where('raffle_id', $raffle->id)
        ->whereIn('number', $numbers)
        ->where(fn($q) => $q->where('order_id', '!=', $order->id)->orWhereNull('order_id'))
        ->where('status', '!=', TicketStatus::Free)
        ->pluck('number')->all();

      throw new NumbersUnavailable($taken ?: $numbers);
    }

    $this->forgetGridCache($raffle);

    return $numbers;
  }

  /**
   * Escolhe e reserva números livres aleatórios.
   *
   * @return int[]
   */
  public function reserveRandom(Raffle $raffle, Order $order, int $quantity, Carbon $until): array
  {
    // Lock por rifa evita que duas escolhas aleatórias simultâneas disputem as mesmas linhas.
    return Cache::lock("raffle:{$raffle->id}:random", 10)->block(5, function () use ($raffle, $order, $quantity, $until) {
      $numbers = DB::select(
        'SELECT number FROM tickets WHERE raffle_id = ? AND status = ? ORDER BY RAND() LIMIT ? FOR UPDATE SKIP LOCKED',
        [$raffle->id, TicketStatus::Free->value, $quantity]
      );
      $numbers = array_map(fn($row) => (int) $row->number, $numbers);

      if (count($numbers) < $quantity) {
        throw new NumbersUnavailable([], 'Não há números livres suficientes nesta rifa.');
      }

      return $this->reserve($raffle, $order, $numbers, $until);
    });
  }

  /**
   * Libera os números reservados (não pagos) de um pedido.
   */
  public function release(Order $order): int
  {
    $released = Ticket::query()
      ->where('order_id', $order->id)
      ->where('status', TicketStatus::Reserved)
      ->update(['status' => TicketStatus::Free, 'order_id' => null, 'reserved_until' => null]);

    $this->forgetGridCache($order->raffle);

    return $released;
  }

  /**
   * Confirma como pagos os números do pedido (tudo ou nada). Se a reserva já
   * expirou, recupera os mesmos números desde que ainda estejam livres.
   * Deve ser chamado dentro de uma transação.
   *
   * @return bool false se algum número já pertence a outro pedido
   */
  public function markPaid(Order $order): bool
  {
    $numbers = $order->items()->pluck('number')->all();
    $raffleId = $order->raffle_id;

    $available = Ticket::query()
      ->where('raffle_id', $raffleId)
      ->whereIn('number', $numbers)
      ->where(function ($q) use ($order) {
        $q->where(fn($q) => $q->where('order_id', $order->id)->where('status', TicketStatus::Reserved))
          ->orWhere('status', TicketStatus::Free);
      })
      ->lockForUpdate()
      ->count();

    if ($available !== count($numbers)) {
      return false;
    }

    Ticket::query()->where('raffle_id', $raffleId)->whereIn('number', $numbers)->update([
      'status' => TicketStatus::Paid,
      'order_id' => $order->id,
      'reserved_until' => null,
      'paid_at' => now(),
    ]);

    $this->forgetGridCache($order->raffle);

    return true;
  }

  /**
   * Status compacto de uma página da grade: string com um caractere por número
   * (0 livre, 1 reservado, 2 pago).
   */
  public function gridPage(Raffle $raffle, int $page): array
  {
    $size = config('saas.grid_page_size');
    $pages = (int) ceil($raffle->total_numbers / $size);
    $page = max(1, min($page, max($pages, 1)));
    $from = $raffle->number_start + ($page - 1) * $size;
    $to = min($from + $size - 1, $raffle->lastNumber());

    $statuses = Cache::remember($this->gridKey($raffle, $page), 3, function () use ($raffle, $from, $to) {
      $map = ['free' => '0', 'reserved' => '1', 'paid' => '2'];
      $rows = Ticket::query()->where('raffle_id', $raffle->id)->whereBetween('number', [$from, $to])
        ->orderBy('number')->pluck('status', 'number');
      $out = '';
      for ($n = $from; $n <= $to; $n++) {
        $status = $rows[$n] ?? TicketStatus::Free;
        $out .= $map[$status instanceof TicketStatus ? $status->value : $status];
      }

      return $out;
    });

    return [
      'page' => $page,
      'pages' => $pages,
      'from' => $from,
      'to' => $to,
      'digits' => $raffle->numberDigits(),
      'statuses' => $statuses,
    ];
  }

  public function forgetGridCache(Raffle $raffle): void
  {
    // Versão da grade: invalida todas as páginas de uma vez (entradas antigas expiram em 3 s).
    Cache::increment("raffle:{$raffle->id}:grid-version");
  }

  protected function gridKey(Raffle $raffle, int $page): string
  {
    $version = (int) Cache::get("raffle:{$raffle->id}:grid-version", 0);

    return "raffle:{$raffle->id}:grid:{$version}:{$page}";
  }
}
