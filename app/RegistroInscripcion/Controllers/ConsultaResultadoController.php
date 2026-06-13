<?php

namespace App\RegistroInscripcion\Controllers;

use App\EvaluacionAdmision\Actions\CalcularNotasPostulacion;
use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\RegistroInscripcion\Models\Postulacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Consulta pública de resultados del CUP por CI (desde la página de inicio).
 * Solo expone el resultado académico/de admisión — nunca documentos ni datos
 * sensibles. Si el CI no existe o aún no hay resultados, responde con un mensaje
 * genérico sin revelar información.
 */
class ConsultaResultadoController extends Controller
{
    // CU15 — Calcular resultados del CUP (consulta pública por CI desde la página de inicio)
    public function consultar(Request $request, CalcularNotasPostulacion $calcular): RedirectResponse
    {
        $data = $request->validate([
            'ci' => ['required', 'string', 'max:20'],
        ]);

        $ci = trim($data['ci']);

        $persona = Persona::where('ci', $ci)->first();

        $postulacion = $persona
            ? Postulacion::whereHas('candidatoEstudiante', fn ($q) => $q->where('persona_id', $persona->id))
                ->with([
                    'gestion', 'carreraAsignada', 'carrera1', 'carrera2',
                    'evaluaciones', 'gestion.parametros',
                    'grupos.horario', 'grupos.materia',
                ])
                ->orderByDesc('gestion_id')
                ->first()
            : null;

        if (! $postulacion) {
            return back()->with('consulta_resultado', [
                'encontrado' => false,
                'ci' => $ci,
                'mensaje' => 'No encontramos resultados para ese CI. Verificá el número o consultá más tarde si los resultados aún no fueron publicados.',
            ])->withFragment('notas');
        }

        $notas = $calcular($postulacion);

        return back()->with('consulta_resultado', [
            'encontrado' => true,
            'ci' => $ci,
            'nombre' => $postulacion->candidatoEstudiante->nombre_completo,
            'gestion' => $postulacion->gestion?->label,
            'carrera1' => $postulacion->carrera1?->nombre,
            'carrera2' => $postulacion->carrera2?->nombre,
            'carrera_asignada' => $postulacion->carreraAsignada?->nombre,
            'estado_admision' => $postulacion->estado_admision,
            'materias' => $notas['materias'],
            'promedio' => $notas['promedio'],
            'estado_academico' => $notas['estado'],
            'nota_minima' => $notas['nota_minima'],
            'boleta' => [
                'unidad_educativa' => $postulacion->unidad_educativa,
                'tipo_colegio' => $postulacion->tipo_colegio,
                'anio_egreso' => $postulacion->anio_egreso,
                'grupos' => $postulacion->grupos->map(fn ($grupo) => [
                    'codigo_materia' => $grupo->codigo_materia,
                    'nombre_materia' => $grupo->materia?->nombre,
                    'grupo' => $grupo->nombre,
                    'aplica_todos_dias' => (bool) ($grupo->horario?->aplica_todos_dias ?? false),
                    'dia' => $grupo->horario?->dia,
                    'hora_inicio' => $grupo->horario?->hora_inicio
                        ? substr((string) $grupo->horario->hora_inicio, 0, 5)
                        : null,
                    'hora_fin' => $grupo->horario?->hora_fin
                        ? substr((string) $grupo->horario->hora_fin, 0, 5)
                        : null,
                ])->values()->all(),
            ],
        ])->withFragment('notas');
    }
}
