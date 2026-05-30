<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Player;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition()
    {
        $types = ['open', 'register', 'complete', 'level_up', 'share'];
        $languages = ['it', 'en', 'es', 'fr', 'de'];
        $sources = ['linkedin', 'facebook', 'google', 'direct', 'newsletter'];

        return [
            'version_id' => Version::factory(),
            'player_id' => Player::factory(),
            'type' => $this->faker->randomElement($types),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'payload' => [
                'score' => $this->faker->numberBetween(0, 1000),
                'level' => $this->faker->numberBetween(1, 20),
                'language' => $this->faker->randomElement($languages),
                'utm_source' => $this->faker->randomElement($sources),
            ],
        ];
    }
}
