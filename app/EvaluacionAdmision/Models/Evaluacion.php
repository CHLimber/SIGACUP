<?php

namespace App\EvaluacionAdmision\Models;

use App\RegistroInscripcion\Models\Postulacion;
use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluacion extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Evaluación';

    protected $table = 'evaluacion';

    public function bitacoraNombreRegistro(): string
    {
        return trim("{$this->codigo_materia} examen {$this->numero_examen}") ?: '#'.$this->getKey();
    }

    protected $fillable = [
        'postulacion_id', 'codigo_materia', 'numero_examen', 'nota_cruda', 'peso',
    ];

    protected function casts(): array
    {
        return [
            'nota_cruda' => 'decimal:2',
            'peso' => 'decimal:2',
            'numero_examen' => 'integer',
        ];
    }

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }
}
