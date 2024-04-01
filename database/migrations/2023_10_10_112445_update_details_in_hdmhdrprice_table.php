<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDetailsInHdmhdrpriceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hdmhdrprice', function (Blueprint $table) {
            $table->string('dmhdrsub', 6)->change();
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
            $table->string('dmhdrsub', 5)->change();
        });
    }
}
