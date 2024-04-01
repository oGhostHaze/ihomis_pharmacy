<?php

use App\Models\Pharmacy\Drugs\DrugStock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToPharmDrugStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pharm_drug_stocks', function (Blueprint $table) {
            $table->string('drug_concat')->nullable();
            $table->string('dmdnost')->nullable();
            $table->string('strecode')->nullable();
            $table->string('formcode')->nullable();
            $table->string('rtecode')->nullable();
            $table->string('brandname')->nullable();
            $table->string('dmdrem')->nullable();
            $table->string('dmdrxot')->nullable();
            $table->string('gencode')->nullable();
        });
        $drugs = DrugStock::all();
        foreach ($drugs as $drug) {
            $drug->drug_concat = $drug->drug->drug_name();
            $drug->dmdnost = $drug->drug->dmdnost;
            $drug->strecode = $drug->drug->strecode;
            $drug->formcode = $drug->drug->formcode;
            $drug->rtecode = $drug->drug->rtecode;
            $drug->brandname = $drug->drug->brandname;
            $drug->dmdrem = $drug->drug->dmdrem;
            $drug->dmdrxot = $drug->drug->dmdrxot;
            $drug->gencode = $drug->drug->generic->gencode;
            $drug->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pharm_drug_stocks', function (Blueprint $table) {
            $table->dropColumn('drug_concat', 'dmdnost', 'strecode', 'formcode', 'rtecode', 'brandname', 'dmdrem', 'dmdrxot', 'gencode');
        });
    }
}
