<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\OrganizacionAcademica\Models\CandidatoDocente;
use App\OrganizacionAcademica\Models\Docente;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
use App\SeguridadAcceso\Concerns\RegistraBitacora;
use App\SeguridadAcceso\Models\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['persona_id', 'name', 'username', 'email', 'password', 'role', 'activo', 'intentos_fallidos', 'bloqueado_hasta'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, RegistraBitacora, TwoFactorAuthenticatable;

    /** Etiqueta legible para la bitácora. */
    protected string $bitacoraEtiqueta = 'Usuario';

    /** Cambios internos que no deben generar registros de bitácora. */
    protected array $bitacoraIgnorarExtra = ['intentos_fallidos', 'bloqueado_hasta', 'email_verified_at'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'activo' => 'boolean',
            'bloqueado_hasta' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function docente(): HasOne
    {
        return $this->hasOne(Docente::class);
    }

    public function candidatoEstudiante(): HasOne
    {
        return $this->hasOne(CandidatoEstudiante::class);
    }

    public function candidatoDocente(): HasOne
    {
        return $this->hasOne(CandidatoDocente::class);
    }

    /** El rol del usuario, vinculado por el slug almacenado en la columna `role`. */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'role', 'nombre');
    }

    public function esAdministrador(): bool
    {
        return $this->role === UserRole::Administrador->value;
    }

    /** Determina si el usuario tiene un permiso por su slug. El administrador siempre lo tiene. */
    public function tienePermiso(string $permiso): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }

        return $this->permisosSlugs()->contains($permiso);
    }

    /** @return Collection<int, string> */
    public function permisosSlugs(): Collection
    {
        return $this->rol
            ? $this->rol->permisos->pluck('nombre')
            : collect();
    }
}
