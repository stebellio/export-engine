<?php

namespace Database\Seeders;

use App\Models\Version;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $playersN = (int) env('DEMO_PLAYERS', 200);
        $eventsPerPlayer = (int) env('DEMO_EVENTS_PER_PLAYER', 50);
        $txPerPlayer = (int) env('DEMO_TRANSACTIONS_PER_PLAYER', 3);
        $ansPerPlayer = (int) env('DEMO_ANSWERS_PER_PLAYER', 2);
        $rwPerPlayer = (int) env('DEMO_REWARDS_PER_PLAYER', 1);
        $chunk = (int) env('DEMO_CHUNK', 1000);

        $version = Version::create(['name' => 'Demo Campaign '.Carbon::now()->format('Y-m-d H:i:s')]);
        $this->command->info("Created version #{$version->id} '{$version->name}'");

        $playerIds = $this->seedPlayers($faker, $version->id, $playersN, $chunk);
        $this->command->info('Seeded '.count($playerIds).' players');

        $this->seedEvents($version->id, $playerIds, $eventsPerPlayer, $chunk);
        $this->command->info('Seeded ~'.($playersN * $eventsPerPlayer).' events');

        $this->seedTransactions($version->id, $playerIds, $txPerPlayer, $chunk);
        $this->command->info('Seeded ~'.($playersN * $txPerPlayer).' transactions');

        $this->seedAnswers($faker, $version->id, $playerIds, $ansPerPlayer, $chunk);
        $this->command->info('Seeded ~'.($playersN * $ansPerPlayer).' answers');

        $this->seedRewards($version->id, $playerIds, $rwPerPlayer, $chunk);
        $this->command->info('Seeded ~'.($playersN * $rwPerPlayer).' rewards');
    }

    private function seedPlayers($faker, int $versionId, int $count, int $chunk): array
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $startRange = strtotime('-1 year');
        $endRange = time();
        $created = 0;

        while ($created < $count) {
            $batch = min($chunk, $count - $created);
            $rows = [];
            for ($i = 0; $i < $batch; $i++) {
                $rows[] = [
                    'version_id' => $versionId,
                    'email' => $faker->unique()->safeEmail(),
                    'registered_at' => date('Y-m-d H:i:s', mt_rand($startRange, $endRange)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('players')->insert($rows);
            $created += $batch;
        }

        return DB::table('players')->where('version_id', $versionId)->pluck('id')->all();
    }

    private function seedEvents(int $versionId, array $playerIds, int $perPlayer, int $chunk): void
    {
        if ($perPlayer <= 0) {
            return;
        }
        $types = ['open', 'register', 'complete', 'level_up', 'share'];
        $langs = ['it', 'en', 'es', 'fr', 'de'];
        $sources = ['linkedin', 'facebook', 'google', 'direct', 'newsletter'];
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $start = strtotime('-6 months');
        $end = time();

        $buffer = [];
        foreach ($playerIds as $pid) {
            for ($i = 0; $i < $perPlayer; $i++) {
                $buffer[] = [
                    'version_id' => $versionId,
                    'player_id' => $pid,
                    'type' => $types[array_rand($types)],
                    'occurred_at' => date('Y-m-d H:i:s', mt_rand($start, $end)),
                    'payload' => json_encode([
                        'score' => mt_rand(0, 1000),
                        'level' => mt_rand(1, 20),
                        'language' => $langs[array_rand($langs)],
                        'utm_source' => $sources[array_rand($sources)],
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($buffer) >= $chunk) {
                    DB::table('events')->insert($buffer);
                    $buffer = [];
                }
            }
        }
        if ($buffer) {
            DB::table('events')->insert($buffer);
        }
    }

    private function seedTransactions(int $versionId, array $playerIds, int $perPlayer, int $chunk): void
    {
        if ($perPlayer <= 0) {
            return;
        }
        $currencies = ['EUR', 'USD', 'GBP'];
        $gateways = ['stripe', 'paypal'];
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $start = strtotime('-6 months');
        $end = time();

        $buffer = [];
        foreach ($playerIds as $pid) {
            for ($i = 0; $i < $perPlayer; $i++) {
                $buffer[] = [
                    'version_id' => $versionId,
                    'player_id' => $pid,
                    'amount' => mt_rand(100, 50000) / 100,
                    'currency' => $currencies[array_rand($currencies)],
                    'occurred_at' => date('Y-m-d H:i:s', mt_rand($start, $end)),
                    'payload' => json_encode(['gateway' => $gateways[array_rand($gateways)]]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($buffer) >= $chunk) {
                    DB::table('transactions')->insert($buffer);
                    $buffer = [];
                }
            }
        }
        if ($buffer) {
            DB::table('transactions')->insert($buffer);
        }
    }

    private function seedAnswers($faker, int $versionId, array $playerIds, int $perPlayer, int $chunk): void
    {
        if ($perPlayer <= 0) {
            return;
        }
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $start = strtotime('-6 months');
        $end = time();

        $buffer = [];
        foreach ($playerIds as $pid) {
            for ($i = 0; $i < $perPlayer; $i++) {
                $buffer[] = [
                    'version_id' => $versionId,
                    'player_id' => $pid,
                    'question' => $faker->sentence(6, true).'?',
                    'answer' => $faker->sentence(),
                    'occurred_at' => date('Y-m-d H:i:s', mt_rand($start, $end)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($buffer) >= $chunk) {
                    DB::table('answers')->insert($buffer);
                    $buffer = [];
                }
            }
        }
        if ($buffer) {
            DB::table('answers')->insert($buffer);
        }
    }

    private function seedRewards(int $versionId, array $playerIds, int $perPlayer, int $chunk): void
    {
        if ($perPlayer <= 0) {
            return;
        }
        $names = ['gift_card', 'discount', 'badge', 'coin_pack'];
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $start = strtotime('-6 months');
        $end = time();

        $buffer = [];
        foreach ($playerIds as $pid) {
            for ($i = 0; $i < $perPlayer; $i++) {
                $buffer[] = [
                    'version_id' => $versionId,
                    'player_id' => $pid,
                    'name' => $names[array_rand($names)],
                    'value' => mt_rand(500, 10000) / 100,
                    'occurred_at' => date('Y-m-d H:i:s', mt_rand($start, $end)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($buffer) >= $chunk) {
                    DB::table('rewards')->insert($buffer);
                    $buffer = [];
                }
            }
        }
        if ($buffer) {
            DB::table('rewards')->insert($buffer);
        }
    }
}
