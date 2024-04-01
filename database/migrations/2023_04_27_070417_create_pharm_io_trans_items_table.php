<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmIoTransItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_io_trans_items', function (Blueprint $table) {
            $table->id();
            $table->string('iotrans_id');
            $table->string('stock_id', 30); //Pharmacy Drug Stock ID
            $table->string('dmdcomb', 30); //drug and medicine combination
            $table->string('dmdctr', 30); //drugs and medicine name
            $table->string('from', 30); //location
            $table->string('to', 30); //location
            $table->string('chrgcode', 30)->nullable(true); //type of account
            $table->string('exp_date')->nullable(true);
            $table->decimal('qty', 12, 2)->nullable(true)->default('0'); //issued qty by warehouse
            $table->string('status', 30)->default('Pending')->nullable(true); //type of account
            $table->string('user_id', 30)->nullable(true); //type of account
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
        Schema::dropIfExists('pharm_io_trans_items');
    }
}
