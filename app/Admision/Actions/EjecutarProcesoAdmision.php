<?php

namespace App\Admision\Actions;

use App\AdministracionSistema\Models\CupoCarrera;
use App\AdministracionSistema\Models\Gestion;
use App\Calificaciones\Models\Evaluacion;
use App\GestionEstudiantes\Models\Postulacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EjecutarProcesoAdmision
{
    /**
     * Ejecuta el proceso de admisión de una gestión:
     *
     *  1. Calcula el promedio general de cada postulación (media de las notas
     *     finales ponderadas de todas las materias rendidas).
     *  2. Ordena las postulaciones de mayor a menor promedio (mejor nota = más
     *     prioridad).
     *  3. Recorre el ranking: cada postulante intenta entrar a su primera
     *     carrera; si ya no hay cupo, intenta la segunda. Si ninguna tiene cupo
     *     —o su promedio no alcanza la nota mínima— queda "no admitido".
     *
     * Devuelve un resumen del resultado.
     *
     * @return array{procesadas:int, admitidos:int, no_admitidos:int, sin_cupo:int, reprobados:int, por_carrera:array<int,int>}
     */
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

        // Calcula el promedio general de cada postulación.
        foreach ($postulaciones as $postulacion) {
            $postulacion->promedio_general = $this->promedioGeneral(
                $evaluaciones->get($postulacion->id, collect())
            );
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

        DB::transaction(function () use ($ranking, $notaMinima, $cupoMax, &$asignados, &$resumen) {
            foreach ($ranking as $postulacion) {
                $resumen['procesadas']++;

                $carreraAsignada = null;
                $reprobado = $postulacion->promedio_general < $notaMinima;

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
     * Promedio general de una postulación: media de las notas finales de cada
     * materia. La nota final de una materia es el promedio ponderado de sus
     * exámenes (sum(nota·peso) / sum(peso)).
     *
     * @param  Collection<int,Evaluacion>  $evaluaciones
     */
    private function promedioGeneral($evaluaciones): float
    {
        if ($evaluaciones->isEmpty()) {
            return 0.0;
        }

        $notasFinales = $evaluaciones
            ->groupBy('codigo_materia')
            ->map(function ($notas) {
                $sumaPesos = (float) $notas->sum('peso');

                if ($sumaPesos <= 0) {
                    return null;
                }

                $ponderada = $notas->sum(fn (Evaluacion $e) => (float) $e->nota_cruda * (float) $e->peso);

                return $ponderada / $sumaPesos;
            })
            ->filter(fn ($n) => $n !== null);

        if ($notasFinales->isEmpty()) {
            return 0.0;
        }

        return round($notasFinales->avg(), 2);
    }
}
