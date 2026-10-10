<?php

namespace App\Policies;

use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Models\Raffle;
use App\Models\User;

/**
 * Proprietário: gestão completa. Operador: leitura, pedidos e relatórios.
 * Sempre confere o tenant da rifa (defesa em profundidade além do escopo global).
 */
class RafflePolicy
{
  public function view(User $user, Raffle $raffle): bool
  {
    return $user->belongsToTenant($raffle->tenant);
  }

  public function create(User $user): bool
  {
    $tenant = app(\App\Domain\Tenants\TenantContext::class)->get();

    return $tenant && $user->isOwnerOf($tenant) && $tenant->isActive();
  }

  public function update(User $user, Raffle $raffle): bool
  {
    return $user->isOwnerOf($raffle->tenant) && $raffle->isEditable();
  }

  public function delete(User $user, Raffle $raffle): bool
  {
    return $user->isOwnerOf($raffle->tenant) && $raffle->status === RaffleStatus::Draft;
  }

  public function submit(User $user, Raffle $raffle): bool
  {
    return $this->update($user, $raffle);
  }

  public function cancel(User $user, Raffle $raffle): bool
  {
    return $user->isOwnerOf($raffle->tenant)
      && in_array($raffle->status, [RaffleStatus::Draft, RaffleStatus::ChangesRequested, RaffleStatus::Published, RaffleStatus::SalesClosed], true);
  }

  public function draw(User $user, Raffle $raffle): bool
  {
    return $user->isOwnerOf($raffle->tenant) && $raffle->status === RaffleStatus::SalesClosed;
  }

  public function complete(User $user, Raffle $raffle): bool
  {
    return $user->isOwnerOf($raffle->tenant) && $raffle->status === RaffleStatus::Drawn;
  }

  public function export(User $user, Raffle $raffle): bool
  {
    return $this->view($user, $raffle);
  }
}
