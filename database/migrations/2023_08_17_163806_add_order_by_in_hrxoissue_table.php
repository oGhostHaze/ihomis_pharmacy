<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderByInHrxoissueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrxoissue', function (Blueprint $table) {
            $table->string('order_by')->nullable();
            $table->string('deptcode')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrxoissue', function (Blueprint $table) {
            $table->dropColumn('order_by', 'deptcode');
        });
    }
}
