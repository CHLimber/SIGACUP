<?php

use App\AdministracionSistema\Models\Gestion;

if (! function_exists('gestionActiva')) {
    function gestionActiva(): ?Gestion
    {
        return Gestion::where('estado', '!=', 'cerrada')
            ->orderByDesc('anio')
            ->orderByDesc('semestre')
            ->with('parametros')
            ->first();
    }
}

if (! function_exists('propsGestion')) {
    function propsGestion(?Gestion $g): array
    {
        return [
            'nota_minima' => (int) ($g?->parametro('nota_minima_aprobacion') ?? 60),
            'peso1' => (int) ($g?->parametro('peso_examen_1') ?? 30),
            'peso2' => (int) ($g?->parametro('peso_examen_2') ?? 30),
            'peso3' => (int) ($g?->parametro('peso_examen_3') ?? 40),
            'gestion_label' => $g ? "{$g->anio} · ".($g->semestre === 1 ? '1er Semestre' : '2do Semestre') : null,
            'gestion_estado' => $g?->estado,
        ];
    }
}
