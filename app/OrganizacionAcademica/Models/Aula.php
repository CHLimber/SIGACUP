<?php

namespace App\OrganizacionAcademica\Models;

use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aula extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Aula';

    protected $table = 'aula';

    protected $fillable = ['nombre', 'capacidad', 'modulo'];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}
