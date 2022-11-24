<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('CaseID')->nullable();
            $table->unsignedBigInteger('invoice_no')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('invoice_date')->nullable();
            $table->timestamp('invoice_due_date')->nullable();
            $table->text('payment_details')->nullable();
            $table->text('note')->nullable();
            $table->float('vat', 8, 2)->default(0)->comment('19 % VAT');
            $table->float('total_price', 8, 2)->default(0);
            $table->float('remaining_amount', 10, 2)->nullable();
            $table->string('method')->nullable();
            $table->string('status');
            $table->string('word_file')->nullable();
            $table->text('word_path')->nullable();
            $table->string('pdf_file')->nullable();
            $table->text('pdf_path')->nullable();
            $table->string('CaseTypeName')->nullable();
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
        Schema::dropIfExists('invoices');
    }
}
