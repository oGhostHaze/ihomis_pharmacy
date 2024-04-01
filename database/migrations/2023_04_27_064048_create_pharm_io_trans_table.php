<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmIoTransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_io_trans', function (Blueprint $table) {
            $table->id();
            $table->string('trans_no');
            $table->string('dmdcomb', 30); //drug and medicine combination
            $table->string('dmdctr', 30); //drugs and medicine name
            $table->string('loc_code', 30); //location
            $table->string('chrgcode', 30)->nullable(true); //type of account
            // $table->string('exp_date')->nullable(true);
            $table->decimal('requested_qty', 12, 2); //requested qty by pharmacy
            $table->decimal('issued_qty', 12, 2)->nullable(true)->default('0'); //issued qty by warehouse
            $table->decimal('received_qty', 12, 2)->nullable(true)->default('0'); //requestor qty
            $table->string('requested_by', 30); //type of account
            $table->string('issued_by', 30)->nullable(true); //type of account
            $table->string('received_by', 30)->nullable(true); //type of account
            $table->string('user_id', 30)->nullable(true); //type of account
            $table->enum('trans_stat', ['Requested', 'Issued', 'Received', 'Cancelled'])->default('Requested')->nullable(true); //delivery type
            $table->decimal('retail_price', 12, 2)->nullable(true); //selling price
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
        Schema::dropIfExists('pharm_io_trans');
    }
}
