<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Transaction;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'version_id' => Version::factory(),
            'player_id' => Player::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 500),
            'currency' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'payload' => [
                'gateway' => $this->faker->randomElement(['stripe', 'paypal']),
            ],
        ];
    }
}
