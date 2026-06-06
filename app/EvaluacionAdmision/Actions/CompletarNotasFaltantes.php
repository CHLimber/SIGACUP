<?php

namespace App\EvaluacionAdmision\Actions;

use App\AdministracionSistema\Models\Gestion;
use App\EvaluacionAdmision\Models\Evaluacion;
use App\OrganizacionAcademica\Models\Grupo;
use Illuminate\Support\Facades\DB;

class CompletarNotasFaltantes
{
    /**
     * Para cada estudiante inscrito en cada grupo de la gestión,
     * crea con nota_cruda = 0 las evaluaciones que aún no existan.
     * Devuelve el total de evaluaciones creadas.
     */
    public function __invoke(Gestion $gestion): int
    {
        $gestion->loadMissing('parametros');

        $pesos = [
            1 => (float) ($gestion->parametro('peso_examen_1') ?? 30),
            2 => (float) ($gestion->parametro('peso_examen_2') ?? 30),
            3 => (float) ($gestion->parametro('peso_examen_3') ?? 40),
        ];

        $grupos = Grupo::where('gestion_id', $gestion->id)
            ->with('postulaciones')
            ->get();

        $creadas = 0;

        DB::transaction(function () use ($grupos, $pesos, &$creadas) {
            foreach ($grupos as $grupo) {
                foreach ($grupo->postulaciones as $postulacion) {
                    foreach ([1, 2, 3] as $num) {
                        $evaluacion = Evaluacion::firstOrCreate(
                            [
                                'postulacion_id' => $postulacion->id,
                                'codigo_materia' => $grupo->codigo_materia,
                                'numero_examen' => $num,
                            ],
                            [
                                'nota_cruda' => 0,
                                'peso' => $pesos[$num],
                            ]
                        );

                        if ($evaluacion->wasRecentlyCreated) {
                            $creadas++;
                        }
                    }
                }
            }
        });

        return $creadas;
    }
}
