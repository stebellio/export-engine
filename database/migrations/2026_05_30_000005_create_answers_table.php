<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnswersTable extends Migration
{
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['version_id', 'player_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('answers');
    }
}
