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
        Schema::create('cloud_storage', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('slug')->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('user_id');
            $table->string('roll_id')->nullable();
            $table->string('file_name')->nullable();
            $table->string('extension')->nullable();
            $table->text('path');
            $table->enum('type', ['folder', 'file'])->comment('[folder, file]');
            $table->timestame('important_at')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('cloud_storage');
    }
};
