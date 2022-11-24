<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTodosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->integer('UserId');
            $table->string('title')->nullable();
            $table->string('Assign')->nullable();
            $table->date('due_date')->nullable();
            $table->string('tag')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_important')->default(0);
            $table->boolean('is_completed')->default(0);
            $table->boolean('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('todos');
    }
}
