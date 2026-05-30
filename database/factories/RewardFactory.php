<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Reward;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardFactory extends Factory
{
    protected $model = Reward::class;

    public function definition()
    {
        return [
            'version_id' => Version::factory(),
            'player_id' => Player::factory(),
            'name' => $this->faker->randomElement(['gift_card', 'discount', 'badge', 'coin_pack']),
            'value' => $this->faker->randomFloat(2, 5, 100),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
