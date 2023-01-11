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
            $table->string('Name')->nullable();
            $table->string('Email')->nullable();
            $table->string('Subject')->nullable();
            $table->text('message')->nullable();
            $table->string('PhoneNo')->nullable();
            $table->integer('IsCase')->default(0);
            $table->string('message_id')->nullable();
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
