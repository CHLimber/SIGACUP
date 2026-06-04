<?php

namespace App\AdministracionSistema\Models;

use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupoCarrera extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Cupo de carrera';

    protected $table = 'cupo_carrera';

    protected $fillable = ['carrera_id', 'gestion_id', 'cupo_max'];

    protected function casts(): array
    {
        return [
            'cupo_max' => 'integer',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }
}
