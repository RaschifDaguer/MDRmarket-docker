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
        Schema::table('Producto', function (Blueprint $table) {
            $table->boolean('en_oferta')->default(false)->after('stock');
            $table->decimal('precio_oferta', 10, 2)->nullable()->after('en_oferta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Producto', function (Blueprint $table) {
            $table->dropColumn(['en_oferta', 'precio_oferta']);
        });
    }
};
