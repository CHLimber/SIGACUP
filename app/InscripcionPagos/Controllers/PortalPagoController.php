<?php

namespace App\InscripcionPagos\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\InscripcionPagos\Models\Pago;
use App\Mail\EstudianteCredencialesMatricula;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\StripeClient;

class PortalPagoController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $pago = $this->pagoPorToken($token);

        if ($pago->estado === Pago::ESTADO_COMPLETADO) {
            return redirect()->route('portal.matricula.comprobante', ['token' => $token]);
        }

        $candidato = $pago->postulacion->candidatoEstudiante;
        $carrera   = $pago->postulacion->carrera1;

        return Inertia::render('Portal/Pago', [
            'token'     => $token,
            'candidato' => [
                'nombre_completo' => $candidato->nombre_completo,
                'ci'              => $candidato->ci,
                'email'           => $candidato->email,
                'carrera'         => $carrera?->nombre,
            ],
            'matricula' => [
                'monto_bs'    => (float) $pago->monto_bs,
                'monto_usd'   => (float) $pago->monto_usd,
                'descripcion' => config('sigacup.matricula.descripcion'),
            ],
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    public function crearPaymentIntent(string $token): JsonResponse
    {
        $pago = $this->pagoPorToken($token);

        if ($pago->estado !== Pago::ESTADO_PENDIENTE) {
            return response()->json(['error' => 'Esta solicitud no está disponible para pago.'], 422);
        }

        $candidato = $pago->postulacion->candidatoEstudiante;

        $stripe = new StripeClient(config('services.stripe.secret'));

        $intent = $stripe->paymentIntents->create([
            'amount'   => (int) round((float) $pago->monto_usd * 100),
            'currency' => config('sigacup.matricula.moneda_stripe'),
            'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
            'description'   => config('sigacup.matricula.descripcion'),
            'receipt_email' => $candidato->email,
            'metadata'      => [
                'pago_id'    => $pago->id,
                'token_pago' => $token,
                'ci'         => $candidato->ci,
                'monto_bs'   => (string) $pago->monto_bs,
            ],
        ]);

        $pago->update(['stripe_payment_intent_id' => $intent->id]);

        return response()->json(['client_secret' => $intent->client_secret]);
    }

    public function confirmar(string $token, Request $request): RedirectResponse
    {
        $pago = $this->pagoPorToken($token);

        if ($pago->estado === Pago::ESTADO_COMPLETADO) {
            return redirect()->route('portal.matricula.comprobante', ['token' => $token]);
        }

        $paymentIntentId = $request->input('payment_intent_id');

        if (! $paymentIntentId) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No se recibió el identificador del pago.']);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $intent = $stripe->paymentIntents->retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            return back()->with('flash', ['type' => 'error', 'message' => 'El pago no se completó. Verifica los datos de tu tarjeta.']);
        }

        $passwordTemporal = Str::random(12);

        DB::transaction(function () use ($pago, $intent, $passwordTemporal, $token) {
            $candidato = $pago->postulacion->candidatoEstudiante;
            $persona   = $candidato->persona;

            $user = User::create([
                'persona_id' => $persona->id,
                'name'       => $persona->apellido . ' ' . $persona->nombres,
                'email'      => $persona->email,
                'password'   => $passwordTemporal,
                'role'       => UserRole::Estudiante,
                'username'   => 'tmp_' . uniqid(),
            ]);

            $apellido     = strtolower(Str::ascii($persona->apellido));
            $primerNombre = strtolower(Str::ascii(Str::of($persona->nombres)->explode(' ')->first()));
            $user->username = $apellido . $primerNombre . $user->id;
            $user->save();

            $pago->update([
                'estado'                   => Pago::ESTADO_COMPLETADO,
                'stripe_payment_intent_id' => $intent->id,
                'numero_factura'           => sprintf('FACT-%s-%06d', now()->format('Y'), $pago->id),
            ]);

            $pago->postulacion->update(['estado_pago' => 'completado']);

            $candidato->update([
                'estado'  => \App\GestionEstudiantes\Models\CandidatoEstudiante::ESTADO_PAGADO,
                'user_id' => $user->id,
            ]);

            Mail::to($persona->email)->send(
                new EstudianteCredencialesMatricula($pago->fresh()->load('postulacion.candidatoEstudiante.persona'), $user->username, $passwordTemporal),
            );
        });

        return redirect()->route('portal.matricula.comprobante', ['token' => $token])
            ->with('flash', [
                'type'    => 'success',
                'message' => '¡Pago confirmado! Te enviamos tus credenciales por correo.',
            ]);
    }

    public function comprobante(string $token): HttpResponse
    {
        $pago = $this->pagoPorToken($token);

        if ($pago->estado !== Pago::ESTADO_COMPLETADO) {
            abort(404, 'Aún no hay comprobante disponible para este pago.');
        }

        $pago->load(['postulacion.candidatoEstudiante.persona', 'postulacion.carrera1']);

        return response()->view('portal.comprobante-matricula', [
            'pago' => $pago,
        ]);
    }

    private function pagoPorToken(string $token): Pago
    {
        $pago = Pago::where('token_pago', $token)
            ->with(['postulacion.candidatoEstudiante.persona', 'postulacion.carrera1'])
            ->first();

        if (! $pago) {
            abort(404, 'Link de pago inválido o expirado.');
        }

        return $pago;
    }
}
