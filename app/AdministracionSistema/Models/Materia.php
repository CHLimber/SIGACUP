<?php

namespace App\AdministracionSistema\Models;

use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Materia';

    protected $table = 'materia';

    protected $primaryKey = 'codigo';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['codigo', 'nombre'];
}
