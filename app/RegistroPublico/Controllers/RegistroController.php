<?php

namespace App\RegistroPublico\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\GestionEstudiantes\Models\Postulacion;
use App\Http\Controllers\Controller;
use App\Mail\SolicitudDocenteRecibida;
use App\Mail\SolicitudEstudianteRecibida;
use App\Models\Persona;
use App\RegistroPublico\Requests\RegistrarCandidatoDocenteRequest;
use App\RegistroPublico\Requests\RegistrarCandidatoEstudianteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistroController extends Controller
{
    public function storeCandidatoEstudiante(RegistrarCandidatoEstudianteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $candidato = DB::transaction(function () use ($data) {
            $persona = Persona::create([
                'ci'               => $data['ci'],
                'apellido'         => $data['apellido'],
                'nombres'          => $data['nombres'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'sexo'             => $data['sexo'],
                'telefono'         => $data['telefono'],
                'email'            => $data['email'],
                'direccion'        => $data['direccion'],
            ]);

            $candidato = CandidatoEstudiante::create([
                'persona_id'   => $persona->id,
                'estado'       => CandidatoEstudiante::ESTADO_PENDIENTE,
                'token_acceso' => Str::random(64),
            ]);

            $gestion = Gestion::where('estado', 'inscripcion')
                ->orWhere('estado', 'configuracion')
                ->orderByDesc('anio')
                ->orderByDesc('semestre')
                ->first();

            if ($gestion) {
                Postulacion::create([
                    'candidato_estudiante_id' => $candidato->id,
                    'gestion_id'              => $gestion->id,
                    'carrera1_id'             => $data['carrera1_id'],
                    'carrera2_id'             => $data['carrera2_id'] ?? null,
                ]);
            }

            return $candidato;
        });

        $candidato->load('persona');

        if ($candidato->email) {
            Mail::to($candidato->email)->send(new SolicitudEstudianteRecibida($candidato));
        }

        return redirect()->route('home')
            ->with('flash', [
                'type'    => 'success',
                'message' => "Solicitud recibida. Te contactaremos cuando sea revisada. Tu CI ({$candidato->ci}) es tu identificador en el portal.",
            ]);
    }

    public function storeCandidatoDocente(RegistrarCandidatoDocenteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $candidato = DB::transaction(function () use ($data) {
            $persona = Persona::create([
                'ci'               => $data['ci'],
                'apellido'         => $data['apellido'],
                'nombres'          => $data['nombres'],
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'sexo'             => $data['sexo'],
                'telefono'         => $data['telefono'],
                'email'            => $data['email'],
                'direccion'        => $data['direccion'],
            ]);

            return CandidatoDocente::create([
                'persona_id'   => $persona->id,
                'estado'       => CandidatoDocente::ESTADO_PENDIENTE,
                'token_acceso' => Str::random(64),
            ]);
        });

        $candidato->load('persona');

        Mail::to($candidato->email)->send(new SolicitudDocenteRecibida($candidato));

        return redirect()->route('home')
            ->with('flash', [
                'type'    => 'success',
                'message' => "Solicitud recibida. Revisaremos tus datos y, si es aprobada, te enviaremos las credenciales de acceso a {$candidato->email}.",
            ]);
    }
}
