<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmDeliveryItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_id');
            $table->string('dmdcomb');
            $table->string('dmdctr');
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->decimal('retail_price', 18, 2);
            $table->string('lot_no');
            $table->date('expiry_date');
            $table->string('pharm_location_id');
            $table->string('charge_code');
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
        Schema::dropIfExists('pharm_delivery_items');
    }
}
