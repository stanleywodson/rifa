<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resposta server-side para DataTables (padrão do exemplo laravel-user-management
 * do template). As células chegam prontas em HTML, renderizadas no servidor,
 * e são exibidas por resources/assets/js/saas-datatable.js.
 */
class DataTable
{
  /**
   * @param  array<int, string|null>  $sortable  índice da coluna => coluna SQL ordenável
   * @param  Closure(Builder, string): void  $search
   * @param  Closure(mixed): array  $row
   */
  public static function make(Request $request, Builder $query, array $sortable, Closure $search, Closure $row): JsonResponse
  {
    $total = (clone $query)->count();

    if (filled($term = trim((string) $request->input('search.value')))) {
      $query->where(fn(Builder $q) => $search($q, $term));
    }
    $filtered = (clone $query)->count();

    $column = $sortable[(int) $request->input('order.0.column')] ?? null;
    if ($column) {
      $query->reorder($column, $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc');
    }

    $length = min(max((int) $request->input('length', 10), 1), 100);
    $rows = $query->offset(max((int) $request->input('start', 0), 0))->limit($length)->get();

    return response()->json([
      'draw' => (int) $request->input('draw'),
      'recordsTotal' => $total,
      'recordsFiltered' => $filtered,
      'data' => $rows->map($row)->values(),
    ]);
  }

  /** Avatar com iniciais + nome/subtítulo (mesmo markup das listas do template). */
  public static function person(string $name, ?string $subtitle = null, ?string $url = null): string
  {
    $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $states = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
    $state = $states[crc32($name) % count($states)];
    $title = $url ? '<a href="' . e($url) . '" class="text-heading">' . e($name) . '</a>' : '<span class="text-heading">' . e($name) . '</span>';

    return '<div class="d-flex justify-content-start align-items-center text-nowrap">'
      . '<div class="avatar-wrapper"><div class="avatar avatar-sm me-3"><span class="avatar-initial rounded-circle bg-label-' . $state . '">' . e($initials) . '</span></div></div>'
      . '<div class="d-flex flex-column"><h6 class="m-0">' . $title . '</h6>'
      . ($subtitle ? '<small>' . e($subtitle) . '</small>' : '')
      . '</div></div>';
  }

  public static function link(string $url, string $text): string
  {
    return '<a href="' . e($url) . '"><span>' . e($text) . '</span></a>';
  }

  public static function date(?\DateTimeInterface $date): string
  {
    return $date ? '<span class="text-nowrap">' . $date->format('d/m/Y H:i') . '</span>' : '—';
  }

  /** Botão de ações com dropdown (ícone ti-dots-vertical do template). */
  public static function actions(array $links): string
  {
    $items = collect($links)->map(fn($url, $label) => '<a href="' . e($url) . '" class="dropdown-item">' . e($label) . '</a>')->implode('');

    return '<div class="d-flex justify-content-sm-start align-items-sm-center">'
      . '<button class="btn btn-icon btn-text-secondary waves-effect waves-light rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>'
      . '<div class="dropdown-menu dropdown-menu-end m-0">' . $items . '</div></div>';
  }
}
