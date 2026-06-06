<?php

namespace App\ReportesNotificaciones\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class EstudiantesReport extends AbstractReport
{
    public function key(): string
    {
        return 'estudiantes';
    }

    public function label(): string
    {
        return 'Estudiantes';
    }

    public function descripcion(): string
    {
        return 'Candidatos a estudiante con sus datos personales, estado y carrera asignada.';
    }

    public function columns(): array
    {
        return [
            'ci' => ['label' => 'CI',           'type' => 'text',     'sort' => 'per.ci'],
            'apellido' => ['label' => 'Apellido',     'type' => 'text',     'sort' => 'per.apellido'],
            'nombres' => ['label' => 'Nombres',      'type' => 'text',     'sort' => 'per.nombres'],
            'sexo' => ['label' => 'Sexo',         'type' => 'badge',    'sort' => 'per.sexo'],
            'fecha_nacimiento' => ['label' => 'Nacimiento',   'type' => 'date',     'sort' => 'per.fecha_nacimiento'],
            'email' => ['label' => 'Email',        'type' => 'text',     'sort' => 'per.email'],
            'telefono' => ['label' => 'Teléfono',     'type' => 'text',     'sort' => 'per.telefono'],
            'estado' => ['label' => 'Estado',       'type' => 'badge',    'sort' => 'ce.estado'],
            'gestion' => ['label' => 'Gestión',      'type' => 'text',     'sort' => 'g.anio, g.semestre'],
            'carrera_asignada' => ['label' => 'Carrera asignada', 'type' => 'text', 'sort' => 'ca.nombre'],
            'created_at' => ['label' => 'Registrado',   'type' => 'datetime', 'sort' => 'ce.created_at'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'estado' => 'Estado',
            'carrera_asignada' => 'Carrera asignada',
            'sexo' => 'Sexo',
            'gestion' => 'Gestión',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',     'label' => 'Buscar (CI / nombre / email)', 'type' => 'text'],
            ['key' => 'estado',     'label' => 'Estado', 'type' => 'select', 'options' => [
                ['value' => 'pendiente',               'label' => 'Pendiente'],
                ['value' => 'en_revision',             'label' => 'En revisión'],
                ['value' => 'requiere_correcciones',   'label' => 'Requiere correcciones'],
                ['value' => 'aprobado_pendiente_pago', 'label' => 'Aprobado (pendiente de pago)'],
                ['value' => 'pagado',                  'label' => 'Pagado'],
                ['value' => 'rechazado',               'label' => 'Rechazado'],
            ]],
            ['key' => 'gestion_id', 'label' => 'Gestión', 'type' => 'select', 'options' => $this->opcionesGestion()],
            ['key' => 'carrera_id', 'label' => 'Carrera asignada', 'type' => 'select', 'options' => $this->opcionesCarrera()],
            ['key' => 'sexo',       'label' => 'Sexo', 'type' => 'select', 'options' => $this->opcionesSexo()],
            ['key' => 'fecha',      'label' => 'Fecha de registro', 'type' => 'daterange'],
        ];
    }

    protected function baseQuery(): Builder
    {
        // Postulación más reciente por candidato (subconsulta para evitar duplicados).
        $ultimaPostulacion = DB::table('postulacion')
            ->select('candidato_estudiante_id', DB::raw('MAX(id) as pid'))
            ->groupBy('candidato_estudiante_id');

        return DB::table('candidato_estudiante as ce')
            ->join('persona as per', 'per.id', '=', 'ce.persona_id')
            ->leftJoinSub($ultimaPostulacion, 'up', 'up.candidato_estudiante_id', '=', 'ce.id')
            ->leftJoin('postulacion as p', 'p.id', '=', 'up.pid')
            ->leftJoin('gestion as g', 'g.id', '=', 'p.gestion_id')
            ->leftJoin('carrera as ca', 'ca.id', '=', 'p.carrera_asignada_id')
            ->select([
                'per.ci as ci',
                'per.apellido as apellido',
                'per.nombres as nombres',
                'per.sexo as sexo',
                'per.fecha_nacimiento as fecha_nacimiento',
                'per.email as email',
                'per.telefono as telefono',
                'ce.estado as estado',
                DB::raw("g.anio || '-' || g.semestre as gestion"),
                'ca.nombre as carrera_asignada',
                'ce.created_at as created_at',
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['per.ci', 'per.apellido', 'per.nombres', 'per.email']);
        $this->filtroIgual($query, $filtros, 'estado', 'ce.estado');
        $this->filtroIgual($query, $filtros, 'gestion_id', 'p.gestion_id');
        $this->filtroIgual($query, $filtros, 'carrera_id', 'p.carrera_asignada_id');
        $this->filtroIgual($query, $filtros, 'sexo', 'per.sexo');
        $this->filtroRangoFecha($query, $filtros, 'fecha', 'ce.created_at');
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
