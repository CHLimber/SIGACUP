<?php

namespace App\AdministracionSistema\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carrera';

    protected $fillable = ['nombre'];
}
