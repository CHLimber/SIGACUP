<?php

namespace App\AdministracionSistema\Models;

use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gestion_id', 'clave', 'valor'])]
class Parametro extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Parámetro';

    protected $table = 'parametro';

    public function bitacoraNombreRegistro(): string
    {
        return (string) ($this->clave ?? '#'.$this->getKey());
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'gestion_id');
    }
}
