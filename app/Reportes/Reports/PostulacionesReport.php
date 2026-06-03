<?php

namespace App\Reportes\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PostulacionesReport extends AbstractReport
{
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
            'estado_pago' => ['label' => 'Pago',            'type' => 'badge',   'sort' => 'p.estado_pago'],
            'created_at' => ['label' => 'Registrado',      'type' => 'datetime', 'sort' => 'p.created_at'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'estado_admision' => 'Estado de admisión',
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
        return DB::table('postulacion as p')
            ->join('candidato_estudiante as ce', 'ce.id', '=', 'p.candidato_estudiante_id')
            ->join('persona as per', 'per.id', '=', 'ce.persona_id')
            ->join('gestion as g', 'g.id', '=', 'p.gestion_id')
            ->leftJoin('carrera as c1', 'c1.id', '=', 'p.carrera1_id')
            ->leftJoin('carrera as c2', 'c2.id', '=', 'p.carrera2_id')
            ->leftJoin('carrera as ca', 'ca.id', '=', 'p.carrera_asignada_id')
            ->select([
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
                'p.estado_pago as estado_pago',
                'p.created_at as created_at',
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['per.ci', 'per.apellido', 'per.nombres']);
        $this->filtroIgual($query, $filtros, 'gestion_id', 'p.gestion_id');
        $this->filtroIgual($query, $filtros, 'estado_admision', 'p.estado_admision');
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
