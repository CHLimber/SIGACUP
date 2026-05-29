<?php

namespace App\RegistroPublico\Controllers;

use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\Http\Controllers\Controller;
use App\Mail\SolicitudDocenteRecibida;
use App\Mail\SolicitudEstudianteRecibida;
use App\RegistroPublico\Requests\RegistrarCandidatoDocenteRequest;
use App\RegistroPublico\Requests\RegistrarCandidatoEstudianteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistroController extends Controller
{
    public function storeCandidatoEstudiante(RegistrarCandidatoEstudianteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $candidato = CandidatoEstudiante::create([
            ...$data,
            'estado'        => CandidatoEstudiante::ESTADO_PENDIENTE,
            'token_acceso'  => Str::random(64),
        ]);

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

        $candidato = CandidatoDocente::create([
            ...$data,
            'estado'       => CandidatoDocente::ESTADO_PENDIENTE,
            'token_acceso' => Str::random(64),
        ]);

        Mail::to($candidato->email)->send(new SolicitudDocenteRecibida($candidato));

        return redirect()->route('home')
            ->with('flash', [
                'type'    => 'success',
                'message' => "Solicitud recibida. Revisaremos tus datos y, si es aprobada, te enviaremos las credenciales de acceso a {$candidato->email}.",
            ]);
    }
}
