<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidatoRechazadoDefinitivamente extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombreCompleto,
        public ?string $ci,
        public string $tipo,
        public string $motivo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Resolución de tu solicitud — SIGACUP');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.candidato-rechazado',
            with: [
                'nombreCompleto' => $this->nombreCompleto,
                'ci' => $this->ci,
                'tipo' => $this->tipo,
                'motivo' => $this->motivo,
            ],
        );
    }
}
