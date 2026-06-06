<?php

namespace App\ReportesNotificaciones\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CalificacionesReport extends AbstractReport
{
    public function key(): string
    {
        return 'calificaciones';
    }

    public function label(): string
    {
        return 'Calificaciones';
    }

    public function descripcion(): string
    {
        return 'Notas por estudiante, materia y examen, con su peso correspondiente.';
    }

    public function columns(): array
    {
        return [
            'ci' => ['label' => 'CI',         'type' => 'text',    'sort' => 'per.ci'],
            'estudiante' => ['label' => 'Estudiante', 'type' => 'text',    'sort' => 'per.apellido, per.nombres'],
            'gestion' => ['label' => 'Gestión',    'type' => 'text',    'sort' => 'g.anio, g.semestre'],
            'materia' => ['label' => 'Materia',    'type' => 'text',    'sort' => 'm.nombre'],
            'numero_examen' => ['label' => 'Examen N°',  'type' => 'number',  'sort' => 'e.numero_examen'],
            'nota_cruda' => ['label' => 'Nota',       'type' => 'decimal', 'sort' => 'e.nota_cruda'],
            'peso' => ['label' => 'Peso (%)',   'type' => 'decimal', 'sort' => 'e.peso'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'materia' => 'Materia',
            'numero_examen' => 'Número de examen',
            'gestion' => 'Gestión',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',        'label' => 'Buscar (CI / nombre)', 'type' => 'text'],
            ['key' => 'gestion_id',    'label' => 'Gestión', 'type' => 'select', 'options' => $this->opcionesGestion()],
            ['key' => 'codigo_materia', 'label' => 'Materia', 'type' => 'select', 'options' => $this->opcionesMateria()],
            ['key' => 'numero_examen', 'label' => 'Examen', 'type' => 'select', 'options' => [
                ['value' => '1', 'label' => '1er examen'],
                ['value' => '2', 'label' => '2do examen'],
                ['value' => '3', 'label' => 'Examen final'],
            ]],
            ['key' => 'nota', 'label' => 'Nota', 'type' => 'numberrange'],
        ];
    }

    protected function baseQuery(): Builder
    {
        return DB::table('evaluacion as e')
            ->join('postulacion as p', 'p.id', '=', 'e.postulacion_id')
            ->join('candidato_estudiante as ce', 'ce.id', '=', 'p.candidato_estudiante_id')
            ->join('persona as per', 'per.id', '=', 'ce.persona_id')
            ->join('gestion as g', 'g.id', '=', 'p.gestion_id')
            ->join('materia as m', 'm.codigo', '=', 'e.codigo_materia')
            ->select([
                'per.ci as ci',
                DB::raw("per.apellido || ' ' || per.nombres as estudiante"),
                DB::raw("g.anio || '-' || g.semestre as gestion"),
                'm.nombre as materia',
                'e.numero_examen as numero_examen',
                'e.nota_cruda as nota_cruda',
                'e.peso as peso',
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['per.ci', 'per.apellido', 'per.nombres']);
        $this->filtroIgual($query, $filtros, 'gestion_id', 'p.gestion_id');
        $this->filtroIgual($query, $filtros, 'codigo_materia', 'e.codigo_materia');
        $this->filtroIgual($query, $filtros, 'numero_examen', 'e.numero_examen');
        $this->filtroRangoNumero($query, $filtros, 'nota', 'e.nota_cruda');
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
