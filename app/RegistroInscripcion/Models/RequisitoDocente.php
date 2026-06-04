<?php

namespace App\RegistroInscripcion\Models;

use App\OrganizacionAcademica\Models\CandidatoDocente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoDocente extends Model
{
    public const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    protected $table = 'requisito_docente';

    protected $fillable = [
        'candidato_docente_id', 'codigo',
        'nombre_original', 'ruta_archivo', 'mime_type', 'tamano',
        'estado', 'motivo_rechazo', 'revisado_at',
    ];

    protected function casts(): array
    {
        return [
            'revisado_at' => 'datetime',
        ];
    }

    public function candidatoDocente(): BelongsTo
    {
        return $this->belongsTo(CandidatoDocente::class);
    }
}
