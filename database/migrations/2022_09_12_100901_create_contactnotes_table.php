<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contactnotes', function (Blueprint $table) {
            $table->increments('ContactNotesID');
            $table->unsignedInteger('ContactID')->nullable();
            $table->text('Notes')->nullable();
            $table->unsignedInteger('UserID')->nullable();
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
        Schema::dropIfExists('contactnotes');
    }
};
