<?php

namespace App\Admision\Controllers;

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\Admision\Actions\EjecutarProcesoAdmision;
use App\GestionEstudiantes\Models\Postulacion;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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

        return Inertia::render('Admision/ProcesoAdmision/Index', [
            'gestiones' => $gestiones,
        ]);
    }

    public function show(Gestion $gestion): Response
    {
        $gestion->loadMissing('parametros');

        $carreras = Carrera::orderBy('nombre')->get();
        $cupos = $gestion->cupos()->pluck('cupo_max', 'carrera_id');

        $postulaciones = Postulacion::where('gestion_id', $gestion->id)
            ->with(['candidatoEstudiante.persona', 'carrera1', 'carrera2', 'carreraAsignada'])
            ->get()
            ->sort(function (Postulacion $a, Postulacion $b) {
                return [$b->promedio_general, $a->id] <=> [$a->promedio_general, $b->id];
            })
            ->values();

        $ejecutado = $postulaciones->contains(
            fn (Postulacion $p) => $p->estado_admision !== Postulacion::ADMISION_PENDIENTE
        );

        $cuposCarrera = $carreras->map(function (Carrera $c) use ($cupos, $postulaciones) {
            $asignados = $postulaciones
                ->where('carrera_asignada_id', $c->id)
                ->count();

            return [
                'carrera_id' => $c->id,
                'nombre' => $c->nombre,
                'cupo_max' => (int) ($cupos[$c->id] ?? 0),
                'asignados' => $asignados,
            ];
        });

        $ranking = $postulaciones->map(function (Postulacion $p, int $i) {
            return [
                'posicion' => $i + 1,
                'postulacion_id' => $p->id,
                'estudiante' => $p->candidatoEstudiante?->nombre_completo ?? '—',
                'promedio_general' => $p->promedio_general !== null ? (float) $p->promedio_general : null,
                'carrera1' => $p->carrera1?->nombre,
                'carrera2' => $p->carrera2?->nombre,
                'carrera_asignada' => $p->carreraAsignada?->nombre,
                'estado_admision' => $p->estado_admision,
            ];
        });

        return Inertia::render('Admision/ProcesoAdmision/Show', [
            'gestion' => [
                'id' => $gestion->id,
                'label' => $gestion->label,
                'estado' => $gestion->estado,
                'nota_minima' => (float) ($gestion->parametro('nota_minima_aprobacion') ?? 60),
            ],
            'cupos' => $cuposCarrera,
            'ranking' => $ranking,
            'ejecutado' => $ejecutado,
            'cuposTotales' => $cuposCarrera->sum('cupo_max'),
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
