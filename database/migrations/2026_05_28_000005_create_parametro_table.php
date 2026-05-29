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
            $table->foreignId('gestion_id')->constrained('gestion')->cascadeOnDelete();
            $table->string('clave', 80);
            $table->string('valor', 255);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->unique(['gestion_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro');
    }
};
