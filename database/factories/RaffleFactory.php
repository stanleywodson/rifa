<?php

namespace Database\Factories;

use App\Domain\Raffles\Enums\DrawMethod;
use App\Domain\Raffles\Enums\RaffleStatus;
use App\Domain\Raffles\Enums\UnsoldRule;
use App\Domain\Raffles\Models\Raffle;
use App\Domain\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Raffle>
 */
class RaffleFactory extends Factory
{
    protected $model = Raffle::class;

    public function definition(): array
    {
        $title = 'Rifa ' . fake()->randomElement(['Moto 0km', 'iPhone 16', 'Smart TV 65"', 'Notebook Gamer', 'Viagem para Gramado', 'Bicicleta elétrica']);

        return [
            'tenant_id' => Tenant::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(5)),
            'summary' => fake()->sentence(12),
            'description' => '<p>' . fake()->paragraph(4) . '</p>',
            'status' => RaffleStatus::Draft,
            'total_numbers' => 100,
            'number_start' => 0,
            'price_cents' => 1000,
            'min_numbers_per_order' => 1,
            'sales_end_at' => now()->addDays(20),
            'draw_at' => now()->addDays(21),
            'draw_method' => DrawMethod::Federal,
            'unsold_rule' => UnsoldRule::NextPaidAbove,
            'regulation' => "1. O sorteio será realizado com base no 1º prêmio da Loteria Federal.\n2. O número contemplado deverá estar pago.\n3. O prêmio será entregue em até 30 dias após o sorteio.",
            'authorization_number' => 'SPA/MF 01.' . fake()->numerify('###.###/2026'),
            'authorization_doc_path' => 'seed/autorizacao.pdf',
        ];
    }

    public function status(RaffleStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function method(DrawMethod $method, UnsoldRule $rule = UnsoldRule::NextPaidAbove): static
    {
        return $this->state(fn () => ['draw_method' => $method, 'unsold_rule' => $rule]);
    }
}
