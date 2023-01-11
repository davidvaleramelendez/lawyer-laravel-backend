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
        Schema::create('inquiry_imaps', function (Blueprint $table) {
            $table->id();
            $table->string('imap_host')->nullable();
            $table->string('imap_email')->nullable();
            $table->string('imap_password')->nullable();
            $table->integer('imap_port')->nullable();
            $table->boolean('imap_ssl')->default(1);
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
        Schema::dropIfExists('inquiry_imaps');
    }
};
