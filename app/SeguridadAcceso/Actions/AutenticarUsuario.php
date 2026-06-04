<?php

namespace App\SeguridadAcceso\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AutenticarUsuario
{
    private const INTENTOS_POR_BLOQUEO = 3;

    private const DURACIONES = [1, 3, 5, 15]; // minutos por bloqueo consecutivo

    public function __invoke(Request $request): ?User
    {
        $user = User::where('username', $request->username)->first();

        if (! $user) {
            return null;
        }

        // Cuenta bloqueada temporalmente
        if ($user->bloqueado_hasta?->isFuture()) {
            $minutos = (int) now()->diffInMinutes($user->bloqueado_hasta) + 1;

            throw ValidationException::withMessages([
                Fortify::username() => [
                    "Cuenta bloqueada. Intenta de nuevo en {$minutos} minuto(s).",
                ],
            ]);
        }

        // Contraseña incorrecta
        if (! Hash::check($request->password, $user->password)) {
            $intentos = $user->intentos_fallidos + 1;
            $data = ['intentos_fallidos' => $intentos];

            // Cada 3 fallos se aplica un bloqueo incremental
            if ($intentos % self::INTENTOS_POR_BLOQUEO === 0) {
                $indice = (int) ($intentos / self::INTENTOS_POR_BLOQUEO) - 1;
                $minutos = self::DURACIONES[min($indice, count(self::DURACIONES) - 1)];
                $data['bloqueado_hasta'] = now()->addMinutes($minutos);
            }

            $user->update($data);

            return null;
        }

        // Éxito: resetear contadores
        $user->update(['intentos_fallidos' => 0, 'bloqueado_hasta' => null]);

        return $user;
    }
}
