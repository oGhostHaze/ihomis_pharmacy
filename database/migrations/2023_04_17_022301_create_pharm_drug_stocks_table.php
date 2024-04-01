<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmDrugStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('dmdcomb', 12); //drugs and medicine name
            $table->decimal('dmdctr', 2, 0); //drugs and medicine name
            $table->integer('loc_code'); //stocks location
            $table->string('chrgcode', 30); //charge code
            $table->date('exp_date'); //expiry date
            $table->dateTime('dmdprdte')->nullable(); //price date
            $table->decimal('retail_price', 12, 2)->nullable(true); //selling price
            $table->decimal('stock_bal', 12, 2)->nullable()->default(0); //remaining stocks
            $table->decimal('beg_bal', 12, 2)->nullable()->default(0); //remaining stocks
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
        Schema::dropIfExists('pharm_drug_stocks');
    }
}
