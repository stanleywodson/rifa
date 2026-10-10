<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   *
   * Menu por contexto: /admin → admin.json, /demo → menus originais do template
   * (com prefixo demo/), demais páginas autenticadas → organizer.json.
   */
  public function boot(): void
  {
    View::composer(['layouts.sections.menu.verticalMenu', 'layouts.sections.menu.horizontalMenu'], function ($view) {
      static $cache = [];
      $context = match (true) {
        request()->is('demo', 'demo/*') => 'demo',
        request()->is('admin', 'admin/*') || (auth()->user()?->isPlatformAdmin() && auth()->user()->tenants->isEmpty()) => 'admin',
        default => 'organizer',
      };

      $view->with('menuData', $cache[$context] ??= $this->load($context));
    });
  }

  protected function load(string $context): array
  {
    if ($context === 'demo') {
      $vertical = $this->prefixUrls(json_decode(file_get_contents(resource_path('menu/demo/verticalMenu.json'))), 'demo/');
      $horizontal = $this->prefixUrls(json_decode(file_get_contents(resource_path('menu/demo/horizontalMenu.json'))), 'demo/');

      return [$vertical, $horizontal];
    }

    $menu = json_decode(file_get_contents(resource_path("menu/{$context}.json")));

    return [$menu, $menu];
  }

  protected function prefixUrls(object $menu, string $prefix): object
  {
    $walk = function (array $items) use (&$walk, $prefix) {
      foreach ($items as $item) {
        if (isset($item->url) && !str_starts_with($item->url, 'http')) {
          $item->url = $prefix . ltrim($item->url, '/');
        }
        if (isset($item->submenu)) {
          $walk($item->submenu);
        }
      }
    };
    $walk($menu->menu);

    return $menu;
  }
}
