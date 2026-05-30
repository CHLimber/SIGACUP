<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestion', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('anio');
            $table->smallInteger('semestre');
            $table->string('estado', 20)->default('configuracion');
            $table->date('fecha_inicio_inscripcion');
            $table->date('fecha_fin_inscripcion');
            $table->date('fecha_inicio_cursado');
            $table->date('fecha_fin_cursado');
            $table->timestamps();

            $table->unique(['anio', 'semestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestion');
    }
};
