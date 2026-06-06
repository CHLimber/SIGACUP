<?php

namespace App\SeguridadAcceso\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraDetalle extends Model
{
    protected $table = 'bitacora_detalle';

    public $timestamps = false;

    protected $fillable = ['bitacora_id', 'campo', 'valor_anterior', 'valor_nuevo'];

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(Bitacora::class, 'bitacora_id');
    }
}
