<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExportsTable extends Migration
{
    public function up()
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('format', 16)->default('xlsx');
            $table->json('config');
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['version_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('exports');
    }
}
