<?php

namespace App\AdministracionSistema\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table      = 'materia';
    protected $primaryKey = 'codigo';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['codigo', 'nombre'];
}
