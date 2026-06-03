<?php

namespace App\AdministracionSistema\Models;

use App\GestionEstudiantes\Models\Postulacion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'anio',
    'semestre',
    'estado',
    'fecha_inicio_inscripcion',
    'fecha_fin_inscripcion',
    'fecha_inicio_cursado',
    'fecha_fin_cursado',
])]
class Gestion extends Model
{
    protected $table = 'gestion';

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'semestre' => 'integer',
            'fecha_inicio_inscripcion' => 'date',
            'fecha_fin_inscripcion' => 'date',
            'fecha_inicio_cursado' => 'date',
            'fecha_fin_cursado' => 'date',
        ];
    }

    public function parametros(): HasMany
    {
        return $this->hasMany(Parametro::class, 'gestion_id');
    }

    public function cupos(): HasMany
    {
        return $this->hasMany(CupoCarrera::class, 'gestion_id');
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class, 'gestion_id');
    }

    /** Devuelve el valor de un parámetro por clave, o $default si no existe. */
    public function parametro(string $clave, mixed $default = null): mixed
    {
        return $this->parametros
            ->firstWhere('clave', $clave)
            ?->valor ?? $default;
    }

    public function getLabelAttribute(): string
    {
        return "{$this->anio}-{$this->semestre}";
    }
}
