<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Comerciante', function (Blueprint $table) {
            $table->string('ruc', 50)->nullable()->after('telefono');
            $table->string('razon_social', 255)->nullable()->after('ruc');
        });
    }

    public function down(): void
    {
        Schema::table('Comerciante', function (Blueprint $table) {
            $table->dropColumn(['ruc', 'razon_social']);
        });
    }
};
