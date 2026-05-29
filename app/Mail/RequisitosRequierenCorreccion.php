<?php

namespace App\Mail;

use App\RegistroPublico\Catalogos\RequisitosCatalogo;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequisitosRequierenCorreccion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Model $candidato,
        public Collection $rechazados,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu solicitud requiere correcciones — SIGACUP');
    }

    public function content(): Content
    {
        $items = $this->rechazados->map(function ($archivo) {
            $def = RequisitosCatalogo::definicion($this->candidato, $archivo->codigo);

            return [
                'nombre'  => $def['nombre'] ?? $archivo->codigo,
                'motivo'  => $archivo->motivo_rechazo,
            ];
        })->all();

        return new Content(
            view: 'emails.requisitos-requieren-correccion',
            with: [
                'candidato' => $this->candidato,
                'items'     => $items,
                'portalUrl' => route('portal.candidato.show', ['token' => $this->candidato->token_acceso]),
            ],
        );
    }
}
