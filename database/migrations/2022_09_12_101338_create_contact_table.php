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
        Schema::create('contact', function (Blueprint $table) {
            $table->integer('ContactID')->unique();
            $table->string('Name');
            $table->string('Email');
            $table->text('Subject')->nullable();
            $table->string('PhoneNo');
            $table->integer('IsCase')->default(0);
            $table->unsignedInteger('message_id');
            $table->datetime('CreatedAt')->useCurrent()->nullable();
            $table->integer('deleted')->default(0);
            $table->biginteger('read_at')->nullable();
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
        Schema::dropIfExists('contact');
    }
};
