<?php

namespace App\OrganizacionAcademica\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aula extends Model
{
    protected $table = 'aula';

    protected $fillable = ['nombre', 'capacidad', 'modulo'];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}
