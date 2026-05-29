<?php

namespace App\RegistroPublico\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'candidato_type',
    'candidato_id',
    'codigo',
    'nombre_original',
    'ruta_archivo',
    'mime_type',
    'tamano',
    'estado',
    'motivo_rechazo',
    'revisado_at',
])]
class RequisitoArchivo extends Model
{
    public const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

    protected $table = 'requisitos_archivos';

    protected function casts(): array
    {
        return [
            'revisado_at' => 'datetime',
        ];
    }

    public function candidato(): MorphTo
    {
        return $this->morphTo();
    }
}
