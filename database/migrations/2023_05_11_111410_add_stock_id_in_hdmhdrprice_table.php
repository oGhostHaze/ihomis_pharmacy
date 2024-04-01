<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockIdInHdmhdrpriceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hdmhdrprice', function (Blueprint $table) {
            $table->string('stock_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hdmhdrprice', function (Blueprint $table) {
            $table->dropColumn('stock_id');
        });
    }
}
