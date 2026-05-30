<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRewardsTable extends Migration
{
    public function up()
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('value', 12, 2)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['version_id', 'player_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rewards');
    }
}
