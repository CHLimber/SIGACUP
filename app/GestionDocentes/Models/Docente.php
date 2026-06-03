<?php

namespace App\GestionDocentes\Models;

use App\Models\User;
use App\OrganizacionAcademica\Models\Grupo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Docente extends Model
{
    protected $table = 'docente';

    protected $fillable = [
        'user_id', 'titulo', 'experiencia_anios', 'tiene_diplomado', 'tiene_maestria',
    ];

    protected function casts(): array
    {
        return [
            'tiene_diplomado' => 'boolean',
            'tiene_maestria' => 'boolean',
            'experiencia_anios' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'docente_grupo', 'docente_id', 'grupo_id')
            ->withTimestamps();
    }
}
