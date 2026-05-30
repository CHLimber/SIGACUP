<?php

namespace App\Mail;

use App\InscripcionPagos\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstudianteCredencialesMatricula extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pago $pago,
        public string $username,
        public string $passwordTemporal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Pago confirmado! — Credenciales de acceso al CUP');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.estudiante-credenciales-matricula',
            with: [
                'pago'             => $this->pago,
                'username'         => $this->username,
                'passwordTemporal' => $this->passwordTemporal,
                'comprobanteUrl'   => route('portal.matricula.comprobante', ['token' => $this->pago->token_pago]),
            ],
        );
    }
}
