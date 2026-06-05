<?php

namespace App\Mail;

use App\RegistroInscripcion\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstudianteMatriculaConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Pago $pago,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Pago confirmado! — Matrícula al CUP registrada');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.estudiante-matricula-confirmada',
            with: [
                'pago' => $this->pago,
                'comprobanteUrl' => route('portal.matricula.comprobante', ['token' => $this->pago->token_pago]),
            ],
        );
    }
}
