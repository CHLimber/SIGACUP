<?php

namespace App\AdministracionSistema\Models;

use Illuminate\Database\Eloquent\Model;

class ParametroCatalogo extends Model
{
    protected $table = 'parametro_catalogo';

    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['clave', 'tipo', 'descripcion'];
}
