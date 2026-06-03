<?php

namespace App\Reportes\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Base de un reporte tabular con filtros dinámicos, selección de columnas,
 * ordenamiento y resumen para gráficos.
 *
 * Cada reporte concreto declara sus columnas, sus filtros (metadata para la UI)
 * y construye el query base con los joins necesarios. La aplicación de filtros,
 * orden y el armado de filas/resumen es común y vive aquí.
 */
abstract class AbstractReport
{
    /** Identificador único del reporte (slug). */
    abstract public function key(): string;

    /** Nombre legible. */
    abstract public function label(): string;

    /** Descripción corta para la UI. */
    abstract public function descripcion(): string;

    /**
     * Columnas disponibles.
     *
     * @return array<string, array{label:string, type:string, sort?:string}>
     *                                                                       type ∈ text|number|decimal|date|datetime|bool|badge
     *                                                                       sort = expresión SQL para ordenar (por defecto, la misma key)
     */
    abstract public function columns(): array;

    /**
     * Filtros disponibles (solo metadata para construir la UI).
     *
     * @return array<int, array{key:string, label:string, type:string, options?:array}>
     *                                                                                  type ∈ select|text|daterange|numberrange|boolean
     */
    abstract public function filters(): array;

    /**
     * Dimensiones por las que se puede agrupar el resumen / gráfico.
     *
     * @return array<string, string> columnKey => label
     */
    abstract public function dimensiones(): array;

    /** Query base con joins y selects (alias = key de columna). */
    abstract protected function baseQuery(): Builder;

    /** Aplica los filtros recibidos al query. */
    abstract protected function aplicarFiltros(Builder $query, array $filtros): void;

    /** Columnas mostradas por defecto (todas, salvo override). */
    public function columnasPorDefecto(): array
    {
        return array_keys($this->columns());
    }

    /**
     * Ejecuta el reporte.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int, resumen: array}
     */
    public function run(array $params): array
    {
        $filtros = $params['filtros'] ?? [];
        $query = $this->baseQuery();
        $this->aplicarFiltros($query, is_array($filtros) ? $filtros : []);

        $this->aplicarOrden($query, $params['sort'] ?? null, $params['dir'] ?? 'asc');

        $registros = $query->get();

        $rows = $registros->map(fn ($r) => $this->mapearFila((array) $r))->all();

        return [
            'rows' => $rows,
            'total' => count($rows),
            'resumen' => $this->resumir($rows, $params['dimension'] ?? null),
        ];
    }

    /** Aplica el ordenamiento validando contra la whitelist de columnas. */
    protected function aplicarOrden(Builder $query, ?string $sort, string $dir): void
    {
        $columnas = $this->columns();

        if (! $sort || ! isset($columnas[$sort])) {
            $sort = array_key_first($columnas);
        }

        $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';
        $expr = $columnas[$sort]['sort'] ?? $sort;

        $query->orderByRaw($expr.' '.$dir);
    }

    /** Normaliza tipos de cada fila según la definición de columnas. */
    protected function mapearFila(array $fila): array
    {
        $out = [];

        foreach ($this->columns() as $key => $def) {
            $valor = $fila[$key] ?? null;

            $out[$key] = match ($def['type']) {
                'number' => $valor === null ? null : (int) $valor,
                'decimal' => $valor === null ? null : round((float) $valor, 2),
                'bool' => (bool) $valor,
                'date' => $valor ? Carbon::parse($valor)->format('Y-m-d') : null,
                'datetime' => $valor ? Carbon::parse($valor)->format('Y-m-d H:i') : null,
                default => $valor,
            };
        }

        return $out;
    }

    /**
     * Agrupa las filas por una dimensión y cuenta, para alimentar gráficos.
     *
     * @return array{dimension: ?string, label: ?string, items: array<int, array{label:string, total:int}>}
     */
    protected function resumir(array $rows, ?string $dimension): array
    {
        $dims = $this->dimensiones();

        if (! $dimension || ! isset($dims[$dimension])) {
            $dimension = array_key_first($dims);
        }

        if (! $dimension) {
            return ['dimension' => null, 'label' => null, 'items' => []];
        }

        $conteo = [];

        foreach ($rows as $row) {
            $valor = $row[$dimension] ?? null;

            if (is_bool($valor)) {
                $valor = $valor ? 'Sí' : 'No';
            }

            $clave = ($valor === null || $valor === '') ? '(sin dato)' : (string) $valor;
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        arsort($conteo);

        $items = [];
        foreach ($conteo as $label => $total) {
            $items[] = ['label' => $label, 'total' => $total];
        }

        return [
            'dimension' => $dimension,
            'label' => $dims[$dimension],
            'items' => $items,
        ];
    }

    /**
     * Metadata completa del reporte para la UI.
     */
    public function meta(): array
    {
        return [
            'key' => $this->key(),
            'label' => $this->label(),
            'descripcion' => $this->descripcion(),
            'columnas' => collect($this->columns())
                ->map(fn ($d, $k) => ['key' => $k, 'label' => $d['label'], 'type' => $d['type']])
                ->values()
                ->all(),
            'columnasPorDefecto' => $this->columnasPorDefecto(),
            'filtros' => array_values($this->filters()),
            'dimensiones' => collect($this->dimensiones())
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])
                ->values()
                ->all(),
        ];
    }

    // ── Helpers de filtrado reutilizables ───────────────────────────────────

    protected function filtroLike(Builder $query, array $filtros, string $key, array $columnas): void
    {
        $valor = trim((string) ($filtros[$key] ?? ''));

        if ($valor === '') {
            return;
        }

        $query->where(function (Builder $q) use ($columnas, $valor) {
            foreach ($columnas as $col) {
                $q->orWhere($col, 'ilike', '%'.$valor.'%');
            }
        });
    }

    protected function filtroIgual(Builder $query, array $filtros, string $key, string $columna): void
    {
        $valor = $filtros[$key] ?? null;

        if ($valor === null || $valor === '' || $valor === 'todos') {
            return;
        }

        $query->where($columna, $valor);
    }

    protected function filtroBooleano(Builder $query, array $filtros, string $key, string $columna): void
    {
        $valor = $filtros[$key] ?? null;

        if ($valor === null || $valor === '' || $valor === 'todos') {
            return;
        }

        $query->where($columna, in_array($valor, ['1', 'si', 'true', true, 1], true));
    }

    protected function filtroRangoNumero(Builder $query, array $filtros, string $key, string $columna): void
    {
        $rango = $filtros[$key] ?? null;

        if (! is_array($rango)) {
            return;
        }

        if (isset($rango['min']) && $rango['min'] !== '') {
            $query->where($columna, '>=', (float) $rango['min']);
        }

        if (isset($rango['max']) && $rango['max'] !== '') {
            $query->where($columna, '<=', (float) $rango['max']);
        }
    }

    protected function filtroRangoFecha(Builder $query, array $filtros, string $key, string $columna): void
    {
        $rango = $filtros[$key] ?? null;

        if (! is_array($rango)) {
            return;
        }

        if (isset($rango['desde']) && $rango['desde'] !== '') {
            $query->whereDate($columna, '>=', $rango['desde']);
        }

        if (isset($rango['hasta']) && $rango['hasta'] !== '') {
            $query->whereDate($columna, '<=', $rango['hasta']);
        }
    }
}
