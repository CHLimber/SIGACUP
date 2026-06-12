<?php

namespace App\RegistroInscripcion\Controllers;

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\AdministracionSistema\Models\Materia;
use App\Http\Controllers\Controller;
use App\OrganizacionAcademica\Models\CandidatoDocente;
use App\RegistroInscripcion\Catalogos\RequisitosCatalogo;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
use App\RegistroInscripcion\Models\Postulacion;
use App\RegistroInscripcion\Models\RequisitoDocente;
use App\RegistroInscripcion\Models\RequisitoEstudiante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalCandidatoController extends Controller
{
    public function show(string $token): Response
    {
        $candidato = $this->candidatoPorToken($token);
        $catalogo = RequisitosCatalogo::paraCandidato($candidato);
        $archivos = $candidato->requisitos()->get()->keyBy('codigo');

        $requisitos = collect($catalogo)->map(function (array $def, string $codigo) use ($archivos): array {
            $archivo = $archivos->get($codigo);

            return [
                'codigo' => $codigo,
                'nombre' => $def['nombre'],
                'descripcion' => $def['descripcion'],
                'obligatorio' => $def['obligatorio'],
                'mimes' => $def['mimes'],
                'tamano_max_kb' => RequisitosCatalogo::TAMANO_MAX_KB,
                'archivo' => $archivo ? [
                    'id' => $archivo->id,
                    'nombre_original' => $archivo->nombre_original,
                    'estado' => $archivo->estado,
                    'motivo_rechazo' => $archivo->motivo_rechazo,
                    'subido_at' => $archivo->created_at?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();

        $esDocente = $candidato instanceof CandidatoDocente;

        $postulacion = $esDocente ? null : $candidato->postulacion;

        return Inertia::render('RegistroInscripcion/Portal/Requisitos', [
            'token' => $token,
            'candidato' => [
                'tipo' => $esDocente ? 'docente' : 'estudiante',
                'nombre_completo' => $candidato->nombre_completo,
                'ci' => $candidato->ci,
                'email' => $candidato->email,
                'estado' => $candidato->estado,
                'motivo_rechazo' => $candidato->motivo_rechazo,
            ],
            'datosProfesionales' => $esDocente ? [
                'titulo' => $candidato->titulo ?? '',
                'experiencia_anios' => $candidato->experiencia_anios ?? 0,
                'tiene_diplomado' => (bool) $candidato->tiene_diplomado,
                'tiene_maestria' => (bool) $candidato->tiene_maestria,
                'materias' => $candidato->materias()->pluck('materia.codigo')->all(),
            ] : null,
            'datosAcademicos' => $esDocente ? null : [
                'carrera1_id' => $postulacion?->carrera1_id,
                'carrera2_id' => $postulacion?->carrera2_id,
                'anio_egreso' => $postulacion?->anio_egreso,
                'unidad_educativa' => $postulacion?->unidad_educativa ?? '',
                'tipo_colegio' => $postulacion?->tipo_colegio ?? '',
            ],
            'carreras' => $esDocente
                ? []
                : Carrera::orderBy('nombre')->get(['id', 'nombre']),
            'materias' => $esDocente
                ? Materia::orderBy('nombre')->get(['codigo', 'nombre'])
                : [],
            'requisitos' => $requisitos,
            'puedeEnviar' => $this->puedeEnviar($candidato),
            'bloqueado' => in_array($candidato->estado, [
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
            'titulo' => 'required|string|max:120',
            'experiencia_anios' => 'required|integer|min:0|max:60',
            'tiene_diplomado' => 'required|boolean',
            'tiene_maestria' => 'required|boolean',
            'materias' => 'required|array|min:1',
            'materias.*' => 'string|exists:materia,codigo',
        ], [
            'titulo.required' => 'El título profesional es obligatorio.',
            'experiencia_anios.required' => 'Indica tus años de experiencia.',
            'materias.required' => 'Selecciona al menos una materia que postulas a enseñar.',
            'materias.min' => 'Selecciona al menos una materia que postulas a enseñar.',
        ]);

        $materias = $data['materias'];
        unset($data['materias']);

        $candidato->update($data);
        $candidato->materias()->sync($materias);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Datos profesionales guardados.',
        ]);
    }

    public function guardarDatosAcademicos(Request $request, string $token): RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if (! $candidato instanceof CandidatoEstudiante) {
            abort(404);
        }

        if (in_array($candidato->estado, [
            CandidatoEstudiante::ESTADO_APROBADO,
            CandidatoEstudiante::ESTADO_PAGADO,
            CandidatoEstudiante::ESTADO_RECHAZADO,
            CandidatoEstudiante::ESTADO_EN_REVISION,
        ], true)) {
            abort(403, 'No puedes editar tus datos mientras la solicitud está bloqueada.');
        }

        $data = $request->validate([
            'carrera1_id' => ['required', 'integer', 'exists:carrera,id'],
            'carrera2_id' => ['required', 'integer', 'exists:carrera,id', 'different:carrera1_id'],
            'anio_egreso' => ['required', 'integer', 'min:1980', 'max:'.now()->year],
            'unidad_educativa' => ['required', 'string', 'max:150'],
            'tipo_colegio' => ['required', 'string', Rule::in(['publica', 'privada', 'convenio'])],
        ], [
            'carrera1_id.required' => 'Selecciona tu primera opción de carrera.',
            'carrera2_id.required' => 'Selecciona tu segunda opción de carrera.',
            'carrera2_id.different' => 'La segunda opción debe ser distinta de la primera.',
            'anio_egreso.required' => 'Indica tu año de egreso del colegio.',
            'unidad_educativa.required' => 'Indica el nombre de tu unidad educativa.',
            'tipo_colegio.required' => 'Indica el tipo de colegio.',
            'tipo_colegio.in' => 'El tipo de colegio debe ser pública, privada o convenio.',
        ]);

        $gestion = Gestion::where('estado', 'inscripcion')
            ->orWhere('estado', 'configuracion')
            ->orderByDesc('anio')
            ->orderByDesc('semestre')
            ->first();

        if (! $gestion) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'No hay una gestión con inscripciones abiertas. Contacta a la coordinación.',
            ]);
        }

        Postulacion::updateOrCreate(
            [
                'candidato_estudiante_id' => $candidato->id,
                'gestion_id' => $gestion->id,
            ],
            [
                'carrera1_id' => $data['carrera1_id'],
                'carrera2_id' => $data['carrera2_id'],
                'anio_egreso' => $data['anio_egreso'],
                'unidad_educativa' => $data['unidad_educativa'],
                'tipo_colegio' => $data['tipo_colegio'],
            ],
        );

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Datos académicos guardados.',
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
                'mimetypes:'.implode(',', $definicion['mimes']),
                'max:'.RequisitosCatalogo::TAMANO_MAX_KB,
            ],
        ], [
            'archivo.mimetypes' => 'El archivo debe ser de tipo: '.implode(', ', $definicion['mimes']).'.',
            'archivo.max' => 'El archivo no puede superar '.RequisitosCatalogo::TAMANO_MAX_KB.' KB.',
        ]);

        $file = $request->file('archivo');

        $directorio = $this->directorioCandidato($candidato);
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $ruta = $file->storeAs($directorio, $codigo.'.'.$extension, 'local');

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
                'ruta_archivo' => $ruta,
                'mime_type' => $file->getMimeType(),
                'tamano' => $file->getSize(),
                'estado' => $estadoPendiente,
                'motivo_rechazo' => null,
                'revisado_at' => null,
            ],
        );

        return back()->with('flash', [
            'type' => 'success',
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
            'type' => 'success',
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
                'type' => 'error',
                'message' => 'Tu solicitud ya fue enviada o procesada.',
            ]);
        }

        $candidato->update(['estado' => CandidatoEstudiante::ESTADO_EN_REVISION]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Requisitos enviados. La coordinación revisará tu documentación.',
        ]);
    }

    public function descargar(string $token, string $codigo): StreamedResponse
    {
        $candidato = $this->candidatoPorToken($token);
        $archivo = $candidato->requisitos()->where('codigo', $codigo)->firstOrFail();

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
        $subidos = $candidato->requisitos()->pluck('codigo')->all();

        if (count(array_diff($obligatorios, $subidos)) > 0) {
            return false;
        }

        if ($candidato instanceof CandidatoDocente) {
            return ! empty($candidato->titulo)
                && $candidato->experiencia_anios !== null
                && $candidato->materias()->exists();
        }

        $postulacion = $candidato->postulacion;

        return $postulacion !== null
            && $postulacion->carrera1_id !== null
            && $postulacion->carrera2_id !== null
            && $postulacion->anio_egreso !== null
            && ! empty($postulacion->unidad_educativa)
            && ! empty($postulacion->tipo_colegio);
    }

    private function directorioCandidato(Model $candidato): string
    {
        $tipo = $candidato instanceof CandidatoEstudiante ? 'estudiantes' : 'docentes';

        return "requisitos/{$tipo}/{$candidato->id}";
    }
}
