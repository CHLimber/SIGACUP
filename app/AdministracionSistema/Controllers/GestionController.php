<?php

namespace App\AdministracionSistema\Controllers;

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\AdministracionSistema\Models\Parametro;
use App\AdministracionSistema\Requests\StoreGestionRequest;
use App\AdministracionSistema\Requests\UpdateGestionRequest;
use App\EvaluacionAdmision\Actions\CompletarNotasFaltantes;
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
        'inscripcion' => 'Inscripción',
        'cursado' => 'Cursado',
        'admision' => 'Admisión',
        'cerrada' => 'Cerrada',
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
        return Inertia::render('AdministracionSistema/Gestion/Create', [
            'carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(StoreGestionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $cupos = $validated['cupos'] ?? [];

        $gestion = DB::transaction(function () use ($validated, $cupos) {
            $gestion = Gestion::create(
                collect($validated)->except([...self::PARAM_CLAVES, 'cupos'])->all()
            );

            foreach (self::PARAM_CLAVES as $clave) {
                Parametro::create([
                    'gestion_id' => $gestion->id,
                    'clave' => $clave,
                    'valor' => (string) $validated[$clave],
                ]);
            }

            $this->guardarCupos($gestion, $cupos);

            return $gestion;
        });

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$gestion->label} creada correctamente."]);
    }

    public function edit(Gestion $gestion): Response
    {
        return Inertia::render('AdministracionSistema/Gestion/Edit', [
            'gestion' => $gestion,
            'parametros' => $gestion->parametros->pluck('valor', 'clave'),
            'carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre']),
            'cupos' => $gestion->cupos->pluck('cupo_max', 'carrera_id'),
        ]);
    }

    public function update(UpdateGestionRequest $request, Gestion $gestion): RedirectResponse
    {
        $validated = $request->validated();
        $cupos = $validated['cupos'] ?? [];

        DB::transaction(function () use ($validated, $cupos, $gestion) {
            $gestion->update(
                collect($validated)->except([...self::PARAM_CLAVES, 'cupos'])->all()
            );

            foreach (self::PARAM_CLAVES as $clave) {
                $gestion->parametros()->updateOrCreate(
                    ['clave' => $clave],
                    ['valor' => (string) $validated[$clave]],
                );
            }

            $this->guardarCupos($gestion, $cupos);
        });

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => "Gestión {$gestion->label} actualizada correctamente."]);
    }

    /**
     * Persiste los cupos por carrera de una gestión.
     *
     * @param  array<int|string, int|string>  $cupos  carrera_id => cupo_max
     */
    private function guardarCupos(Gestion $gestion, array $cupos): void
    {
        foreach ($cupos as $carreraId => $cupoMax) {
            $gestion->cupos()->updateOrCreate(
                ['carrera_id' => (int) $carreraId],
                ['cupo_max' => (int) $cupoMax],
            );
        }
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

    public function avanzar(Gestion $gestion, CompletarNotasFaltantes $completar): RedirectResponse
    {
        $idx = array_search($gestion->estado, self::ESTADOS);

        if ($idx === false || $idx >= count(self::ESTADOS) - 1) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Esta gestión ya se encuentra cerrada.']);
        }

        $nuevoEstado = self::ESTADOS[$idx + 1];
        $gestion->update(['estado' => $nuevoEstado]);

        $label = self::ESTADO_LABELS[$nuevoEstado];
        $mensaje = "Gestión {$gestion->label} avanzó a: {$label}.";

        if ($nuevoEstado === 'admision') {
            $creadas = $completar($gestion);

            if ($creadas > 0) {
                $mensaje .= " Se completaron con nota 0 un total de {$creadas} evaluación(es) faltante(s).";
            }
        }

        return redirect()->route('gestiones.index')
            ->with('flash', ['type' => 'success', 'message' => $mensaje]);
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
