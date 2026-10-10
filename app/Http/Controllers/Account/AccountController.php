<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Perfil, senha e 2FA — os formulários enviam para as rotas do Fortify.
 */
class AccountController extends Controller
{
  public function edit(Request $request)
  {
    return view('content.account.edit', [
      'user' => $request->user(),
      'tab' => $request->query('aba', 'perfil'),
    ]);
  }
}
