<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gestion_id')->constrained('gestion')->restrictOnDelete();
            $table->string('clave', 80);
            $table->string('valor', 255);
            $table->timestamps();

            $table->unique(['gestion_id', 'clave']);
            $table->foreign('clave')->references('clave')->on('parametro_catalogo')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro');
    }
};
