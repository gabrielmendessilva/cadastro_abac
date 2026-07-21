<?php

namespace Database\Factories;

use App\Models\Client;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 *
 * Núcleo da tabela `clients` é EM INGLÊS (name/fantasy_name/document/email/phone/mobile/notes).
 * As extensões legadas continuam em PT-BR. Esta factory preenche só o núcleo —
 * campos legados devem ser passados explicitamente nos testes que precisarem deles.
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Locale fixo em pt_BR (APP_FAKER_LOCALE) — não depende do .env da máquina,
     * e é o que dá acesso a cnpj()/companyEmail() no formato brasileiro.
     */
    protected function withFaker(): Generator
    {
        return FakerFactory::create('pt_BR');
    }

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'fantasy_name' => $this->faker->company(),
            // cnpj() já vem formatado ##.###.###/####-## (18 chars, cabe no varchar(20)).
            // unique() é obrigatório: a coluna tem índice UNIQUE.
            'document' => $this->faker->unique()->cnpj(),
            'email' => $this->faker->unique()->companyEmail(),
            // numerify em vez de phoneNumber(): garante <= 20 chars (limite da coluna).
            'phone' => $this->faker->numerify('(##) 3###-####'),
            'mobile' => $this->faker->numerify('(##) 9####-####'),
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }

}
