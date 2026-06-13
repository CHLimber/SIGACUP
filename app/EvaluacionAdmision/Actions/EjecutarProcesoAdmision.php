<?php

namespace App\EvaluacionAdmision\Actions;

use App\AdministracionSistema\Models\CupoCarrera;
use App\AdministracionSistema\Models\Gestion;
use App\EvaluacionAdmision\Models\Evaluacion;
use App\RegistroInscripcion\Models\Postulacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EjecutarProcesoAdmision
{
    /**
     * Ejecuta el proceso de admisión de una gestión:
     *
     *  1. Calcula, por postulación, la nota final ponderada de cada materia y su
     *     promedio general (solo para fines de ranking).
     *  2. Determina quién aprueba: para ser admisible en la CUP TODAS las materias
     *     rendidas deben alcanzar la nota mínima. Una sola materia por debajo del
     *     mínimo reprueba al postulante, sin importar su promedio.
     *  3. Ordena a los aprobados de mayor a menor promedio (mejor nota = más
     *     prioridad).
     *  4. Recorre el ranking: cada postulante intenta entrar a su primera carrera;
     *     si ya no hay cupo, intenta la segunda. Si ninguna tiene cupo queda "no
     *     admitido por falta de cupo"; si reprobó alguna materia queda "no admitido
     *     por nota".
     *
     * Devuelve un resumen del resultado.
     *
     * @return array{procesadas:int, admitidos:int, no_admitidos:int, sin_cupo:int, reprobados:int, por_carrera:array<int,int>}
     */
    // CU16 — Ejecutar proceso de admisión (algoritmo principal: ranking por mérito, asignación de carreras según cupos)
    public function __invoke(Gestion $gestion): array
    {
        $gestion->loadMissing('parametros');

        $notaMinima = (float) ($gestion->parametro('nota_minima_aprobacion') ?? 60);

        $postulaciones = Postulacion::where('gestion_id', $gestion->id)->get();

        $evaluaciones = Evaluacion::whereIn('postulacion_id', $postulaciones->pluck('id'))
            ->get()
            ->groupBy('postulacion_id');

        // Cupo máximo configurado por carrera para esta gestión.
        $cupoMax = CupoCarrera::where('gestion_id', $gestion->id)
            ->pluck('cupo_max', 'carrera_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        // Para cada postulación: promedio (ranking) y si reprobó por nota.
        // Reprueba quien no rindió materias o tiene al menos una por debajo de la
        // nota mínima (regla clave: el mínimo es por materia, no por promedio).
        $reprobadoPorNota = [];
        foreach ($postulaciones as $postulacion) {
            $notasFinales = $this->notasFinalesPorMateria(
                $evaluaciones->get($postulacion->id, collect())
            );

            $postulacion->promedio_general = $notasFinales->isEmpty()
                ? 0.0
                : round($notasFinales->avg(), 2);

            $reprobadoPorNota[$postulacion->id] = $notasFinales->isEmpty()
                || $notasFinales->contains(fn ($n) => $n < $notaMinima);
        }

        // Ranking: mejor promedio primero; en empate, la postulación más antigua.
        $ranking = $postulaciones
            ->sort(function (Postulacion $a, Postulacion $b) {
                return [$b->promedio_general, $a->id] <=> [$a->promedio_general, $b->id];
            })
            ->values();

        $asignados = [];   // carrera_id => cantidad ya asignada
        $resumen = [
            'procesadas' => 0,
            'admitidos' => 0,
            'no_admitidos' => 0,
            'sin_cupo' => 0,
            'reprobados' => 0,
            'por_carrera' => [],
        ];

        DB::transaction(function () use ($ranking, $reprobadoPorNota, $cupoMax, &$asignados, &$resumen) {
            foreach ($ranking as $postulacion) {
                $resumen['procesadas']++;

                $carreraAsignada = null;
                $reprobado = $reprobadoPorNota[$postulacion->id];

                if (! $reprobado) {
                    foreach ([$postulacion->carrera1_id, $postulacion->carrera2_id] as $carreraId) {
                        if (! $carreraId) {
                            continue;
                        }

                        $max = $cupoMax[$carreraId] ?? 0;
                        $usados = $asignados[$carreraId] ?? 0;

                        if ($usados < $max) {
                            $carreraAsignada = $carreraId;
                            $asignados[$carreraId] = $usados + 1;
                            break;
                        }
                    }
                }

                if ($carreraAsignada !== null) {
                    $postulacion->estado_admision = Postulacion::ADMISION_ADMITIDO;
                    $postulacion->carrera_asignada_id = $carreraAsignada;
                    $resumen['admitidos']++;
                    $resumen['por_carrera'][$carreraAsignada] =
                        ($resumen['por_carrera'][$carreraAsignada] ?? 0) + 1;
                } else {
                    $postulacion->estado_admision = Postulacion::ADMISION_NO_ADMITIDO;
                    $postulacion->carrera_asignada_id = null;
                    $resumen['no_admitidos']++;
                    $reprobado ? $resumen['reprobados']++ : $resumen['sin_cupo']++;
                }

                $postulacion->save();
            }
        });

        return $resumen;
    }

    /**
     * Nota final ponderada de cada materia rendida por la postulación. La nota
     * final de una materia es el promedio ponderado de sus exámenes
     * (sum(nota·peso) / sum(peso)), redondeada a 2 decimales para que el corte de
     * la nota mínima coincida con lo que se muestra en la vista de ponderadas.
     *
     * @param  Collection<int,Evaluacion>  $evaluaciones
     * @return Collection<string,float> [codigo_materia => nota_final]
     */
    // CU16 — Ejecutar proceso de admisión | CU15 — Calcular resultados del CUP (nota final ponderada por materia)
    private function notasFinalesPorMateria($evaluaciones): Collection
    {
        return $evaluaciones
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
    }
}
