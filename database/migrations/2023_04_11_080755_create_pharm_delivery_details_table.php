<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmDeliveryDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_delivery_details', function (Blueprint $table) {
            $table->id();
            $table->string('po_no');
            $table->string('si_no');
            $table->string('pharm_location_id');
            $table->string('user_id');
            $table->date('delivery_date');
            $table->string('suppcode');
            $table->string('delivery_type');
            $table->string('charge_code');
            $table->string('status', 50)->nullable()->default('pending');
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
        Schema::dropIfExists('pharm_delivery_details');
    }
}
