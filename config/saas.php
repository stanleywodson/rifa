<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Vendas
  |--------------------------------------------------------------------------
  | Bloqueio jurídico: enquanto false, páginas públicas são exibidas mas
  | nenhuma reserva/cobrança é aceita.
  */
  'sales_enabled' => (bool) env('RAFFLE_SALES_ENABLED', true),

  // Tempo (minutos) que os números ficam reservados aguardando o Pix
  'reservation_ttl' => (int) env('RAFFLE_RESERVATION_TTL', 10),

  // Limite de números por rifa no MVP (modelo com uma linha por número)
  'max_numbers' => (int) env('RAFFLE_MAX_NUMBERS', 100000),

  // Máximo de números em um único pedido
  'max_numbers_per_order' => 500,

  // Tamanho da página da grade pública de números
  'grid_page_size' => 500,

  // Taxa padrão da plataforma (%) quando o plano/organizador não define outra
  'platform_fee_percent' => (float) env('PLATFORM_FEE_PERCENT', 5),

  // 2FA obrigatório para administradores da plataforma
  'require_admin_2fa' => (bool) env('REQUIRE_ADMIN_2FA', env('APP_ENV') !== 'local' && env('APP_ENV') !== 'testing'),

  // Idade mínima do comprador
  'minimum_age' => 18,

  /*
  |--------------------------------------------------------------------------
  | Pagamentos
  |--------------------------------------------------------------------------
  */
  'payment_gateway' => env('PAYMENT_GATEWAY', 'fake'), // fake | asaas

  'asaas' => [
    'env' => env('ASAAS_ENV', 'sandbox'),
    'api_key' => env('ASAAS_API_KEY'),
    'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
    'base_url' => env('ASAAS_ENV', 'sandbox') === 'production'
      ? 'https://api.asaas.com/v3'
      : 'https://api-sandbox.asaas.com/v3',
  ],

  /*
  |--------------------------------------------------------------------------
  | Sorteio
  |--------------------------------------------------------------------------
  */
  'loteria_federal_url' => env('LOTERIA_FEDERAL_URL', 'https://servicebus2.caixa.gov.br/portaldeloterias/api/federal'),

  /*
  |--------------------------------------------------------------------------
  | Slugs reservados
  |--------------------------------------------------------------------------
  | Primeiro segmento das URLs públicas /{organizador}/{rifa}; não podem ser
  | usados como slug de organizador.
  */
  'reserved_slugs' => [
    'admin', 'painel', 'login', 'logout', 'register', 'cadastro', 'api', 'webhooks',
    'meus-numeros', 'pedido', 'pedidos', 'demo', 'lang', 'up', 'storage', 'assets',
    'build', 'forgot-password', 'reset-password', 'email', 'user', 'two-factor-challenge',
    'termos', 'privacidade', 'planos', 'sorteio', 'rifas', 'conta', 'arquivos',
  ],

];
