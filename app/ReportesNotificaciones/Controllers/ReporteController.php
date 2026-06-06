<?php

namespace App\ReportesNotificaciones\Controllers;

use App\Http\Controllers\Controller;
use App\ReportesNotificaciones\Actions\GenerarResumen;
use App\ReportesNotificaciones\ReporteRegistry;
use App\ReportesNotificaciones\Reports\AbstractReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteRegistry $registry) {}

    public function resumen(Request $request, GenerarResumen $generar): Response
    {
        $gestionId = $request->integer('gestion_id') ?: null;

        return Inertia::render('ReportesNotificaciones/Reportes/Resumen', [
            'resumen' => $generar($gestionId),
            'gestiones' => DB::table('gestion')
                ->orderByDesc('anio')->orderByDesc('semestre')
                ->get()
                ->map(fn ($g) => ['value' => (string) $g->id, 'label' => $g->anio.'-'.$g->semestre])
                ->all(),
            'gestionId' => $gestionId ? (string) $gestionId : '',
        ]);
    }

    public function index(Request $request): Response
    {
        $reporteKey = $request->string('reporte')->toString();
        $reporte = $reporteKey ? $this->registry->obtener($reporteKey) : null;

        $params = $this->params($request);

        $resultado = null;
        if ($reporte) {
            $datos = $reporte->run($params);
            $resultado = [
                'reporte' => $reporte->key(),
                'rows' => $datos['rows'],
                'total' => $datos['total'],
                'resumen' => $datos['resumen'],
                'columnas' => $this->columnasSeleccionadas($reporte, $params['columnas']),
                'sort' => $params['sort'],
                'dir' => $params['dir'],
                'dimension' => $datos['resumen']['dimension'],
            ];
        }

        return Inertia::render('ReportesNotificaciones/Reportes/Index', [
            'reportes' => $this->registry->meta(),
            'consulta' => [
                'reporte' => $reporte?->key(),
                'filtros' => $params['filtros'],
                'columnas' => $params['columnas'],
                'sort' => $params['sort'],
                'dir' => $params['dir'],
                'dimension' => $params['dimension'],
            ],
            'resultado' => $resultado,
        ]);
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $reporte = $this->registry->obtener($request->string('reporte')->toString());
        abort_unless($reporte, 404);

        $params = $this->params($request);
        $datos = $reporte->run($params);
        $columnas = $this->columnasSeleccionadas($reporte, $params['columnas']);

        $nombreArchivo = 'reporte_'.$reporte->key().'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($datos, $columnas) {
            $salida = fopen('php://output', 'w');

            // BOM para que Excel reconozca UTF-8 con acentos.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, array_column($columnas, 'label'));

            foreach ($datos['rows'] as $row) {
                $linea = [];
                foreach ($columnas as $col) {
                    $valor = $row[$col['key']] ?? '';
                    if (is_bool($valor)) {
                        $valor = $valor ? 'Sí' : 'No';
                    }
                    $linea[] = $valor;
                }
                fputcsv($salida, $linea);
            }

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $reporte = $this->registry->obtener($request->string('reporte')->toString());
        abort_unless($reporte, 404);

        $params = $this->params($request);
        $datos = $reporte->run($params);
        $columnas = $this->columnasSeleccionadas($reporte, $params['columnas']);

        $pdf = Pdf::loadView('reportes.pdf', [
            'titulo' => $reporte->label(),
            'descripcion' => $reporte->descripcion(),
            'columnas' => $columnas,
            'rows' => $datos['rows'],
            'total' => $datos['total'],
            'filtros' => $this->filtrosLegibles($reporte, $params['filtros']),
            'fecha' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', count($columnas) > 6 ? 'landscape' : 'portrait');

        return $pdf->download('reporte_'.$reporte->key().'_'.now()->format('Ymd_His').'.pdf');
    }

    public function resumenPdf(Request $request, GenerarResumen $generar)
    {
        $gestionId = $request->integer('gestion_id') ?: null;

        $gestionLabel = 'Todas las gestiones';
        if ($gestionId) {
            $g = DB::table('gestion')->where('id', $gestionId)->first();
            $gestionLabel = $g ? 'Gestión '.$g->anio.'-'.$g->semestre : $gestionLabel;
        }

        $pdf = Pdf::loadView('reportes.resumen-pdf', [
            'resumen' => $generar($gestionId),
            'gestionLabel' => $gestionLabel,
            'fecha' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('resumen_estadistico_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * Traduce los filtros aplicados a pares {label, valor} legibles, resolviendo
     * las opciones de los selects contra la metadata del reporte.
     *
     * @return array<int, array{label:string, valor:string}>
     */
    private function filtrosLegibles(AbstractReport $reporte, array $filtros): array
    {
        $out = [];

        foreach ($reporte->filters() as $def) {
            $valor = $filtros[$def['key']] ?? null;

            if ($valor === null || $valor === '' || $valor === 'todos') {
                continue;
            }

            if (is_array($valor)) {
                $partes = array_filter($valor, fn ($v) => $v !== null && $v !== '');
                if (empty($partes)) {
                    continue;
                }
                $out[] = ['label' => $def['label'], 'valor' => implode(' — ', $partes)];

                continue;
            }

            $legible = (string) $valor;
            foreach ($def['options'] ?? [] as $op) {
                if ((string) $op['value'] === (string) $valor) {
                    $legible = $op['label'];
                    break;
                }
            }

            $out[] = ['label' => $def['label'], 'valor' => $legible];
        }

        return $out;
    }

    /** Extrae y normaliza los parámetros de la consulta. */
    private function params(Request $request): array
    {
        return [
            'filtros' => (array) $request->input('filtros', []),
            'columnas' => array_filter((array) $request->input('columnas', [])),
            'sort' => $request->string('sort')->toString() ?: null,
            'dir' => $request->string('dir')->toString() ?: 'asc',
            'dimension' => $request->string('dimension')->toString() ?: null,
        ];
    }

    /**
     * Resuelve qué columnas mostrar/exportar respetando el orden del reporte.
     *
     * @return array<int, array{key:string, label:string, type:string}>
     */
    private function columnasSeleccionadas(AbstractReport $reporte, array $seleccion): array
    {
        $todas = $reporte->columns();
        $seleccion = empty($seleccion) ? array_keys($todas) : $seleccion;

        $out = [];
        foreach ($todas as $key => $def) {
            if (in_array($key, $seleccion, true)) {
                $out[] = ['key' => $key, 'label' => $def['label'], 'type' => $def['type']];
            }
        }

        return $out;
    }
}
