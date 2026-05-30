<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition()
    {
        return [
            'version_id' => Version::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'registered_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
