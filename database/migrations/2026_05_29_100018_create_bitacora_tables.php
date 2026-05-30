<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 50);
            $table->string('modulo', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('fecha')->useCurrent();

            $table->index('usuario_id');
            $table->index(['accion', 'fecha']);
            $table->index('modulo');
            $table->index('fecha');
        });

        Schema::create('bitacora_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bitacora_id')->constrained('bitacora')->cascadeOnDelete();
            $table->string('campo', 100);
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();

            $table->index('bitacora_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_detalle');
        Schema::dropIfExists('bitacora');
    }
};
