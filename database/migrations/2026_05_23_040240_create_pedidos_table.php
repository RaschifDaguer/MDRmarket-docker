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
        Schema::create('Pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('Cliente')->cascadeOnDelete();
            $table->unsignedBigInteger('id_comerciante');
            $table->unsignedBigInteger('id_repartidor')->nullable();
            $table->integer('id_zona_envio')->nullable();
            $table->integer('id_ubicacion_entrega')->nullable();
            $table->decimal('subtotal_productos', 10, 2)->default(0.00);
            $table->decimal('costo_envio', 10, 2)->default(0.00);
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
