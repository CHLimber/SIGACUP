<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitos_archivos', function (Blueprint $table) {
            $table->id();
            $table->string('candidato_type', 50);
            $table->unsignedBigInteger('candidato_id');
            $table->string('codigo', 60);
            $table->string('nombre_original');
            $table->string('ruta_archivo');
            $table->string('mime_type', 120);
            $table->unsignedInteger('tamano');
            $table->string('estado', 30)->default('pendiente_revision');
            $table->string('motivo_rechazo', 500)->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->index(['candidato_type', 'candidato_id'], 'requisitos_candidato_idx');
            $table->unique(['candidato_type', 'candidato_id', 'codigo'], 'requisitos_unico_por_codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitos_archivos');
    }
};
