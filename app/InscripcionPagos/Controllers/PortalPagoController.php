<?php

namespace App\InscripcionPagos\Controllers;

use App\Enums\UserRole;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\Http\Controllers\Controller;
use App\Mail\EstudianteCredencialesMatricula;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;

class PortalPagoController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if ($candidato->estado === CandidatoEstudiante::ESTADO_PAGADO) {
            return redirect()->route('portal.matricula.comprobante', ['token' => $token]);
        }

        return Inertia::render('Portal/Pago', [
            'token'     => $token,
            'candidato' => [
                'nombre_completo' => $candidato->nombre_completo,
                'ci'              => $candidato->ci,
                'email'           => $candidato->email,
                'carrera'         => $candidato->carrera_primera_opcion,
            ],
            'matricula' => [
                'monto_bs'    => (float) $candidato->monto_bs,
                'monto_usd'   => (float) $candidato->monto_usd,
                'tasa_cambio' => (float) $candidato->tasa_cambio,
                'descripcion' => config('sigacup.matricula.descripcion'),
            ],
        ]);
    }

    public function iniciar(string $token): RedirectResponse|HttpResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if ($candidato->estado !== CandidatoEstudiante::ESTADO_APROBADO) {
            return redirect()->route('portal.matricula.show', ['token' => $token]);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $candidato->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => config('sigacup.matricula.moneda_stripe'),
                    'unit_amount' => (int) round($candidato->monto_usd * 100),
                    'product_data' => [
                        'name'        => config('sigacup.matricula.descripcion'),
                        'description' => "Candidato: {$candidato->nombre_completo} · CI {$candidato->ci} · Equivalente Bs {$candidato->monto_bs} (tasa {$candidato->tasa_cambio})",
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('portal.matricula.exitoso', ['token' => $token]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('portal.matricula.cancelado', ['token' => $token]),
            'metadata' => [
                'candidato_id' => $candidato->id,
                'token_pago'   => $token,
                'ci'           => $candidato->ci,
            ],
        ]);

        $candidato->update(['stripe_session_id' => $session->id]);

        return Inertia::location($session->url);
    }

    public function exitoso(string $token, \Illuminate\Http\Request $request): RedirectResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if ($candidato->estado === CandidatoEstudiante::ESTADO_PAGADO) {
            return redirect()->route('portal.matricula.comprobante', ['token' => $token]);
        }

        $sessionId = $request->query('session_id');
        if (! $sessionId) {
            return redirect()->route('portal.matricula.show', ['token' => $token])
                ->with('flash', ['type' => 'error', 'message' => 'No se pudo verificar el pago. Inténtalo nuevamente.']);
        }

        $stripe  = new StripeClient(config('services.stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return redirect()->route('portal.matricula.show', ['token' => $token])
                ->with('flash', ['type' => 'error', 'message' => 'El pago aún no se ha confirmado.']);
        }

        $passwordTemporal = Str::random(12);

        DB::transaction(function () use ($candidato, $session, $passwordTemporal) {
            $user = User::create([
                'name'             => $candidato->apellido . ' ' . $candidato->nombres,
                'email'            => $candidato->email,
                'password'         => $passwordTemporal,
                'role'             => UserRole::Estudiante,
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
                'estado'                   => CandidatoEstudiante::ESTADO_PAGADO,
                'pagado_at'                => now(),
                'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : ($session->payment_intent->id ?? null),
                'numero_factura'           => $this->generarNumeroFactura($candidato),
                'user_id'                  => $user->id,
            ]);

            Mail::to($candidato->email)->send(
                new EstudianteCredencialesMatricula($candidato->fresh(), $user->username, $passwordTemporal),
            );
        });

        return redirect()->route('portal.matricula.comprobante', ['token' => $token])
            ->with('flash', [
                'type'    => 'success',
                'message' => '¡Pago confirmado! Te enviamos tus credenciales por correo.',
            ]);
    }

    public function cancelado(string $token): RedirectResponse
    {
        return redirect()->route('portal.matricula.show', ['token' => $token])
            ->with('flash', [
                'type'    => 'error',
                'message' => 'El pago fue cancelado. Puedes intentarlo nuevamente cuando quieras.',
            ]);
    }

    public function comprobante(string $token): HttpResponse
    {
        $candidato = $this->candidatoPorToken($token);

        if ($candidato->estado !== CandidatoEstudiante::ESTADO_PAGADO) {
            abort(404, 'Aún no hay comprobante disponible para este candidato.');
        }

        return response()->view('portal.comprobante-matricula', [
            'candidato' => $candidato,
        ]);
    }

    private function candidatoPorToken(string $token): CandidatoEstudiante
    {
        $candidato = CandidatoEstudiante::where('token_pago', $token)->first();

        if (! $candidato) {
            abort(404, 'Link de pago inválido o expirado.');
        }

        return $candidato;
    }

    private function generarNumeroFactura(CandidatoEstudiante $candidato): string
    {
        return sprintf('FACT-%s-%06d', now()->format('Y'), $candidato->id);
    }
}
