<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOtherFeeDetailsInHdmhdrpriceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hdmhdrprice', function (Blueprint $table) {
            $table->decimal('mark_up', 12, 2)->nullable()->default(0); //mark up price
            $table->decimal('acquisition_cost', 12, 2)->nullable()->default(0); //acquisition cost
            $table->boolean('has_compounding')->nullable()->default(false); //has compounding fee
            $table->decimal('compounding_fee', 12, 2)->nullable()->default(0); //compounding fee
            $table->decimal('retail_price', 12, 2)->nullable()->default(0); //retail price
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
            $table->dropColumn('mark_up', 'acquisition_cost', 'has_compounding', 'compounding_fee', 'retail_price');
        });
    }
}
