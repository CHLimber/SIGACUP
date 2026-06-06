<?php

namespace App\ReportesNotificaciones\Reports\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Helpers SQL compartidos por los reportes que miden desempeño académico
 * (Grupos, Rendimiento docente). Centraliza tres piezas que deben coincidir
 * exactamente con la regla de admisión implementada en PHP
 * (CalcularNotasPostulacion / EjecutarProcesoAdmision):
 *
 *  1. La nota final de una materia = promedio ponderado de sus exámenes
 *     (sum(nota·peso) / sum(peso)).
 *  2. El umbral de aprobación es configurable por gestión (parámetro
 *     `nota_minima_aprobacion`), con 60 como valor por defecto.
 *  3. Aprobar una materia es nota_final >= umbral.
 */
trait AnalisisAcademico
{
    /**
     * Subconsulta con la nota final ponderada de cada materia rendida por cada
     * postulación: (postulacion_id, codigo_materia, nota_final).
     */
    protected function notaFinalPorMateria(): Builder
    {
        return DB::table('evaluacion')
            ->select(
                'postulacion_id',
                'codigo_materia',
                DB::raw('SUM(nota_cruda * peso) / NULLIF(SUM(peso), 0) as nota_final'),
            )
            ->groupBy('postulacion_id', 'codigo_materia');
    }

    /**
     * Une la tabla `parametro` para exponer la nota mínima de aprobación de la
     * gestión del grupo bajo el alias `pm`.
     */
    protected function joinUmbralAprobacion(Builder $query, string $gestionColumna): void
    {
        $query->leftJoin('parametro as pm', function ($join) use ($gestionColumna) {
            $join->on('pm.gestion_id', '=', $gestionColumna)
                ->where('pm.clave', '=', 'nota_minima_aprobacion');
        });
    }

    /** Expresión SQL del umbral de aprobación (con 60 por defecto). */
    protected function umbralAprobacionExpr(): string
    {
        return 'COALESCE(CAST(pm.valor AS DECIMAL(5,2)), 60)';
    }

    /**
     * Expresión SQL agregada que cuenta aprobados sobre la columna de nota final
     * del join (`nf.nota_final`).
     */
    protected function aprobadosExpr(string $notaColumna = 'nf.nota_final'): string
    {
        return "SUM(CASE WHEN {$notaColumna} >= {$this->umbralAprobacionExpr()} THEN 1 ELSE 0 END)";
    }

    /** Expresión SQL para derivar el turno a partir de una hora de inicio. */
    protected function turnoExpr(string $horaColumna): string
    {
        return "CASE
            WHEN {$horaColumna} IS NULL THEN 'Sin horario'
            WHEN {$horaColumna} < '12:00:00' THEN 'Mañana'
            WHEN {$horaColumna} < '18:00:00' THEN 'Tarde'
            ELSE 'Noche'
        END";
    }

    /**
     * Concatenación agregada de cadenas, portable entre PostgreSQL (producción)
     * y SQLite (tests): STRING_AGG vs GROUP_CONCAT.
     */
    protected function concatAgg(string $expr, string $separador = ', '): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'pgsql'
            ? "STRING_AGG(DISTINCT {$expr}, '{$separador}')"
            : "GROUP_CONCAT(DISTINCT {$expr})";
    }
}
