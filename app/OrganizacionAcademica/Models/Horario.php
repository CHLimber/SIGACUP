<?php

namespace App\OrganizacionAcademica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = 'horario';

    protected $fillable = ['aplica_todos_dias', 'dia', 'hora_inicio', 'hora_fin'];

    protected function casts(): array
    {
        return [
            'aplica_todos_dias' => 'boolean',
        ];
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}
