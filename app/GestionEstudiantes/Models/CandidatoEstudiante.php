<?php

namespace App\GestionEstudiantes\Models;

use App\Models\User;
use App\RegistroPublico\Models\RequisitoArchivo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'ci',
    'apellido',
    'nombres',
    'fecha_nacimiento',
    'sexo',
    'telefono',
    'email',
    'direccion',
    'carrera_primera_opcion',
    'carrera_segunda_opcion',
    'estado',
    'token_acceso',
    'aprobado_at',
    'rechazado_at',
    'motivo_rechazo',
    'token_pago',
    'monto_bs',
    'monto_usd',
    'tasa_cambio',
    'stripe_session_id',
    'stripe_payment_intent_id',
    'pagado_at',
    'numero_factura',
    'user_id',
])]
class CandidatoEstudiante extends Model
{
    public const ESTADO_PENDIENTE              = 'pendiente';
    public const ESTADO_EN_REVISION            = 'en_revision';
    public const ESTADO_REQUIERE_CORRECCIONES  = 'requiere_correcciones';
    public const ESTADO_APROBADO               = 'aprobado_pendiente_pago';
    public const ESTADO_PAGADO                 = 'pagado';
    public const ESTADO_RECHAZADO              = 'rechazado';

    protected $table = 'candidato_estudiante';

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'aprobado_at'      => 'datetime',
            'rechazado_at'     => 'datetime',
            'pagado_at'        => 'datetime',
            'monto_bs'         => 'decimal:2',
            'monto_usd'        => 'decimal:2',
            'tasa_cambio'      => 'decimal:4',
        ];
    }

    public function requisitos(): MorphMany
    {
        return $this->morphMany(RequisitoArchivo::class, 'candidato');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellido} {$this->nombres}";
    }
}
