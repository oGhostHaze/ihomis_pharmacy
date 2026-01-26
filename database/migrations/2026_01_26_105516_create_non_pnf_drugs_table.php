<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pharm_non_pnf_drugs', function (Blueprint $table) {
            $table->id();
            $table->string('medicine_name');
            $table->string('dose')->nullable();
            $table->string('unit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index('medicine_name');
            $table->index('is_active');
            $table->index(['medicine_name', 'dose', 'unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharm_non_pnf_drugs');
    }
};
