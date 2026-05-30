<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('type', 64);
            $table->timestamp('occurred_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['version_id', 'occurred_at']);
            $table->index(['version_id', 'type', 'occurred_at']);
            $table->index(['version_id', 'player_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
}
