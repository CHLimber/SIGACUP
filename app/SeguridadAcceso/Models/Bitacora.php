<?php

namespace App\SeguridadAcceso\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    /** La tabla usa la columna `fecha` en lugar de timestamps de Eloquent. */
    public $timestamps = false;

    /** Verbos legibles por código de acción. */
    public const VERBOS = [
        'crear' => 'Creó',
        'actualizar' => 'Actualizó',
        'eliminar' => 'Eliminó',
        'login' => 'Inició sesión',
        'logout' => 'Cerró sesión',
    ];

    protected $fillable = ['usuario_id', 'accion', 'modulo', 'descripcion', 'ip', 'fecha'];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(BitacoraDetalle::class, 'bitacora_id');
    }

    /**
     * Crea un registro de bitácora (y sus detalles de cambios) capturando
     * el usuario autenticado y la IP de la petición.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, array{anterior: mixed, nuevo: mixed}>  $cambios
     */
    public static function registrar(string $accion, array $datos = [], array $cambios = []): self
    {
        $request = request();
        $usuario = auth()->user();

        return DB::transaction(function () use ($accion, $datos, $cambios, $request, $usuario) {
            $registro = static::create([
                'usuario_id' => $datos['usuario_id'] ?? $usuario?->id,
                'accion' => $accion,
                'modulo' => $datos['modulo'] ?? null,
                'descripcion' => $datos['descripcion'] ?? '',
                'ip' => $request?->ip(),
                'fecha' => now(),
            ]);

            foreach ($cambios as $campo => $valores) {
                $registro->detalles()->create([
                    'campo' => $campo,
                    'valor_anterior' => self::aTexto($valores['anterior'] ?? null),
                    'valor_nuevo' => self::aTexto($valores['nuevo'] ?? null),
                ]);
            }

            return $registro;
        });
    }

    /** Normaliza un valor a texto para guardarlo en el detalle. */
    private static function aTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if (is_array($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return (string) $valor;
    }
}
