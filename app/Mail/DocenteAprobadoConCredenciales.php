<?php

namespace App\Mail;

use App\OrganizacionAcademica\Models\CandidatoDocente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocenteAprobadoConCredenciales extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CandidatoDocente $candidato,
        public string $username,
        public string $passwordTemporal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Bienvenido al equipo docente! — Credenciales de acceso SIGACUP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.docente-aprobado-credenciales');
    }
}
