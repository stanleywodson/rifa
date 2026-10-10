<?php

namespace App\Actions\Fortify;

use App\Domain\Compliance\AuditLogger;
use App\Domain\Tenants\Enums\KycStatus;
use App\Domain\Tenants\Enums\TenantRole;
use App\Domain\Tenants\Enums\TenantStatus;
use App\Domain\Tenants\Models\Plan;
use App\Domain\Tenants\Models\Tenant;
use App\Models\User;
use App\Rules\CpfCnpj;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Cadastro do organizador: cria o usuário (owner) e a organização (tenant).
 */
class CreateNewUser implements CreatesNewUsers
{
  use PasswordValidationRules;

  /**
   * Validate and create a newly registered user.
   *
   * @param  array<string, string>  $input
   *
   * @throws ValidationException
   */
  public function create(array $input): User
  {
    $input['slug'] = Str::slug($input['slug'] ?? '');

    Validator::make($input, [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
      'phone' => ['required', 'string', 'max:20'],
      'password' => $this->passwordRules(),
      'organization' => ['required', 'string', 'max:120'],
      'slug' => ['required', 'string', 'min:3', 'max:60', 'alpha_dash', Rule::unique(Tenant::class, 'slug'), Rule::notIn(config('saas.reserved_slugs'))],
      'document' => ['required', new CpfCnpj],
      'terms' => ['accepted'],
      'adult' => ['accepted'],
    ], [
      'slug.not_in' => 'Este endereço é reservado pela plataforma. Escolha outro.',
      'terms.accepted' => 'É necessário aceitar os Termos de Uso e a Política de Privacidade.',
      'adult.accepted' => 'O responsável pela conta deve ser maior de 18 anos.',
    ], [
      'name' => 'nome',
      'phone' => 'telefone',
      'password' => 'senha',
      'organization' => 'nome da organização',
      'slug' => 'endereço da página',
      'document' => 'CPF/CNPJ',
    ])->validate();

    return DB::transaction(function () use ($input) {
      $user = User::create([
        'name' => $input['name'],
        'email' => $input['email'],
        'phone' => only_digits($input['phone']),
        'password' => Hash::make($input['password']),
      ]);

      $tenant = Tenant::create([
        'name' => $input['organization'],
        'slug' => $input['slug'],
        'document' => only_digits($input['document']),
        'email' => $input['email'],
        'phone' => only_digits($input['phone']),
        'status' => TenantStatus::Active,
        'kyc_status' => KycStatus::Pending,
        'plan_id' => Plan::query()->where('code', 'starter')->value('id'),
      ]);
      $tenant->users()->attach($user->id, ['role' => TenantRole::Owner->value]);

      app(AuditLogger::class)->log('tenant.registered', $tenant, null, ['name' => $tenant->name, 'slug' => $tenant->slug], $tenant->id);

      return $user;
    });
  }
}
