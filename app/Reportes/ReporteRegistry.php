<?php

namespace App\Reportes;

use App\Reportes\Reports\AbstractReport;
use App\Reportes\Reports\CalificacionesReport;
use App\Reportes\Reports\DocentesReport;
use App\Reportes\Reports\EstudiantesReport;
use App\Reportes\Reports\PagosReport;
use App\Reportes\Reports\PostulacionesReport;

/**
 * Catálogo de reportes disponibles. Para agregar un reporte nuevo basta con
 * crear su clase y registrarla en $reportes.
 */
class ReporteRegistry
{
    /** @var array<int, class-string<AbstractReport>> */
    private array $reportes = [
        PostulacionesReport::class,
        EstudiantesReport::class,
        DocentesReport::class,
        PagosReport::class,
        CalificacionesReport::class,
    ];

    /** @return array<string, AbstractReport> */
    public function todos(): array
    {
        $out = [];

        foreach ($this->reportes as $clase) {
            $reporte = new $clase;
            $out[$reporte->key()] = $reporte;
        }

        return $out;
    }

    public function obtener(string $key): ?AbstractReport
    {
        return $this->todos()[$key] ?? null;
    }

    /** Metadata de todos los reportes para alimentar la UI. */
    public function meta(): array
    {
        return array_values(array_map(fn (AbstractReport $r) => $r->meta(), $this->todos()));
    }
}
