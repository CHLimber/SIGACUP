<?php

namespace App\OrganizacionAcademica\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\Http\Controllers\Controller;
use App\OrganizacionAcademica\Models\Aula;
use App\OrganizacionAcademica\Models\Docente;
use App\OrganizacionAcademica\Models\Grupo;
use App\OrganizacionAcademica\Models\Horario;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
use App\RegistroInscripcion\Models\Postulacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GruposController extends Controller
{
    // CU10 — Calcular y generar grupos automáticamente | CU11 — Gestionar grupo (seleccionar gestión)
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

    // CU10 — Calcular y generar grupos automáticamente | CU11 — Gestionar grupo (resumen de grupos de la gestión)
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

    // CU10 — Calcular y generar grupos automáticamente (distribuye estudiantes, crea grupos y asigna aulas/horarios)
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

        // Postulantes que caerán en cada paralelo (distribución round-robin), para
        // elegir aulas con capacidad suficiente al asignar slots automáticamente.
        $conteoPorNombre = array_fill_keys($nombres, 0);
        foreach ($postulaciones as $i => $postulacion) {
            $conteoPorNombre[$nombres[$i % count($nombres)]]++;
        }

        $aulas = Aula::orderBy('capacidad')->orderBy('id')->get();
        $horarios = Horario::orderBy('hora_inicio')->orderBy('id')->get();

        $sinAsignar = 0;

        DB::transaction(function () use ($gestion, $postulaciones, $nombres, $materias, $capacidadMax, $conteoPorNombre, $aulas, $horarios, &$sinAsignar) {
            $existingIds = Grupo::where('gestion_id', $gestion->id)->pluck('id');
            if ($existingIds->isNotEmpty()) {
                DB::table('asignacion_grupo')->whereIn('grupo_id', $existingIds)->delete();
                Grupo::whereIn('id', $existingIds)->delete();
            }

            $gruposPorNombre = [];
            $gruposCreados = [];
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
                    $gruposCreados[] = ['id' => $grupo->id, 'nombre' => $nombre];
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

            $sinAsignar = $this->asignarAulasHorarios($gruposCreados, $conteoPorNombre, $capacidadMax, $aulas, $horarios);
        });

        $mensaje = "Se generaron {$n} grupos y se distribuyeron {$postulaciones->count()} postulantes equitativamente.";

        if ($sinAsignar > 0) {
            $mensaje .= " {$sinAsignar} grupo(s) quedaron sin aula/horario por falta de disponibilidad; asígnalos manualmente en Configurar.";

            return back()->with('flash', ['type' => 'warning', 'message' => $mensaje]);
        }

        $mensaje .= ' Se asignaron aulas y horarios automáticamente, sin choques.';

        return back()->with('flash', ['type' => 'success', 'message' => $mensaje]);
    }

    /**
     * Asigna aula y horario a cada grupo recién creado evitando choques:
     * dentro de un mismo paralelo (nombre) cada materia recibe un horario
     * distinto —el postulante las cursa todas— y ningún par aula×horario se
     * repite entre grupos (un aula no puede usarse dos veces a la misma hora).
     *
     * @param  list<array{id:int, nombre:string}>  $grupos
     * @param  array<string, int>  $conteoPorNombre  postulantes por paralelo
     * @param  Collection<int, Aula>  $aulas  ordenadas por capacidad ascendente (best-fit)
     * @param  Collection<int, Horario>  $horarios
     * @return int cantidad de grupos que quedaron sin asignar
     */
    // CU10 — Calcular y generar grupos automáticamente (asigna aula y horario sin choques)
    private function asignarAulasHorarios(array $grupos, array $conteoPorNombre, int $capacidadMax, Collection $aulas, Collection $horarios): int
    {
        if ($aulas->isEmpty() || $horarios->isEmpty()) {
            return count($grupos);
        }

        $ocupadas = [];          // [horario_id][aula_id] = true (reserva física del aula)
        $horariosParalelo = [];  // [nombre][horario_id] = true (hora ya tomada por el paralelo)
        $sinAsignar = 0;

        foreach ($grupos as $g) {
            $nombre = $g['nombre'];
            $necesidad = $conteoPorNombre[$nombre] ?? $capacidadMax;

            // Primero un aula con capacidad suficiente; si no hay, se relaja la capacidad.
            $slot = $this->buscarSlotLibre($nombre, $necesidad, $ocupadas, $horariosParalelo, $aulas, $horarios, true)
                ?? $this->buscarSlotLibre($nombre, $necesidad, $ocupadas, $horariosParalelo, $aulas, $horarios, false);

            if ($slot === null) {
                $sinAsignar++;

                continue;
            }

            [$horarioId, $aulaId] = $slot;
            $ocupadas[$horarioId][$aulaId] = true;
            $horariosParalelo[$nombre][$horarioId] = true;

            Grupo::where('id', $g['id'])->update(['horario_id' => $horarioId, 'aula_id' => $aulaId]);
        }

        return $sinAsignar;
    }

    /**
     * Busca el primer par (horario, aula) libre para un grupo del paralelo $nombre.
     *
     * @param  array<int, array<int, bool>>  $ocupadas
     * @param  array<string, array<int, bool>>  $horariosParalelo
     * @param  Collection<int, Aula>  $aulas
     * @param  Collection<int, Horario>  $horarios
     * @return array{0:int, 1:int}|null [horario_id, aula_id] o null si no hay slot
     */
    private function buscarSlotLibre(string $nombre, int $necesidad, array $ocupadas, array $horariosParalelo, Collection $aulas, Collection $horarios, bool $requiereCapacidad): ?array
    {
        foreach ($horarios as $horario) {
            if (isset($horariosParalelo[$nombre][$horario->id])) {
                continue;
            }
            foreach ($aulas as $aula) {
                if (isset($ocupadas[$horario->id][$aula->id])) {
                    continue;
                }
                if ($requiereCapacidad && $aula->capacidad < $necesidad) {
                    continue;
                }

                return [$horario->id, $aula->id];
            }
        }

        return null;
    }

    // CU11 — Gestionar grupo (eliminar todos los grupos de la gestión)
    public function limpiar(Gestion $gestion): RedirectResponse
    {
        $ids = Grupo::where('gestion_id', $gestion->id)->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('flash', [
                'type' => 'info',
                'message' => 'No hay grupos que eliminar en esta gestión.',
            ]);
        }

        DB::transaction(function () use ($ids) {
            DB::table('asignacion_grupo')->whereIn('grupo_id', $ids)->delete();
            DB::table('docente_grupo')->whereIn('grupo_id', $ids)->delete();
            Grupo::whereIn('id', $ids)->delete();
        });

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Todos los grupos y sus asignaciones fueron eliminados.',
        ]);
    }

    // CU11 — Gestionar grupo (ver y editar aula/horario de cada materia del paralelo)
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

    // CU11 — Gestionar grupo (guardar asignación de aulas y horarios validando conflictos)
    public function actualizar(Request $request, Gestion $gestion, string $nombre): RedirectResponse
    {
        $data = $request->validate([
            'asignaciones' => 'required|array|min:1',
            'asignaciones.*.grupo_id' => 'required|integer|exists:grupo,id',
            'asignaciones.*.horario_id' => 'nullable|integer|exists:horario,id',
            'asignaciones.*.aula_id' => 'nullable|integer|exists:aula,id',
        ]);

        // Los grupos de este paralelo se reescriben por completo, así que no chocan
        // contra sí mismos: solo deben revisarse contra el resto de la gestión.
        $idsLote = array_column($data['asignaciones'], 'grupo_id');

        $this->verificarConflictosAula($data['asignaciones'], $gestion, $nombre, $idsLote);

        DB::transaction(function () use ($data, $gestion, $nombre) {
            foreach ($data['asignaciones'] as $asig) {
                Grupo::where('id', $asig['grupo_id'])
                    ->where('gestion_id', $gestion->id)
                    ->where('nombre', $nombre)
                    ->update([
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

    /**
     * Verifica que ninguna asignación reserve un aula ya ocupada en ese horario,
     * ni dentro del propio envío ni por otros grupos de la gestión. Lanza una
     * ValidationException (que Inertia entrega al `onError` del frontend) en vez
     * de abortar con una excepción HTTP cruda.
     *
     * @param  list<array{grupo_id:int, horario_id:int|null, aula_id:int|null}>  $asignaciones
     * @param  int[]  $idsLote  ids de los grupos que se están reescribiendo
     */
    private function verificarConflictosAula(array $asignaciones, Gestion $gestion, string $nombre, array $idsLote): void
    {
        $vistos = [];

        foreach ($asignaciones as $asig) {
            if (! $asig['aula_id'] || ! $asig['horario_id']) {
                continue;
            }

            $clave = $asig['aula_id'].'-'.$asig['horario_id'];

            // Choque dentro del mismo paralelo (dos materias en la misma aula y hora).
            if (isset($vistos[$clave])) {
                throw ValidationException::withMessages([
                    'asignaciones' => "Conflicto: dos materias del grupo {$nombre} comparten la misma aula y horario. Cada materia debe ir en una franja distinta.",
                ]);
            }
            $vistos[$clave] = true;

            // Choque con un grupo de otro paralelo (o gestión) que ya usa ese aula y hora.
            $conflicto = Grupo::where('gestion_id', $gestion->id)
                ->where('aula_id', $asig['aula_id'])
                ->where('horario_id', $asig['horario_id'])
                ->whereNotIn('id', $idsLote)
                ->first();

            if ($conflicto) {
                throw ValidationException::withMessages([
                    'asignaciones' => "Conflicto: el aula seleccionada ya está ocupada en ese horario por el grupo {$conflicto->nombre} ({$conflicto->codigo_materia}).",
                ]);
            }
        }
    }

    // ── Asignación de docentes ──────────────────────────────────────────────

    // CU13 — Asignar grupos a docente (vista de asignación docente-grupo)
    public function docentes(Gestion $gestion): Response
    {
        $gestion->load('parametros');
        $maxPorDocente = (int) ($gestion->parametro('max_grupos_docente') ?? 5);

        $grupos = Grupo::where('gestion_id', $gestion->id)
            ->with(['materia', 'horario', 'docentes' => fn ($q) => $q->where('activo', true)])
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

        $docentes = Docente::with(['user.persona', 'materias'])
            ->where('activo', true)
            ->get()
            ->map(fn (Docente $d) => [
                'id' => $d->id,
                'nombre' => $this->nombreDocente($d),
                'titulo' => $d->titulo,
                'materias' => $d->materias->pluck('codigo')->all(),
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

    // CU13 — Asignar grupos a docente (guardar asignación manual validando límites y horarios)
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

    // CU13 — Asignar grupos a docente (asignación automática respetando materia, carga máxima y horarios)
    public function autoAsignarDocentes(Gestion $gestion): RedirectResponse
    {
        $gestion->load('parametros');
        $max = (int) ($gestion->parametro('max_grupos_docente') ?? 5);

        $grupos = Grupo::where('gestion_id', $gestion->id)->with('horario')->get();
        if ($grupos->isEmpty()) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No hay grupos generados en esta gestión.']);
        }

        $docentes = Docente::with('materias')->where('activo', true)->get();
        if ($docentes->isEmpty()) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No hay docentes activos registrados para asignar.']);
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
                if (! $d->materias->contains('codigo', $grupo->codigo_materia)) {
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
            $mensaje .= " {$sinAsignar} grupo(s) quedaron sin docente por falta de disponibilidad (límite de {$max} grupos, choque de horarios o ningún docente activo que dicte la materia).";

            return back()->with('flash', ['type' => 'warning', 'message' => $mensaje]);
        }

        return back()->with('flash', ['type' => 'success', 'message' => $mensaje]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Valida un mapa grupo_id => docente_id contra las reglas:
     * máximo de grupos por docente, docente activo, que dicte la materia
     * del grupo y ausencia de choques de horario.
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

        $docentes = Docente::with(['user.persona', 'materias'])
            ->whereIn('id', array_keys($porDocente))
            ->get()
            ->keyBy('id');

        $errores = [];

        foreach ($porDocente as $docenteId => $gruposDoc) {
            $docente = $docentes->get($docenteId);
            $nombre = $docente ? $this->nombreDocente($docente) : "Docente #{$docenteId}";

            if ($docente && ! $docente->activo) {
                $errores[] = "{$nombre} está deshabilitado y no puede recibir grupos.";
            }

            if (count($gruposDoc) > $max) {
                $errores[] = "{$nombre} supera el máximo de {$max} grupos (tiene ".count($gruposDoc).').';
            }

            foreach ($gruposDoc as $g) {
                if ($docente && ! $docente->materias->contains('codigo', $g->codigo_materia)) {
                    $errores[] = "{$nombre} no dicta la materia {$g->codigo_materia} (grupo {$g->nombre}).";
                }
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
