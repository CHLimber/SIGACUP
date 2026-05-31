<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidato_docente', function (Blueprint $table) {
            $table->string('titulo', 120)->nullable()->after('motivo_rechazo');
            $table->unsignedTinyInteger('experiencia_anios')->nullable()->after('titulo');
            $table->boolean('tiene_diplomado')->default(false)->after('experiencia_anios');
            $table->boolean('tiene_maestria')->default(false)->after('tiene_diplomado');
        });
    }

    public function down(): void
    {
        Schema::table('candidato_docente', function (Blueprint $table) {
            $table->dropColumn(['titulo', 'experiencia_anios', 'tiene_diplomado', 'tiene_maestria']);
        });
    }
};
