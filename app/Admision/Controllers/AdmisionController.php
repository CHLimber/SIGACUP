<?php

namespace App\Admision\Controllers;

use App\Enums\UserRole;
use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionDocentes\Models\Docente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\GestionEstudiantes\Models\Postulacion;
use App\Http\Controllers\Controller;
use App\InscripcionPagos\Models\Pago;
use App\Mail\DocenteAprobadoConCredenciales;
use App\Mail\EstudianteAprobadoConPago;
use App\Mail\RequisitosRequierenCorreccion;
use App\Models\Persona;
use App\Models\User;
use App\RegistroPublico\Catalogos\RequisitosCatalogo;
use App\RegistroPublico\Models\RequisitoDocente;
use App\RegistroPublico\Models\RequisitoEstudiante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdmisionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admision/Index', [
            'candidatosEstudiante' => CandidatoEstudiante::with(['persona', 'postulacion.carrera1', 'postulacion.carrera2'])
                ->withCount([
                    'requisitos as requisitos_pendientes_revision_count' => fn ($q) => $q->where('estado', RequisitoEstudiante::ESTADO_PENDIENTE_REVISION),
                ])
                ->orderBy('created_at', 'desc')
                ->get(),
            'candidatosDocente' => CandidatoDocente::with('persona')
                ->withCount([
                    'requisitos as requisitos_pendientes_revision_count' => fn ($q) => $q->where('estado', RequisitoDocente::ESTADO_PENDIENTE_REVISION),
                ])
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    public function revisarCandidatoEstudiante(CandidatoEstudiante $candidato): Response
    {
        $candidato->load(['persona', 'postulacion.carrera1', 'postulacion.carrera2']);

        return $this->renderRevisar($candidato, 'estudiante');
    }

    public function revisarCandidatoDocente(CandidatoDocente $candidato): Response
    {
        $candidato->load('persona');

        return $this->renderRevisar($candidato, 'docente');
    }

    public function aprobarRequisito(Request $request): RedirectResponse
    {
        $tipo = $request->input('tipo', 'estudiante');

        if ($tipo === 'docente') {
            $archivo = RequisitoDocente::findOrFail($request->input('id'));
            $archivo->update([
                'estado'         => RequisitoDocente::ESTADO_APROBADO,
                'motivo_rechazo' => null,
                'revisado_at'    => now(),
            ]);
        } else {
            $archivo = RequisitoEstudiante::findOrFail($request->input('id'));
            $archivo->update([
                'estado'         => RequisitoEstudiante::ESTADO_APROBADO,
                'motivo_rechazo' => null,
                'revisado_at'    => now(),
            ]);
        }

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Requisito aprobado.',
        ]);
    }

    public function rechazarRequisito(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id'     => 'required|integer',
            'tipo'   => 'required|string|in:estudiante,docente',
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'motivo.required' => 'Debes indicar el motivo del rechazo para que el candidato pueda corregir.',
            'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if ($data['tipo'] === 'docente') {
            $archivo = RequisitoDocente::findOrFail($data['id']);
            $archivo->update([
                'estado'         => RequisitoDocente::ESTADO_RECHAZADO,
                'motivo_rechazo' => $data['motivo'],
                'revisado_at'    => now(),
            ]);
        } else {
            $archivo = RequisitoEstudiante::findOrFail($data['id']);
            $archivo->update([
                'estado'         => RequisitoEstudiante::ESTADO_RECHAZADO,
                'motivo_rechazo' => $data['motivo'],
                'revisado_at'    => now(),
            ]);
        }

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Requisito rechazado. Recuerda enviar la solicitud de correcciones al candidato.',
        ]);
    }

    public function descargarRequisito(Request $request): StreamedResponse
    {
        $tipo = $request->input('tipo', 'estudiante');

        $archivo = $tipo === 'docente'
            ? RequisitoDocente::findOrFail($request->input('id'))
            : RequisitoEstudiante::findOrFail($request->input('id'));

        return Storage::disk('local')->download($archivo->ruta_archivo, $archivo->nombre_original);
    }

    public function solicitarCorreccionesEstudiante(CandidatoEstudiante $candidato): RedirectResponse
    {
        $candidato->load('persona');

        return $this->solicitarCorrecciones($candidato);
    }

    public function solicitarCorreccionesDocente(CandidatoDocente $candidato): RedirectResponse
    {
        $candidato->load('persona');

        return $this->solicitarCorrecciones($candidato);
    }

    public function aprobarCandidatoEstudiante(CandidatoEstudiante $candidato): RedirectResponse
    {
        $candidato->load(['persona', 'postulacion.gestion']);

        if (! in_array($candidato->estado, [CandidatoEstudiante::ESTADO_EN_REVISION, CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES], true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta solicitud no puede ser aprobada en su estado actual.']);
        }

        if (! $this->todosRequisitosObligatoriosAprobados($candidato)) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'Aún hay requisitos obligatorios sin aprobar. Aprueba cada documento antes de cerrar la solicitud.',
            ]);
        }

        $postulacion = $candidato->postulacion;

        if (! $postulacion) {
            return back()->with('flash', ['type' => 'error', 'message' => 'El candidato no tiene una postulación activa.']);
        }

        $gestion = $postulacion->gestion;
        $montoBs = (float) ($gestion?->parametro('monto_matricula_bs') ?? config('sigacup.matricula.monto_bs', 800));

        DB::transaction(function () use ($candidato, $postulacion, $montoBs) {
            $candidato->update(['estado' => CandidatoEstudiante::ESTADO_APROBADO]);

            Pago::create([
                'postulacion_id' => $postulacion->id,
                'token_pago'     => Str::random(64),
                'monto_bs'       => $montoBs,
                'monto_usd'      => $montoBs,
                'tasa_cambio'    => 1.0,
                'metodo'         => 'stripe',
                'estado'         => Pago::ESTADO_PENDIENTE,
            ]);

            $postulacion->update(['estado_pago' => 'pendiente']);
        });

        $candidato->load('postulacion.pago');
        $pago = $candidato->postulacion?->pago;

        Mail::to($candidato->email)->send(new EstudianteAprobadoConPago($candidato, $pago));

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Candidato {$candidato->apellido} {$candidato->nombres} aprobado. Se le envió el link de pago de matrícula.",
        ]);
    }

    public function rechazarCandidatoEstudiante(Request $request, CandidatoEstudiante $candidato): RedirectResponse
    {
        if (in_array($candidato->estado, [
            CandidatoEstudiante::ESTADO_APROBADO,
            CandidatoEstudiante::ESTADO_PAGADO,
            CandidatoEstudiante::ESTADO_RECHAZADO,
        ], true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta solicitud ya fue cerrada.']);
        }

        $request->validate(['motivo' => 'required|string|min:5|max:500']);

        $candidato->update([
            'estado'         => CandidatoEstudiante::ESTADO_RECHAZADO,
            'motivo_rechazo' => $request->input('motivo'),
        ]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Solicitud de {$candidato->apellido} {$candidato->nombres} rechazada definitivamente.",
        ]);
    }

    public function aprobarCandidatoDocente(CandidatoDocente $candidato): RedirectResponse
    {
        $candidato->load('persona');

        if (! in_array($candidato->estado, [CandidatoDocente::ESTADO_EN_REVISION, CandidatoDocente::ESTADO_REQUIERE_CORRECCIONES], true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta solicitud no puede ser aprobada en su estado actual.']);
        }

        if (! $this->todosRequisitosObligatoriosAprobados($candidato)) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'Aún hay requisitos obligatorios sin aprobar.',
            ]);
        }

        if (empty($candidato->titulo) || $candidato->experiencia_anios === null) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'El candidato no completó sus datos profesionales (título y años de experiencia).',
            ]);
        }

        $passwordTemporal = Str::random(12);

        $user = DB::transaction(function () use ($candidato, $passwordTemporal) {
            $persona = $candidato->persona;

            $user = User::create([
                'persona_id' => $persona->id,
                'name'       => $persona->apellido . ' ' . $persona->nombres,
                'email'      => $persona->email,
                'password'   => $passwordTemporal,
                'role'       => UserRole::Docente,
                'username'   => 'tmp_' . uniqid(),
            ]);

            $apellido     = strtolower(Str::ascii($persona->apellido));
            $primerNombre = strtolower(Str::ascii(Str::of($persona->nombres)->explode(' ')->first()));
            $user->username = $apellido . $primerNombre . $user->id;
            $user->save();

            Docente::create([
                'user_id'           => $user->id,
                'titulo'            => $candidato->titulo,
                'experiencia_anios' => $candidato->experiencia_anios,
                'tiene_diplomado'   => (bool) $candidato->tiene_diplomado,
                'tiene_maestria'    => (bool) $candidato->tiene_maestria,
            ]);

            $candidato->update([
                'estado'  => CandidatoDocente::ESTADO_APROBADO,
                'user_id' => $user->id,
            ]);

            return $user;
        });

        Mail::to($candidato->email)->send(
            new DocenteAprobadoConCredenciales($candidato, $user->username, $passwordTemporal),
        );

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Docente aprobado. Credenciales enviadas a {$candidato->email}. Usuario: {$user->username}",
        ]);
    }

    public function rechazarCandidatoDocente(Request $request, CandidatoDocente $candidato): RedirectResponse
    {
        if (in_array($candidato->estado, [CandidatoDocente::ESTADO_APROBADO, CandidatoDocente::ESTADO_RECHAZADO], true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta solicitud ya fue cerrada.']);
        }

        $request->validate(['motivo' => 'required|string|min:5|max:500']);

        $candidato->update([
            'estado'         => CandidatoDocente::ESTADO_RECHAZADO,
            'motivo_rechazo' => $request->input('motivo'),
        ]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Solicitud de {$candidato->apellido} {$candidato->nombres} rechazada definitivamente.",
        ]);
    }

    public function eliminarCandidatoEstudiante(CandidatoEstudiante $candidato): RedirectResponse
    {
        $nombre = "{$candidato->apellido} {$candidato->nombres}";

        $candidato->load('requisitos', 'postulaciones.pago');

        DB::transaction(function () use ($candidato) {
            foreach ($candidato->requisitos as $requisito) {
                Storage::disk('local')->delete($requisito->ruta_archivo);
            }
            Storage::disk('local')->deleteDirectory("requisitos/estudiantes/{$candidato->id}");

            foreach ($candidato->postulaciones as $postulacion) {
                $postulacion->pago?->delete();
                $postulacion->delete();
            }

            $candidato->requisitos()->delete();
            $candidato->delete();
        });

        return redirect()->route('admision.index')->with('flash', [
            'type'    => 'success',
            'message' => "Candidato {$nombre} eliminado correctamente.",
        ]);
    }

    public function eliminarCandidatoDocente(CandidatoDocente $candidato): RedirectResponse
    {
        $nombre = "{$candidato->apellido} {$candidato->nombres}";

        $candidato->load('requisitos');

        DB::transaction(function () use ($candidato) {
            foreach ($candidato->requisitos as $requisito) {
                Storage::disk('local')->delete($requisito->ruta_archivo);
            }
            Storage::disk('local')->deleteDirectory("requisitos/docentes/{$candidato->id}");

            $candidato->requisitos()->delete();

            if ($candidato->user_id) {
                User::destroy($candidato->user_id);
            }

            $candidato->delete();
        });

        return redirect()->route('admision.index')->with('flash', [
            'type'    => 'success',
            'message' => "Candidato docente {$nombre} eliminado correctamente.",
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function renderRevisar(Model $candidato, string $tipo): Response
    {
        $catalogo = RequisitosCatalogo::paraCandidato($candidato);
        $archivos = $candidato->requisitos()->get()->keyBy('codigo');

        $requisitos = collect($catalogo)->map(function (array $def, string $codigo) use ($archivos): array {
            $archivo = $archivos->get($codigo);

            return [
                'codigo'      => $codigo,
                'nombre'      => $def['nombre'],
                'descripcion' => $def['descripcion'],
                'obligatorio' => $def['obligatorio'],
                'archivo'     => $archivo ? [
                    'id'              => $archivo->id,
                    'nombre_original' => $archivo->nombre_original,
                    'mime_type'       => $archivo->mime_type,
                    'tamano'          => $archivo->tamano,
                    'estado'          => $archivo->estado,
                    'motivo_rechazo'  => $archivo->motivo_rechazo,
                    'subido_at'       => $archivo->created_at?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();

        $estadoRechazado = $tipo === 'docente'
            ? RequisitoDocente::ESTADO_RECHAZADO
            : RequisitoEstudiante::ESTADO_RECHAZADO;

        return Inertia::render('Admision/RevisarCandidato', [
            'tipo'       => $tipo,
            'candidato'  => array_merge($candidato->toArray(), [
                'nombre_completo' => $candidato->nombre_completo,
            ]),
            'requisitos'    => $requisitos,
            'puedeAprobar'  => $this->todosRequisitosObligatoriosAprobados($candidato)
                && in_array($candidato->estado, [
                    CandidatoEstudiante::ESTADO_EN_REVISION,
                    CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES,
                ], true),
            'tieneRechazados' => $candidato->requisitos()
                ->where('estado', $estadoRechazado)
                ->exists(),
        ]);
    }

    private function todosRequisitosObligatoriosAprobados(Model $candidato): bool
    {
        $obligatorios = RequisitosCatalogo::codigosObligatorios($candidato);
        $estadoAprobado = $candidato instanceof CandidatoEstudiante
            ? RequisitoEstudiante::ESTADO_APROBADO
            : RequisitoDocente::ESTADO_APROBADO;

        $aprobados = $candidato->requisitos()
            ->whereIn('codigo', $obligatorios)
            ->where('estado', $estadoAprobado)
            ->pluck('codigo')
            ->all();

        return count(array_diff($obligatorios, $aprobados)) === 0;
    }

    private function solicitarCorrecciones(Model $candidato): RedirectResponse
    {
        if ($candidato->estado !== CandidatoEstudiante::ESTADO_EN_REVISION) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Solo se pueden solicitar correcciones desde el estado "En revisión".']);
        }

        $estadoRechazado = $candidato instanceof CandidatoEstudiante
            ? RequisitoEstudiante::ESTADO_RECHAZADO
            : RequisitoDocente::ESTADO_RECHAZADO;

        $rechazados = $candidato->requisitos()
            ->where('estado', $estadoRechazado)
            ->get();

        if ($rechazados->isEmpty()) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'No hay requisitos rechazados. Aprueba o rechaza al menos un requisito antes de solicitar correcciones.',
            ]);
        }

        $candidato->update(['estado' => CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES]);

        if ($candidato->email) {
            Mail::to($candidato->email)->send(new RequisitosRequierenCorreccion($candidato, $rechazados));
        }

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Se notificó al candidato. Podrá volver a subir los requisitos rechazados.',
        ]);
    }
}
