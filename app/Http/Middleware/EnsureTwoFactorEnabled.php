<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige verificação em duas etapas confirmada (administradores da plataforma).
 */
class EnsureTwoFactorEnabled
{
  public function handle(Request $request, Closure $next): Response
  {
    if (config('saas.require_admin_2fa') && !$request->user()?->two_factor_confirmed_at) {
      return redirect()->route('conta.edit', ['aba' => 'seguranca'])
        ->with('warning', 'Ative a verificação em duas etapas para acessar a administração.');
    }

    return $next($request);
  }
}
