<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('stock_id');
            $table->bigInteger('user_id');
            $table->decimal('from_qty');
            $table->decimal('to_qty');
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
        Schema::dropIfExists('pharm_stock_adjustments');
    }
}
