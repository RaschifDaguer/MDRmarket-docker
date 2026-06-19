<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Comerciante', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable()->after('banner_url');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
        });

        Schema::table('Repartidor', function (Blueprint $table) {
            $table->decimal('rating_promedio', 3, 1)->nullable()->after('tipo');
            $table->unsignedInteger('total_calificaciones')->default(0)->after('rating_promedio');
        });
    }

    public function down(): void
    {
        Schema::table('Comerciante', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud']);
        });

        Schema::table('Repartidor', function (Blueprint $table) {
            $table->dropColumn(['rating_promedio', 'total_calificaciones']);
        });
    }
};
