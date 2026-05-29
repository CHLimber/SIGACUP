<?php

namespace App\Mail;

use App\GestionEstudiantes\Models\CandidatoEstudiante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstudianteCredencialesMatricula extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CandidatoEstudiante $candidato,
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
                'candidato'        => $this->candidato,
                'username'         => $this->username,
                'passwordTemporal' => $this->passwordTemporal,
                'comprobanteUrl'   => route('portal.matricula.comprobante', ['token' => $this->candidato->token_pago]),
            ],
        );
    }
}
