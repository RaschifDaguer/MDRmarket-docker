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
        Schema::table('Pedido', function (Blueprint $table) {
            if (! Schema::hasColumn('Pedido', 'latitud_entrega')) {
                $table->decimal('latitud_entrega', 10, 8)->nullable()->after('estado');
            }

            if (! Schema::hasColumn('Pedido', 'longitud_entrega')) {
                $table->decimal('longitud_entrega', 11, 8)->nullable()->after('latitud_entrega');
            }

            if (! Schema::hasColumn('Pedido', 'id_repartidor')) {
                $table->unsignedBigInteger('id_repartidor')->nullable()->after('id_comerciante');
                $table->foreign('id_repartidor')->references('id')->on('Repartidor')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Pedido', function (Blueprint $table) {
            if (Schema::hasColumn('Pedido', 'id_repartidor')) {
                $table->dropColumn('id_repartidor');
            }

            if (Schema::hasColumn('Pedido', 'latitud_entrega')) {
                $table->dropColumn('latitud_entrega');
            }

            if (Schema::hasColumn('Pedido', 'longitud_entrega')) {
                $table->dropColumn('longitud_entrega');
            }
        });
    }
};
