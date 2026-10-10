<?php

use App\Domain\Draws\Exceptions\DrawException;
use App\Domain\Orders\Exceptions\CheckoutUnavailable;
use App\Domain\Raffles\Exceptions\InvalidRaffleTransition;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetPanelTenant;
use App\Http\Middleware\SetPublicTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [LocaleMiddleware::class, SecurityHeaders::class]);

    // Atrás de proxy/túnel (ngrok, Cloudflare, Nginx): respeita X-Forwarded-Proto/Host
    // para gerar URLs https e evitar bloqueio de "mixed content".
    $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

    $middleware->alias([
      'tenant.panel' => SetPanelTenant::class,
      'tenant.public' => SetPublicTenant::class,
      '2fa' => EnsureTwoFactorEnabled::class,
    ]);

    // O tenant precisa estar no contexto antes do route model binding.
    $middleware->prependToPriorityList(SubstituteBindings::class, SetPanelTenant::class);
    $middleware->prependToPriorityList(SubstituteBindings::class, SetPublicTenant::class);

    $middleware->validateCsrfTokens(except: ['webhooks/*']);

    $middleware->redirectGuestsTo(fn() => route('login'));
  })
  ->withExceptions(function (Exceptions $exceptions) {
    // Regras de negócio não são falhas da aplicação: não poluem o log de erros
    // (a causa técnica, ex. resposta do gateway, já é registrada no ponto de origem).
    $exceptions->dontReport([CheckoutUnavailable::class, DrawException::class, InvalidRaffleTransition::class]);

    // Regras de negócio violadas viram mensagem amigável na tela anterior (ou 422 em JSON).
    $exceptions->render(function (CheckoutUnavailable|DrawException|InvalidRaffleTransition $e, Request $request) {
      if ($request->expectsJson()) {
        return response()->json(['message' => $e->getMessage()], 422);
      }

      return back()->withInput()->with('error', $e->getMessage());
    });
  })->create();
