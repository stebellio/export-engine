<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_version()
    {
        $response = $this->postJson('/api/v1/versions', ['name' => 'Campagna X']);

        $response->assertStatus(201)->assertJsonPath('name', 'Campagna X');
        $this->assertDatabaseHas('versions', ['name' => 'Campagna X']);
    }

    public function test_version_name_is_required()
    {
        $this->postJson('/api/v1/versions', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_bulk_inserts_players_and_returns_ids()
    {
        $version = Version::factory()->create();

        $response = $this->postJson("/api/v1/versions/{$version->id}/players", [
            'items' => [
                ['email' => 'a@example.test', 'registered_at' => '2026-01-01 10:00:00'],
                ['email' => 'b@example.test'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('count', 2)
            ->assertJsonCount(2, 'items');
        $this->assertDatabaseHas('players', ['version_id' => $version->id, 'email' => 'a@example.test']);
        $this->assertDatabaseHas('players', ['version_id' => $version->id, 'email' => 'b@example.test']);
    }

    public function test_player_email_must_be_valid()
    {
        $version = Version::factory()->create();

        $this->postJson("/api/v1/versions/{$version->id}/players", [
            'items' => [['email' => 'not-an-email']],
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.email']);
    }

    public function test_batch_size_is_capped_by_config()
    {
        config(['ingestion.max_items_per_batch' => 2]);
        $version = Version::factory()->create();

        $this->postJson("/api/v1/versions/{$version->id}/players", [
            'items' => [['email' => 'a@x.test'], ['email' => 'b@x.test'], ['email' => 'c@x.test']],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);
    }

    public function test_inserts_events_for_players_in_version()
    {
        $version = Version::factory()->create();
        $player = Player::factory()->for($version)->create();

        $this->postJson("/api/v1/versions/{$version->id}/events", [
            'items' => [[
                'player_id' => $player->id,
                'type' => 'open',
                'occurred_at' => '2026-01-01 10:00:00',
                'payload' => ['language' => 'it'],
            ]],
        ])->assertStatus(201)->assertJsonPath('count', 1);

        $this->assertDatabaseHas('events', [
            'version_id' => $version->id,
            'player_id' => $player->id,
            'type' => 'open',
        ]);
    }

    public function test_rejects_child_records_for_player_of_another_version()
    {
        $versionA = Version::factory()->create();
        $versionB = Version::factory()->create();
        $playerB = Player::factory()->for($versionB)->create();

        // Evento sotto la versione A che referenzia un player della versione B.
        $this->postJson("/api/v1/versions/{$versionA->id}/events", [
            'items' => [[
                'player_id' => $playerB->id,
                'type' => 'open',
                'occurred_at' => '2026-01-01 10:00:00',
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_inserts_transactions_uppercasing_currency()
    {
        $version = Version::factory()->create();
        $player = Player::factory()->for($version)->create();

        $this->postJson("/api/v1/versions/{$version->id}/transactions", [
            'items' => [[
                'player_id' => $player->id,
                'amount' => 12.5,
                'currency' => 'eur',
                'occurred_at' => '2026-01-01 10:00:00',
            ]],
        ])->assertStatus(201);

        $this->assertDatabaseHas('transactions', [
            'version_id' => $version->id,
            'player_id' => $player->id,
            'currency' => 'EUR',
        ]);
    }

    public function test_inserts_answers_and_rewards()
    {
        $version = Version::factory()->create();
        $player = Player::factory()->for($version)->create();

        $this->postJson("/api/v1/versions/{$version->id}/answers", [
            'items' => [[
                'player_id' => $player->id,
                'question' => 'Q?',
                'answer' => 'A',
                'occurred_at' => '2026-01-01 10:00:00',
            ]],
        ])->assertStatus(201);

        $this->postJson("/api/v1/versions/{$version->id}/rewards", [
            'items' => [[
                'player_id' => $player->id,
                'name' => 'gift_card',
                'value' => 9.99,
                'occurred_at' => '2026-01-01 10:00:00',
            ]],
        ])->assertStatus(201);

        $this->assertDatabaseHas('answers', ['version_id' => $version->id, 'question' => 'Q?']);
        $this->assertDatabaseHas('rewards', ['version_id' => $version->id, 'name' => 'gift_card']);
    }

    public function test_events_require_known_player()
    {
        $version = Version::factory()->create();

        $this->postJson("/api/v1/versions/{$version->id}/events", [
            'items' => [[
                'player_id' => 999999,
                'type' => 'open',
                'occurred_at' => '2026-01-01 10:00:00',
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);
    }
}
