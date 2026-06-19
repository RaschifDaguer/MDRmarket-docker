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
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_pedido')->unsigned();
            $table->bigInteger('id_cliente')->unsigned();
            $table->bigInteger('id_producto')->unsigned()->nullable();
            $table->integer('estrellas_producto')->unsigned();
            $table->integer('estrellas_repartidor')->unsigned();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('id_pedido')->references('id')->on('Pedido')->onDelete('cascade');
            $table->foreign('id_cliente')->references('id')->on('Cliente')->onDelete('cascade');
            $table->foreign('id_producto')->references('id')->on('Producto')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
