<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Comerciante', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('direccion');
            $table->string('foto_perfil_url')->nullable()->after('descripcion');
            $table->string('banner_url')->nullable()->after('foto_perfil_url');
        });
    }

    public function down(): void
    {
        Schema::table('Comerciante', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'foto_perfil_url', 'banner_url']);
        });
    }
};
