<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->timestamp('occurred_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['version_id', 'occurred_at']);
            $table->index(['version_id', 'player_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
