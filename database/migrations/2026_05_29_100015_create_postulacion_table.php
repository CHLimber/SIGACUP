<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidato_estudiante_id')
                ->constrained('candidato_estudiante')->restrictOnDelete();
            $table->foreignId('gestion_id')->constrained('gestion')->restrictOnDelete();
            $table->foreignId('carrera1_id')->constrained('carrera')->restrictOnDelete();
            $table->foreignId('carrera2_id')->nullable()->constrained('carrera')->restrictOnDelete();
            $table->string('estado_pago', 20)->default('pendiente');
            $table->string('estado_cup', 20)->default('pendiente');
            $table->decimal('promedio_general', 5, 2)->nullable();
            $table->foreignId('carrera_asignada_id')->nullable()->constrained('carrera')->restrictOnDelete();
            $table->string('estado_admision', 20)->default('pendiente');
            $table->timestamps();

            $table->unique(['candidato_estudiante_id', 'gestion_id']);
            $table->index('candidato_estudiante_id');
            $table->index('gestion_id');
            $table->index('estado_admision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulacion');
    }
};
