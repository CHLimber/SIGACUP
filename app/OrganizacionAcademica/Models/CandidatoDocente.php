<?php

namespace App\OrganizacionAcademica\Models;

use App\AdministracionSistema\Models\Materia;
use App\Models\Persona;
use App\Models\User;
use App\RegistroInscripcion\Models\RequisitoDocente;
use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidatoDocente extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Candidato a docente';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_REVISION = 'en_revision';

    public const ESTADO_REQUIERE_CORRECCIONES = 'requiere_correcciones';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    protected $table = 'candidato_docente';

    protected $fillable = [
        'persona_id', 'estado', 'token_acceso', 'motivo_rechazo', 'user_id',
        'titulo', 'experiencia_anios', 'tiene_diplomado', 'tiene_maestria',
    ];

    protected function casts(): array
    {
        return [
            'experiencia_anios' => 'integer',
            'tiene_diplomado' => 'boolean',
            'tiene_maestria' => 'boolean',
        ];
    }

    protected $appends = [
        'ci', 'apellido', 'nombres', 'fecha_nacimiento',
        'sexo', 'telefono', 'email', 'direccion',
        'nombre_completo',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoDocente::class);
    }

    /** Materias a las que postula enseñar. */
    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'candidato_docente_materia', 'candidato_docente_id', 'codigo_materia', 'id', 'codigo')
            ->withTimestamps();
    }

    // ── Accessors (delegados a persona) ─────────────────────────────────

    public function getCiAttribute(): ?string
    {
        return $this->persona?->ci;
    }

    public function getApellidoAttribute(): ?string
    {
        return $this->persona?->apellido;
    }

    public function getNombresAttribute(): ?string
    {
        return $this->persona?->nombres;
    }

    public function getFechaNacimientoAttribute(): mixed
    {
        return $this->persona?->fecha_nacimiento;
    }

    public function getSexoAttribute(): ?string
    {
        return $this->persona?->sexo;
    }

    public function getTelefonoAttribute(): ?string
    {
        return $this->persona?->telefono;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->persona?->email;
    }

    public function getDireccionAttribute(): ?string
    {
        return $this->persona?->direccion;
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(($this->persona?->apellido ?? '').' '.($this->persona?->nombres ?? ''));
    }
}
