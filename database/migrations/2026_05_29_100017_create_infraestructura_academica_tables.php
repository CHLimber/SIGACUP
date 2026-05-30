<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aula', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->unsignedInteger('capacidad');
            $table->string('modulo', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('horario', function (Blueprint $table) {
            $table->id();
            $table->boolean('aplica_todos_dias')->default(false);
            $table->string('dia', 15)->nullable();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });

        Schema::create('grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestion_id')->constrained('gestion')->restrictOnDelete();
            $table->char('codigo_materia', 6);
            $table->string('nombre', 10);
            $table->foreignId('horario_id')->constrained('horario')->restrictOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aula')->nullOnDelete();
            $table->unsignedInteger('capacidad_max');
            $table->timestamps();

            $table->unique(['gestion_id', 'codigo_materia', 'nombre']);
            $table->foreign('codigo_materia')->references('codigo')->on('materia')->restrictOnDelete();
            $table->index('gestion_id');
            $table->index('aula_id');
            $table->index('horario_id');
        });

        Schema::create('asignacion_grupo', function (Blueprint $table) {
            $table->foreignId('postulacion_id')->constrained('postulacion')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupo')->restrictOnDelete();
            $table->timestamps();

            $table->primary(['postulacion_id', 'grupo_id']);
            $table->index('grupo_id');
        });

        Schema::create('docente_grupo', function (Blueprint $table) {
            $table->foreignId('docente_id')->constrained('docente')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupo')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['docente_id', 'grupo_id']);
            $table->index('grupo_id');
        });

        Schema::create('evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('postulacion')->cascadeOnDelete();
            $table->char('codigo_materia', 6);
            $table->smallInteger('numero_examen');
            $table->decimal('nota_cruda', 5, 2);
            $table->decimal('peso', 5, 2);
            $table->timestamps();

            $table->unique(['postulacion_id', 'codigo_materia', 'numero_examen']);
            $table->foreign('codigo_materia')->references('codigo')->on('materia')->restrictOnDelete();
            $table->index('postulacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion');
        Schema::dropIfExists('docente_grupo');
        Schema::dropIfExists('asignacion_grupo');
        Schema::dropIfExists('grupo');
        Schema::dropIfExists('horario');
        Schema::dropIfExists('aula');
    }
};
