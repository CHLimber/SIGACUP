<?php

namespace App\GestionDocentes\Models;

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
    'estado',
    'token_acceso',
    'user_id',
    'aprobado_at',
    'rechazado_at',
    'motivo_rechazo',
])]
class CandidatoDocente extends Model
{
    public const ESTADO_PENDIENTE              = 'pendiente';
    public const ESTADO_EN_REVISION            = 'en_revision';
    public const ESTADO_REQUIERE_CORRECCIONES  = 'requiere_correcciones';
    public const ESTADO_APROBADO               = 'aprobado';
    public const ESTADO_RECHAZADO              = 'rechazado';

    protected $table = 'candidato_docente';

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'aprobado_at'      => 'datetime',
            'rechazado_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requisitos(): MorphMany
    {
        return $this->morphMany(RequisitoArchivo::class, 'candidato');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellido} {$this->nombres}";
    }
}
