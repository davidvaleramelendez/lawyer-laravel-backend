<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id');
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();
            $table->string('current_team_id')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('language')->nullable();
            $table->string('Status')->enum('Active', 'InActive')->default('Active');
            $table->string('Contact')->nullable();
            $table->string('Company')->nullable();
            $table->string('DOB')->nullable();
            $table->string('Gender')->nullable();
            $table->string('Address')->nullable();
            $table->string('Address1')->nullable();
            $table->string('Postcode')->nullable();
            $table->string('City')->nullable();
            $table->string('State')->nullable();
            $table->string('Country')->nullable();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
