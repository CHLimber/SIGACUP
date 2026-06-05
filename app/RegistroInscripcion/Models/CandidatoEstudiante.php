<?php

namespace App\RegistroInscripcion\Models;

use App\Models\Persona;
use App\SeguridadAcceso\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CandidatoEstudiante extends Model
{
    use RegistraBitacora;

    protected string $bitacoraEtiqueta = 'Candidato a estudiante';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_REVISION = 'en_revision';

    public const ESTADO_REQUIERE_CORRECCIONES = 'requiere_correcciones';

    public const ESTADO_APROBADO = 'aprobado_pendiente_pago';

    public const ESTADO_PAGADO = 'pagado';

    public const ESTADO_RECHAZADO = 'rechazado';

    protected $table = 'candidato_estudiante';

    protected $fillable = [
        'persona_id', 'estado', 'token_acceso', 'motivo_rechazo',
    ];

    protected $appends = [
        'ci', 'apellido', 'nombres', 'fecha_nacimiento',
        'sexo', 'telefono', 'email', 'direccion',
        'nombre_completo',
        'carrera_primera_opcion', 'carrera_segunda_opcion',
        'anio_egreso', 'unidad_educativa', 'tipo_colegio',
    ];

    // ── Relaciones ──────────────────────────────────────────────────────

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    public function postulacion(): HasOne
    {
        return $this->hasOne(Postulacion::class)->latestOfMany();
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoEstudiante::class);
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

    public function getCarreraPrimeraOpcionAttribute(): ?string
    {
        return $this->postulacion?->carrera1?->nombre;
    }

    public function getCarreraSegundaOpcionAttribute(): ?string
    {
        return $this->postulacion?->carrera2?->nombre;
    }

    public function getAnioEgresoAttribute(): ?int
    {
        return $this->postulacion?->anio_egreso;
    }

    public function getUnidadEducativaAttribute(): ?string
    {
        return $this->postulacion?->unidad_educativa;
    }

    public function getTipoColegioAttribute(): ?string
    {
        return $this->postulacion?->tipo_colegio;
    }
}
