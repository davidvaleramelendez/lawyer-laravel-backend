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
        Schema::create('case_records', function (Blueprint $table) {
            $table->bigIncrements('RecordID');
            $table->unsignedInteger('CaseID');
            $table->unsignedInteger('UserID');
            $table->string('Email')->nullable();
            $table->string('Subject')->nullable();
            $table->text('Content')->nullable();
            $table->longText('File')->comment('DC2Type:json')->nullable();
            $table->string('Type');
            $table->text('attachment_id')->nullable();
            $table->datetime('CreatedAt')->useCurrent()->nullable();
            $table->unsignedInteger('ToUserID')->nullable();
            $table->integer('IsShare')->default(1);
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->integer('interval_time')->nullable();
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
        Schema::dropIfExists('case_records');
    }
};
