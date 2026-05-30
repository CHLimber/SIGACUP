<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona', function (Blueprint $table) {
            $table->id();
            $table->string('ci', 20)->unique();
            $table->string('apellido');
            $table->string('nombres');
            $table->date('fecha_nacimiento');
            $table->string('sexo', 20);
            $table->string('telefono', 30);
            $table->string('email')->unique();
            $table->string('direccion', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona');
    }
};
