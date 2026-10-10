<?php

namespace App\Models;

use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Models\Tenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
  use HasFactory, Notifiable, TwoFactorAuthenticatable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'cpf',
    'phone',
    'birthdate',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'birthdate' => 'date',
      'is_platform_admin' => 'boolean',
      'two_factor_confirmed_at' => 'datetime',
    ];
  }

  public function tenants(): BelongsToMany
  {
    return $this->belongsToMany(Tenant::class)->withPivot('role')->withTimestamps();
  }

  public function isPlatformAdmin(): bool
  {
    return (bool) $this->is_platform_admin;
  }

  public function roleIn(?Tenant $tenant): ?TenantRole
  {
    if (!$tenant) {
      return null;
    }
    $membership = $this->tenants->firstWhere('id', $tenant->id);

    return $membership ? TenantRole::from($membership->pivot->role) : null;
  }

  public function isOwnerOf(?Tenant $tenant): bool
  {
    return $this->roleIn($tenant) === TenantRole::Owner;
  }

  public function belongsToTenant(?Tenant $tenant): bool
  {
    return $this->roleIn($tenant) !== null;
  }

  public function initials(): string
  {
    return collect(explode(' ', $this->name))->filter()->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
  }
}
