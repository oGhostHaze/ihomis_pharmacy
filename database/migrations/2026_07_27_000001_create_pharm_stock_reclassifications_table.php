<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePharmStockReclassificationsTable extends Migration
{
    public function up()
    {
        Schema::connection('hospital')->create('pharm_stock_reclassifications', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 50);
            $table->bigInteger('source_stock_id');
            $table->bigInteger('destination_stock_id');
            $table->bigInteger('user_id');
            $table->integer('loc_code');
            $table->string('dmdcomb', 30);
            $table->string('dmdctr', 30);
            $table->string('source_chrgcode', 30);
            $table->string('destination_chrgcode', 30);
            $table->decimal('quantity', 18, 2);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('source_before', 18, 2);
            $table->decimal('source_after', 18, 2);
            $table->decimal('destination_before', 18, 2);
            $table->decimal('destination_after', 18, 2);
            $table->dateTime('executed_at');
            $table->timestamps();

            $table->index('reference_no');
            $table->index(['source_chrgcode', 'destination_chrgcode']);
            $table->index(['loc_code', 'dmdcomb', 'dmdctr']);
        });
    }

    public function down()
    {
        Schema::connection('hospital')->dropIfExists('pharm_stock_reclassifications');
    }
}
