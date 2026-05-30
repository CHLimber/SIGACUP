<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->smallInteger('experiencia_anios')->default(0);
            $table->boolean('tiene_diplomado')->default(false);
            $table->boolean('tiene_maestria')->default(false);
            $table->timestamps();
        });

        Schema::create('docente_documento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docente')->cascadeOnDelete();
            $table->string('tipo', 60);
            $table->string('archivo_url', 300);
            $table->timestamp('subido_en')->useCurrent();
            $table->timestamps();

            $table->index('docente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_documento');
        Schema::dropIfExists('docente');
    }
};
