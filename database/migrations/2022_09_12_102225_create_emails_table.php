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
            $table->unsignedInteger('case_id')->nullable();
            $table->biginteger('imap_id')->nullable();
            $table->string('folder')->nullable();
            $table->boolean('sent')->default(0);
            $table->integer('from_id')->nullable();
            $table->integer('to_id')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('reply_to')->nullable();
            $table->string('sender')->nullable();
            $table->text('subject')->nullable();
            $table->string('message_id')->nullable();
            $table->string('uid')->nullable();
            $table->string('email_group_id')->nullable();
            $table->datetime('date')->nullable();
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
            $table->integer('status')->default(0)->comment('0 = Not Readed , 1 = Readed , 2 = To Resolve , 3 = Resolved');
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
