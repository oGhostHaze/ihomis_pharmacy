<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrugEmergencyPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_drug_emergency_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('or_no')->nullable();
            $table->string('pharmacy_name')->nullable();
            $table->string('user_id')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('dmdcomb')->nullable();
            $table->string('dmdctr')->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->dateTime('dmdprdte')->nullable();
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->decimal('markup_price', 18, 2)->nullable();
            $table->decimal('total_amount', 18, 2)->nullable();
            $table->decimal('retail_price', 18, 2)->nullable();
            $table->string('lot_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('charge_code')->nullable();
            $table->string('pharm_location_id')->nullable();
            $table->string('remarks')->nullable();
            $table->enum('status', ['pending', 'pushed', 'cancelled'])->nullable()->default('pending');
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
        Schema::dropIfExists('pharm_drug_emergency_purchases');
    }
}
