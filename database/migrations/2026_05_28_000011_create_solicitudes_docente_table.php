<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_docente', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 20)->unique();
            $table->string('apellido');
            $table->string('nombres');
            $table->date('fecha_nacimiento');
            $table->string('sexo', 20);
            $table->string('telefono', 30);
            $table->string('email')->unique();
            $table->string('direccion');
            $table->string('estado', 30)->default('pendiente');
            $table->string('token_acceso', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamp('rechazado_at')->nullable();
            $table->string('motivo_rechazo')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_docente');
    }
};
