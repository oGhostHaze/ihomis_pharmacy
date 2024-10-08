<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugStockCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_stock_cards', function (Blueprint $table) {
            $table->id();
            $table->string('loc_code', 30);
            $table->string('dmdcomb', 30);
            $table->string('dmdctr', 30);
            $table->string('chrgcode', 30);
            $table->string('drug_concat');
            $table->date('exp_date');
            $table->date('stock_date');
            $table->decimal('reference', 18, 2)->nullable();
            $table->decimal('rec', 18, 2)->nullable();
            $table->decimal('iss', 18, 2)->nullable();
            $table->decimal('bal', 18, 2)->nullable();
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
        Schema::dropIfExists('pharm_drug_stock_cards');
    }
}
