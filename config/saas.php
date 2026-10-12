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
  // Gateway padrão (fake | asaas | mercadopago). Com "fake", todos os organizadores
  // recebem pelo gateway simulado, sem precisar conectar uma conta.
  'payment_gateway' => env('PAYMENT_GATEWAY', 'fake'),

  // Gateways que o organizador pode escolher em Configurações → Recebimentos,
  // além do padrão. Ex.: PAYMENT_GATEWAYS=asaas,mercadopago
  'payment_gateways' => array_values(array_filter(array_map('trim', explode(',', (string) env('PAYMENT_GATEWAYS', ''))))),

  'asaas' => [
    'env' => env('ASAAS_ENV', 'sandbox'),
    'api_key' => env('ASAAS_API_KEY'),
    'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),
    'base_url' => env('ASAAS_ENV', 'sandbox') === 'production'
      ? 'https://api.asaas.com/v3'
      : 'https://api-sandbox.asaas.com/v3',
  ],

  // Mercado Pago (modelo marketplace): o organizador conecta a própria conta via
  // OAuth; a cobrança é criada com o token dele e a taxa vai como application_fee.
  'mercadopago' => [
    'env' => env('MERCADOPAGO_ENV', 'sandbox'),
    // Pede token "TEST-" no OAuth (test_token=true). Só serve para contas reais em modo
    // de teste; com usuários de teste (recomendado) o Mercado Pago usa credenciais de produção.
    'test_token' => (bool) env('MERCADOPAGO_TEST_TOKEN', false),
    // Fora de produção: e-mail do comprador de teste usado como pagador em todas as
    // cobranças (o Mercado Pago recusa e-mail real com credenciais de teste).
    'test_payer_email' => env('MERCADOPAGO_TEST_PAYER_EMAIL'),
    'client_id' => env('MERCADOPAGO_CLIENT_ID'),
    'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
    // Assinatura secreta dos webhooks (Suas integrações → Webhooks)
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    'base_url' => 'https://api.mercadopago.com',
    'auth_url' => 'https://auth.mercadopago.com.br/authorization',
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
