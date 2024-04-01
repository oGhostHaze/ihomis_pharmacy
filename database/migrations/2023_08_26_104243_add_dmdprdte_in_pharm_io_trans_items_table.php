<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDmdprdteInPharmIoTransItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_io_trans_items', function (Blueprint $table) {
            $table->dateTime('dmdprdte')->nullable(); //price date
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_io_trans_items', function (Blueprint $table) {
            $table->dropColumn('dmdprdte');
        });
    }
}
