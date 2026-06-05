<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsuarioCredencialesGeneradas extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $username,
        public string $passwordTemporal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Credenciales de acceso — SIGACUP FICCT');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.usuario-credenciales-generadas');
    }
}
