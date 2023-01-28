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
        Schema::create('import_letter_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('case_id')->nullable();
            $table->string('name')->nullable();
            $table->string('subject')->nullable();
            $table->date('frist_date')->nullable();
            $table->string('file_name')->nullable();
            $table->text('file_path')->nullable();
            $table->integer('isErledigt')->default(0);
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
        Schema::dropIfExists('import_letter_files');
    }
};
