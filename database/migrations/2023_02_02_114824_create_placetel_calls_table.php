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
        Schema::create('placetel_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('placetel_call_id')->nullable();
            $table->string('type')->nullable();
            $table->string('from_number')->nullable();
            $table->longtext('response')->nullable();
            $table->text('note')->nullable();
            $table->boolean('unread')->default(0);
            $table->datetime('placetel_received_at')->nullable();
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
        Schema::dropIfExists('placetel_calls');
    }
};
