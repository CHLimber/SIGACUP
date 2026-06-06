<?php

namespace App\ReportesNotificaciones\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PagosReport extends AbstractReport
{
    public function key(): string
    {
        return 'pagos';
    }

    public function label(): string
    {
        return 'Pagos de matrícula';
    }

    public function descripcion(): string
    {
        return 'Pagos de matrícula por estudiante: monto, método, estado y facturación.';
    }

    public function columns(): array
    {
        return [
            'ci' => ['label' => 'CI',        'type' => 'text',     'sort' => 'per.ci'],
            'estudiante' => ['label' => 'Estudiante', 'type' => 'text',    'sort' => 'per.apellido, per.nombres'],
            'gestion' => ['label' => 'Gestión',   'type' => 'text',     'sort' => 'g.anio, g.semestre'],
            'monto_bs' => ['label' => 'Monto (Bs)', 'type' => 'decimal', 'sort' => 'pg.monto_bs'],
            'metodo' => ['label' => 'Método',    'type' => 'badge',    'sort' => 'pg.metodo'],
            'estado' => ['label' => 'Estado',    'type' => 'badge',    'sort' => 'pg.estado'],
            'numero_factura' => ['label' => 'Factura',   'type' => 'text',     'sort' => 'pg.numero_factura'],
            'fecha' => ['label' => 'Fecha',     'type' => 'datetime', 'sort' => 'pg.fecha'],
        ];
    }

    public function dimensiones(): array
    {
        return [
            'estado' => 'Estado del pago',
            'metodo' => 'Método',
            'gestion' => 'Gestión',
        ];
    }

    public function filters(): array
    {
        return [
            ['key' => 'buscar',     'label' => 'Buscar (CI / nombre / factura)', 'type' => 'text'],
            ['key' => 'gestion_id', 'label' => 'Gestión', 'type' => 'select', 'options' => $this->opcionesGestion()],
            ['key' => 'estado',     'label' => 'Estado', 'type' => 'select', 'options' => [
                ['value' => 'pendiente', 'label' => 'Pendiente'],
                ['value' => 'pagado',    'label' => 'Pagado'],
                ['value' => 'fallido',   'label' => 'Fallido'],
                ['value' => 'reembolsado', 'label' => 'Reembolsado'],
            ]],
            ['key' => 'metodo', 'label' => 'Método', 'type' => 'select', 'options' => [
                ['value' => 'stripe',         'label' => 'Stripe'],
                ['value' => 'transferencia',  'label' => 'Transferencia'],
                ['value' => 'efectivo',       'label' => 'Efectivo'],
            ]],
            ['key' => 'monto', 'label' => 'Monto (Bs)', 'type' => 'numberrange'],
            ['key' => 'fecha', 'label' => 'Fecha de pago', 'type' => 'daterange'],
        ];
    }

    protected function baseQuery(): Builder
    {
        return DB::table('pago as pg')
            ->join('postulacion as p', 'p.id', '=', 'pg.postulacion_id')
            ->join('candidato_estudiante as ce', 'ce.id', '=', 'p.candidato_estudiante_id')
            ->join('persona as per', 'per.id', '=', 'ce.persona_id')
            ->join('gestion as g', 'g.id', '=', 'p.gestion_id')
            ->select([
                'per.ci as ci',
                DB::raw("per.apellido || ' ' || per.nombres as estudiante"),
                DB::raw("g.anio || '-' || g.semestre as gestion"),
                'pg.monto_bs as monto_bs',
                'pg.metodo as metodo',
                'pg.estado as estado',
                'pg.numero_factura as numero_factura',
                'pg.fecha as fecha',
            ]);
    }

    protected function aplicarFiltros(Builder $query, array $filtros): void
    {
        $this->filtroLike($query, $filtros, 'buscar', ['per.ci', 'per.apellido', 'per.nombres', 'pg.numero_factura']);
        $this->filtroIgual($query, $filtros, 'gestion_id', 'p.gestion_id');
        $this->filtroIgual($query, $filtros, 'estado', 'pg.estado');
        $this->filtroIgual($query, $filtros, 'metodo', 'pg.metodo');
        $this->filtroRangoNumero($query, $filtros, 'monto', 'pg.monto_bs');
        $this->filtroRangoFecha($query, $filtros, 'fecha', 'pg.fecha');
    }

    private function opcionesGestion(): array
    {
        return DB::table('gestion')
            ->orderByDesc('anio')->orderByDesc('semestre')
            ->get()
            ->map(fn ($g) => ['value' => (string) $g->id, 'label' => $g->anio.'-'.$g->semestre])
            ->all();
    }
}
