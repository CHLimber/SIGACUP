<?php

namespace App\Mail;

use App\GestionDocentes\Models\CandidatoDocente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudDocenteRecibida extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CandidatoDocente $candidato) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Solicitud de docencia recibida — SIGACUP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.solicitud-docente-recibida');
    }
}
