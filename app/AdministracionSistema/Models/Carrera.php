<?php

namespace App\AdministracionSistema\Models;

use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Carrera';

    protected $table = 'carrera';

    protected $fillable = ['nombre'];
}
