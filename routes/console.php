<?php

use App\Domain\Compliance\AuditLogger;
use App\Domain\Orders\Jobs\ReleaseExpiredReservationsJob;
use App\Domain\Payments\Jobs\ReconcilePendingPaymentsJob;
use App\Domain\Payments\Jobs\RefreshMercadoPagoTokensJob;
use App\Domain\Raffles\Jobs\CloseSalesJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Reservas vencidas voltam para a grade
Schedule::job(new ReleaseExpiredReservationsJob)->everyMinute()->withoutOverlapping();
// Encerra vendas por data
Schedule::job(new CloseSalesJob)->everyMinute()->withoutOverlapping();
// Conciliação com o gateway (fallback do webhook)
Schedule::job(new ReconcilePendingPaymentsJob)->everyFiveMinutes()->withoutOverlapping();
// Tokens OAuth do Mercado Pago (validade de 180 dias)
Schedule::job(new RefreshMercadoPagoTokensJob)->dailyAt('03:00');

Artisan::command('audit:verify', function (AuditLogger $audit) {
  $broken = $audit->verifyChain();
  if ($broken) {
    $this->error("Cadeia de auditoria adulterada a partir do registro #{$broken}.");

    return 1;
  }
  $this->info('Cadeia de auditoria íntegra.');

  return 0;
})->purpose('Verifica a integridade (hash encadeado) da trilha de auditoria');
