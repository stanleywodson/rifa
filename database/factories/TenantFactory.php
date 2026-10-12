<?php

namespace Database\Factories;

use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Enums\TenantStatus;
use App\Domain\Tenants\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'document' => fake()->cnpj(false),
            'email' => fake()->companyEmail(),
            'phone' => '119' . fake()->numerify('########'),
            'status' => TenantStatus::Active,
            'kyc_status' => KycStatus::Approved,
            'kyc_reviewed_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['kyc_status' => KycStatus::Pending, 'kyc_reviewed_at' => null]);
    }

    /**
     * Conta Mercado Pago conectada via OAuth.
     */
    public function mercadoPago(string $userId = '123456789'): static
    {
        return $this->state(fn () => [
            'payment_gateway' => 'mercadopago',
            'mp_user_id' => $userId,
            'mp_access_token' => 'APP_USR-seller-token',
            'mp_refresh_token' => 'TG-seller-refresh',
            'mp_public_key' => 'APP_USR-public-key',
            'mp_token_expires_at' => now()->addDays(180),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => TenantStatus::Suspended]);
    }

    /**
     * Vincula um usuário como membro do organizador.
     */
    public function withOwner(?User $user = null, TenantRole $role = TenantRole::Owner): static
    {
        return $this->afterCreating(function (Tenant $tenant) use ($user, $role) {
            $tenant->users()->attach($user ?? User::factory()->create(), ['role' => $role->value]);
        });
    }
}
