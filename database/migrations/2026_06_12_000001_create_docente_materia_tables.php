<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docente', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('tiene_maestria');
        });

        Schema::create('candidato_docente_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidato_docente_id')->constrained('candidato_docente')->cascadeOnDelete();
            $table->char('codigo_materia', 6);
            $table->timestamps();

            $table->unique(['candidato_docente_id', 'codigo_materia']);
            $table->foreign('codigo_materia')->references('codigo')->on('materia')->cascadeOnDelete();
        });

        Schema::create('docente_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docente')->cascadeOnDelete();
            $table->char('codigo_materia', 6);
            $table->timestamps();

            $table->unique(['docente_id', 'codigo_materia']);
            $table->foreign('codigo_materia')->references('codigo')->on('materia')->cascadeOnDelete();
        });

        $this->backfillDesdeGruposAsignados();
    }

    /**
     * Los docentes existentes no declararon materias (el dato no existía):
     * se infieren de los grupos que ya tienen asignados, y se copian a su
     * candidato para mantener consistente el historial de postulación.
     */
    private function backfillDesdeGruposAsignados(): void
    {
        $now = now();

        $paresDocente = DB::table('docente_grupo')
            ->join('grupo', 'grupo.id', '=', 'docente_grupo.grupo_id')
            ->select('docente_grupo.docente_id', 'grupo.codigo_materia')
            ->distinct()
            ->get();

        DB::table('docente_materia')->insert(
            $paresDocente->map(fn ($p) => [
                'docente_id' => $p->docente_id,
                'codigo_materia' => $p->codigo_materia,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );

        $paresCandidato = DB::table('docente_materia')
            ->join('docente', 'docente.id', '=', 'docente_materia.docente_id')
            ->join('candidato_docente', 'candidato_docente.user_id', '=', 'docente.user_id')
            ->select('candidato_docente.id as candidato_docente_id', 'docente_materia.codigo_materia')
            ->distinct()
            ->get();

        DB::table('candidato_docente_materia')->insert(
            $paresCandidato->map(fn ($p) => [
                'candidato_docente_id' => $p->candidato_docente_id,
                'codigo_materia' => $p->codigo_materia,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_materia');
        Schema::dropIfExists('candidato_docente_materia');

        Schema::table('docente', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
