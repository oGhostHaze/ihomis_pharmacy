<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderDetailsInHrxoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrxo', function (Blueprint $table) {
            $table->string('exp_date')->nullable();
            $table->string('loc_code')->nullable();
            $table->string('item_id')->nullable();
            $table->boolean('has_tag')->nullable();
            $table->string('tx_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrxo', function (Blueprint $table) {
            $table->dropColumn('exp_date', 'loc_code', 'item_id', 'has_tag', 'tx_type');
        });
    }
}
