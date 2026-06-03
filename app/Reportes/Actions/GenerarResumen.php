<?php

namespace App\Reportes\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Calcula las estadísticas agregadas del sistema (KPIs, tasas de admisión por
 * carrera, recaudación por gestión y promedios por materia), opcionalmente
 * acotadas a una gestión.
 */
class GenerarResumen
{
    public function __invoke(?int $gestionId = null): array
    {
        return [
            'kpis' => $this->kpis($gestionId),
            'porCarrera' => $this->porCarrera($gestionId),
            'porGestion' => $this->porGestion($gestionId),
            'porMateria' => $this->porMateria($gestionId),
            'admisionDist' => $this->distribucionAdmision($gestionId),
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

        $recaudacion = (clone $pagos)->where('pg.estado', 'pagado')->sum('pg.monto_bs');
        $pagosPagados = (clone $pagos)->where('pg.estado', 'pagado')->count();
        $pagosPendientes = (clone $pagos)->where('pg.estado', 'pendiente')->count();

        $grupos = DB::table('grupo')
            ->when($gestionId, fn ($q) => $q->where('gestion_id', $gestionId))
            ->count();

        return [
            'postulaciones' => $total,
            'admitidos' => $admitidos,
            'no_admitidos' => $noAdmitidos,
            'pendientes' => $pendientes,
            'tasa_admision' => $total > 0 ? round($admitidos / $total * 100, 1) : 0.0,
            'promedio_general' => $promedio !== null ? round((float) $promedio, 2) : null,
            'recaudacion_bs' => round((float) $recaudacion, 2),
            'pagos_pagados' => $pagosPagados,
            'pagos_pendientes' => $pagosPendientes,
            'grupos' => $grupos,
            'docentes' => DB::table('docente')->count(),
        ];
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
            ->where('pg.estado', 'pagado')
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

    private function porMateria(?int $gestionId): array
    {
        return DB::table('evaluacion as e')
            ->join('postulacion as p', 'p.id', '=', 'e.postulacion_id')
            ->join('materia as m', 'm.codigo', '=', 'e.codigo_materia')
            ->when($gestionId, fn ($q) => $q->where('p.gestion_id', $gestionId))
            ->select(
                'm.nombre as materia',
                DB::raw('COUNT(*) as evaluaciones'),
                DB::raw('AVG(e.nota_cruda) as promedio'),
                DB::raw('MAX(e.nota_cruda) as maxima'),
                DB::raw('MIN(e.nota_cruda) as minima'),
            )
            ->groupBy('m.nombre')
            ->orderBy('m.nombre')
            ->get()
            ->map(fn ($r) => [
                'materia' => $r->materia,
                'evaluaciones' => (int) $r->evaluaciones,
                'promedio' => round((float) $r->promedio, 2),
                'maxima' => round((float) $r->maxima, 2),
                'minima' => round((float) $r->minima, 2),
            ])
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
