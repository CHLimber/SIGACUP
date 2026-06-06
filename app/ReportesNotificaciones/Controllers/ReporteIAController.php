<?php

namespace App\ReportesNotificaciones\Controllers;

use App\Http\Controllers\Controller;
use App\ReportesNotificaciones\IA\AsistenteReportes;
use App\ReportesNotificaciones\ReporteRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Asistente de reportes por voz/texto. Recibe lenguaje natural, la IA lo
 * traduce a una consulta estructurada (ver AsistenteReportes) y se ejecuta el
 * reporte correspondiente reutilizando el motor de AbstractReport.
 */
class ReporteIAController extends Controller
{
    public function __construct(private readonly ReporteRegistry $registry) {}

    public function index(): Response
    {
        return Inertia::render('ReportesNotificaciones/Reportes/Asistente', [
            'configurado' => ! empty(config('services.anthropic.key')),
            'ejemplos' => [
                'Postulantes admitidos en Ingeniería en Sistemas',
                'Grupos con mayor cantidad de aprobados',
                'Qué docente tuvo mayor porcentaje de aprobados',
                'Lista de docentes con maestría',
                'Pagos completados por gestión',
                'Postulantes mujeres con promedio mayor a 80',
            ],
        ]);
    }

    public function consultar(Request $request, AsistenteReportes $asistente): RedirectResponse
    {
        $data = $request->validate([
            'consulta' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $interpretacion = $asistente->interpretar($data['consulta']);
        } catch (Throwable $e) {
            return back()->with('flash', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        $reporte = $this->registry->obtener($interpretacion['reporte']);

        $datos = $reporte->run([
            'filtros' => $interpretacion['filtros'],
            'sort' => $interpretacion['sort'],
            'dir' => $interpretacion['dir'],
            'dimension' => $interpretacion['dimension'],
        ]);

        $columnas = $this->columnasSeleccionadas($reporte, $interpretacion['columnas']);

        return back()->with('asistente_resultado', [
            'consulta' => $data['consulta'],
            'explicacion' => $interpretacion['explicacion'],
            'reporte' => ['key' => $reporte->key(), 'label' => $reporte->label()],
            'filtros' => $interpretacion['filtros'],
            'columnas' => $columnas,
            'rows' => $datos['rows'],
            'total' => $datos['total'],
        ]);
    }

    /**
     * Resuelve qué columnas mostrar respetando el orden del reporte.
     *
     * @return array<int, array{key:string, label:string, type:string}>
     */
    private function columnasSeleccionadas($reporte, array $seleccion): array
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
