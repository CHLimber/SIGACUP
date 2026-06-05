<?php

namespace App\EvaluacionAdmision\Actions;

use App\AdministracionSistema\Models\Materia;
use App\EvaluacionAdmision\Models\Evaluacion;
use App\RegistroInscripcion\Models\Postulacion;

/**
 * Calcula la nota final ponderada por materia de una postulación, el promedio
 * general y el estado (aprobado / reprobado por promedio / reprobado por una
 * materia), aplicando la regla de admisión del CUP.
 *
 * Centraliza la lógica que también usa CalificacionesController::ponderadas para
 * que la consulta pública por CI y el panel interno coincidan exactamente.
 */
class CalcularNotasPostulacion
{
    /**
     * @return array{
     *   materias: array<int, array{codigo:string, nombre:string, nota:float|null, aprobada:bool}>,
     *   promedio: float|null,
     *   estado: 'aprobado'|'reprobado'|'reprobado_materia'|null,
     *   nota_minima: float
     * }
     */
    public function __invoke(Postulacion $postulacion): array
    {
        $postulacion->loadMissing(['gestion.parametros', 'evaluaciones']);

        $notaMinima = (float) ($postulacion->gestion?->parametro('nota_minima_aprobacion') ?? 60);

        // Nombre legible de cada materia por código.
        $nombres = Materia::pluck('nombre', 'codigo');

        $ponderadas = $postulacion->evaluaciones
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

        $materias = $ponderadas
            ->map(fn ($nota, $codigo) => [
                'codigo' => $codigo,
                'nombre' => $nombres[$codigo] ?? $codigo,
                'nota' => $nota,
                'aprobada' => $nota >= $notaMinima,
            ])
            ->sortBy('nombre')
            ->values()
            ->all();

        $promedio = $ponderadas->isEmpty() ? null : round($ponderadas->avg(), 2);

        $estado = match (true) {
            $promedio === null => null,
            $promedio < $notaMinima => 'reprobado',
            $ponderadas->contains(fn ($n) => $n < $notaMinima) => 'reprobado_materia',
            default => 'aprobado',
        };

        return [
            'materias' => $materias,
            'promedio' => $promedio,
            'estado' => $estado,
            'nota_minima' => $notaMinima,
        ];
    }
}
