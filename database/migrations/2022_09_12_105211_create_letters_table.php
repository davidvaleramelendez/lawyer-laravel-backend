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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('case_id');
            $table->unsignedInteger('letter_template_id')->nullable();
            $table->text('subject');
            $table->text('message');
            $table->string('best_regards')->nullable();
            $table->date('frist_date')->nullable();
            $table->datetime('created_date')->useCurrent();
            $table->datetime('last_date')->useCurrent();
            $table->integer('is_print')->default(0);
            $table->integer('deleted')->default(0);
            $table->integer('is_archived')->default(0);
            $table->string('word_file')->nullable();
            $table->text('word_path')->nullable();
            $table->string('pdf_file')->nullable();
            $table->text('pdf_path')->nullable();
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
        Schema::dropIfExists('letters');
    }
};
