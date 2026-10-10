<?php

namespace App\Domain\Draws;

use App\Domain\Draws\Exceptions\DrawException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Consulta o resultado oficial da Loteria Federal (portal de loterias da Caixa).
 */
class LoteriaFederalClient
{
  /**
   * @return array{concurso: string, data: ?string, premios: string[], fonte: string}
   */
  public function result(string $concurso): array
  {
    $url = rtrim(config('saas.loteria_federal_url'), '/') . '/' . urlencode($concurso);

    try {
      $response = Http::timeout(10)->acceptJson()->withOptions(['verify' => app()->isProduction()])->get($url);
    } catch (Throwable $e) {
      throw new DrawException('Não foi possível consultar a Loteria Federal: ' . $e->getMessage());
    }

    if ($response->failed()) {
      throw new DrawException("Concurso {$concurso} não encontrado na Loteria Federal.");
    }

    $json = $response->json() ?? [];
    $premios = $json['listaDezenas'] ?? $json['dezenasSorteadasOrdemSorteio'] ?? [];
    $premios = array_values(array_filter(array_map(fn($v) => only_digits((string) $v), (array) $premios)));

    if (!$premios) {
      throw new DrawException("O resultado do concurso {$concurso} ainda não está disponível.");
    }

    return [
      'concurso' => (string) ($json['numero'] ?? $concurso),
      'data' => $json['dataApuracao'] ?? null,
      'premios' => $premios,
      'fonte' => $url,
    ];
  }
}
