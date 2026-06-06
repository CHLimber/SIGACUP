<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermiso
{
    /**
     * Permite el acceso si el usuario tiene al menos uno de los permisos indicados.
     */
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $user = $request->user();

        if (! $user || ! $user->activo) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        foreach ($permisos as $permiso) {
            if ($user->tienePermiso($permiso)) {
                return $next($request);
            }
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
