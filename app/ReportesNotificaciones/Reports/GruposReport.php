<?php

namespace App\ReportesNotificaciones\Reports;

use App\ReportesNotificaciones\Reports\Concerns\AnalisisAcademico;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Grupos habilitados por gestión: turno, aula, docentes asignados, ocupación y
 * desempeño (inscritos, aprobados, reprobados y % de aprobación).
 *
 * Cubre los reportes obligatorios "cantidad de grupos habilitados", "docentes
 * por grupos", "grupos con mayor cantidad de aprobados" (ordenando por
 * aprobados ↓) y "aprobados/reprobados por grupo".
 */
class GruposReport extends AbstractReport
{
    use AnalisisAcademico;

    public function key(): string
    {
        return 'grupos';
    }

    public function label(): string
    {
        return 'Grupos';
    }

    public function descripcion(): string
    {
        return 'Grupos habilitados, turno, docentes, ocupación y aprobados/reprobados por grupo.';
    }

    public function columns(): array
    {
        return [
            'gestion' => ['label' => 'Gestión',       'type' => 'text'],
            'materia' => ['label' => 'Materia',       'type' => 'text'],
            'grupo' => ['label' => 'Grupo',         'type' => 'text'],
            'turno' => ['label' => 'Turno',         'type' => 'badge'],
            'aula' => ['label' => 'Aula',          'type' => 'text'],
            'docentes' => ['label' => 'Docente(s)',    'type' => 'text'],
            'capacidad_max' => ['label' => 'Capacidad',     'type' => 'number'],
            'inscritos' => ['label' => 'Inscritos',     'type' => 'number'],
            'ocupacion' => ['label' => 'Ocupación (%)', 'type' => 'decimal'],
            'aprobados' => ['label' => 'Aprobados',     'type' => 'number'],
            'reprobados' => ['label' => 'Reprobados',    'type' => 'number'],
            'pct_aprobados' => ['label' => '% Aprobados',   'type' => 'decimal'],
            'promedio' => ['label' => 'Promedio',      'type' => 'decimal'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'materia' => 'Materia',
            'turno' => 'Turno',
            'gestion' => 'Gestión',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',         'label' => 'Buscar (grupo / materia)', 'type' => 'text'],
            ['key' => 'gestion_id',     'label' => 'Gestión', 'type' => 'select', 'options' => $this->opcionesGestion()],
            ['key' => 'codigo_materia', 'label' => 'Materia', 'type' => 'select', 'options' => $this->opcionesMateria()],
            ['key' => 'turno',          'label' => 'Turno', 'type' => 'select', 'options' => [
                ['value' => 'manana', 'label' => 'Mañana'],
                ['value' => 'tarde',  'label' => 'Tarde'],
                ['value' => 'noche',  'label' => 'Noche'],
            ]],
        ];
    }

    protected function baseQuery(): Builder
    {
        $docentes = DB::table('docente_grupo as dg')
            ->join('docente as d', 'd.id', '=', 'dg.docente_id')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->join('persona as dp', 'dp.id', '=', 'u.persona_id')
            ->select('dg.grupo_id', DB::raw($this->concatAgg("dp.apellido || ' ' || dp.nombres").' as docentes'))
            ->groupBy('dg.grupo_id');

        $query = DB::table('grupo as g')
            ->join('gestion as ge', 'ge.id', '=', 'g.gestion_id')
            ->join('materia as m', 'm.codigo', '=', 'g.codigo_materia')
            ->leftJoin('horario as h', 'h.id', '=', 'g.horario_id')
            ->leftJoin('aula as a', 'a.id', '=', 'g.aula_id')
            ->leftJoin('asignacion_grupo as ag', 'ag.grupo_id', '=', 'g.id')
            ->leftJoinSub($this->notaFinalPorMateria(), 'nf', function ($join) {
                $join->on('nf.postulacion_id', '=', 'ag.postulacion_id')
                    ->on('nf.codigo_materia', '=', 'g.codigo_materia');
            })
            ->leftJoinSub($docentes, 'ds', 'ds.grupo_id', '=', 'g.id');

        $this->joinUmbralAprobacion($query, 'g.gestion_id');

        $inscritos = 'COUNT(DISTINCT ag.postulacion_id)';

        return $query
            ->groupBy('g.id', 'ge.anio', 'ge.semestre', 'm.nombre', 'g.nombre', 'h.hora_inicio', 'a.nombre', 'g.capacidad_max', 'ds.docentes')
            ->select([
                DB::raw("ge.anio || '-' || ge.semestre as gestion"),
                'm.nombre as materia',
                'g.nombre as grupo',
                DB::raw($this->turnoExpr('h.hora_inicio').' as turno'),
                'a.nombre as aula',
                'ds.docentes as docentes',
                'g.capacidad_max as capacidad_max',
                DB::raw("{$inscritos} as inscritos"),
                DB::raw("ROUND({$inscritos} * 100.0 / NULLIF(g.capacidad_max, 0), 1) as ocupacion"),
                DB::raw($this->aprobadosExpr().' as aprobados'),
                DB::raw("{$inscritos} - ".$this->aprobadosExpr().' as reprobados'),
                DB::raw('ROUND('.$this->aprobadosExpr()." * 100.0 / NULLIF({$inscritos}, 0), 1) as pct_aprobados"),
                DB::raw('ROUND(AVG(nf.nota_final), 2) as promedio'),
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['m.nombre', 'g.nombre']);
        $this->filtroIgual($query, $filtros, 'gestion_id', 'g.gestion_id');
        $this->filtroIgual($query, $filtros, 'codigo_materia', 'g.codigo_materia');
        $this->filtroTurno($query, $filtros);
    }

    /** Filtra por turno traduciendo a un rango de hora de inicio. */
    private function filtroTurno(Builder $query, array $filtros): void
    {
        $turno = $filtros['turno'] ?? null;

        match ($turno) {
            'manana' => $query->whereRaw("h.hora_inicio < '12:00:00'"),
            'tarde' => $query->whereRaw("h.hora_inicio >= '12:00:00' and h.hora_inicio < '18:00:00'"),
            'noche' => $query->whereRaw("h.hora_inicio >= '18:00:00'"),
            default => null,
        };
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
