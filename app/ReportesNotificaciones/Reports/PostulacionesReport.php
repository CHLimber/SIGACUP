<?php

namespace App\ReportesNotificaciones\Reports;

use App\ReportesNotificaciones\Reports\Concerns\AnalisisAcademico;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PostulacionesReport extends AbstractReport
{
    use AnalisisAcademico;

    public function key(): string
    {
        return 'postulaciones';
    }

    public function label(): string
    {
        return 'Postulaciones / Admisión';
    }

    public function descripcion(): string
    {
        return 'Postulantes, carreras de preferencia, promedio y resultado del proceso de admisión.';
    }

    public function columns(): array
    {
        return [
            'ci' => ['label' => 'CI',              'type' => 'text',    'sort' => 'per.ci'],
            'apellido' => ['label' => 'Apellido',        'type' => 'text',    'sort' => 'per.apellido'],
            'nombres' => ['label' => 'Nombres',         'type' => 'text',    'sort' => 'per.nombres'],
            'sexo' => ['label' => 'Sexo',            'type' => 'badge',   'sort' => 'per.sexo'],
            'gestion' => ['label' => 'Gestión',         'type' => 'text',    'sort' => 'g.anio, g.semestre'],
            'carrera1' => ['label' => '1ª opción',       'type' => 'text',    'sort' => 'c1.nombre'],
            'carrera2' => ['label' => '2ª opción',       'type' => 'text',    'sort' => 'c2.nombre'],
            'promedio_general' => ['label' => 'Promedio',        'type' => 'decimal', 'sort' => 'p.promedio_general'],
            'carrera_asignada' => ['label' => 'Carrera asignada', 'type' => 'text',   'sort' => 'ca.nombre'],
            'estado_admision' => ['label' => 'Admisión',        'type' => 'badge',   'sort' => 'p.estado_admision'],
            'resultado_final' => ['label' => 'Resultado',        'type' => 'badge',   'sort' => 'p.estado_admision'],
            'estado_pago' => ['label' => 'Pago',            'type' => 'badge',   'sort' => 'p.estado_pago'],
            'created_at' => ['label' => 'Registrado',      'type' => 'datetime', 'sort' => 'p.created_at'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'estado_admision' => 'Estado de admisión',
            'resultado_final' => 'Resultado final',
            'carrera_asignada' => 'Carrera asignada',
            'carrera1' => '1ª opción',
            'sexo' => 'Sexo',
            'estado_pago' => 'Estado de pago',
            'gestion' => 'Gestión',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',          'label' => 'Buscar (CI / nombre)', 'type' => 'text'],
            ['key' => 'gestion_id',      'label' => 'Gestión',              'type' => 'select', 'options' => $this->opcionesGestion()],
            ['key' => 'carrera_id',      'label' => 'Carrera (cualquier opción)', 'type' => 'select', 'options' => $this->opcionesCarrera()],
            ['key' => 'estado_admision', 'label' => 'Estado de admisión',   'type' => 'select', 'options' => [
                ['value' => 'pendiente',   'label' => 'Pendiente'],
                ['value' => 'admitido',    'label' => 'Admitido'],
                ['value' => 'no_admitido', 'label' => 'No admitido'],
            ]],
            ['key' => 'resultado_final', 'label' => 'Resultado final', 'type' => 'select', 'options' => [
                ['value' => 'admitido',  'label' => 'Admitido'],
                ['value' => 'sin_cupo',  'label' => 'Sin cupo (aprobó pero no entró)'],
                ['value' => 'reprobado', 'label' => 'Reprobado (nota < mínima)'],
                ['value' => 'pendiente', 'label' => 'Pendiente'],
            ]],
            ['key' => 'estado_pago', 'label' => 'Estado de pago', 'type' => 'select', 'options' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'pagado',    'label' => 'Pagado'],
            ]],
            ['key' => 'sexo', 'label' => 'Sexo', 'type' => 'select', 'options' => $this->opcionesSexo()],
            ['key' => 'promedio', 'label' => 'Promedio', 'type' => 'numberrange'],
            ['key' => 'fecha',    'label' => 'Fecha de registro', 'type' => 'daterange'],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = DB::table('postulacion as p')
            ->join('candidato_estudiante as ce', 'ce.id', '=', 'p.candidato_estudiante_id')
            ->join('persona as per', 'per.id', '=', 'ce.persona_id')
            ->join('gestion as g', 'g.id', '=', 'p.gestion_id')
            ->leftJoin('carrera as c1', 'c1.id', '=', 'p.carrera1_id')
            ->leftJoin('carrera as c2', 'c2.id', '=', 'p.carrera2_id')
            ->leftJoin('carrera as ca', 'ca.id', '=', 'p.carrera_asignada_id')
            // Resumen de notas finales por postulación (mínima y materias rendidas)
            // para distinguir "sin cupo" (aprobó todo) de "reprobado" (alguna < mínima).
            ->leftJoinSub($this->resumenNotas(), 'rn', 'rn.postulacion_id', '=', 'p.id');

        $this->joinUmbralAprobacion($query, 'p.gestion_id');

        return $query->select([
            'per.ci as ci',
            'per.apellido as apellido',
            'per.nombres as nombres',
            'per.sexo as sexo',
            DB::raw("g.anio || '-' || g.semestre as gestion"),
            'c1.nombre as carrera1',
            'c2.nombre as carrera2',
            'p.promedio_general as promedio_general',
            'ca.nombre as carrera_asignada',
            'p.estado_admision as estado_admision',
            DB::raw($this->resultadoFinalExpr().' as resultado_final'),
            'p.estado_pago as estado_pago',
            'p.created_at as created_at',
        ]);
    }

    /**
     * Subconsulta: por postulación, la nota final más baja entre sus materias y
     * cuántas materias rindió. Postulaciones sin evaluaciones no aparecen (→ null).
     */
    private function resumenNotas(): Builder
    {
        return DB::query()
            ->fromSub($this->notaFinalPorMateria(), 'nf')
            ->select('postulacion_id', DB::raw('MIN(nota_final) as min_nota_final'), DB::raw('COUNT(*) as materias_rendidas'))
            ->groupBy('postulacion_id');
    }

    /**
     * Expresión SQL del resultado real del proceso de admisión, coherente con
     * EjecutarProcesoAdmision: admitido / pendiente / reprobado (alguna materia
     * bajo la nota mínima o no rindió) / sin_cupo (aprobó todo pero no entró).
     */
    private function resultadoFinalExpr(): string
    {
        $umbral = $this->umbralAprobacionExpr();

        return "CASE
            WHEN p.estado_admision = 'admitido' THEN 'admitido'
            WHEN p.estado_admision = 'pendiente' THEN 'pendiente'
            WHEN rn.min_nota_final IS NULL THEN 'reprobado'
            WHEN rn.min_nota_final < {$umbral} THEN 'reprobado'
            ELSE 'sin_cupo'
        END";
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['per.ci', 'per.apellido', 'per.nombres']);
        $this->filtroIgual($query, $filtros, 'gestion_id', 'p.gestion_id');
        $this->filtroIgual($query, $filtros, 'estado_admision', 'p.estado_admision');
        $this->filtroResultadoFinal($query, $filtros);
        $this->filtroIgual($query, $filtros, 'estado_pago', 'p.estado_pago');
        $this->filtroIgual($query, $filtros, 'sexo', 'per.sexo');
        $this->filtroRangoNumero($query, $filtros, 'promedio', 'p.promedio_general');
        $this->filtroRangoFecha($query, $filtros, 'fecha', 'p.created_at');

        $carreraId = $filtros['carrera_id'] ?? null;
        if ($carreraId !== null && $carreraId !== '' && $carreraId !== 'todos') {
            $query->where(function (Builder $q) use ($carreraId) {
                $q->where('p.carrera1_id', $carreraId)
                    ->orWhere('p.carrera2_id', $carreraId)
                    ->orWhere('p.carrera_asignada_id', $carreraId);
            });
        }
    }

    /**
     * Filtra por el resultado real del proceso de admisión. "sin_cupo" y
     * "reprobado" comparten estado_admision='no_admitido' y se distinguen por la
     * nota mínima de aprobación (misma regla que EjecutarProcesoAdmision).
     */
    private function filtroResultadoFinal(Builder $query, array $filtros): void
    {
        $valor = $filtros['resultado_final'] ?? null;

        if ($valor === null || $valor === '' || $valor === 'todos') {
            return;
        }

        $umbral = $this->umbralAprobacionExpr();

        match ($valor) {
            'admitido' => $query->where('p.estado_admision', 'admitido'),
            'pendiente' => $query->where('p.estado_admision', 'pendiente'),
            'reprobado' => $query->where('p.estado_admision', 'no_admitido')
                ->where(fn (Builder $q) => $q
                    ->whereNull('rn.min_nota_final')
                    ->orWhereRaw("rn.min_nota_final < {$umbral}")),
            'sin_cupo' => $query->where('p.estado_admision', 'no_admitido')
                ->whereNotNull('rn.min_nota_final')
                ->whereRaw("rn.min_nota_final >= {$umbral}"),
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

    private function opcionesCarrera(): array
    {
        return DB::table('carrera')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->nombre])
            ->all();
    }

    private function opcionesSexo(): array
    {
        return DB::table('persona')
            ->select('sexo')->distinct()->whereNotNull('sexo')->orderBy('sexo')
            ->pluck('sexo')
            ->map(fn ($s) => ['value' => $s, 'label' => ucfirst($s)])
            ->all();
    }
}
