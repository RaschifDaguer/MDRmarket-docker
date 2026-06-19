<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto', 10, 2);
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('concepto_detalle', 300);
            $table->enum('estado_pago', ['completado', 'pendiente', 'fallido'])->default('completado');
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->unsignedBigInteger('comerciante_id')->nullable();
            $table->unsignedBigInteger('repartidor_id')->nullable();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamps();

            $table->foreign('comerciante_id')->references('id')->on('Comerciante')->nullOnDelete();
            $table->foreign('repartidor_id')->references('id')->on('Repartidor')->nullOnDelete();
            $table->foreign('cliente_id')->references('id')->on('Cliente')->nullOnDelete();

            $table->index(['tipo', 'estado_pago']);
            $table->index('fecha_registro');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
