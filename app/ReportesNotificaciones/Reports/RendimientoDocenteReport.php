<?php

namespace App\ReportesNotificaciones\Reports;

use App\ReportesNotificaciones\Reports\Concerns\AnalisisAcademico;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Rendimiento de cada docente en los grupos que tiene asignados: inscritos,
 * aprobados, reprobados y % de aprobación.
 *
 * Cubre el reporte obligatorio "qué docente tuvo mayor % de aprobados en su
 * grupo" (ordenando por % de aprobados ↓).
 */
class RendimientoDocenteReport extends AbstractReport
{
    use AnalisisAcademico;

    public function key(): string
    {
        return 'rendimiento_docente';
    }

    public function label(): string
    {
        return 'Rendimiento docente';
    }

    public function descripcion(): string
    {
        return 'Porcentaje de aprobados por docente y grupo, para comparar su desempeño.';
    }

    public function columns(): array
    {
        return [
            'ci' => ['label' => 'CI',           'type' => 'text'],
            'docente' => ['label' => 'Docente',      'type' => 'text'],
            'materia' => ['label' => 'Materia',      'type' => 'text'],
            'grupo' => ['label' => 'Grupo',        'type' => 'text'],
            'turno' => ['label' => 'Turno',        'type' => 'badge'],
            'gestion' => ['label' => 'Gestión',      'type' => 'text'],
            'inscritos' => ['label' => 'Inscritos',    'type' => 'number'],
            'aprobados' => ['label' => 'Aprobados',    'type' => 'number'],
            'reprobados' => ['label' => 'Reprobados',   'type' => 'number'],
            'pct_aprobados' => ['label' => '% Aprobados',  'type' => 'decimal'],
            'promedio' => ['label' => 'Promedio',     'type' => 'decimal'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'docente' => 'Docente',
            'materia' => 'Materia',
            'turno' => 'Turno',
            'gestion' => 'Gestión',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',         'label' => 'Buscar (CI / docente)', 'type' => 'text'],
            ['key' => 'gestion_id',     'label' => 'Gestión', 'type' => 'select', 'options' => $this->opcionesGestion()],
            ['key' => 'codigo_materia', 'label' => 'Materia', 'type' => 'select', 'options' => $this->opcionesMateria()],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = DB::table('docente_grupo as dg')
            ->join('docente as d', 'd.id', '=', 'dg.docente_id')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->join('persona as dp', 'dp.id', '=', 'u.persona_id')
            ->join('grupo as g', 'g.id', '=', 'dg.grupo_id')
            ->join('gestion as ge', 'ge.id', '=', 'g.gestion_id')
            ->join('materia as m', 'm.codigo', '=', 'g.codigo_materia')
            ->leftJoin('horario as h', 'h.id', '=', 'g.horario_id')
            ->leftJoin('asignacion_grupo as ag', 'ag.grupo_id', '=', 'g.id')
            ->leftJoinSub($this->notaFinalPorMateria(), 'nf', function ($join) {
                $join->on('nf.postulacion_id', '=', 'ag.postulacion_id')
                    ->on('nf.codigo_materia', '=', 'g.codigo_materia');
            });

        $this->joinUmbralAprobacion($query, 'g.gestion_id');

        $inscritos = 'COUNT(DISTINCT ag.postulacion_id)';

        return $query
            ->groupBy('dg.docente_id', 'g.id', 'dp.ci', 'dp.apellido', 'dp.nombres', 'm.nombre', 'g.nombre', 'h.hora_inicio', 'ge.anio', 'ge.semestre')
            ->select([
                'dp.ci as ci',
                DB::raw("dp.apellido || ' ' || dp.nombres as docente"),
                'm.nombre as materia',
                'g.nombre as grupo',
                DB::raw($this->turnoExpr('h.hora_inicio').' as turno'),
                DB::raw("ge.anio || '-' || ge.semestre as gestion"),
                DB::raw("{$inscritos} as inscritos"),
                DB::raw($this->aprobadosExpr().' as aprobados'),
                DB::raw("{$inscritos} - ".$this->aprobadosExpr().' as reprobados'),
                DB::raw('ROUND('.$this->aprobadosExpr()." * 100.0 / NULLIF({$inscritos}, 0), 1) as pct_aprobados"),
                DB::raw('ROUND(AVG(nf.nota_final), 2) as promedio'),
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['dp.ci', 'dp.apellido', 'dp.nombres']);
        $this->filtroIgual($query, $filtros, 'gestion_id', 'g.gestion_id');
        $this->filtroIgual($query, $filtros, 'codigo_materia', 'g.codigo_materia');
    }

    private function opcionesGestion(): array
    {
        return DB::table('gestion')
            ->orderByDesc('anio')->orderByDesc('semestre')
            ->get()
            ->map(fn ($g) => ['value' => (string) $g->id, 'label' => $g->anio.'-'.$g->semestre])
            ->all();
    }

    private function opcionesMateria(): array
    {
        return DB::table('materia')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($m) => ['value' => $m->codigo, 'label' => $m->nombre])
            ->all();
    }
}
