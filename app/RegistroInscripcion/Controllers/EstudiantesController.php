<?php

namespace App\RegistroInscripcion\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\Http\Controllers\Controller;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
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

    public function index(Request $request): Response
    {
        $estadoMap = [
            'aprobados' => CandidatoEstudiante::ESTADO_APROBADO,
            'pagados' => CandidatoEstudiante::ESTADO_PAGADO,
        ];

        $query = CandidatoEstudiante::whereIn('estado', self::ESTADOS_GESTION)
            ->when($request->filled('gestion_id'), fn ($q) => $q->whereHas('postulaciones', fn ($p) => $p->where('gestion_id', $request->gestion_id))
            )
            ->when($request->filled('busqueda'), function ($q) use ($request) {
                $term = '%'.$request->busqueda.'%';
                $q->whereHas('persona', fn ($p) => $p->where('ci', 'ILIKE', $term)
                    ->orWhere('apellido', 'ILIKE', $term)
                    ->orWhere('nombres', 'ILIKE', $term)
                    ->orWhere('email', 'ILIKE', $term)
                );
            });

        $resumen = [
            'aprobados' => (clone $query)->where('estado', CandidatoEstudiante::ESTADO_APROBADO)->count(),
            'pagados' => (clone $query)->where('estado', CandidatoEstudiante::ESTADO_PAGADO)->count(),
        ];

        $estudiantes = $query
            ->when($request->filled('estado') && isset($estadoMap[$request->estado]),
                fn ($q) => $q->where('estado', $estadoMap[$request->estado])
            )
            ->with([
                'persona',
                'postulacion.gestion',
                'postulacion.carrera1',
                'postulacion.carrera2',
                'postulacion.carreraAsignada',
            ])
            ->orderBy('id', 'desc')
            ->paginate(30)
            ->through(fn (CandidatoEstudiante $c) => [
                'id' => $c->id,
                'ci' => $c->ci,
                'apellido' => $c->apellido,
                'nombres' => $c->nombres,
                'email' => $c->email,
                'telefono' => $c->telefono,
                'estado' => $c->estado,
                'gestion_label' => $c->postulacion?->gestion
                    ? $c->postulacion->gestion->anio.'-'.$c->postulacion->gestion->semestre
                    : null,
                'carrera1' => $c->postulacion?->carrera1?->nombre,
                'carrera2' => $c->postulacion?->carrera2?->nombre,
                'carrera_asignada' => $c->postulacion?->carreraAsignada?->nombre,
            ]);

        $gestiones = Gestion::orderByDesc('anio')->orderByDesc('semestre')->get(['id', 'anio', 'semestre']);

        return Inertia::render('RegistroInscripcion/GestionEstudiantes/Index', [
            'estudiantes' => $estudiantes,
            'resumen' => $resumen,
            'gestiones' => $gestiones,
            'filtros' => [
                'gestion_id' => $request->gestion_id ?? '',
                'estado' => $request->estado ?? '',
                'busqueda' => $request->busqueda ?? '',
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

        return Inertia::render('RegistroInscripcion/GestionEstudiantes/Edit', [
            'estudiante' => [
                'id' => $estudiante->id,
                'ci' => $estudiante->ci,
                'apellido' => $estudiante->apellido,
                'nombres' => $estudiante->nombres,
                'fecha_nacimiento' => $estudiante->persona?->fecha_nacimiento?->format('Y-m-d'),
                'sexo' => $estudiante->sexo,
                'email' => $estudiante->email ?? '',
                'telefono' => $estudiante->telefono ?? '',
                'direccion' => $estudiante->direccion ?? '',
                'estado' => $estudiante->estado,
                'gestion_label' => $postulacion?->gestion
                    ? $postulacion->gestion->anio.'-'.$postulacion->gestion->semestre
                    : null,
                'carrera1' => $postulacion?->carrera1?->nombre,
                'carrera2' => $postulacion?->carrera2?->nombre,
                'carrera_asignada' => $postulacion?->carreraAsignada?->nombre,
                'anio_egreso' => $postulacion?->anio_egreso,
                'unidad_educativa' => $postulacion?->unidad_educativa,
                'tipo_colegio' => $postulacion?->tipo_colegio,
            ],
        ]);
    }

    public function update(Request $request, CandidatoEstudiante $estudiante): RedirectResponse
    {
        abort_unless(in_array($estudiante->estado, self::ESTADOS_GESTION, true), 404);

        $data = $request->validate([
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
        ]);

        if ($estudiante->persona) {
            $estudiante->persona->update([
                'email' => $data['email'] ?? $estudiante->persona->email,
                'telefono' => $data['telefono'] ?? $estudiante->persona->telefono,
                'direccion' => $data['direccion'] ?? $estudiante->persona->direccion,
            ]);
        }

        return redirect()->route('estudiantes.index')->with('flash', [
            'type' => 'success',
            'message' => 'Datos del estudiante actualizados correctamente.',
        ]);
    }
}
