<?php

namespace App\ReportesNotificaciones\Actions;

use App\RegistroInscripcion\Models\Pago;
use App\ReportesNotificaciones\Reports\GruposReport;
use App\ReportesNotificaciones\Reports\RendimientoDocenteReport;
use Illuminate\Support\Facades\DB;

/**
 * Calcula las estadísticas agregadas del sistema (KPIs, tasas de admisión por
 * carrera, recaudación por gestión, estadísticas por materia, grupos con más
 * aprobados y rendimiento docente), opcionalmente acotadas a una gestión.
 *
 * Concentra en un solo lugar todos los reportes obligatorios del brief en su
 * versión resumida/agregada para alimentar el dashboard de "Resumen estadístico".
 */
class GenerarResumen
{
    // CU17 — Generar reporte con filtros dinámicos (calcula todos los KPIs y estadísticas del resumen)
    public function __invoke(?int $gestionId = null): array
    {
        return [
            'kpis' => $this->kpis($gestionId),
            'porCarrera' => $this->porCarrera($gestionId),
            'porGestion' => $this->porGestion($gestionId),
            'porMateria' => $this->porMateria($gestionId),
            'admisionDist' => $this->distribucionAdmision($gestionId),
            'topGrupos' => $this->topGrupos($gestionId),
            'topDocentes' => $this->topDocentes($gestionId),
        ];
    }

