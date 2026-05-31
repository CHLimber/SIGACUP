<?php

namespace App\GestionEstudiantes\Controllers;

use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstudiantesController extends Controller
{
    private const ESTADOS_GESTION = [
        CandidatoEstudiante::ESTADO_APROBADO,
        CandidatoEstudiante::ESTADO_PAGADO,
    ];

    public function index(): Response
    {
        $candidatos = CandidatoEstudiante::whereIn('estado', self::ESTADOS_GESTION)
            ->with([
                'persona',
                'postulacion.gestion',
                'postulacion.carrera1',
                'postulacion.carrera2',
                'postulacion.carreraAsignada',
            ])
            ->orderBy('id', 'desc')
            ->get();

        $estudiantes = $candidatos->map(function (CandidatoEstudiante $c) {
            $postulacion = $c->postulacion;

            return [
                'id'               => $c->id,
                'ci'               => $c->ci,
                'apellido'         => $c->apellido,
                'nombres'          => $c->nombres,
                'email'            => $c->email,
                'telefono'         => $c->telefono,
                'estado'           => $c->estado,
                'gestion_label'    => $postulacion?->gestion
                    ? $postulacion->gestion->anio . '-' . $postulacion->gestion->semestre
                    : null,
                'carrera1'         => $postulacion?->carrera1?->nombre,
                'carrera2'         => $postulacion?->carrera2?->nombre,
                'carrera_asignada' => $postulacion?->carreraAsignada?->nombre,
            ];
        });

        return Inertia::render('GestionEstudiantes/Index', [
            'estudiantes' => $estudiantes->values(),
            'totales'     => [
                'total'    => $estudiantes->count(),
                'aprobados' => $estudiantes->where('estado', CandidatoEstudiante::ESTADO_APROBADO)->count(),
                'pagados'  => $estudiantes->where('estado', CandidatoEstudiante::ESTADO_PAGADO)->count(),
            ],
        ]);
    }

    public function edit(CandidatoEstudiante $estudiante): Response
    {
        abort_unless(in_array($estudiante->estado, self::ESTADOS_GESTION, true), 404);

        $estudiante->load([
            'persona',
            'postulacion.gestion',
            'postulacion.carrera1',
            'postulacion.carrera2',
            'postulacion.carreraAsignada',
        ]);

        $postulacion = $estudiante->postulacion;

        return Inertia::render('GestionEstudiantes/Edit', [
            'estudiante' => [
                'id'               => $estudiante->id,
                'ci'               => $estudiante->ci,
                'apellido'         => $estudiante->apellido,
                'nombres'          => $estudiante->nombres,
                'fecha_nacimiento' => $estudiante->persona?->fecha_nacimiento?->format('Y-m-d'),
                'sexo'             => $estudiante->sexo,
                'email'            => $estudiante->email ?? '',
                'telefono'         => $estudiante->telefono ?? '',
                'direccion'        => $estudiante->direccion ?? '',
                'estado'           => $estudiante->estado,
                'gestion_label'    => $postulacion?->gestion
                    ? $postulacion->gestion->anio . '-' . $postulacion->gestion->semestre
                    : null,
                'carrera1'         => $postulacion?->carrera1?->nombre,
                'carrera2'         => $postulacion?->carrera2?->nombre,
                'carrera_asignada' => $postulacion?->carreraAsignada?->nombre,
            ],
        ]);
    }

    public function update(Request $request, CandidatoEstudiante $estudiante): RedirectResponse
    {
        abort_unless(in_array($estudiante->estado, self::ESTADOS_GESTION, true), 404);

        $data = $request->validate([
            'email'     => 'nullable|email|max:255',
            'telefono'  => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
        ]);

        if ($estudiante->persona) {
            $estudiante->persona->update([
                'email'     => $data['email']     ?? $estudiante->persona->email,
                'telefono'  => $data['telefono']  ?? $estudiante->persona->telefono,
                'direccion' => $data['direccion'] ?? $estudiante->persona->direccion,
            ]);
        }

        return redirect()->route('estudiantes.index')->with('flash', [
            'type'    => 'success',
            'message' => 'Datos del estudiante actualizados correctamente.',
        ]);
    }
}
