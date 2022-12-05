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
        Schema::create('email_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('to_ids')->nullable();
            $table->string('cc_ids')->nullable();
            $table->string('bcc_ids')->nullable();
            $table->text('subject')->nullable();
            $table->longtext('body')->nullable();
            $table->text('attached_files')->nullable();
            $table->string('attached_ids')->nullable();
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
        Schema::dropIfExists('email_drafts');
    }
};
