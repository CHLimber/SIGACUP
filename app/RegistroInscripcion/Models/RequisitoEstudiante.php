<?php

namespace App\RegistroInscripcion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoEstudiante extends Model
{
    public const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    protected $table = 'requisito_estudiante';

    protected $fillable = [
        'candidato_estudiante_id', 'codigo',
        'nombre_original', 'ruta_archivo', 'mime_type', 'tamano',
        'estado', 'motivo_rechazo', 'revisado_at',
    ];

    protected function casts(): array
    {
        return [
            'revisado_at' => 'datetime',
        ];
    }

    public function candidatoEstudiante(): BelongsTo
    {
        return $this->belongsTo(CandidatoEstudiante::class);
    }
}
