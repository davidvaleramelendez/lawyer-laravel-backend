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
            $table->unsignedInteger('UserID');
            $table->unsignedInteger('ContactID');
            $table->unsignedInteger('LaywerID');
            $table->unsignedInteger('CaseTypeID');
            $table->string('Name');
            $table->timestamp('Date');
            $table->enum('Status', ['Open', 'Close', 'Hold'])->default('Open');
            $table->unsignedInteger('CreatedBy');
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
