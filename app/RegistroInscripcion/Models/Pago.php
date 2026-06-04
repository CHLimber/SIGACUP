<?php

namespace App\RegistroInscripcion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pago';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_COMPLETADO = 'completado';

    public const ESTADO_FALLIDO = 'fallido';

    protected $fillable = [
        'postulacion_id', 'token_pago',
        'monto_bs', 'monto_usd', 'tasa_cambio',
        'metodo', 'stripe_session_id', 'stripe_payment_intent_id',
        'referencia_externa', 'numero_factura', 'estado', 'fecha',
    ];

    protected function casts(): array
    {
        return [
            'monto_bs' => 'decimal:2',
            'monto_usd' => 'decimal:2',
            'tasa_cambio' => 'decimal:4',
            'fecha' => 'datetime',
        ];
    }

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }
}
