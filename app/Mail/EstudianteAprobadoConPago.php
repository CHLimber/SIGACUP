<?php

namespace App\Mail;

use App\GestionEstudiantes\Models\CandidatoEstudiante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstudianteAprobadoConPago extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CandidatoEstudiante $candidato) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Solicitud aprobada! — Realiza el pago de tu matrícula CUP');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.estudiante-aprobado-con-pago',
            with: [
                'candidato' => $this->candidato,
                'pagoUrl'   => route('portal.matricula.show', ['token' => $this->candidato->token_pago]),
            ],
        );
    }
}