    private function kpis(?int $gestionId): array
    {
        $post = DB::table('postulacion')->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId));

        $total = (clone $post)->count();
        $admitidos = (clone $post)->where('estado_admision', 'admitido')->count();
        $noAdmitidos = (clone $post)->where('estado_admision', 'no_admitido')->count();
        $pendientes = (clone $post)->where('estado_admision', 'pendiente')->count();
        $promedio = (clone $post)->whereNotNull('promedio_general')->avg('promedio_general');

        $pagos = DB::table('pago as pg')
            ->join('postulacion as p', 'p.id', '=', 'pg.postulacion_id')
            ->when($gestionId, fn ($q) => $q->where('p.gestion_id', $gestionId));

        $recaudacion = (clone $pagos)->where('pg.estado', Pago::ESTADO_COMPLETADO)->sum('pg.monto_bs');
        $pagosPagados = (clone $pagos)->where('pg.estado', Pago::ESTADO_COMPLETADO)->count();
        $pagosPendientes = (clone $pagos)->where('pg.estado', Pago::ESTADO_PENDIENTE)->count();

        $grupos = DB::table('grupo')
            ->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId))
            ->count();

        $cup = $this->resultadoCup($gestionId);

        return [
            'postulaciones' => $total,
            'admitidos' => $admitidos,
            'no_admitidos' => $noAdmitidos,
            'pendientes' => $pendientes,
            'tasa_admision' => $total > 0 ? round($admitidos / $total * 100, 1) : 0.0,
            'promedio_general' => $promedio !== null ? round((float) $promedio, 2) : null,
            'aprobados_cup' => $cup['aprobados'],
            'reprobados_cup' => $cup['reprobados'],
            'recaudacion_bs' => round((float) $recaudacion, 2),
            'pagos_pagados' => $pagosPagados,
            'pagos_pendientes' => $pagosPendientes,
            'grupos' => $grupos,
            'docentes' => DB::table('docente')->count(),
        ];
    }

    /**
     * Aprobados / reprobados del CUP por resultado académico: aprueba quien
     * obtuvo la nota mínima en TODAS las materias rendidas (regla por materia,
     * no por promedio). Independiente del cupo de carrera.
     *
     * @return array{aprobados:int, reprobados:int}
     */
    private function resultadoCup(?int $gestionId): array
    {
        // Nivel 1: ¿aprobó cada materia? (1/0) por postulación y materia.
        $porMateria = DB::table('evaluacion as e')
            ->join('postulacion as p', 'p.id', '=', 'e.postulacion_id')
            ->leftJoin('parametro as pm', function ($j) {
                $j->on('pm.gestion_id', '=', 'p.gestion_id')
                    ->where('pm.clave', '=', 'nota_minima_aprobacion');
            })
            ->when($gestionId, fn ($q) => $q->where('p.gestion_id', $gestionId))
            ->groupBy('e.postulacion_id', 'e.codigo_materia', 'pm.valor')
            ->select(
                'e.postulacion_id',
                DB::raw('CASE WHEN SUM(e.nota_cruda * e.peso) / NULLIF(SUM(e.peso), 0) >= COALESCE(CAST(pm.valor AS DECIMAL(5,2)), 60) THEN 1 ELSE 0 END as aprob'),
            );

        // Nivel 2: aprobó el CUP si aprobó TODAS las materias rendidas (MIN = 1).
        $porPostulacion = DB::query()->fromSub($porMateria, 'pm2')
            ->groupBy('pm2.postulacion_id')
            ->select(DB::raw('MIN(aprob) as aprobado'));

        $row = DB::query()->fromSub($porPostulacion, 'pp')
            ->selectRaw('COALESCE(SUM(aprobado), 0) as aprobados, COUNT(*) - COALESCE(SUM(aprobado), 0) as reprobados')
            ->first();

        return [
            'aprobados' => (int) ($row->aprobados ?? 0),
            'reprobados' => (int) ($row->reprobados ?? 0),
        ];
    }

    /**
     * Grupos con mayor cantidad de aprobados (top 10), con sus docentes,
     * ocupación y tasa de aprobación. Reutiliza el motor de GruposReport para
     * que el cálculo coincida con el reporte tabular.
     */
    private function topGrupos(?int $gestionId): array
    {
        $filtros = $gestionId ? ['gestion_id' => (string) $gestionId] : [];

        $datos = (new GruposReport)->run([
            'filtros' => $filtros,
            'sort' => 'aprobados',
            'dir' => 'desc',
        ]);

        return array_slice($datos['rows'], 0, 10);
    }

    /**
     * Docentes con mayor porcentaje de aprobados en sus grupos (top 10).
     * Reutiliza el motor de RendimientoDocenteReport.
     */
    private function topDocentes(?int $gestionId): array
    {
        $filtros = $gestionId ? ['gestion_id' => (string) $gestionId] : [];

        $datos = (new RendimientoDocenteReport)->run([
            'filtros' => $filtros,
            'sort' => 'pct_aprobados',
            'dir' => 'desc',
        ]);

        // Solo grupos con inscritos aportan al ranking de desempeño.
        $rows = array_values(array_filter(
            $datos['rows'],
            fn ($r) => (int) ($r['inscritos'] ?? 0) > 0,
        ));

        return array_slice($rows, 0, 10);
    }

    private function porCarrera(?int $gestionId): array
    {
        $carreras = DB::table('carrera')->orderBy('nombre')->get();

        $primera = DB::table('postulacion')
            ->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId))
            ->select('carrera1_id', DB::raw('COUNT(*) as total'))
            ->groupBy('carrera1_id')
            ->pluck('total', 'carrera1_id');

        $asignados = DB::table('postulacion')
            ->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId))
            ->where('estado_admision', 'admitido')
            ->select('carrera_asignada_id', DB::raw('COUNT(*) as total'), DB::raw('AVG(promedio_general) as prom'))
            ->groupBy('carrera_asignada_id')
            ->get()
            ->keyBy('carrera_asignada_id');

        $cupos = DB::table('cupo_carrera')
            ->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId))
            ->select('carrera_id', DB::raw('SUM(cupo_max) as cupo'))
            ->groupBy('carrera_id')
            ->pluck('cupo', 'carrera_id');

        return $carreras->map(function ($c) use ($primera, $asignados, $cupos) {
            $admitidos = (int) ($asignados[$c->id]->total ?? 0);
            $cupo = (int) ($cupos[$c->id] ?? 0);

            return [
                'carrera' => $c->nombre,
                'primera_opcion' => (int) ($primera[$c->id] ?? 0),
                'admitidos' => $admitidos,
                'cupo' => $cupo,
                'ocupacion' => $cupo > 0 ? round($admitidos / $cupo * 100, 1) : null,
                'promedio' => isset($asignados[$c->id]->prom) ? round((float) $asignados[$c->id]->prom, 2) : null,
            ];
        })->all();
    }

    private function porGestion(?int $gestionId): array
    {
        $gestiones = DB::table('gestion')
            ->when($gestionId, fn ($q) => $q->where('id', $gestionId))
            ->orderByDesc('anio')->orderByDesc('semestre')
            ->get();

        $post = DB::table('postulacion')
            ->select(
                'gestion_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN estado_admision = 'admitido' THEN 1 ELSE 0 END) as admitidos"),
            )
            ->groupBy('gestion_id')
            ->get()
            ->keyBy('gestion_id');

        $recaudacion = DB::table('pago as pg')
            ->join('postulacion as p', 'p.id', '=', 'pg.postulacion_id')
            ->where('pg.estado', Pago::ESTADO_COMPLETADO)
            ->select('p.gestion_id', DB::raw('SUM(pg.monto_bs) as rec'), DB::raw('COUNT(*) as pagos'))
            ->groupBy('p.gestion_id')
            ->get()
            ->keyBy('gestion_id');

        return $gestiones->map(function ($g) use ($post, $recaudacion) {
            $total = (int) ($post[$g->id]->total ?? 0);
            $admitidos = (int) ($post[$g->id]->admitidos ?? 0);

            return [
                'gestion' => $g->anio.'-'.$g->semestre,
                'estado' => $g->estado,
                'postulaciones' => $total,
                'admitidos' => $admitidos,
                'tasa_admision' => $total > 0 ? round($admitidos / $total * 100, 1) : 0.0,
                'recaudacion' => round((float) ($recaudacion[$g->id]->rec ?? 0), 2),
                'pagos' => (int) ($recaudacion[$g->id]->pagos ?? 0),
            ];
        })->all();
    }

    /**
     * Estadísticas por materia sobre la NOTA FINAL ponderada de cada estudiante
     * (no el examen suelto): promedio, máxima, mínima y aprobados / reprobados
     * según la nota mínima configurada por gestión.
     */
    private function porMateria(?int $gestionId): array
    {
        // Nota final ponderada por materia y postulación.
        $notaFinal = DB::table('evaluacion as e')
            ->join('postulacion as p', 'p.id', '=', 'e.postulacion_id')
            ->when($gestionId, fn ($q) => $q->where('p.gestion_id', $gestionId))
            ->select(
                'p.gestion_id',
                'e.codigo_materia',
                'e.postulacion_id',
                DB::raw('SUM(e.nota_cruda * e.peso) / NULLIF(SUM(e.peso), 0) as nota_final'),
            )
            ->groupBy('p.gestion_id', 'e.codigo_materia', 'e.postulacion_id');

        return DB::query()->fromSub($notaFinal, 'nf')
            ->join('materia as m', 'm.codigo', '=', 'nf.codigo_materia')
            ->leftJoin('parametro as pm', function ($j) {
                $j->on('pm.gestion_id', '=', 'nf.gestion_id')
                    ->where('pm.clave', '=', 'nota_minima_aprobacion');
            })
            ->groupBy('m.nombre')
            ->orderBy('m.nombre')
            ->select(
                'm.nombre as materia',
                DB::raw('COUNT(*) as estudiantes'),
                DB::raw('AVG(nf.nota_final) as promedio'),
                DB::raw('MAX(nf.nota_final) as maxima'),
                DB::raw('MIN(nf.nota_final) as minima'),
                DB::raw('SUM(CASE WHEN nf.nota_final >= COALESCE(CAST(pm.valor AS DECIMAL(5,2)), 60) THEN 1 ELSE 0 END) as aprobados'),
            )
            ->get()
            ->map(function ($r) {
                $estudiantes = (int) $r->estudiantes;
                $aprobados = (int) $r->aprobados;

                return [
                    'materia' => $r->materia,
                    'estudiantes' => $estudiantes,
                    'promedio' => round((float) $r->promedio, 2),
                    'maxima' => round((float) $r->maxima, 2),
                    'minima' => round((float) $r->minima, 2),
                    'aprobados' => $aprobados,
                    'reprobados' => $estudiantes - $aprobados,
                    'tasa_aprobacion' => $estudiantes > 0 ? round($aprobados / $estudiantes * 100, 1) : 0.0,
                ];
            })
            ->all();
    }

    private function distribucionAdmision(?int $gestionId): array
    {
        return DB::table('postulacion')
            ->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId))
            ->select('estado_admision', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_admision')
            ->get()
            ->map(fn ($r) => ['label' => $r->estado_admision, 'total' => (int) $r->total])
            ->all();
    }
}
