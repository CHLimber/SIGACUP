<?php

namespace App\SeguridadAcceso\Concerns;

use App\SeguridadAcceso\Models\Bitacora;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra automáticamente en la bitácora las creaciones, actualizaciones
 * y eliminaciones del modelo que use este trait.
 *
 * Los modelos pueden personalizar:
 *  - protected string $bitacoraEtiqueta       → nombre legible de la entidad.
 *  - protected array  $bitacoraIgnorarExtra   → atributos extra a omitir en los cambios.
 */
trait RegistraBitacora
{
    public static function bootRegistraBitacora(): void
    {
        static::created(fn (Model $modelo) => $modelo->guardarBitacora('crear'));
        static::updated(fn (Model $modelo) => $modelo->guardarBitacora('actualizar'));
        static::deleted(fn (Model $modelo) => $modelo->guardarBitacora('eliminar'));
    }

    protected function guardarBitacora(string $accion): void
    {
        $cambios = [];

        if ($accion === 'actualizar') {
            $cambios = $this->bitacoraCambios();

            // Si solo cambiaron atributos ignorados, no registramos nada.
            if (empty($cambios)) {
                return;
            }
        }

        $entidad = $this->bitacoraEntidad();
        $nombre = $this->bitacoraNombreRegistro();
        $verbo = Bitacora::VERBOS[$accion] ?? ucfirst($accion);

        Bitacora::registrar($accion, [
            'modulo' => $entidad,
            'descripcion' => trim("{$verbo} {$entidad} «{$nombre}»"),
        ], $cambios);
    }

    /** Atributos que nunca se registran como parte de los cambios. */
    protected function bitacoraIgnorar(): array
    {
        return array_merge(
            [
                $this->getKeyName(), 'created_at', 'updated_at', 'password', 'remember_token',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            ],
            property_exists($this, 'bitacoraIgnorarExtra') ? $this->bitacoraIgnorarExtra : [],
        );
    }

    /**
     * Diferencia entre los valores anteriores y nuevos de los atributos modificados.
     *
     * @return array<string, array{anterior: mixed, nuevo: mixed}>
     */
    protected function bitacoraCambios(): array
    {
        $ignorar = $this->bitacoraIgnorar();
        $cambios = [];

        foreach ($this->getChanges() as $campo => $nuevo) {
            if (in_array($campo, $ignorar, true)) {
                continue;
            }

            $cambios[$campo] = [
                'anterior' => $this->getOriginal($campo),
                'nuevo' => $nuevo,
            ];
        }

        return $cambios;
    }

    /** Nombre legible de la entidad (ej. «Usuario»). */
    public function bitacoraEntidad(): string
    {
        return property_exists($this, 'bitacoraEtiqueta')
            ? $this->bitacoraEtiqueta
            : class_basename($this);
    }

    /** Identificador legible del registro concreto (ej. el nombre del usuario). */
    public function bitacoraNombreRegistro(): string
    {
        foreach (['nombre_completo', 'name', 'nombre', 'label', 'titulo', 'codigo', 'username'] as $attr) {
            if (! empty($this->{$attr})) {
                return (string) $this->{$attr};
            }
        }

        return '#'.$this->getKey();
    }
}
