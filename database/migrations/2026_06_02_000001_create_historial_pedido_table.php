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
        Schema::create('HistorialPedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pedido')->constrained('Pedido')->cascadeOnDelete();
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->comment('Nuevo estado del pedido');
            $table->text('motivo')->nullable()->comment('Razón del cambio de estado');
            $table->unsignedBigInteger('usuario_id')->nullable()->comment('Usuario que realizó el cambio');
            $table->string('usuario_rol')->nullable()->comment('Rol del usuario (admin, comerciante, repartidor)');
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('HistorialPedido');
    }
};
