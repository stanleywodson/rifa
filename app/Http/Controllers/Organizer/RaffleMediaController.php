<?php

namespace App\Http\Controllers\Organizer;

use App\Domain\Raffles\Models\Raffle;
use App\Domain\Raffles\Models\RaffleMedia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Upload de imagens do prêmio via Dropzone (tela de edição).
 */
class RaffleMediaController extends Controller
{
  public function store(Request $request, Raffle $raffle)
  {
    $this->authorize('update', $raffle);
    $request->validate(['file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']], [], ['file' => 'imagem']);
    abort_if($raffle->media()->count() >= 8, 422, 'Limite de 8 imagens por rifa.');

    $media = $raffle->media()->create([
      'path' => $request->file('file')->store("raffles/{$raffle->id}", 'public'),
      'sort' => (int) $raffle->media()->max('sort') + 1,
    ]);

    return response()->json([
      'id' => $media->id,
      'url' => $media->url(),
      'delete_url' => route('painel.rifas.media.destroy', [$raffle, $media]),
    ]);
  }

  public function destroy(Raffle $raffle, RaffleMedia $media)
  {
    $this->authorize('update', $raffle);
    Storage::disk('public')->delete($media->path);
    $media->delete();

    return request()->expectsJson() ? response()->json(['deleted' => true]) : back()->with('success', 'Imagem removida.');
  }
}
