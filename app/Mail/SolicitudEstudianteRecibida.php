<?php

namespace App\Mail;

use App\RegistroInscripcion\Models\CandidatoEstudiante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudEstudianteRecibida extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CandidatoEstudiante $candidato) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Solicitud de inscripción al CUP recibida — SIGACUP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.solicitud-estudiante-recibida');
    }
}
