<?php

namespace App\RegistroPublico\Controllers;

use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\Http\Controllers\Controller;
use App\RegistroPublico\Catalogos\RequisitosCatalogo;
use App\RegistroPublico\Models\RequisitoDocente;
use App\RegistroPublico\Models\RequisitoEstudiante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalCandidatoController extends Controller
{
    public function show(string $token): Response
    {
        $candidato = $this->candidatoPorToken($token);
        $catalogo  = RequisitosCatalogo::paraCandidato($candidato);
        $archivos  = $candidato->requisitos()->get()->keyBy('codigo');

        $requisitos = collect($catalogo)->map(function (array $def, string $codigo) use ($archivos): array {
            $archivo = $archivos->get($codigo);

            return [
                'codigo'         => $codigo,
                'nombre'         => $def['nombre'],
                'descripcion'    => $def['descripcion'],
                'obligatorio'    => $def['obligatorio'],
                'mimes'          => $def['mimes'],
                'tamano_max_kb'  => RequisitosCatalogo::TAMANO_MAX_KB,
                'archivo'        => $archivo ? [
                    'id'              => $archivo->id,
                    'nombre_original' => $archivo->nombre_original,
                    'estado'          => $archivo->estado,
                    'motivo_rechazo'  => $archivo->motivo_rechazo,
                    'subido_at'       => $archivo->created_at?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();

        $esDocente = $candidato instanceof CandidatoDocente;

        return Inertia::render('Portal/Requisitos', [
            'token'        => $token,
            'candidato'    => [
                'tipo'             => $esDocente ? 'docente' : 'estudiante',
                'nombre_completo'  => $candidato->nombre_completo,
                'ci'               => $candidato->ci,
                'email'            => $candidato->email,
                'estado'           => $candidato->estado,
                'motivo_rechazo'   => $candidato->motivo_rechazo,
            ],
            'datosProfesionales' => $esDocente ? [
                'titulo'            => $candidato->titulo ?? '',
                'experiencia_anios' => $candidato->experiencia_anios ?? 0,
                'tiene_diplomado'   => (bool) $candidato->tiene_diplomado,
                'tiene_maestria'    => (bool) $candidato->tiene_maestria,
            ] : null,
            'requisitos'   => $requisitos,
            'puedeEnviar'  => $this->puedeEnviar($candidato),
            'bloqueado'    => in_array($candidato->estado, [
                CandidatoEstudiante::ESTADO_APROBADO,
                CandidatoEstudiante::ESTADO_PAGADO,
                CandidatoEstudiante::ESTADO_RECHAZADO,
                CandidatoEstudiante::ESTADO_EN_REVISION,
            ], true),
        ]);
    }

    public function guardarDatosProfesionales(Request $request, string $token): RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if (! $candidato instanceof CandidatoDocente) {
            abort(404);
        }

        if (in_array($candidato->estado, [
            CandidatoDocente::ESTADO_APROBADO,
            CandidatoDocente::ESTADO_RECHAZADO,
            CandidatoDocente::ESTADO_EN_REVISION,
        ], true)) {
            abort(403, 'No puedes editar tus datos mientras la solicitud está bloqueada.');
        }

        $data = $request->validate([
            'titulo'            => 'required|string|max:120',
            'experiencia_anios' => 'required|integer|min:0|max:60',
            'tiene_diplomado'   => 'required|boolean',
            'tiene_maestria'    => 'required|boolean',
        ], [
            'titulo.required' => 'El título profesional es obligatorio.',
            'experiencia_anios.required' => 'Indica tus años de experiencia.',
        ]);

        $candidato->update($data);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Datos profesionales guardados.',
        ]);
    }

    public function subir(Request $request, string $token, string $codigo): RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);

        $this->verificarEditable($candidato, $codigo);

        $definicion = RequisitosCatalogo::definicion($candidato, $codigo);

        if (! $definicion) {
            abort(404, 'Requisito no encontrado.');
        }

        $request->validate([
            'archivo' => [
                'required',
                'file',
                'mimetypes:' . implode(',', $definicion['mimes']),
                'max:' . RequisitosCatalogo::TAMANO_MAX_KB,
            ],
        ], [
            'archivo.mimetypes' => 'El archivo debe ser de tipo: ' . implode(', ', $definicion['mimes']) . '.',
            'archivo.max'       => 'El archivo no puede superar ' . RequisitosCatalogo::TAMANO_MAX_KB . ' KB.',
        ]);

        $file = $request->file('archivo');

        $directorio = $this->directorioCandidato($candidato);
        $extension  = $file->getClientOriginalExtension() ?: $file->extension();
        $ruta       = $file->storeAs($directorio, $codigo . '.' . $extension, 'local');

        $previo = $candidato->requisitos()->where('codigo', $codigo)->first();
        if ($previo && $previo->ruta_archivo !== $ruta) {
            Storage::disk('local')->delete($previo->ruta_archivo);
        }

        $estadoPendiente = $candidato instanceof CandidatoEstudiante
            ? RequisitoEstudiante::ESTADO_PENDIENTE_REVISION
            : RequisitoDocente::ESTADO_PENDIENTE_REVISION;

        $candidato->requisitos()->updateOrCreate(
            ['codigo' => $codigo],
            [
                'nombre_original' => $file->getClientOriginalName(),
                'ruta_archivo'    => $ruta,
                'mime_type'       => $file->getMimeType(),
                'tamano'          => $file->getSize(),
                'estado'          => $estadoPendiente,
                'motivo_rechazo'  => null,
                'revisado_at'     => null,
            ],
        );

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Archivo subido correctamente.',
        ]);
    }

    public function eliminar(string $token, string $codigo): RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);
        $this->verificarEditable($candidato, $codigo);

        $archivo = $candidato->requisitos()->where('codigo', $codigo)->first();

        if ($archivo) {
            Storage::disk('local')->delete($archivo->ruta_archivo);
            $archivo->delete();
        }

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Archivo eliminado.',
        ]);
    }

    public function enviar(string $token): RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if (! $this->puedeEnviar($candidato)) {
            throw ValidationException::withMessages([
                'requisitos' => 'Aún faltan requisitos obligatorios por subir.',
            ]);
        }

        if (in_array($candidato->estado, [
            CandidatoEstudiante::ESTADO_APROBADO,
            CandidatoEstudiante::ESTADO_PAGADO,
            CandidatoEstudiante::ESTADO_RECHAZADO,
            CandidatoEstudiante::ESTADO_EN_REVISION,
        ], true)) {
            return back()->with('flash', [
                'type'    => 'error',
                'message' => 'Tu solicitud ya fue enviada o procesada.',
            ]);
        }

        $candidato->update(['estado' => CandidatoEstudiante::ESTADO_EN_REVISION]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Requisitos enviados. La coordinación revisará tu documentación.',
        ]);
    }

    public function descargar(string $token, string $codigo): StreamedResponse
    {
        $candidato = $this->candidatoPorToken($token);
        $archivo   = $candidato->requisitos()->where('codigo', $codigo)->firstOrFail();

        return Storage::disk('local')->download($archivo->ruta_archivo, $archivo->nombre_original);
    }

    private function candidatoPorToken(string $token): Model
    {
        $candidato = CandidatoEstudiante::where('token_acceso', $token)->with('persona')->first()
            ?? CandidatoDocente::where('token_acceso', $token)->with('persona')->first();

        if (! $candidato) {
            abort(404, 'Link inválido o expirado.');
        }

        return $candidato;
    }

    private function verificarEditable(Model $candidato, string $codigo): void
    {
        if (in_array($candidato->estado, [
            CandidatoEstudiante::ESTADO_APROBADO,
            CandidatoEstudiante::ESTADO_PAGADO,
            CandidatoEstudiante::ESTADO_RECHAZADO,
        ], true)) {
            abort(403, 'Esta solicitud ya fue cerrada.');
        }

        if ($candidato->estado === CandidatoEstudiante::ESTADO_EN_REVISION) {
            abort(403, 'Tu solicitud está en revisión. Espera la respuesta del administrador.');
        }

        if ($candidato->estado === CandidatoEstudiante::ESTADO_REQUIERE_CORRECCIONES) {
            $archivo = $candidato->requisitos()->where('codigo', $codigo)->first();
            $estadoAprobado = $candidato instanceof CandidatoEstudiante
                ? RequisitoEstudiante::ESTADO_APROBADO
                : RequisitoDocente::ESTADO_APROBADO;

            if ($archivo && $archivo->estado === $estadoAprobado) {
                abort(403, 'Este requisito ya fue aprobado y no puede modificarse.');
            }
        }
    }

    private function puedeEnviar(Model $candidato): bool
    {
        $obligatorios = RequisitosCatalogo::codigosObligatorios($candidato);
        $subidos      = $candidato->requisitos()->pluck('codigo')->all();

        if (count(array_diff($obligatorios, $subidos)) > 0) {
            return false;
        }

        if ($candidato instanceof CandidatoDocente) {
            return ! empty($candidato->titulo) && $candidato->experiencia_anios !== null;
        }

        return true;
    }

    private function directorioCandidato(Model $candidato): string
    {
        $tipo = $candidato instanceof CandidatoEstudiante ? 'estudiantes' : 'docentes';

        return "requisitos/{$tipo}/{$candidato->id}";
    }
}
