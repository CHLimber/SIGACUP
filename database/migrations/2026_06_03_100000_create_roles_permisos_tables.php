<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('label', 80);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('es_sistema')->default(false);
            $table->timestamps();
        });

        Schema::create('permiso', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('label', 120);
            $table->string('grupo', 60)->default('General');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('permiso_rol', function (Blueprint $table) {
            $table->foreignId('rol_id')->constrained('rol')->cascadeOnDelete();
            $table->foreignId('permiso_id')->constrained('permiso')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['rol_id', 'permiso_id']);
            $table->index('permiso_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permiso_rol');
        Schema::dropIfExists('permiso');
        Schema::dropIfExists('rol');
    }
};
