<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('UserId');
            $table->text('bank_information')->nullable();
            $table->text('footer_columns')->nullable();
            $table->text('invoice_logo')->nullable();
            $table->string('Account_Name')->nullable();
            $table->string('Account_title')->nullable();
            $table->integer('Account_Number')->nullable();
            $table->string('User_Name')->nullable();
            $table->text('Address')->nullable();
            $table->integer('Postal_Code')->nullable();
            $table->string('City')->nullable();
            $table->string('Invoice_text')->nullable();
            $table->string('Casetype')->nullable();
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
        Schema::dropIfExists('account_settings');
    }
}
