<?php

namespace App\OrganizacionAcademica\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\GestionDocentes\Models\Docente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;
use App\GestionEstudiantes\Models\Postulacion;
use App\Http\Controllers\Controller;
use App\OrganizacionAcademica\Models\Aula;
use App\OrganizacionAcademica\Models\Grupo;
use App\OrganizacionAcademica\Models\Horario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GruposController extends Controller
{
    public function seleccionar(): Response
    {
        $estadosValidos = ['cursado', 'admision', 'cerrada'];

        $gestiones = Gestion::whereIn('estado', $estadosValidos)
            ->orderByDesc('anio')
            ->orderByDesc('semestre')
            ->get()
            ->map(function (Gestion $g) {
                $totalGrupos = Grupo::where('gestion_id', $g->id)
                    ->distinct('nombre')
                    ->count('nombre');

                $totalPagados = $this->contarPagados($g);

                return [
                    'id' => $g->id,
                    'anio' => $g->anio,
                    'semestre' => $g->semestre,
                    'estado' => $g->estado,
                    'total_grupos' => $totalGrupos,
                    'total_pagados' => $totalPagados,
                ];
            })
            ->values();

        return Inertia::render('OrganizacionAcademica/Grupos/Seleccionar', [
            'gestiones' => $gestiones,
        ]);
    }

    public function index(Gestion $gestion): Response
    {
        $gestion->load('parametros');

        $capacidadMax = (int) ($gestion->parametro('capacidad_max_grupo') ?? 70);
        $totalPagados = $this->contarPagados($gestion);
        $nCalculado = $totalPagados > 0 ? (int) ceil($totalPagados / $capacidadMax) : 0;

        $gruposAgrupados = Grupo::where('gestion_id', $gestion->id)
            ->withCount('postulaciones')
            ->get()
            ->groupBy('nombre')
            ->map(fn ($items, $nombre) => [
                'nombre' => $nombre,
                'estudiantes' => $items->first()->postulaciones_count,
            ])
            ->values();

        return Inertia::render('OrganizacionAcademica/Grupos/Index', [
            'gestion' => $gestion,
            'totalPagados' => $totalPagados,
            'capacidadMax' => $capacidadMax,
            'nCalculado' => $nCalculado,
            'grupos' => $gruposAgrupados,
        ]);
    }

    public function generar(Gestion $gestion): RedirectResponse
    {
        if (! in_array($gestion->estado, ['cursado', 'admision', 'cerrada'], true)) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'La gestión debe estar en estado Cursado o posterior para generar grupos.',
            ]);
        }

        $gestion->load('parametros');
        $capacidadMax = (int) ($gestion->parametro('capacidad_max_grupo') ?? 70);

        $postulaciones = Postulacion::where('gestion_id', $gestion->id)
            ->whereHas('candidatoEstudiante', fn ($q) => $q->where('estado', CandidatoEstudiante::ESTADO_PAGADO))
            ->orderBy('id')
            ->get();

        if ($postulaciones->isEmpty()) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'No hay postulantes con pago confirmado en esta gestión.',
            ]);
        }

        $n = (int) ceil($postulaciones->count() / $capacidadMax);
        $nombres = $this->generarNombres($n);
        $materias = DB::table('materia')->pluck('codigo')->all();

        DB::transaction(function () use ($gestion, $postulaciones, $nombres, $materias, $capacidadMax) {
            $existingIds = Grupo::where('gestion_id', $gestion->id)->pluck('id');
            if ($existingIds->isNotEmpty()) {
                DB::table('asignacion_grupo')->whereIn('grupo_id', $existingIds)->delete();
                Grupo::whereIn('id', $existingIds)->delete();
            }

            $gruposPorNombre = [];
            foreach ($nombres as $nombre) {
                $gruposPorNombre[$nombre] = [];
                foreach ($materias as $codigo) {
                    $grupo = Grupo::create([
                        'gestion_id' => $gestion->id,
                        'codigo_materia' => $codigo,
                        'nombre' => $nombre,
                        'horario_id' => null,
                        'aula_id' => null,
                        'capacidad_max' => $capacidadMax,
                    ]);
                    $gruposPorNombre[$nombre][] = $grupo->id;
                }
            }

            $now = now();
            $inserts = [];
            foreach ($postulaciones as $i => $postulacion) {
                $nombre = $nombres[$i % count($nombres)];
                foreach ($gruposPorNombre[$nombre] as $grupoId) {
                    $inserts[] = [
                        'postulacion_id' => $postulacion->id,
                        'grupo_id' => $grupoId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            DB::table('asignacion_grupo')->insert($inserts);
        });

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Se generaron {$n} grupos y se distribuyeron {$postulaciones->count()} postulantes equitativamente.",
        ]);
    }

    public function configurar(Gestion $gestion, string $nombre): Response
    {
        $grupos = Grupo::where('gestion_id', $gestion->id)
            ->where('nombre', $nombre)
            ->with(['materia', 'horario', 'aula'])
            ->orderBy('codigo_materia')
            ->get();

        if ($grupos->isEmpty()) {
            abort(404, 'Grupo no encontrado.');
        }

        $primer = $grupos->first();

        $postulantes = $primer->postulaciones()
            ->with(['candidatoEstudiante.persona', 'carrera1', 'carrera2'])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'ci' => $p->candidatoEstudiante?->ci,
                'apellido' => $p->candidatoEstudiante?->apellido,
                'nombres' => $p->candidatoEstudiante?->nombres,
                'carrera1' => $p->carrera1?->nombre,
                'carrera2' => $p->carrera2?->nombre,
            ])
            ->values();

        return Inertia::render('OrganizacionAcademica/Grupos/Configurar', [
            'gestion' => $gestion,
            'nombre' => $nombre,
            'capacidadMax' => $primer->capacidad_max,
            'grupos' => $grupos->map(fn ($g) => [
                'id' => $g->id,
                'codigo_materia' => $g->codigo_materia,
                'materia_nombre' => $g->materia?->nombre ?? $g->codigo_materia,
                'horario_id' => $g->horario_id,
                'aula_id' => $g->aula_id,
                'horario_label' => $g->horario ? $this->etiquetaHorario($g->horario) : null,
                'aula_label' => $g->aula?->nombre,
            ])->values(),
            'aulas' => Aula::orderBy('nombre')->get()->map(fn ($a) => [
                'id' => $a->id,
                'nombre' => $a->nombre,
                'capacidad' => $a->capacidad,
                'modulo' => $a->modulo,
            ]),
            'horarios' => Horario::orderBy('hora_inicio')->get()->map(fn ($h) => [
                'id' => $h->id,
                'label' => $this->etiquetaHorario($h),
            ]),
            'postulantes' => $postulantes,
        ]);
    }

    public function actualizar(Request $request, Gestion $gestion, string $nombre): RedirectResponse
    {
        $data = $request->validate([
            'asignaciones' => 'required|array|min:1',
            'asignaciones.*.grupo_id' => 'required|integer|exists:grupo,id',
            'asignaciones.*.horario_id' => 'nullable|integer|exists:horario,id',
            'asignaciones.*.aula_id' => 'nullable|integer|exists:aula,id',
        ]);

        DB::transaction(function () use ($data, $gestion, $nombre) {
            foreach ($data['asignaciones'] as $asig) {
                $grupo = Grupo::where('id', $asig['grupo_id'])
                    ->where('gestion_id', $gestion->id)
                    ->where('nombre', $nombre)
                    ->firstOrFail();

                if ($asig['aula_id'] && $asig['horario_id']) {
                    $conflicto = Grupo::where('gestion_id', $gestion->id)
                        ->where('aula_id', $asig['aula_id'])
                        ->where('horario_id', $asig['horario_id'])
                        ->where('id', '!=', $grupo->id)
                        ->first();

                    if ($conflicto) {
                        abort(422, "Conflicto: el aula seleccionada ya está ocupada en ese horario por el grupo {$conflicto->nombre} ({$conflicto->codigo_materia}).");
                    }
                }

                $grupo->update([
                    'horario_id' => $asig['horario_id'] ?: null,
                    'aula_id' => $asig['aula_id'] ?: null,
                ]);
            }
        });

        return back()->with('flash', [
            'type' => 'success',
            'message' => "Grupo {$nombre} actualizado correctamente.",
        ]);
    }

    // ── Asignación de docentes ──────────────────────────────────────────────

    public function docentes(Gestion $gestion): Response
    {
        $gestion->load('parametros');
        $maxPorDocente = (int) ($gestion->parametro('max_grupos_docente') ?? 5);

        $grupos = Grupo::where('gestion_id', $gestion->id)
            ->with(['materia', 'horario', 'docentes'])
            ->orderBy('nombre')
            ->orderBy('codigo_materia')
            ->get();

        $gruposData = $grupos->map(fn (Grupo $g) => [
            'id' => $g->id,
            'nombre' => $g->nombre,
            'codigo_materia' => $g->codigo_materia,
            'materia_nombre' => $g->materia?->nombre ?? $g->codigo_materia,
            'horario_id' => $g->horario_id,
            'horario_label' => $g->horario ? $this->etiquetaHorario($g->horario) : null,
            'docente_id' => $g->docentes->first()?->id,
        ])->values();

        $docentes = Docente::with('user.persona')
            ->get()
            ->map(fn (Docente $d) => [
                'id' => $d->id,
                'nombre' => $this->nombreDocente($d),
                'titulo' => $d->titulo,
            ])
            ->sortBy('nombre', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return Inertia::render('OrganizacionAcademica/Grupos/Docentes', [
            'gestion' => $gestion->only(['id', 'anio', 'semestre', 'estado']),
            'grupos' => $gruposData,
            'docentes' => $docentes,
            'maxPorDocente' => $maxPorDocente,
        ]);
    }

    public function asignarDocentes(Request $request, Gestion $gestion): RedirectResponse
    {
        $data = $request->validate([
            'asignaciones' => 'present|array',
            'asignaciones.*.grupo_id' => 'required|integer|exists:grupo,id',
            'asignaciones.*.docente_id' => 'nullable|integer|exists:docente,id',
        ]);

        $gestion->load('parametros');
        $max = (int) ($gestion->parametro('max_grupos_docente') ?? 5);

        $grupos = Grupo::where('gestion_id', $gestion->id)->with('horario')->get()->keyBy('id');

        $mapa = [];
        foreach ($data['asignaciones'] as $a) {
            if ($grupos->has($a['grupo_id'])) {
                $mapa[$a['grupo_id']] = $a['docente_id'] ?: null;
            }
        }

        $errores = $this->validarAsignaciones($mapa, $grupos, $max);
        if ($errores) {
            return back()->with('flash', ['type' => 'error', 'message' => $errores[0]]);
        }

        DB::transaction(function () use ($mapa, $grupos) {
            $now = now();
            DB::table('docente_grupo')->whereIn('grupo_id', $grupos->keys()->all())->delete();

            $inserts = [];
            foreach ($mapa as $grupoId => $docenteId) {
                if ($docenteId) {
                    $inserts[] = ['docente_id' => $docenteId, 'grupo_id' => $grupoId, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if ($inserts) {
                DB::table('docente_grupo')->insert($inserts);
            }
        });

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Asignación de docentes guardada correctamente.',
        ]);
    }

    public function autoAsignarDocentes(Gestion $gestion): RedirectResponse
    {
        $gestion->load('parametros');
        $max = (int) ($gestion->parametro('max_grupos_docente') ?? 5);

        $grupos = Grupo::where('gestion_id', $gestion->id)->with('horario')->get();
        if ($grupos->isEmpty()) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No hay grupos generados en esta gestión.']);
        }

        $docentes = Docente::all();
        if ($docentes->isEmpty()) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No hay docentes registrados para asignar.']);
        }

        $carga = [];   // docente_id => cantidad de grupos
        $horariosDoc = [];   // docente_id => [Horario, ...]
        foreach ($docentes as $d) {
            $carga[$d->id] = 0;
            $horariosDoc[$d->id] = [];
        }

        $asignacion = [];    // grupo_id => docente_id
        $sinAsignar = 0;

        // Recorrido aleatorio de grupos para repartir con azar respetando las reglas.
        foreach ($grupos->shuffle() as $grupo) {
            $candidatos = $docentes->filter(function (Docente $d) use ($carga, $horariosDoc, $grupo, $max) {
                if ($carga[$d->id] >= $max) {
                    return false;
                }
                foreach ($horariosDoc[$d->id] as $h) {
                    if ($this->horariosChocan($h, $grupo->horario)) {
                        return false;
                    }
                }

                return true;
            });

            if ($candidatos->isEmpty()) {
                $sinAsignar++;

                continue;
            }

            // Preferir al docente con menor carga; desempate aleatorio.
            $minCarga = $candidatos->min(fn (Docente $d) => $carga[$d->id]);
            $elegido = $candidatos->filter(fn (Docente $d) => $carga[$d->id] === $minCarga)->shuffle()->first();

            $asignacion[$grupo->id] = $elegido->id;
            $carga[$elegido->id]++;
            $horariosDoc[$elegido->id][] = $grupo->horario;
        }

        DB::transaction(function () use ($asignacion, $grupos) {
            $now = now();
            DB::table('docente_grupo')->whereIn('grupo_id', $grupos->pluck('id')->all())->delete();

            $inserts = [];
            foreach ($asignacion as $grupoId => $docenteId) {
                $inserts[] = ['docente_id' => $docenteId, 'grupo_id' => $grupoId, 'created_at' => $now, 'updated_at' => $now];
            }
            if ($inserts) {
                DB::table('docente_grupo')->insert($inserts);
            }
        });

        $asignados = count($asignacion);
        $mensaje = "Asignación automática completada: {$asignados} grupo(s) asignado(s).";

        if ($sinAsignar > 0) {
            $mensaje .= " {$sinAsignar} grupo(s) quedaron sin docente por falta de disponibilidad (límite de {$max} grupos o choque de horarios).";

            return back()->with('flash', ['type' => 'warning', 'message' => $mensaje]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => $mensaje]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Valida un mapa grupo_id => docente_id contra las reglas:
     * máximo de grupos por docente y ausencia de choques de horario.
     *
     * @param  array<int, int|null>  $mapa
     * @param  Collection<int, Grupo>  $grupos  indexada por id, con horario cargado
     * @return string[] lista de errores (vacía si todo es válido)
     */
    private function validarAsignaciones(array $mapa, Collection $grupos, int $max): array
    {
        $porDocente = [];
        foreach ($mapa as $grupoId => $docenteId) {
            if ($docenteId && $grupos->has($grupoId)) {
                $porDocente[$docenteId][] = $grupos[$grupoId];
            }
        }

        $nombres = $this->nombresDocentes(array_keys($porDocente));
        $errores = [];

        foreach ($porDocente as $docenteId => $gruposDoc) {
            $nombre = $nombres[$docenteId] ?? "Docente #{$docenteId}";

            if (count($gruposDoc) > $max) {
                $errores[] = "{$nombre} supera el máximo de {$max} grupos (tiene ".count($gruposDoc).').';
            }

            $total = count($gruposDoc);
            for ($i = 0; $i < $total; $i++) {
                for ($j = $i + 1; $j < $total; $j++) {
                    if ($this->horariosChocan($gruposDoc[$i]->horario, $gruposDoc[$j]->horario)) {
                        $errores[] = "{$nombre} tiene choque de horario entre {$gruposDoc[$i]->nombre}·{$gruposDoc[$i]->codigo_materia} y {$gruposDoc[$j]->nombre}·{$gruposDoc[$j]->codigo_materia}.";
                    }
                }
            }
        }

        return array_values(array_unique($errores));
    }

    /** Indica si dos horarios se solapan en el mismo día. */
    private function horariosChocan(?Horario $a, ?Horario $b): bool
    {
        if (! $a || ! $b) {
            return false;
        }

        $mismoDia = $a->aplica_todos_dias || $b->aplica_todos_dias
            || ($a->dia !== null && $a->dia === $b->dia);

        if (! $mismoDia) {
            return false;
        }

        return $a->hora_inicio < $b->hora_fin && $b->hora_inicio < $a->hora_fin;
    }

    /** @param int[] $ids  @return array<int, string> */
    private function nombresDocentes(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Docente::with('user.persona')
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (Docente $d) => [$d->id => $this->nombreDocente($d)])
            ->all();
    }

    private function nombreDocente(Docente $d): string
    {
        $persona = $d->user?->persona;
        $nombre = trim(($persona?->apellido ?? '').' '.($persona?->nombres ?? ''));

        return $nombre !== '' ? $nombre : ($d->user?->username ?? "Docente #{$d->id}");
    }

    private function etiquetaHorario(Horario $h): string
    {
        $inicio = substr($h->hora_inicio, 0, 5);
        $fin = substr($h->hora_fin, 0, 5);

        return $h->aplica_todos_dias
            ? "L-V {$inicio} → {$fin}"
            : "{$h->dia} {$inicio} → {$fin}";
    }

    private function contarPagados(Gestion $gestion): int
    {
        return Postulacion::where('gestion_id', $gestion->id)
            ->whereHas('candidatoEstudiante', fn ($q) => $q->where('estado', CandidatoEstudiante::ESTADO_PAGADO))
            ->count();
    }

    private function generarNombres(int $n): array
    {
        $nombres = [];
        for ($i = 0; $i < $n; $i++) {
            $nombres[] = $i < 26 ? chr(65 + $i) : 'G'.($i + 1);
        }

        return $nombres;
    }
}
