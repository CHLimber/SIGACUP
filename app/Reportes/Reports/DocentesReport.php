<?php

namespace App\Reportes\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DocentesReport extends AbstractReport
{
    public function key(): string
    {
        return 'docentes';
    }

    public function label(): string
    {
        return 'Docentes';
    }

    public function descripcion(): string
    {
        return 'Docentes registrados: título, experiencia, posgrados y grupos asignados.';
    }

    public function columns(): array
    {
        return [
            'ci' => ['label' => 'CI',           'type' => 'text',   'sort' => 'per.ci'],
            'apellido' => ['label' => 'Apellido',     'type' => 'text',   'sort' => 'per.apellido'],
            'nombres' => ['label' => 'Nombres',      'type' => 'text',   'sort' => 'per.nombres'],
            'email' => ['label' => 'Email',        'type' => 'text',   'sort' => 'per.email'],
            'titulo' => ['label' => 'Título',       'type' => 'text',   'sort' => 'd.titulo'],
            'experiencia_anios' => ['label' => 'Experiencia',  'type' => 'number', 'sort' => 'd.experiencia_anios'],
            'tiene_diplomado' => ['label' => 'Diplomado',    'type' => 'bool',   'sort' => 'd.tiene_diplomado'],
            'tiene_maestria' => ['label' => 'Maestría',     'type' => 'bool',   'sort' => 'd.tiene_maestria'],
            'grupos_count' => ['label' => 'Grupos',       'type' => 'number', 'sort' => 'grupos_count'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'titulo' => 'Título',
            'tiene_diplomado' => 'Tiene diplomado',
            'tiene_maestria' => 'Tiene maestría',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',          'label' => 'Buscar (CI / nombre / email)', 'type' => 'text'],
            ['key' => 'titulo',          'label' => 'Título contiene',  'type' => 'text'],
            ['key' => 'experiencia',     'label' => 'Años de experiencia', 'type' => 'numberrange'],
            ['key' => 'tiene_diplomado', 'label' => 'Tiene diplomado',  'type' => 'boolean'],
            ['key' => 'tiene_maestria',  'label' => 'Tiene maestría',   'type' => 'boolean'],
        ];
    }

    protected function baseQuery(): Builder
    {
        $grupos = DB::table('docente_grupo')
            ->select('docente_id', DB::raw('COUNT(*) as total'))
            ->groupBy('docente_id');

        return DB::table('docente as d')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->join('persona as per', 'per.id', '=', 'u.persona_id')
            ->leftJoinSub($grupos, 'gc', 'gc.docente_id', '=', 'd.id')
            ->select([
                'per.ci as ci',
                'per.apellido as apellido',
                'per.nombres as nombres',
                'per.email as email',
                'd.titulo as titulo',
                'd.experiencia_anios as experiencia_anios',
                'd.tiene_diplomado as tiene_diplomado',
                'd.tiene_maestria as tiene_maestria',
                DB::raw('COALESCE(gc.total, 0) as grupos_count'),
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['per.ci', 'per.apellido', 'per.nombres', 'per.email']);
        $this->filtroLike($query, $filtros, 'titulo', ['d.titulo']);
        $this->filtroRangoNumero($query, $filtros, 'experiencia', 'd.experiencia_anios');
        $this->filtroBooleano($query, $filtros, 'tiene_diplomado', 'd.tiene_diplomado');
        $this->filtroBooleano($query, $filtros, 'tiene_maestria', 'd.tiene_maestria');
    }
}
