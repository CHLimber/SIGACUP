<?php

namespace App\AdministracionSistema\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\AdministracionSistema\Requests\UpdateParametrosRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ParametroController extends Controller
{
    public function edit(Gestion $gestion): Response
    {
        $parametros = $gestion->parametros->pluck('valor', 'clave');

        return Inertia::render('AdministracionSistema/Parametro/Edit', [
            'gestion'    => $gestion->only('id', 'anio', 'semestre', 'estado'),
            'parametros' => $parametros,
        ]);
    }

    public function update(UpdateParametrosRequest $request, Gestion $gestion): RedirectResponse
    {
        foreach ($request->validated() as $clave => $valor) {
            $gestion->parametros()->where('clave', $clave)->update(['valor' => (string) $valor]);
        }

        return redirect()->route('gestiones.parametros.edit', $gestion)
            ->with('flash', ['type' => 'success', 'message' => "Parámetros de la gestión {$gestion->label} actualizados."]);
    }
}
