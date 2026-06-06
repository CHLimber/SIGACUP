<?php

namespace App\EvaluacionAdmision\Controllers;

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\EvaluacionAdmision\Actions\EjecutarProcesoAdmision;
use App\Http\Controllers\Controller;
use App\RegistroInscripcion\Models\Postulacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcesoAdmisionController extends Controller
{
    public function index(): Response
    {
        $gestiones = Gestion::orderByDesc('anio')
            ->orderByDesc('semestre')
            ->withCount('postulaciones')
            ->get()
            ->map(fn (Gestion $g) => [
                'id' => $g->id,
                'label' => $g->label,
                'anio' => $g->anio,
                'semestre' => $g->semestre,
                'estado' => $g->estado,
                'postulaciones_count' => $g->postulaciones_count,
            ]);

        return Inertia::render('EvaluacionAdmision/Admision/ProcesoAdmision/Index', [
            'gestiones' => $gestiones,
        ]);
    }

    public function show(Request $request, Gestion $gestion): Response
    {
        $gestion->loadMissing('parametros');

        $carreras = Carrera::orderBy('nombre')->get(['id', 'nombre']);
        $cupos = $gestion->cupos()->pluck('cupo_max', 'carrera_id');

        // Cupos con asignados calculados sobre el universo completo de la gestión
        $asignadosPorCarrera = Postulacion::where('gestion_id', $gestion->id)
            ->whereNotNull('carrera_asignada_id')
            ->selectRaw('carrera_asignada_id, COUNT(*) as cnt')
            ->groupBy('carrera_asignada_id')
            ->pluck('cnt', 'carrera_asignada_id');

        $cuposCarrera = $carreras->map(fn (Carrera $c) => [
            'carrera_id' => $c->id,
            'nombre' => $c->nombre,
            'cupo_max' => (int) ($cupos[$c->id] ?? 0),
            'asignados' => (int) ($asignadosPorCarrera[$c->id] ?? 0),
        ]);

        $ejecutado = Postulacion::where('gestion_id', $gestion->id)
            ->where('estado_admision', '!=', Postulacion::ADMISION_PENDIENTE)
            ->exists();

        // Query base filtrable (carrera + búsqueda, sin estado para que el resumen sea exacto)
        $baseQuery = Postulacion::where('gestion_id', $gestion->id)
            ->when($request->filled('carrera_id'), fn ($q) => $q->where('carrera1_id', $request->carrera_id)
            )
            ->when($request->filled('busqueda'), function ($q) use ($request) {
                $term = '%'.$request->busqueda.'%';
                $q->whereHas('candidatoEstudiante.persona', fn ($p) => $p->where('apellido', 'ILIKE', $term)
                    ->orWhere('nombres', 'ILIKE', $term)
                );
            });

        $resumen = [
            'total' => (clone $baseQuery)->count(),
            'admitidos' => (clone $baseQuery)->where('estado_admision', Postulacion::ADMISION_ADMITIDO)->count(),
            'no_admitidos' => (clone $baseQuery)->where('estado_admision', Postulacion::ADMISION_NO_ADMITIDO)->count(),
            'pendientes' => (clone $baseQuery)->where('estado_admision', Postulacion::ADMISION_PENDIENTE)->count(),
        ];

        $paginador = $baseQuery
            ->when($request->filled('estado'), fn ($q) => $q->where('estado_admision', $request->estado))
            ->with(['candidatoEstudiante.persona', 'carrera1', 'carrera2', 'carreraAsignada'])
            ->orderByRaw('promedio_general DESC NULLS LAST')
            ->orderBy('id')
            ->paginate(30);

        $inicio = $paginador->firstItem() ?? 1;
        $idx = 0;

        $ranking = $paginador->through(function (Postulacion $p) use ($inicio, &$idx) {
            return [
                'posicion' => $inicio + $idx++,
                'postulacion_id' => $p->id,
                'estudiante' => $p->candidatoEstudiante?->nombre_completo ?? '—',
                'promedio_general' => $p->promedio_general !== null ? (float) $p->promedio_general : null,
                'carrera1' => $p->carrera1?->nombre,
                'carrera2' => $p->carrera2?->nombre,
                'carrera_asignada' => $p->carreraAsignada?->nombre,
                'estado_admision' => $p->estado_admision,
            ];
        });

        return Inertia::render('EvaluacionAdmision/Admision/ProcesoAdmision/Show', [
            'gestion' => [
                'id' => $gestion->id,
                'label' => $gestion->label,
                'estado' => $gestion->estado,
                'nota_minima' => (float) ($gestion->parametro('nota_minima_aprobacion') ?? 60),
            ],
            'cupos' => $cuposCarrera,
            'ranking' => $ranking,
            'resumen' => $resumen,
            'ejecutado' => $ejecutado,
            'cuposTotales' => $cuposCarrera->sum('cupo_max'),
            'carreras' => $carreras,
            'filtros' => [
                'estado' => $request->estado ?? '',
                'carrera_id' => $request->carrera_id ?? '',
                'busqueda' => $request->busqueda ?? '',
            ],
        ]);
    }

    public function ejecutar(Gestion $gestion, EjecutarProcesoAdmision $ejecutar): RedirectResponse
    {
        if ($gestion->cupos()->where('cupo_max', '>', 0)->doesntExist()) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'No hay cupos configurados para ninguna carrera de esta gestión. Configúralos en la edición de la gestión antes de ejecutar la admisión.',
            ]);
        }

        $resumen = $ejecutar($gestion);

        $mensaje = "Proceso de admisión ejecutado: {$resumen['admitidos']} admitido(s) y "
            ."{$resumen['no_admitidos']} no admitido(s) de {$resumen['procesadas']} postulación(es)";

        if ($resumen['reprobados'] > 0) {
            $mensaje .= " — {$resumen['reprobados']} por debajo de la nota mínima";
        }

        if ($resumen['sin_cupo'] > 0) {
            $mensaje .= " — {$resumen['sin_cupo']} sin cupo disponible";
        }

        $mensaje .= '.';

        return redirect()->route('proceso-admision.show', $gestion)->with('flash', [
            'type' => 'success',
            'message' => $mensaje,
        ]);
    }
}
