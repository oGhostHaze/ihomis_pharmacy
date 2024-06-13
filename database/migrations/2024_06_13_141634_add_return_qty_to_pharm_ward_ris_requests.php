<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReturnQtyToPharmWardRisRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_ward_ris_requests', function (Blueprint $table) {
            $table->decimal('return_qty')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_ward_ris_requests', function (Blueprint $table) {
            $table->dropColumn('return_qty');
        });
    }
}
