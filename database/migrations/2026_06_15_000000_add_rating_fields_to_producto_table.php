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
            $table->decimal('rating_producto', 3, 1)->nullable()->after('stock');
            $table->decimal('rating_general', 3, 1)->nullable()->after('rating_producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Producto', function (Blueprint $table) {
            $table->dropColumn(['rating_producto', 'rating_general']);
        });
    }
};
