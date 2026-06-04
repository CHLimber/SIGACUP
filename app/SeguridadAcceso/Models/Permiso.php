<?php

namespace App\SeguridadAcceso\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    protected $table = 'permiso';

    protected $fillable = ['nombre', 'label', 'grupo', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'permiso_rol', 'permiso_id', 'rol_id')
            ->withTimestamps();
    }
}
