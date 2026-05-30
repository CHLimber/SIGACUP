<?php

namespace App\AdministracionSistema\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gestion_id', 'clave', 'valor'])]
class Parametro extends Model
{
    protected $table = 'parametro';

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'gestion_id');
    }
}
