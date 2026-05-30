<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisito_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidato_estudiante_id')
                ->constrained('candidato_estudiante')->cascadeOnDelete();
            $table->string('codigo', 60);
            $table->string('nombre_original');
            $table->string('ruta_archivo');
            $table->string('mime_type', 120);
            $table->unsignedInteger('tamano');
            $table->string('estado', 30)->default('pendiente_revision');
            $table->string('motivo_rechazo', 500)->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->unique(['candidato_estudiante_id', 'codigo']);
            $table->index('candidato_estudiante_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisito_estudiante');
    }
};
