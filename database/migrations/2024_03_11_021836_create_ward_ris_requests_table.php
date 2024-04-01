<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWardRisRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pharm_ward_ris_requests', function (Blueprint $table) {
            $table->id();
            $table->string('trans_no');
            $table->foreignId('stock_id');
            $table->foreignId('ris_location_id');
            $table->string('dmdcomb', 30); //drug and medicine combination
            $table->string('dmdctr', 30); //drugs and medicine name
            $table->string('loc_code', 30); //location
            $table->string('chrgcode', 30)->nullable(true); //type of account
            $table->decimal('issued_qty', 12, 2)->nullable(true)->default('0'); //issued qty by warehouse
            $table->string('issued_by', 30)->nullable(true); //type of account
            $table->enum('trans_stat', ['Issued', 'Cancelled'])->default('Issued')->nullable(true); //delivery type
            $table->string('dmdprdte');
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
        Schema::dropIfExists('ward_ris_requests');
    }
}