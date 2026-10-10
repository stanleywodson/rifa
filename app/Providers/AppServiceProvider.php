<?php

namespace App\Providers;

use App\Domain\Notifications\Listeners\LogSentMessage;
use App\Domain\Orders\Models\Customer;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\TenantContext;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\RafflePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->app->scoped(TenantContext::class);
    $this->app->singleton(PaymentGatewayManager::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                    (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });

    Paginator::useBootstrapFive();

    // Autorização
    Gate::define('platform-admin', fn(User $user) => $user->isPlatformAdmin());
    Gate::policy(Raffle::class, RafflePolicy::class);
    Gate::policy(Order::class, OrderPolicy::class);
    Gate::policy(Customer::class, CustomerPolicy::class);

    // Rate limits das rotas públicas sensíveis
    RateLimiter::for('checkout', fn(Request $request) => [
      Limit::perMinute(10)->by('ip:' . $request->ip()),
      Limit::perHour(30)->by('phone:' . only_digits($request->input('phone'))),
    ]);
    RateLimiter::for('lookup', fn(Request $request) => Limit::perMinute(10)->by($request->ip()));
    RateLimiter::for('grid', fn(Request $request) => Limit::perMinute(120)->by($request->ip()));
    RateLimiter::for('webhooks', fn(Request $request) => Limit::perMinute(600)->by($request->ip()));

    Event::listen(MessageSent::class, LogSentMessage::class);
  }
}
