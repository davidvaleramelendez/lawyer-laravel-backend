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
        Schema::create('case_docs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('case_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->string('attachment_pdf')->nullable();
            $table->string('deleted')->default(0);
            $table->integer('is_archived')->default(0);
            $table->integer('is_print')->default(0);
            $table->date('frist_date')->nullable();
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
        Schema::dropIfExists('case_docs');
    }
};
