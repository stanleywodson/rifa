<?php

namespace Tests\Feature;

use App\Domain\Orders\Models\Order;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Tenant;
use App\Domain\Tickets\Enums\TicketStatus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Processos paralelos (pcntl_fork) disputando os mesmos números em conexões
 * MySQL independentes: exatamente um deve vencer. Não usa RefreshDatabase
 * (as transações precisam ser reais e visíveis entre processos).
 */
class ConcurrencyTest extends TestCase
{
  protected function tearDown(): void
  {
    if (isset($this->raffle)) {
      DB::table('order_items')->whereIn('order_id', Order::withoutGlobalScopes()->where('raffle_id', $this->raffle->id)->pluck('id'))->delete();
      DB::table('tickets')->where('raffle_id', $this->raffle->id)->update(['order_id' => null]);
      DB::table('payment_splits')->delete();
      DB::table('payments')->whereIn('order_id', Order::withoutGlobalScopes()->where('raffle_id', $this->raffle->id)->pluck('id'))->delete();
      Order::withoutGlobalScopes()->where('raffle_id', $this->raffle->id)->delete();
      Tenant::query()->whereKey($this->raffle->tenant_id)->delete();
    }
    parent::tearDown();
  }

  protected ?Raffle $raffle = null;

  public function test_only_one_buyer_wins_the_same_number(): void
  {
    if (!function_exists('pcntl_fork')) {
      $this->markTestSkipped('pcntl indisponível');
    }

    $this->artisan('migrate');
    [$tenant] = $this->organizer();
    $this->raffle = $this->publishedRaffle($tenant, ['total_numbers' => 50]);
    $this->forgetTenant();

    $workers = 8;
    $dir = sys_get_temp_dir() . '/rifa-race-' . uniqid();
    mkdir($dir);
    $pids = [];
    for ($i = 0; $i < $workers; $i++) {
      $pid = pcntl_fork();
      if ($pid === 0) {
        // Processo filho: conexão própria; grava o resultado e encerra sem
        // executar os handlers de shutdown do PHPUnit.
        DB::purge();
        try {
          $this->checkout($this->raffle, [7, 8], buyer: ['cpf' => fake('pt_BR')->cpf(false)]);
          file_put_contents("{$dir}/{$i}", 'win');
        } catch (\Throwable $e) {
          file_put_contents("{$dir}/{$i}", 'lose');
        }
        posix_kill(getmypid(), SIGKILL);
      }
      $pids[] = $pid;
    }

    foreach ($pids as $pid) {
      pcntl_waitpid($pid, $status);
    }
    $wins = count(array_filter(glob("{$dir}/*"), fn($f) => file_get_contents($f) === 'win'));
    array_map('unlink', glob("{$dir}/*"));
    rmdir($dir);
    DB::purge();
    DB::reconnect();

    $this->assertSame(1, $wins);
    $owners = DB::table('tickets')->where('raffle_id', $this->raffle->id)->whereIn('number', [7, 8])
      ->where('status', TicketStatus::Reserved->value)->distinct()->pluck('order_id');
    $this->assertCount(1, $owners);
  }
}
