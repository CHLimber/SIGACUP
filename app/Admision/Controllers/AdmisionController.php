<?php

namespace App\Admision\Controllers;

use App\Enums\UserRole;
use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionDocentes\Models\Docente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\GestionEstudiantes\Models\Postulacion;
use App\Http\Controllers\Controller;
use App\InscripcionPagos\Models\Pago;
use App\Mail\CandidatoRechazadoDefinitivamente;
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

        $pago = DB::transaction(function () use ($candidato, $postulacion, $montoBs): Pago {
            $candidato->update(['estado' => CandidatoEstudiante::ESTADO_APROBADO]);

            $pago = Pago::create([
                'postulacion_id' => $postulacion->id,
                'token_pago'     => Str::random(64),
                'monto_bs'       => $montoBs,
                'monto_usd'      => $montoBs,
                'tasa_cambio'    => 1.0,
                'metodo'         => 'stripe',
                'estado'         => Pago::ESTADO_PENDIENTE,
            ]);

            $postulacion->update(['estado_pago' => 'pendiente']);

            return $pago;
        });

        $mailEnviado = true;
        try {
            Mail::to($candidato->email)->send(new EstudianteAprobadoConPago($candidato, $pago));
        } catch (\Throwable) {
            $mailEnviado = false;
        }

        $mensaje = "Candidato {$candidato->apellido} {$candidato->nombres} aprobado.";
        $mensaje .= $mailEnviado
            ? ' Se le envió el link de pago de matrícula.'
            : ' No se pudo enviar el email — verifique la configuración de correo.';

        return back()->with('flash', [
            'type'    => 'success',
            'message' => $mensaje,
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

        $candidato->load('persona');

        $nombre = "{$candidato->apellido} {$candidato->nombres}";
        $email  = $candidato->email;
        $ci     = $candidato->ci;
        $motivo = (string) $request->input('motivo');

        $mailEnviado = $this->enviarCorreoRechazo($email, $nombre, $ci, 'estudiante', $motivo);

        $this->borrarCandidatoEstudiante($candidato);

        $mensaje = "Solicitud de {$nombre} rechazada definitivamente y eliminada del sistema.";
        $mensaje .= $mailEnviado
            ? ' Se le envió un correo notificando la decisión.'
            : ' No se pudo enviar el correo de notificación.';

        return redirect()->route('admision.index')->with('flash', [
            'type'    => 'success',
            'message' => $mensaje,
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

        $candidato->load('persona');

        $nombre = "{$candidato->apellido} {$candidato->nombres}";
        $email  = $candidato->email;
        $ci     = $candidato->ci;
        $motivo = (string) $request->input('motivo');

        $mailEnviado = $this->enviarCorreoRechazo($email, $nombre, $ci, 'docente', $motivo);

        $this->borrarCandidatoDocente($candidato);

        $mensaje = "Solicitud de {$nombre} rechazada definitivamente y eliminada del sistema.";
        $mensaje .= $mailEnviado
            ? ' Se le envió un correo notificando la decisión.'
            : ' No se pudo enviar el correo de notificación.';

        return redirect()->route('admision.index')->with('flash', [
            'type'    => 'success',
            'message' => $mensaje,
        ]);
    }

    public function eliminarCandidatoEstudiante(CandidatoEstudiante $candidato): RedirectResponse
    {
        $nombre = "{$candidato->apellido} {$candidato->nombres}";

        $this->borrarCandidatoEstudiante($candidato);

        return redirect()->route('admision.index')->with('flash', [
            'type'    => 'success',
            'message' => "Candidato {$nombre} eliminado correctamente.",
        ]);
    }

    public function eliminarCandidatoDocente(CandidatoDocente $candidato): RedirectResponse
    {
        $nombre = "{$candidato->apellido} {$candidato->nombres}";

        $this->borrarCandidatoDocente($candidato);

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

        $tienePostulacion = $tipo === 'estudiante'
            ? $candidato->postulacion()->exists()
            : true;

        return Inertia::render('Admision/RevisarCandidato', [
            'tipo'       => $tipo,
            'candidato'  => array_merge($candidato->toArray(), [
                'nombre_completo' => $candidato->nombre_completo,
            ]),
            'requisitos'    => $requisitos,
            'puedeAprobar'  => $this->todosRequisitosObligatoriosAprobados($candidato)
                && $tienePostulacion
                && in_array($candidato->estado, [
                    CandidatoEstudiante::ESTADO_EN_REVISION,
                    CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES,
                ], true),
            'tienePostulacion' => $tienePostulacion,
            'tieneRechazados'  => $candidato->requisitos()
                ->where('estado', $estadoRechazado)
                ->exists(),
        ]);
    }

    private function enviarCorreoRechazo(?string $email, string $nombreCompleto, ?string $ci, string $tipo, string $motivo): bool
    {
        if (! $email) {
            return false;
        }

        try {
            Mail::to($email)->send(new CandidatoRechazadoDefinitivamente($nombreCompleto, $ci, $tipo, $motivo));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function borrarCandidatoEstudiante(CandidatoEstudiante $candidato): void
    {
        $candidato->load(['requisitos', 'postulaciones.pago', 'persona']);

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
            $persona = $candidato->persona;
            $candidato->delete();

            $this->borrarPersonaSiHuerfana($persona);
        });
    }

    private function borrarCandidatoDocente(CandidatoDocente $candidato): void
    {
        $candidato->load(['requisitos', 'persona']);

        DB::transaction(function () use ($candidato) {
            foreach ($candidato->requisitos as $requisito) {
                Storage::disk('local')->delete($requisito->ruta_archivo);
            }
            Storage::disk('local')->deleteDirectory("requisitos/docentes/{$candidato->id}");

            $candidato->requisitos()->delete();

            if ($candidato->user_id) {
                User::destroy($candidato->user_id);
            }

            $persona = $candidato->persona;
            $candidato->delete();

            $this->borrarPersonaSiHuerfana($persona);
        });
    }

    private function borrarPersonaSiHuerfana(?Persona $persona): void
    {
        if (! $persona) {
            return;
        }

        $tieneUsuario = User::where('persona_id', $persona->id)->exists();
        $tieneEstudiante = CandidatoEstudiante::where('persona_id', $persona->id)->exists();
        $tieneDocente = CandidatoDocente::where('persona_id', $persona->id)->exists();

        if (! $tieneUsuario && ! $tieneEstudiante && ! $tieneDocente) {
            try {
                $persona->delete();
            } catch (\Throwable) {
                // Conserva la persona si hay otras FK desconocidas que impidan borrarla.
            }
        }
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
