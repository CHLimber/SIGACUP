<?php

namespace App\GestionDocentes\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Docente extends Model
{
    protected $table = 'docente';

    protected $fillable = [
        'user_id', 'titulo', 'experiencia_anios', 'tiene_diplomado', 'tiene_maestria',
    ];

    protected function casts(): array
    {
        return [
            'tiene_diplomado'    => 'boolean',
            'tiene_maestria'     => 'boolean',
            'experiencia_anios'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
