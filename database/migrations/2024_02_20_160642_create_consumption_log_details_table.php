<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsumptionLogDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_consumption_log_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('consumption_from');
            $table->dateTime('consumption_to')->nullable();
            $table->string('status', 1)->default('A');
            $table->string('entry_by');
            $table->string('closed_by')->nullable();
            $table->bigInteger('loc_code');
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
        Schema::dropIfExists('pharm_consumption_log_details');
    }
}
