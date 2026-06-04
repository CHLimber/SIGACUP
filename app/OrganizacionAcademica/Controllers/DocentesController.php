<?php

namespace App\OrganizacionAcademica\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\OrganizacionAcademica\Models\CandidatoDocente;
use App\OrganizacionAcademica\Models\Docente;
use App\RegistroInscripcion\Catalogos\RequisitosCatalogo;
use App\RegistroInscripcion\Models\RequisitoDocente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocentesController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::where('role', UserRole::Docente)
            ->when($request->filled('gestion_id'), fn ($q) => $q->whereHas('docente', fn ($d) => $d->whereHas('grupos', fn ($g) => $g->where('gestion_id', $request->gestion_id))
            )
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
            'completos' => (clone $query)->whereHas('docente')->count(),
            'pendientes' => (clone $query)->whereDoesntHave('docente')->count(),
        ];

        $docentes = $query
            ->when($request->datos === 'completos', fn ($q) => $q->whereHas('docente'))
            ->when($request->datos === 'pendientes', fn ($q) => $q->whereDoesntHave('docente'))
            ->with(['persona', 'docente', 'candidatoDocente'])
            ->orderBy('id', 'desc')
            ->paginate(30)
            ->through(fn (User $u) => [
                'user_id' => $u->id,
                'username' => $u->username,
                'email' => $u->email,
                'ci' => $u->persona?->ci,
                'apellido' => $u->persona?->apellido,
                'nombres' => $u->persona?->nombres,
                'telefono' => $u->persona?->telefono,
                'titulo' => $u->docente?->titulo,
                'experiencia_anios' => $u->docente?->experiencia_anios,
                'tiene_diplomado' => (bool) $u->docente?->tiene_diplomado,
                'tiene_maestria' => (bool) $u->docente?->tiene_maestria,
                'datos_completos' => $u->docente !== null,
                'candidato_id' => $u->candidatoDocente?->id,
            ]);

        $gestiones = Gestion::orderByDesc('anio')->orderByDesc('semestre')->get(['id', 'anio', 'semestre']);

        return Inertia::render('OrganizacionAcademica/GestionDocentes/Index', [
            'docentes' => $docentes,
            'resumen' => $resumen,
            'gestiones' => $gestiones,
            'filtros' => [
                'gestion_id' => $request->gestion_id ?? '',
                'datos' => $request->datos ?? '',
                'busqueda' => $request->busqueda ?? '',
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        abort_unless($user->role === UserRole::Docente->value, 404);

        $user->load('persona');
        $docente = Docente::firstWhere('user_id', $user->id);
        $candidato = CandidatoDocente::where('user_id', $user->id)->with('persona')->first();

        $documentos = [];
        if ($candidato) {
            $catalogo = RequisitosCatalogo::paraCandidato($candidato);
            $archivos = $candidato->requisitos()->get()->keyBy('codigo');

            $documentos = collect($catalogo)->map(function (array $def, string $codigo) use ($archivos): array {
                $archivo = $archivos->get($codigo);

                return [
                    'codigo' => $codigo,
                    'nombre' => $def['nombre'],
                    'descripcion' => $def['descripcion'],
                    'obligatorio' => $def['obligatorio'],
                    'archivo' => $archivo ? [
                        'id' => $archivo->id,
                        'nombre_original' => $archivo->nombre_original,
                        'estado' => $archivo->estado,
                        'subido_at' => $archivo->created_at?->toIso8601String(),
                    ] : null,
                ];
            })->values()->all();
        }

        return Inertia::render('OrganizacionAcademica/GestionDocentes/Edit', [
            'docente' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ci' => $user->persona?->ci,
                'apellido' => $user->persona?->apellido,
                'nombres' => $user->persona?->nombres,
                'fecha_nacimiento' => $user->persona?->fecha_nacimiento?->format('Y-m-d'),
                'sexo' => $user->persona?->sexo,
                'telefono' => $user->persona?->telefono,
                'direccion' => $user->persona?->direccion,
                'titulo' => $docente?->titulo ?? '',
                'experiencia_anios' => $docente?->experiencia_anios ?? 0,
                'tiene_diplomado' => (bool) $docente?->tiene_diplomado,
                'tiene_maestria' => (bool) $docente?->tiene_maestria,
            ],
            'documentos' => $documentos,
            'candidatoId' => $candidato?->id,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::Docente->value, 404);

        $data = $request->validate([
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:500',
        ]);

        if ($user->persona) {
            $user->persona->update([
                'telefono' => $data['telefono'] ?? $user->persona->telefono,
                'direccion' => $data['direccion'] ?? $user->persona->direccion,
            ]);
        }

        return redirect()->route('docentes.index')->with('flash', [
            'type' => 'success',
            'message' => 'Datos del docente actualizados correctamente.',
        ]);
    }

    public function descargarDocumento(Request $request): StreamedResponse
    {
        $archivo = RequisitoDocente::findOrFail($request->input('id'));

        return Storage::disk('local')->download($archivo->ruta_archivo, $archivo->nombre_original);
    }
}
