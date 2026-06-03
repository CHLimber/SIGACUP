<?php

namespace App\Calificaciones\Models;

use App\GestionEstudiantes\Models\Postulacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluacion extends Model
{
    protected $table = 'evaluacion';

    protected $fillable = [
        'postulacion_id', 'codigo_materia', 'numero_examen', 'nota_cruda', 'peso',
    ];

    protected function casts(): array
    {
        return [
            'nota_cruda'    => 'decimal:2',
            'peso'          => 'decimal:2',
            'numero_examen' => 'integer',
        ];
    }

    public function postulacion(): BelongsTo
    {
        return $this->belongsTo(Postulacion::class);
    }
}
