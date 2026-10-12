<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\Organizer;
use App\Http\Controllers\PublicSite;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Demonstração do template (referência visual) — somente ambiente local
|--------------------------------------------------------------------------
*/
if (app()->isLocal()) {
  Route::prefix('demo')->group(base_path('routes/demo.php'));
}

Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

/*
|--------------------------------------------------------------------------
| Site público da plataforma
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicSite\HomeController::class, 'index'])->name('home');
Route::view('/termos', 'content.public.terms')->name('public.terms');
Route::view('/privacidade', 'content.public.privacy')->name('public.privacy');

Route::get('/meus-numeros', [PublicSite\MyTicketsController::class, 'index'])->name('public.my-tickets');
Route::post('/meus-numeros', [PublicSite\MyTicketsController::class, 'lookup'])->middleware('throttle:lookup')->name('public.my-tickets.lookup');

Route::get('/pedido/{order:code}', [PublicSite\OrderPageController::class, 'show'])->name('public.order');
Route::get('/pedido/{order:code}/status', [PublicSite\OrderPageController::class, 'status'])->middleware('throttle:grid')->name('public.order.status');
Route::post('/pedido/{order:code}/simular-pagamento', [PublicSite\OrderPageController::class, 'simulate'])->name('public.order.simulate');

Route::post('/webhooks/payments/{gateway}', PaymentWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.payments');

/*
|--------------------------------------------------------------------------
| Área autenticada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
  Route::get('/conta', [AccountController::class, 'edit'])->name('conta.edit');

  // Painel do organizador
  Route::prefix('painel')->name('painel.')->middleware('tenant.panel')->group(function () {
    Route::get('/', [Organizer\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/organizador/{tenant:id}', [Organizer\DashboardController::class, 'switchTenant'])->name('switch');

    Route::get('/rifas/dados', [Organizer\RaffleController::class, 'data'])->name('rifas.data');
    Route::resource('rifas', Organizer\RaffleController::class)->parameters(['rifas' => 'raffle']);
    Route::post('/rifas/{raffle}/enviar', [Organizer\RaffleController::class, 'submit'])->name('rifas.submit');
    Route::post('/rifas/{raffle}/reabrir', [Organizer\RaffleController::class, 'reopen'])->name('rifas.reopen');
    Route::post('/rifas/{raffle}/cancelar', [Organizer\RaffleController::class, 'cancel'])->name('rifas.cancel');
    Route::post('/rifas/{raffle}/midias', [Organizer\RaffleMediaController::class, 'store'])->name('rifas.media.store');
    Route::delete('/rifas/{raffle}/midias/{media}', [Organizer\RaffleMediaController::class, 'destroy'])->scopeBindings()->name('rifas.media.destroy');
    Route::get('/rifas/{raffle}/autorizacao', [Organizer\RaffleExportController::class, 'authorization'])->name('rifas.authorization');
    Route::get('/rifas/{raffle}/exportar.csv', [Organizer\RaffleExportController::class, 'csv'])->name('rifas.export.csv');
    Route::get('/rifas/{raffle}/relatorio.pdf', [Organizer\RaffleExportController::class, 'pdf'])->name('rifas.export.pdf');
    Route::get('/rifas/{raffle}/sorteio', [Organizer\DrawController::class, 'create'])->name('rifas.draw.create');
    Route::post('/rifas/{raffle}/sorteio', [Organizer\DrawController::class, 'store'])->name('rifas.draw.store');
    Route::post('/rifas/{raffle}/entrega', [Organizer\DrawController::class, 'delivery'])->name('rifas.delivery');

    Route::get('/pedidos/dados', [Organizer\OrderController::class, 'data'])->name('pedidos.data');
    Route::get('/pedidos', [Organizer\OrderController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{order}', [Organizer\OrderController::class, 'show'])->name('pedidos.show');

    Route::get('/compradores/dados', [Organizer\CustomerController::class, 'data'])->name('compradores.data');
    Route::get('/compradores', [Organizer\CustomerController::class, 'index'])->name('compradores.index');
    Route::get('/compradores/{customer}', [Organizer\CustomerController::class, 'show'])->name('compradores.show');

    Route::get('/configuracoes', [Organizer\SettingsController::class, 'edit'])->name('configuracoes.edit');
    Route::put('/configuracoes', [Organizer\SettingsController::class, 'update'])->name('configuracoes.update');
    Route::post('/configuracoes/documentos', [Organizer\SettingsController::class, 'uploadDocument'])->name('configuracoes.documents');
    Route::post('/configuracoes/recebimentos', [Organizer\SettingsController::class, 'payments'])->name('configuracoes.payments');
    Route::delete('/configuracoes/recebimentos', [Organizer\SettingsController::class, 'disconnectPayments'])->name('configuracoes.payments.disconnect');
    Route::get('/configuracoes/{gateway}/conectar', [Organizer\SettingsController::class, 'connectPayments'])->name('configuracoes.payments.connect');
    // Redirect URL cadastrada na aplicação do gateway (OAuth)
    Route::get('/configuracoes/{gateway}/retorno', [Organizer\SettingsController::class, 'connectCallback'])->name('configuracoes.payments.callback');
  });

  // Administração da plataforma
  Route::prefix('admin')->name('admin.')->middleware(['can:platform-admin', '2fa'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/rifas/dados', [Admin\RaffleReviewController::class, 'data'])->name('rifas.data');
    Route::get('/rifas', [Admin\RaffleReviewController::class, 'index'])->name('rifas.index');
    Route::get('/rifas/{raffle}', [Admin\RaffleReviewController::class, 'show'])->name('rifas.show');
    Route::get('/rifas/{raffle}/autorizacao', [Admin\RaffleReviewController::class, 'authorization'])->name('rifas.authorization');
    Route::post('/rifas/{raffle}/aprovar', [Admin\RaffleReviewController::class, 'approve'])->name('rifas.approve');
    Route::post('/rifas/{raffle}/ajustes', [Admin\RaffleReviewController::class, 'requestChanges'])->name('rifas.changes');
    Route::post('/rifas/{raffle}/reprovar', [Admin\RaffleReviewController::class, 'reject'])->name('rifas.reject');
    Route::post('/rifas/{raffle}/cancelar', [Admin\RaffleReviewController::class, 'cancel'])->name('rifas.cancel');

    Route::get('/organizadores/dados', [Admin\TenantController::class, 'data'])->name('organizadores.data');
    Route::get('/organizadores', [Admin\TenantController::class, 'index'])->name('organizadores.index');
    Route::get('/organizadores/{tenant:id}', [Admin\TenantController::class, 'show'])->name('organizadores.show');
    Route::put('/organizadores/{tenant:id}', [Admin\TenantController::class, 'update'])->name('organizadores.update');
    Route::post('/organizadores/{tenant:id}/kyc', [Admin\TenantController::class, 'kyc'])->name('organizadores.kyc');
    Route::get('/organizadores/{tenant:id}/documentos/{document}', [Admin\TenantController::class, 'document'])->name('organizadores.document');

    Route::get('/planos', [Admin\PlanController::class, 'index'])->name('planos.index');
    Route::put('/planos/{plan}', [Admin\PlanController::class, 'update'])->name('planos.update');

    Route::get('/financeiro/dados', [Admin\FinanceController::class, 'data'])->name('financeiro.data');
    Route::get('/financeiro', [Admin\FinanceController::class, 'index'])->name('financeiro.index');

    Route::get('/auditoria/dados', [Admin\AuditLogController::class, 'data'])->name('auditoria.data');
    Route::get('/auditoria', [Admin\AuditLogController::class, 'index'])->name('auditoria.index');
  });
});

/*
|--------------------------------------------------------------------------
| Páginas públicas do organizador — /{organizador}/{rifa}
|--------------------------------------------------------------------------
| Registradas por último: qualquer rota acima tem prioridade. Os slugs que
| colidiriam com rotas fixas estão bloqueados em config('saas.reserved_slugs').
*/
Route::middleware('tenant.public')->group(function () {
  Route::get('/{tenant}', [PublicSite\TenantPageController::class, 'show'])->name('public.tenant');
  Route::get('/{tenant}/{raffle:slug}', [PublicSite\RafflePageController::class, 'show'])->name('public.raffle');
  Route::get('/{tenant}/{raffle:slug}/numeros', [PublicSite\RafflePageController::class, 'grid'])->middleware('throttle:grid')->name('public.raffle.grid');
  Route::get('/{tenant}/{raffle:slug}/participar', [PublicSite\CheckoutController::class, 'create'])->name('public.checkout');
  Route::post('/{tenant}/{raffle:slug}/participar', [PublicSite\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('public.checkout.store');
  Route::get('/{tenant}/{raffle:slug}/sorteio', [PublicSite\RafflePageController::class, 'draw'])->name('public.draw');
  Route::get('/{tenant}/{raffle:slug}/sorteio/ata.pdf', [PublicSite\RafflePageController::class, 'minutes'])->name('public.draw.minutes');
});
