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
        Schema::create('dropbox_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('client_id')->nullable();
            $table->text('secret')->nullable();
            $table->longtext('token')->nullable();
            $table->string('access_type')->nullable();
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
        Schema::dropIfExists('dropbox_api_tokens');
    }
};
