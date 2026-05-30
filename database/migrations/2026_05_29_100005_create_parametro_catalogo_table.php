<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametro_catalogo', function (Blueprint $table) {
            $table->string('clave', 80)->primary();
            $table->string('tipo', 20);
            $table->string('descripcion', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro_catalogo');
    }
};
