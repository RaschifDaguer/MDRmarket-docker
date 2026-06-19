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
        Schema::table('Repartidor', function (Blueprint $table) {
            if (! Schema::hasColumn('Repartidor', 'tipo')) {
                $table->string('tipo')->nullable()->after('placa')
                    ->comment('Tipo de vehículo del repartidor: moto, bicicleta, auto, camion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Repartidor', function (Blueprint $table) {
            if (Schema::hasColumn('Repartidor', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
