<?php

namespace App\Domain\Tenants\Models;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Enums\TenantStatus;
use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(TenantFactory::class)]
class Tenant extends Model
{
  use HasFactory;

  protected $fillable = [
    'name', 'slug', 'document', 'email', 'phone', 'status', 'plan_id', 'fee_percent',
    'kyc_status', 'kyc_reviewed_at', 'kyc_notes', 'asaas_account_id', 'asaas_wallet_id',
    'asaas_api_key', 'payment_gateway', 'mp_user_id', 'mp_access_token', 'mp_refresh_token',
    'mp_public_key', 'mp_token_expires_at', 'mp_live_mode', 'settings',
  ];

  protected $hidden = ['asaas_api_key', 'mp_access_token', 'mp_refresh_token'];

  protected function casts(): array
  {
    return [
      'status' => TenantStatus::class,
      'kyc_status' => KycStatus::class,
      'kyc_reviewed_at' => 'datetime',
      'asaas_api_key' => 'encrypted',
      'mp_access_token' => 'encrypted',
      'mp_refresh_token' => 'encrypted',
      'mp_token_expires_at' => 'datetime',
      'mp_live_mode' => 'boolean',
      'settings' => 'array',
      'fee_percent' => 'float',
    ];
  }

  public function getRouteKeyName(): string
  {
    return 'slug';
  }

  public function plan(): BelongsTo
  {
    return $this->belongsTo(Plan::class);
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
  }

  public function raffles(): HasMany
  {
    return $this->hasMany(Raffle::class);
  }

  public function kycDocuments(): HasMany
  {
    return $this->hasMany(KycDocument::class);
  }

  public function isActive(): bool
  {
    return $this->status === TenantStatus::Active;
  }

  public function isVerified(): bool
  {
    return $this->kyc_status === KycStatus::Approved;
  }

  public function feePercent(): float
  {
    return $this->fee_percent ?? $this->plan?->fee_percent ?? (float) config('saas.platform_fee_percent');
  }

  public function roleOf(User $user): ?TenantRole
  {
    $member = $this->users()->whereKey($user->id)->first();

    return $member ? TenantRole::from($member->pivot->role) : null;
  }

  /**
   * Gateway em que o organizador tem conta de recebimento válida (habilitado na
   * plataforma e com credenciais). Uma conta de outro gateway/ambiente não vale.
   */
  public function paymentGateway(): ?PaymentGateway
  {
    return app(PaymentGatewayManager::class)->connected($this);
  }

  public function hasPaymentAccount(): bool
  {
    return $this->paymentGateway() !== null;
  }

  /**
   * Conta que recebe o split (carteira Asaas, usuário Mercado Pago...).
   */
  public function paymentAccountId(): ?string
  {
    return $this->paymentGateway()?->accountId($this);
  }

  /**
   * Existe uma conta registrada, mas de um gateway desabilitado ou de outro ambiente.
   */
  public function hasStalePaymentAccount(): bool
  {
    return filled($this->payment_gateway) && !$this->hasPaymentAccount();
  }

  public function canReceivePayments(): bool
  {
    return app(PaymentGatewayManager::class)->forTenant($this) !== null;
  }
}
