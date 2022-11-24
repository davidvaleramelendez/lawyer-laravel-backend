<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->biginteger('sender_id')->nullable();
            $table->integer('important')->default(0);
            $table->string('email_group_id');
            $table->integer('status')->default(0)->comment('0 = Not Readed , 1 = Readed , 2 = To Resolve , 3 = Resolved');
            $table->unsignedInteger('case_id')->nullable();
            $table->text('message_only');
            $table->integer('read_later')->default(0);
            $table->text('reply_id')->nullable();
            $table->json('label')->default(json_encode(['important']));
            $table->morphs('notifiable');
            $table->text('data');
            $table->text('attachment_id')->nullable();
            $table->integer('is_trash')->default(0);
            $table->integer('is_delete')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
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
        Schema::dropIfExists('notifications');
    }
}
