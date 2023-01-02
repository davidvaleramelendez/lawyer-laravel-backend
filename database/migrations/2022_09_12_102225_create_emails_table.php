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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->biginteger('imap_id');
            $table->string('folder');
            $table->integer('from_id');
            $table->integer('to_id');
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('sender')->nullable();
            $table->text('subject')->nullable();
            $table->string('message_id')->nullable();
            $table->string('uid');
            $table->string('email_group_id');
            $table->string('date')->nullable();
            $table->string('toaddress')->nullable();
            $table->string('fromaddress')->nullable();
            $table->string('reply_toaddress')->nullable();
            $table->string('senderaddress')->nullable();
            $table->longText('body')->nullable();
            $table->string('hasAttachment')->nullable();
            $table->longText('attachedFiles')->nullable();
            $table->text('attachment_id')->nullable();
            $table->boolean('is_read')->default(0);
            $table->boolean('is_delete')->default(0);
            $table->boolean('is_trash')->default(0);
            $table->boolean('important')->default(0);
            $table->string('label')->default('important');
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
        Schema::dropIfExists('emails');
    }
};
