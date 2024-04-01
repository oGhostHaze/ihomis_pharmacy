<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugStockReorderLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_stock_reorder_levels', function (Blueprint $table) {
            $table->id();
            $table->string('dmdcomb', 30);
            $table->string('dmdctr', 10);
            $table->unsignedBigInteger('reorder_point')->default(0);
            $table->foreignId('user_id');
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
        Schema::dropIfExists('pharm_drug_stock_reorder_levels');
    }
}
