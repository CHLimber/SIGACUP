<?php

namespace App\SeguridadAcceso\Models;

use App\Models\User;
use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Rol';

    protected $table = 'rol';

    protected $fillable = ['nombre', 'label', 'descripcion', 'es_sistema'];

    protected function casts(): array
    {
        return [
            'es_sistema' => 'boolean',
        ];
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'permiso_rol', 'rol_id', 'permiso_id')
            ->withTimestamps();
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'nombre');
    }
}
