<?php

namespace App\OrganizacionAcademica\Models;

use App\AdministracionSistema\Models\Gestion;
use App\AdministracionSistema\Models\Materia;
use App\RegistroInscripcion\Models\Postulacion;
use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grupo extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Grupo';

    protected $table = 'grupo';

    protected $fillable = [
        'gestion_id', 'codigo_materia', 'nombre', 'horario_id', 'aula_id', 'capacidad_max',
    ];

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'codigo_materia', 'codigo');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function postulaciones(): BelongsToMany
    {
        return $this->belongsToMany(Postulacion::class, 'asignacion_grupo', 'grupo_id', 'postulacion_id')
            ->withTimestamps();
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'docente_grupo', 'grupo_id', 'docente_id')
            ->withTimestamps();
    }
}
