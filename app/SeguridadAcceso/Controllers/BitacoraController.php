<?php

namespace App\SeguridadAcceso\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\SeguridadAcceso\Models\Bitacora;
use App\SeguridadAcceso\Models\BitacoraDetalle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BitacoraController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = [
            'accion' => $request->string('accion')->toString() ?: null,
            'modulo' => $request->string('modulo')->toString() ?: null,
            'usuario_id' => $request->integer('usuario_id') ?: null,
            'desde' => $request->date('desde')?->format('Y-m-d'),
            'hasta' => $request->date('hasta')?->format('Y-m-d'),
            'q' => $request->string('q')->toString() ?: null,
        ];

        $registros = Bitacora::query()
            ->with(['usuario:id,name,username', 'detalles:id,bitacora_id,campo,valor_anterior,valor_nuevo'])
            ->latest('fecha')
            ->latest('id')
            ->when($filtros['accion'], fn ($q, $v) => $q->where('accion', $v))
            ->when($filtros['modulo'], fn ($q, $v) => $q->where('modulo', $v))
            ->when($filtros['usuario_id'], fn ($q, $v) => $q->where('usuario_id', $v))
            ->when($filtros['desde'], fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($filtros['hasta'], fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
            ->when($filtros['q'], fn ($q, $v) => $q->whereLike('descripcion', "%{$v}%"))
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Bitacora $b) => [
                'id' => $b->id,
                'accion' => $b->accion,
                'verbo' => Bitacora::VERBOS[$b->accion] ?? ucfirst($b->accion),
                'modulo' => $b->modulo,
                'descripcion' => $b->descripcion,
                'usuario' => $b->usuario?->name ?? 'Sistema',
                'cambios' => $b->detalles->map(fn (BitacoraDetalle $d) => [
                    'campo' => $d->campo,
                    'anterior' => $d->valor_anterior,
                    'nuevo' => $d->valor_nuevo,
                ])->values(),
                'ip' => $b->ip,
                'fecha' => $b->fecha?->toIso8601String(),
            ]);

        return Inertia::render('SeguridadAcceso/Bitacora/Index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'opciones' => [
                'acciones' => collect(Bitacora::VERBOS)
                    ->map(fn ($label, $valor) => ['valor' => $valor, 'label' => $label])
                    ->values(),
                'modulos' => Bitacora::query()
                    ->whereNotNull('modulo')
                    ->distinct()
                    ->orderBy('modulo')
                    ->pluck('modulo'),
                'usuarios' => User::query()
                    ->whereIn('id', Bitacora::query()->whereNotNull('usuario_id')->distinct()->pluck('usuario_id'))
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }
}
