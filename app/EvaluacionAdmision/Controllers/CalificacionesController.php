<?php

namespace App\EvaluacionAdmision\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\EvaluacionAdmision\Models\Evaluacion;
use App\Http\Controllers\Controller;
use App\OrganizacionAcademica\Models\Grupo;
use App\RegistroInscripcion\Models\Postulacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CalificacionesController extends Controller
{
    public function index(): Response
    {
        $grupos = Grupo::with(['gestion', 'materia', 'horario', 'docentes.user'])
            ->withCount('postulaciones')
            ->get()
            ->sortBy([
                fn ($a, $b) => $b->gestion->anio <=> $a->gestion->anio,
                fn ($a, $b) => $b->gestion->semestre <=> $a->gestion->semestre,
                fn ($a, $b) => $a->materia->nombre <=> $b->materia->nombre,
                fn ($a, $b) => $a->nombre <=> $b->nombre,
            ])
            ->values();

        $porGestion = $grupos
            ->groupBy('gestion_id')
            ->map(function ($items) {
                $g = $items->first()->gestion;

                return [
                    'id' => $g->id,
                    'label' => $g->label,
                    'anio' => $g->anio,
                    'semestre' => $g->semestre,
                    'grupos' => $items->map(fn ($grupo) => [
                        'id' => $grupo->id,
                        'nombre' => $grupo->nombre,
                        'materia' => $grupo->materia->nombre,
                        'horario' => $this->formatHorario($grupo->horario),
                        'postulaciones_count' => $grupo->postulaciones_count,
                        'docentes' => $grupo->docentes->map(fn ($d) => $d->user->name)->join(', '),
                    ])->values(),
                ];
            })
            ->values();

        return Inertia::render('EvaluacionAdmision/Calificaciones/Index', [
            'gestiones' => $porGestion,
        ]);
    }

    /**
     * Vista consolidada: una fila por estudiante y una columna por materia,
     * mostrando la nota final ponderada de cada materia. Filtrable por gestión.
     */
    public function ponderadas(Request $request): Response
    {
        $gestiones = Gestion::orderByDesc('anio')->orderByDesc('semestre')->get();

        if ($gestiones->isEmpty()) {
            return Inertia::render('EvaluacionAdmision/Calificaciones/Ponderadas', [
                'gestiones' => [],
                'gestionSeleccionada' => null,
                'materias' => [],
                'estudiantes' => [],
                'notaMinima' => 60,
            ]);
        }

        $gestion = $gestiones->firstWhere('id', $request->integer('gestion')) ?? $gestiones->first();
        $gestion->loadMissing('parametros');

        $notaMinima = (float) ($gestion->parametro('nota_minima_aprobacion') ?? 60);

        // Materias (columnas) de la gestión, tomadas de los grupos existentes.
        $materias = Grupo::where('gestion_id', $gestion->id)
            ->with('materia')
            ->get()
            ->map(fn ($g) => [
                'codigo' => $g->codigo_materia,
                'nombre' => $g->materia?->nombre ?? $g->codigo_materia,
            ])
            ->unique('codigo')
            ->sortBy('nombre')
            ->values();

        $postulaciones = Postulacion::where('gestion_id', $gestion->id)
            ->with(['candidatoEstudiante.persona', 'grupos', 'evaluaciones'])
            ->get();

        $estudiantes = $postulaciones
            ->sortBy(fn ($p) => $p->candidatoEstudiante->nombre_completo)
            ->values()
            ->map(function ($p) use ($notaMinima) {
                $ponderadas = $p->evaluaciones
                    ->groupBy('codigo_materia')
                    ->map(function ($notas) {
                        $sumaPesos = (float) $notas->sum('peso');

                        if ($sumaPesos <= 0) {
                            return null;
                        }

                        $ponderada = $notas->sum(fn (Evaluacion $e) => (float) $e->nota_cruda * (float) $e->peso);

                        return round($ponderada / $sumaPesos, 2);
                    })
                    ->filter(fn ($n) => $n !== null);

                $promedio = $ponderadas->isEmpty() ? null : round($ponderadas->avg(), 2);

                // Regla de admisión: una sola materia por debajo de la mínima
                // reprueba al estudiante aunque su promedio alcance.
                $estado = match (true) {
                    $promedio === null => null,
                    $promedio < $notaMinima => 'reprobado',
                    $ponderadas->contains(fn ($n) => $n < $notaMinima) => 'reprobado_materia',
                    default => 'aprobado',
                };

                return [
                    'postulacion_id' => $p->id,
                    'nombre' => $p->candidatoEstudiante->nombre_completo,
                    'grupo' => $p->grupos->pluck('nombre')->unique()->sort()->implode(', ') ?: '—',
                    'ponderadas' => $ponderadas,
                    'promedio' => $promedio,
                    'estado' => $estado,
                ];
            });

        return Inertia::render('EvaluacionAdmision/Calificaciones/Ponderadas', [
            'gestiones' => $gestiones->map(fn ($g) => [
                'id' => $g->id,
                'label' => $g->label,
                'anio' => $g->anio,
                'semestre' => $g->semestre,
            ])->values(),
            'gestionSeleccionada' => $gestion->id,
            'materias' => $materias,
            'estudiantes' => $estudiantes,
            'notaMinima' => $notaMinima,
        ]);
    }

    public function calificar(Grupo $grupo): Response
    {
        $grupo->load(['gestion.parametros', 'materia', 'horario']);

        $pesos = [
            'examen_1' => (float) ($grupo->gestion->parametro('peso_examen_1') ?? 30),
            'examen_2' => (float) ($grupo->gestion->parametro('peso_examen_2') ?? 30),
            'examen_3' => (float) ($grupo->gestion->parametro('peso_examen_3') ?? 40),
            'nota_minima' => (float) ($grupo->gestion->parametro('nota_minima_aprobacion') ?? 60),
        ];

        $postulaciones = $grupo->postulaciones()
            ->with('candidatoEstudiante.persona')
            ->get();

        $evaluaciones = Evaluacion::where('codigo_materia', $grupo->codigo_materia)
            ->whereIn('postulacion_id', $postulaciones->pluck('id'))
            ->get()
            ->groupBy('postulacion_id');

        $estudiantes = $postulaciones
            ->sortBy(fn ($p) => $p->candidatoEstudiante->nombre_completo)
            ->values()
            ->map(function ($postulacion) use ($evaluaciones) {
                $notas = $evaluaciones->get($postulacion->id, collect());

                return [
                    'postulacion_id' => $postulacion->id,
                    'nombre' => $postulacion->candidatoEstudiante->nombre_completo,
                    'examen_1' => optional($notas->firstWhere('numero_examen', 1))->nota_cruda,
                    'examen_2' => optional($notas->firstWhere('numero_examen', 2))->nota_cruda,
                    'examen_3' => optional($notas->firstWhere('numero_examen', 3))->nota_cruda,
                ];
            });

        return Inertia::render('EvaluacionAdmision/Calificaciones/Calificar', [
            'grupo' => [
                'id' => $grupo->id,
                'nombre' => $grupo->nombre,
                'materia' => $grupo->materia->nombre,
                'codigo_materia' => $grupo->codigo_materia,
                'gestion' => [
                    'id' => $grupo->gestion->id,
                    'label' => $grupo->gestion->label,
                ],
                'horario' => $this->formatHorario($grupo->horario),
            ],
            'pesos' => $pesos,
            'estudiantes' => $estudiantes,
        ]);
    }

    public function guardar(Request $request, Grupo $grupo): RedirectResponse
    {
        $request->validate([
            'calificaciones' => ['required', 'array'],
            'calificaciones.*.postulacion_id' => ['required', 'integer', 'exists:postulacion,id'],
            'calificaciones.*.examen_1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'calificaciones.*.examen_2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'calificaciones.*.examen_3' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $grupo->load('gestion.parametros');

        $pesos = [
            1 => (float) ($grupo->gestion->parametro('peso_examen_1') ?? 30),
            2 => (float) ($grupo->gestion->parametro('peso_examen_2') ?? 30),
            3 => (float) ($grupo->gestion->parametro('peso_examen_3') ?? 40),
        ];

        DB::transaction(function () use ($request, $grupo, $pesos) {
            foreach ($request->calificaciones as $cal) {
                foreach ([1, 2, 3] as $num) {
                    $nota = isset($cal["examen_{$num}"]) && $cal["examen_{$num}"] !== '' && $cal["examen_{$num}"] !== null
                        ? (float) $cal["examen_{$num}"]
                        : null;

                    if ($nota !== null) {
                        Evaluacion::updateOrCreate(
                            [
                                'postulacion_id' => $cal['postulacion_id'],
                                'codigo_materia' => $grupo->codigo_materia,
                                'numero_examen' => $num,
                            ],
                            [
                                'nota_cruda' => $nota,
                                'peso' => $pesos[$num],
                            ]
                        );
                    } else {
                        Evaluacion::where([
                            'postulacion_id' => $cal['postulacion_id'],
                            'codigo_materia' => $grupo->codigo_materia,
                            'numero_examen' => $num,
                        ])->delete();
                    }
                }
            }
        });

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Calificaciones guardadas correctamente.',
        ]);
    }

    private function formatHorario(mixed $horario): string
    {
        if (! $horario) {
            return '—';
        }

        $dia = $horario->aplica_todos_dias ? 'Lun–Vie' : ($horario->dia ?? '');

        return "{$dia} {$horario->hora_inicio}–{$horario->hora_fin}";
    }
}
