<?php

namespace App\AdministracionSistema\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\AdministracionSistema\Models\Parametro;
use App\AdministracionSistema\Requests\StoreGestionRequest;
use App\AdministracionSistema\Requests\UpdateGestionRequest;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GestionController extends Controller
{
    private const ESTADOS = ['configuracion', 'inscripcion', 'cursado', 'admision', 'cerrada'];

    private const ESTADO_LABELS = [
        'configuracion' => 'Configuración',
        'inscripcion'   => 'Inscripción',
        'cursado'       => 'Cursado',
        'admision'      => 'Admisión',
        'cerrada'       => 'Cerrada',
    ];

    private const PARAM_CLAVES = [
        'monto_matricula_bs',
        'capacidad_max_grupo',
        'peso_examen_1',
        'peso_examen_2',
        'peso_examen_3',
        'nota_minima_aprobacion',
    ];

    public function index(): Response
    {
        $gestiones = Gestion::orderByDesc('anio')->orderByDesc('semestre')->get();

        $activaId = $gestiones->where('estado', '!=', 'cerrada')->first()?->id;

        return Inertia::render('AdministracionSistema/Gestion/Index', [
            'gestiones' => $gestiones,
            'activa_id' => $activaId,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('AdministracionSistema/Gestion/Create');
    }

    public function store(StoreGestionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $gestion = Gestion::create(collect($validated)->except(self::PARAM_CLAVES)->all());

        foreach (self::PARAM_CLAVES as $clave) {
            Parametro::create([
                'gestion_id' => $gestion->id,
                'clave'      => $clave,
                'valor'      => (string) $validated[$clave],
            ]);
        }

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$gestion->label} creada correctamente."]);
    }

    public function edit(Gestion $gestion): Response
    {
        return Inertia::render('AdministracionSistema/Gestion/Edit', [
            'gestion'    => $gestion,
            'parametros' => $gestion->parametros->pluck('valor', 'clave'),
        ]);
    }

    public function update(UpdateGestionRequest $request, Gestion $gestion): RedirectResponse
    {
        $validated = $request->validated();

        $gestion->update(collect($validated)->except(self::PARAM_CLAVES)->all());

        foreach (self::PARAM_CLAVES as $clave) {
            $gestion->parametros()->updateOrCreate(
                ['clave' => $clave],
                ['valor' => (string) $validated[$clave]],
            );
        }

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$gestion->label} actualizada correctamente."]);
    }

    public function destroy(Gestion $gestion): RedirectResponse
    {
        $label = $gestion->label;

        try {
            DB::transaction(function () use ($gestion) {
                $gestion->parametros()->delete();
                $gestion->delete();
            });
        } catch (QueryException) {
            return redirect()->route('gestiones.index')
                ->with('flash', ['type' => 'error', 'message' => "No se puede eliminar la gestión {$label} porque tiene datos asociados (postulaciones, grupos u otros registros)."]);
        }

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$label} eliminada."]);
    }

    public function avanzar(Gestion $gestion): RedirectResponse
    {
        $idx = array_search($gestion->estado, self::ESTADOS);

        if ($idx === false || $idx >= count(self::ESTADOS) - 1) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta gestión ya se encuentra cerrada.']);
        }

        $nuevoEstado = self::ESTADOS[$idx + 1];
        $gestion->update(['estado' => $nuevoEstado]);

        $label = self::ESTADO_LABELS[$nuevoEstado];

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$gestion->label} avanzó a: {$label}."]);
    }

    public function retroceder(Gestion $gestion): RedirectResponse
    {
        $idx = array_search($gestion->estado, self::ESTADOS);

        if ($idx === false || $idx <= 0) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta gestión ya se encuentra en el primer estado.']);
        }

        $nuevoEstado = self::ESTADOS[$idx - 1];
        $gestion->update(['estado' => $nuevoEstado]);

        $label = self::ESTADO_LABELS[$nuevoEstado];

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$gestion->label} retrocedió a: {$label}."]);
    }
}
