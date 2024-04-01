<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemarksToPharmIoTrans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_io_trans', function (Blueprint $table) {
            $table->text('remarks_request')->nullable();
            $table->text('remarks_issue')->nullable();
            $table->text('remarks_received')->nullable();
            $table->text('remarks_cancel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_io_trans', function (Blueprint $table) {
            $table->dropColumn('remarks_request', 'remarks_issue', 'remarks_received', 'remarks_cancel');
        });
    }
}
