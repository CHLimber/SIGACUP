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
            $table->tinyInteger('semestre')->comment('1 o 2');
            $table->enum('estado', [
                'configuracion',
                'inscripcion',
                'cursado',
                'admision',
                'cerrada',
            ])->default('configuracion');
            $table->date('fecha_inicio_inscripcion')->nullable();
            $table->date('fecha_fin_inscripcion')->nullable();
            $table->date('fecha_inicio_cursado')->nullable();
            $table->date('fecha_fin_cursado')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'semestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestion');
    }
};
