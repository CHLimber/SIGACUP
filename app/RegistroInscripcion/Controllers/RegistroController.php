<?php

namespace App\RegistroInscripcion\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SolicitudDocenteRecibida;
use App\Mail\SolicitudEstudianteRecibida;
use App\Models\Persona;
use App\OrganizacionAcademica\Models\CandidatoDocente;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
use App\RegistroInscripcion\Requests\RegistrarCandidatoDocenteRequest;
use App\RegistroInscripcion\Requests\RegistrarCandidatoEstudianteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistroController extends Controller
{
    // CU05 — Registrar postulante | CU20 — Enviar notificaciones automáticas (correo de recepción al estudiante)
    public function storeCandidatoEstudiante(RegistrarCandidatoEstudianteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $candidato = DB::transaction(function () use ($data) {
            $persona = Persona::create([
                'ci' => $data['ci'],
                'apellido' => $data['apellido'],
                'nombres' => $data['nombres'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'sexo' => $data['sexo'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'direccion' => $data['direccion'],
            ]);

            // La postulación (carreras y datos académicos) se completa luego
            // desde el portal del candidato, vía el link tokenizado.
            return CandidatoEstudiante::create([
                'persona_id' => $persona->id,
                'estado' => CandidatoEstudiante::ESTADO_PENDIENTE,
                'token_acceso' => Str::random(64),
            ]);
        });

        $candidato->load('persona');

        if ($candidato->email) {
            try {
                Mail::to($candidato->email)->send(new SolicitudEstudianteRecibida($candidato));
            } catch (\Throwable) {
            }
        }

        return redirect()->route('home')
            ->with('flash', [
                'type' => 'success',
                'message' => "Solicitud recibida. Te contactaremos cuando sea revisada. Tu CI ({$candidato->ci}) es tu identificador en el portal.",
            ]);
    }

    // CU23 — Registrar candidato docente | CU20 — Enviar notificaciones automáticas (correo de recepción al docente)
    public function storeCandidatoDocente(RegistrarCandidatoDocenteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $candidato = DB::transaction(function () use ($data) {
            $persona = Persona::create([
                'ci' => $data['ci'],
                'apellido' => $data['apellido'],
                'nombres' => $data['nombres'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'sexo' => $data['sexo'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'direccion' => $data['direccion'],
            ]);

            return CandidatoDocente::create([
                'persona_id' => $persona->id,
                'estado' => CandidatoDocente::ESTADO_PENDIENTE,
                'token_acceso' => Str::random(64),
            ]);
        });

        $candidato->load('persona');

        try {
            Mail::to($candidato->email)->send(new SolicitudDocenteRecibida($candidato));
        } catch (\Throwable) {
        }

        return redirect()->route('home')
            ->with('flash', [
                'type' => 'success',
                'message' => "Solicitud recibida. Revisaremos tus datos y, si es aprobada, te enviaremos las credenciales de acceso a {$candidato->email}.",
            ]);
    }
}
