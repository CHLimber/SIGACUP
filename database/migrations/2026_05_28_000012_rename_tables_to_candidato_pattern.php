<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('estudiantes', 'candidato_estudiante');
        Schema::rename('solicitudes_docente', 'candidato_docente');
    }

    public function down(): void
    {
        Schema::rename('candidato_estudiante', 'estudiantes');
        Schema::rename('candidato_docente', 'solicitudes_docente');
    }
};
