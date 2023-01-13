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
        Schema::create('cases', function (Blueprint $table) {
            $table->integer('CaseID')->unique();
            $table->unsignedInteger('UserID')->nullable();
            $table->unsignedInteger('ContactID')->nullable();
            $table->unsignedInteger('LaywerID')->nullable();
            $table->unsignedInteger('CaseTypeID')->nullable();
            $table->string('Name')->nullable();
            $table->timestamp('Date')->nullable();
            $table->enum('Status', ['Open', 'Close', 'Hold'])->default('Open');
            $table->unsignedInteger('CreatedBy')->nullable();
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
        Schema::dropIfExists('cases');
    }
};
