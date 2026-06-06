<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulacion', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio_egreso')->nullable()->after('carrera2_id');
            $table->string('unidad_educativa', 150)->nullable()->after('anio_egreso');
            $table->string('tipo_colegio', 20)->nullable()->after('unidad_educativa');
        });
    }

    public function down(): void
    {
        Schema::table('postulacion', function (Blueprint $table) {
            $table->dropColumn(['anio_egreso', 'unidad_educativa', 'tipo_colegio']);
        });
    }
};
