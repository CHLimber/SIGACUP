<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupo_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carrera')->restrictOnDelete();
            $table->foreignId('gestion_id')->constrained('gestion')->restrictOnDelete();
            $table->unsignedInteger('cupo_max');
            $table->timestamps();

            $table->unique(['carrera_id', 'gestion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupo_carrera');
    }
};
