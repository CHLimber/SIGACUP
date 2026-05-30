<?php

namespace App\Mail;

use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\InscripcionPagos\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstudianteAprobadoConPago extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CandidatoEstudiante $candidato,
        public Pago $pago,
    ) {}

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
                'pago'      => $this->pago,
                'pagoUrl'   => route('portal.matricula.show', ['token' => $this->pago->token_pago]),
            ],
        );
    }
}
