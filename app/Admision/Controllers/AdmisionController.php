<?php

namespace App\Admision\Controllers;

use App\Enums\UserRole;
use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\Http\Controllers\Controller;
use App\Mail\DocenteAprobadoConCredenciales;
use App\Mail\EstudianteAprobadoConPago;
use App\Mail\RequisitosRequierenCorreccion;
use App\Models\User;
use App\RegistroPublico\Catalogos\RequisitosCatalogo;
use App\RegistroPublico\Models\RequisitoArchivo;
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
            'candidatosEstudiante' => CandidatoEstudiante::withCount([
                'requisitos as requisitos_pendientes_revision_count' => fn ($q) => $q->where('estado', RequisitoArchivo::ESTADO_PENDIENTE_REVISION),
            ])->orderBy('created_at', 'desc')->get(),
            'candidatosDocente'    => CandidatoDocente::withCount([
                'requisitos as requisitos_pendientes_revision_count' => fn ($q) => $q->where('estado', RequisitoArchivo::ESTADO_PENDIENTE_REVISION),
            ])->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function revisarCandidatoEstudiante(CandidatoEstudiante $candidato): Response
    {
        return $this->renderRevisar($candidato, 'estudiante');
    }

    public function revisarCandidatoDocente(CandidatoDocente $candidato): Response
    {
        return $this->renderRevisar($candidato, 'docente');
    }

    public function aprobarRequisito(RequisitoArchivo $archivo): RedirectResponse
    {
        $archivo->update([
            'estado'         => RequisitoArchivo::ESTADO_APROBADO,
            'motivo_rechazo' => null,
            'revisado_at'    => now(),
        ]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Requisito aprobado.',
        ]);
    }

    public function rechazarRequisito(Request $request, RequisitoArchivo $archivo): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'motivo.required' => 'Debes indicar el motivo del rechazo para que el candidato pueda corregir.',
            'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $archivo->update([
            'estado'         => RequisitoArchivo::ESTADO_RECHAZADO,
            'motivo_rechazo' => $data['motivo'],
            'revisado_at'    => now(),
        ]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Requisito rechazado. Recuerda enviar la solicitud de correcciones al candidato.',
        ]);
    }

    public function descargarRequisito(RequisitoArchivo $archivo): StreamedResponse
    {
        return Storage::disk('local')->download($archivo->ruta_archivo, $archivo->nombre_original);
    }

    public function solicitarCorreccionesEstudiante(CandidatoEstudiante $candidato): RedirectResponse
    {
        return $this->solicitarCorrecciones($candidato);
    }

    public function solicitarCorreccionesDocente(CandidatoDocente $candidato): RedirectResponse
    {
        return $this->solicitarCorrecciones($candidato);
    }

    public function aprobarCandidatoEstudiante(CandidatoEstudiante $candidato): RedirectResponse
    {
        if (! in_array($candidato->estado, [CandidatoEstudiante::ESTADO_EN_REVISION, CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES], true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta solicitud no puede ser aprobada en su estado actual.']);
        }

        if (! $this->todosRequisitosObligatoriosAprobados($candidato)) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'Aún hay requisitos obligatorios sin aprobar. Aprueba cada documento antes de cerrar la solicitud.',
            ]);
        }

        $montoBs    = (float) config('sigacup.matricula.monto_bs');
        $tasaCambio = (float) config('sigacup.matricula.tasa_bs_usd');
        $montoUsd   = round($montoBs / $tasaCambio, 2);

        $candidato->update([
            'estado'      => CandidatoEstudiante::ESTADO_APROBADO,
            'aprobado_at' => now(),
            'token_pago'  => Str::random(64),
            'monto_bs'    => $montoBs,
            'monto_usd'   => $montoUsd,
            'tasa_cambio' => $tasaCambio,
        ]);

        Mail::to($candidato->email)->send(new EstudianteAprobadoConPago($candidato->fresh()));

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
            'rechazado_at'   => now(),
            'motivo_rechazo' => $request->input('motivo'),
        ]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Solicitud de {$candidato->apellido} {$candidato->nombres} rechazada definitivamente.",
        ]);
    }

    public function aprobarCandidatoDocente(CandidatoDocente $candidato): RedirectResponse
    {
        if (! in_array($candidato->estado, [CandidatoDocente::ESTADO_EN_REVISION, CandidatoDocente::ESTADO_REQUIERE_CORRECCIONES], true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta solicitud no puede ser aprobada en su estado actual.']);
        }

        if (! $this->todosRequisitosObligatoriosAprobados($candidato)) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'Aún hay requisitos obligatorios sin aprobar.',
            ]);
        }

        $passwordTemporal = Str::random(12);

        $user = DB::transaction(function () use ($candidato, $passwordTemporal) {
            $user = User::create([
                'name'             => $candidato->apellido . ' ' . $candidato->nombres,
                'email'            => $candidato->email,
                'password'         => $passwordTemporal,
                'role'             => UserRole::Docente,
                'fecha_nacimiento' => $candidato->fecha_nacimiento,
                'sexo'             => $candidato->sexo,
                'telefono'         => $candidato->telefono,
                'direccion'        => $candidato->direccion,
                'username'         => 'tmp_' . uniqid(),
            ]);

            $apellido     = ucfirst(strtolower(Str::ascii($candidato->apellido)));
            $primerNombre = ucfirst(strtolower(Str::ascii(Str::of($candidato->nombres)->explode(' ')->first())));
            $user->username = $apellido . $primerNombre . $user->id;
            $user->save();

            $candidato->update([
                'estado'      => CandidatoDocente::ESTADO_APROBADO,
                'aprobado_at' => now(),
                'user_id'     => $user->id,
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
            'rechazado_at'   => now(),
            'motivo_rechazo' => $request->input('motivo'),
        ]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => "Solicitud de {$candidato->apellido} {$candidato->nombres} rechazada definitivamente.",
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

        return Inertia::render('Admision/RevisarCandidato', [
            'tipo' => $tipo,
            'candidato' => $candidato->toArray() + [
                'nombre_completo' => $candidato->nombre_completo,
            ],
            'requisitos' => $requisitos,
            'puedeAprobar' => $this->todosRequisitosObligatoriosAprobados($candidato)
                && in_array($candidato->estado, [
                    CandidatoEstudiante::ESTADO_EN_REVISION,
                    CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES,
                ], true),
            'tieneRechazados' => $candidato->requisitos()
                ->where('estado', RequisitoArchivo::ESTADO_RECHAZADO)
                ->exists(),
        ]);
    }

    private function todosRequisitosObligatoriosAprobados(Model $candidato): bool
    {
        $obligatorios = RequisitosCatalogo::codigosObligatorios($candidato);

        $aprobados = $candidato->requisitos()
            ->whereIn('codigo', $obligatorios)
            ->where('estado', RequisitoArchivo::ESTADO_APROBADO)
            ->pluck('codigo')
            ->all();

        return count(array_diff($obligatorios, $aprobados)) === 0;
    }

    private function solicitarCorrecciones(Model $candidato): RedirectResponse
    {
        if ($candidato->estado !== CandidatoEstudiante::ESTADO_EN_REVISION) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Solo se pueden solicitar correcciones desde el estado "En revisión".']);
        }

        $rechazados = $candidato->requisitos()
            ->where('estado', RequisitoArchivo::ESTADO_RECHAZADO)
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
