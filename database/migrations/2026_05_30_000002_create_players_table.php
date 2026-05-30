<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
    public function up()
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index(['version_id', 'registered_at']);
            $table->index(['version_id', 'email']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('players');
    }
}
