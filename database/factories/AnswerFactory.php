<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Player;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition()
    {
        return [
            'version_id' => Version::factory(),
            'player_id' => Player::factory(),
            'question' => $this->faker->sentence(6, true).'?',
            'answer' => $this->faker->sentence(),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
