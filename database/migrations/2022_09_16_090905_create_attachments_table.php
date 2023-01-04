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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('reference_id')->nullable();
            $table->biginteger('email_group_id')->nullable();
            $table->biginteger('user_id')->nullable();
            $table->biginteger('sender_id')->nullable();
            $table->enum('type', ['email', 'notification'])->default('email');
            $table->text('name')->nullable();
            $table->text('path')->nullable();
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
        Schema::dropIfExists('attachments');
    }
};
