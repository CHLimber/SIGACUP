<?php

namespace App\GestionEstudiantes\Models;

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\Calificaciones\Models\Evaluacion;
use App\InscripcionPagos\Models\Pago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Postulacion extends Model
{
    public const ADMISION_PENDIENTE = 'pendiente';

    public const ADMISION_ADMITIDO = 'admitido';

    public const ADMISION_NO_ADMITIDO = 'no_admitido';

    protected $table = 'postulacion';

    protected $fillable = [
        'candidato_estudiante_id', 'gestion_id',
        'carrera1_id', 'carrera2_id',
        'estado_pago', 'estado_cup',
        'promedio_general', 'carrera_asignada_id', 'estado_admision',
    ];

    protected function casts(): array
    {
        return [
            'promedio_general' => 'decimal:2',
        ];
    }

    public function candidatoEstudiante(): BelongsTo
    {
        return $this->belongsTo(CandidatoEstudiante::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function carrera1(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera1_id');
    }

    public function carrera2(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera2_id');
    }

    public function carreraAsignada(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_asignada_id');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }
}
