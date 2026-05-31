<?php

namespace App\GestionDocentes\Controllers;

use App\Enums\UserRole;
use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionDocentes\Models\Docente;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\RegistroPublico\Catalogos\RequisitosCatalogo;
use App\RegistroPublico\Models\RequisitoDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocentesController extends Controller
{
    public function index(): Response
    {
        $usuarios = User::where('role', UserRole::Docente)
            ->with(['persona'])
            ->orderBy('id', 'desc')
            ->get();

        $docentesPorUser = Docente::whereIn('user_id', $usuarios->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $candidatosPorUser = CandidatoDocente::whereIn('user_id', $usuarios->pluck('id'))
            ->get()
            ->keyBy('user_id');

        $docentes = $usuarios->map(function (User $u) use ($docentesPorUser, $candidatosPorUser) {
            $docente   = $docentesPorUser->get($u->id);
            $candidato = $candidatosPorUser->get($u->id);

            return [
                'user_id'           => $u->id,
                'username'          => $u->username,
                'email'             => $u->email,
                'ci'                => $u->persona?->ci,
                'apellido'          => $u->persona?->apellido,
                'nombres'           => $u->persona?->nombres,
                'telefono'          => $u->persona?->telefono,
                'titulo'            => $docente?->titulo,
                'experiencia_anios' => $docente?->experiencia_anios,
                'tiene_diplomado'   => (bool) $docente?->tiene_diplomado,
                'tiene_maestria'    => (bool) $docente?->tiene_maestria,
                'datos_completos'   => $docente !== null,
                'candidato_id'      => $candidato?->id,
            ];
        });

        return Inertia::render('GestionDocentes/Index', [
            'docentes' => $docentes->values(),
            'totales'  => [
                'total'      => $docentes->count(),
                'completos'  => $docentes->where('datos_completos', true)->count(),
                'pendientes' => $docentes->where('datos_completos', false)->count(),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        abort_unless($user->role === UserRole::Docente, 404);

        $user->load('persona');
        $docente   = Docente::firstWhere('user_id', $user->id);
        $candidato = CandidatoDocente::where('user_id', $user->id)->with('persona')->first();

        $documentos = [];
        if ($candidato) {
            $catalogo = RequisitosCatalogo::paraCandidato($candidato);
            $archivos = $candidato->requisitos()->get()->keyBy('codigo');

            $documentos = collect($catalogo)->map(function (array $def, string $codigo) use ($archivos): array {
                $archivo = $archivos->get($codigo);

                return [
                    'codigo'         => $codigo,
                    'nombre'         => $def['nombre'],
                    'descripcion'    => $def['descripcion'],
                    'obligatorio'    => $def['obligatorio'],
                    'archivo'        => $archivo ? [
                        'id'              => $archivo->id,
                        'nombre_original' => $archivo->nombre_original,
                        'estado'          => $archivo->estado,
                        'subido_at'       => $archivo->created_at?->toIso8601String(),
                    ] : null,
                ];
            })->values()->all();
        }

        return Inertia::render('GestionDocentes/Edit', [
            'docente' => [
                'user_id'           => $user->id,
                'username'          => $user->username,
                'email'             => $user->email,
                'ci'                => $user->persona?->ci,
                'apellido'          => $user->persona?->apellido,
                'nombres'           => $user->persona?->nombres,
                'fecha_nacimiento'  => $user->persona?->fecha_nacimiento?->format('Y-m-d'),
                'sexo'              => $user->persona?->sexo,
                'telefono'          => $user->persona?->telefono,
                'direccion'         => $user->persona?->direccion,
                'titulo'            => $docente?->titulo ?? '',
                'experiencia_anios' => $docente?->experiencia_anios ?? 0,
                'tiene_diplomado'   => (bool) $docente?->tiene_diplomado,
                'tiene_maestria'    => (bool) $docente?->tiene_maestria,
            ],
            'documentos' => $documentos,
            'candidatoId' => $candidato?->id,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::Docente, 404);

        $data = $request->validate([
            'telefono'  => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
        ]);

        if ($user->persona) {
            $user->persona->update([
                'telefono'  => $data['telefono']  ?? $user->persona->telefono,
                'direccion' => $data['direccion'] ?? $user->persona->direccion,
            ]);
        }

        return redirect()->route('docentes.index')->with('flash', [
            'type'    => 'success',
            'message' => 'Datos del docente actualizados correctamente.',
        ]);
    }

    public function descargarDocumento(Request $request): StreamedResponse
    {
        $archivo = RequisitoDocente::findOrFail($request->input('id'));

        return Storage::disk('local')->download($archivo->ruta_archivo, $archivo->nombre_original);
    }
}
