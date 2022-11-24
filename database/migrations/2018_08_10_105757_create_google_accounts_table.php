<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoogleAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google_accounts', function (Blueprint $table) {
            $table->increments('id');

            // Relationships.
            $table->unsignedInteger('user_id')->nullable();
            /*$table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');*/

            // Data.
            $table->string('google_id')->nullable();
            $table->string('name')->nullable();
            $table->json('token')->nullable();

            // Timestamps.
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
        Schema::dropIfExists('google_accounts');
    }
}
