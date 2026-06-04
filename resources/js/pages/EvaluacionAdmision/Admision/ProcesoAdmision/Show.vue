<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { AlertTriangle, ArrowLeft, ChevronLeft, ChevronRight, Play, Users } from 'lucide-vue-next';
import { dashboard } from '@/routes';

interface CupoCarrera {
    carrera_id: number;
    nombre: string;
    cupo_max: number;
    asignados: number;
}

interface RankingItem {
    posicion: number;
    postulacion_id: number;
    estudiante: string;
    promedio_general: number | null;
    carrera1: string | null;
    carrera2: string | null;
    carrera_asignada: string | null;
    estado_admision: string;
}

interface Paginado {
    data: RankingItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Gestion {
    id: number;
    label: string;
    estado: string;
    nota_minima: number;
}

interface CarreraOption {
    id: number;
    nombre: string;
}

interface Filtros {
    estado: string;
    carrera_id: string;
    busqueda: string;
}

const props = defineProps<{
    gestion: Gestion;
    cupos: CupoCarrera[];
    ranking: Paginado;
    resumen: { total: number; admitidos: number; no_admitidos: number; pendientes: number };
    ejecutado: boolean;
    cuposTotales: number;
    carreras: CarreraOption[];
    filtros: Filtros;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Proceso de admisión', href: '/administracion/proceso-admision' },
        ],
    },
});

const procesando = ref(false);
const sinCupos = computed(() => props.cuposTotales === 0);

const estadoConfig: Record<string, { label: string; clases: string }> = {
    pendiente:   { label: 'Pendiente',   clases: 'bg-gray-100 text-gray-600' },
    admitido:    { label: 'Admitido',    clases: 'bg-green-100 text-green-700' },
    no_admitido: { label: 'No admitido', clases: 'bg-red-100 text-red-700' },
};

function ejecutar() {
    if (sinCupos.value) return;
    const msg = props.ejecutado
        ? '¿Volver a ejecutar el proceso de admisión? Se recalcularán los promedios y se reasignarán las carreras.'
        : '¿Ejecutar el proceso de admisión? Se asignarán las carreras según el promedio y los cupos disponibles.';
    if (!confirm(msg)) return;
    router.post(
        `/administracion/proceso-admision/${props.gestion.id}/ejecutar`,
        {},
        { onStart: () => (procesando.value = true), onFinish: () => (procesando.value = false) },
    );
}

function fmt(n: number | null): string {
    return n === null ? '—' : n.toFixed(2);
}

// ── Filtros ──────────────────────────────────────────────────────────────────
const filtroEstado   = ref(props.filtros.estado);
const filtroCarrera  = ref(props.filtros.carrera_id);
const busqueda       = ref(props.filtros.busqueda);

const hayFiltros = computed(() => !!(filtroEstado.value || filtroCarrera.value || busqueda.value));

function aplicarFiltros(page?: number) {
    const params: Record<string, string | number> = {};
    if (filtroEstado.value)  params.estado     = filtroEstado.value;
    if (filtroCarrera.value) params.carrera_id = filtroCarrera.value;
    if (busqueda.value.trim()) params.busqueda = busqueda.value.trim();
    if (page && page > 1)    params.page       = page;
    router.get(
        `/administracion/proceso-admision/${props.gestion.id}`,
        params,
        { preserveScroll: true, replace: true },
    );
}

watch([filtroEstado, filtroCarrera], () => aplicarFiltros());

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
watch(busqueda, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => aplicarFiltros(), 400);
});

function limpiarFiltros() {
    filtroEstado.value  = '';
    filtroCarrera.value = '';
    busqueda.value      = '';
}

// ── Paginación ────────────────────────────────────────────────────────────────
function irAPagina(page: number) {
    if (page < 1 || page > props.ranking.last_page) return;
    aplicarFiltros(page);
}

const paginas = computed<(number | '...')[]>(() => {
    const total = props.ranking.last_page;
    const actual = props.ranking.current_page;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages: (number | '...')[] = [1];
    if (actual > 3) pages.push('...');
    for (let i = Math.max(2, actual - 1); i <= Math.min(total - 1, actual + 1); i++) pages.push(i);
    if (actual < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});
</script>

<template>
    <Head :title="`Proceso de admisión · ${gestion.label}`" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Link
                    href="/administracion/proceso-admision"
                    class="text-muted-foreground hover:text-foreground mb-1 inline-flex items-center gap-1 text-xs"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Volver
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Proceso de admisión · {{ gestion.label }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Nota mínima de aprobación: <strong>{{ gestion.nota_minima }}</strong> · Mejor promedio = mayor prioridad.
                </p>
            </div>
            <button
                type="button"
                :disabled="procesando || sinCupos"
                class="inline-flex items-center gap-2 rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60"
                style="background-color: #c70e0a;"
                @click="ejecutar"
            >
                <Play class="h-4 w-4" />
                {{ procesando ? 'Ejecutando…' : ejecutado ? 'Volver a ejecutar' : 'Ejecutar proceso de admisión' }}
            </button>
        </div>

        <!-- Aviso sin cupos -->
        <div
            v-if="sinCupos"
            class="flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800"
        >
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
            <span>
                No hay cupos configurados para esta gestión. Defínelos en
                <Link :href="`/administracion/gestiones/${gestion.id}/edit`" class="font-semibold underline">
                    la edición de la gestión
                </Link>
                antes de ejecutar el proceso.
            </span>
        </div>

        <!-- Cupos por carrera -->
        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">Cupos por carrera</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="cupo in cupos"
                    :key="cupo.carrera_id"
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <p class="truncate text-sm font-semibold text-gray-900">{{ cupo.nombre }}</p>
                    <div class="mt-2 flex items-baseline gap-1.5">
                        <span
                            class="text-2xl font-bold"
                            :class="cupo.asignados >= cupo.cupo_max && cupo.cupo_max > 0 ? 'text-red-600' : 'text-[#073b75]'"
                        >
                            {{ cupo.asignados }}
                        </span>
                        <span class="text-sm text-gray-400">/ {{ cupo.cupo_max }} cupos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking -->
        <div>
            <h2 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
                <Users class="h-4 w-4" />
                Ranking de postulantes
                <span class="font-normal text-gray-400">({{ resumen.total }})</span>
            </h2>

            <!-- Barra de filtros -->
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <!-- Tabs estado -->
                <div class="flex gap-1 border-b border-gray-200">
                    <button
                        v-for="opt in [
                            { v: '',            l: 'Todos',        count: resumen.total },
                            { v: 'admitido',    l: 'Admitidos',    count: resumen.admitidos },
                            { v: 'no_admitido', l: 'No admitidos', count: resumen.no_admitidos },
                            { v: 'pendiente',   l: 'Pendientes',   count: resumen.pendientes },
                        ]"
                        :key="opt.v"
                        type="button"
                        :class="[
                            'inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                            filtroEstado === opt.v
                                ? 'border-[#073b75] text-[#073b75]'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                        @click="filtroEstado = opt.v"
                    >
                        {{ opt.l }}
                        <span
                            class="rounded-full px-1.5 py-0.5 text-xs"
                            :class="filtroEstado === opt.v ? 'bg-[#073b75] text-white' : 'bg-gray-100 text-gray-500'"
                        >
                            {{ opt.count }}
                        </span>
                    </button>
                </div>

                <!-- Carrera + búsqueda -->
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">1ª opción</label>
                        <select
                            v-model="filtroCarrera"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                        >
                            <option value="">Todas las carreras</option>
                            <option v-for="c in carreras" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
                        </select>
                    </div>

                    <input
                        v-model="busqueda"
                        placeholder="Buscar estudiante…"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                    />

                    <button
                        v-if="hayFiltros"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        @click="limpiarFiltros"
                    >
                        ✕ Limpiar
                    </button>
                </div>
            </div>

            <div v-if="ranking.total === 0 && !hayFiltros" class="rounded-xl border border-dashed py-16 text-center text-sm text-gray-500">
                No hay postulaciones para esta gestión.
            </div>

            <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 font-semibold">#</th>
                            <th class="px-4 py-3 font-semibold">Estudiante</th>
                            <th class="px-4 py-3 text-center font-semibold">Promedio</th>
                            <th class="px-4 py-3 font-semibold">1ª opción</th>
                            <th class="px-4 py-3 font-semibold">2ª opción</th>
                            <th class="px-4 py-3 font-semibold">Carrera asignada</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="ranking.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                No hay postulantes que coincidan con los filtros.
                            </td>
                        </tr>
                        <tr
                            v-for="item in ranking.data"
                            :key="item.postulacion_id"
                            class="border-b border-gray-50 last:border-0 hover:bg-gray-50"
                        >
                            <td class="px-4 py-3 font-semibold text-gray-400">{{ item.posicion }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ item.estudiante }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ fmt(item.promedio_general) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.carrera1 ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.carrera2 ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium" :class="item.carrera_asignada ? 'text-[#073b75]' : 'text-gray-400'">
                                {{ item.carrera_asignada ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="estadoConfig[item.estado_admision]?.clases ?? 'bg-gray-100 text-gray-600'"
                                >
                                    {{ estadoConfig[item.estado_admision]?.label ?? item.estado_admision }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pie de paginación -->
                <div
                    v-if="ranking.last_page > 1 || ranking.total > 0"
                    class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row"
                >
                    <p class="text-xs text-gray-500">
                        <template v-if="ranking.total === 0">Sin resultados</template>
                        <template v-else>
                            Mostrando {{ ranking.from }}–{{ ranking.to }} de {{ ranking.total }} postulantes
                        </template>
                    </p>

                    <div v-if="ranking.last_page > 1" class="flex items-center gap-1">
                        <button
                            type="button"
                            :disabled="ranking.current_page === 1"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="irAPagina(ranking.current_page - 1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>

                        <template v-for="(p, i) in paginas" :key="i">
                            <span v-if="p === '...'" class="flex h-8 w-8 items-center justify-center text-xs text-gray-400">…</span>
                            <button
                                v-else
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border text-xs font-medium transition"
                                :class="p === ranking.current_page
                                    ? 'border-transparent text-white'
                                    : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                                :style="p === ranking.current_page ? 'background-color: #060041;' : ''"
                                @click="irAPagina(Number(p))"
                            >
                                {{ p }}
                            </button>
                        </template>

                        <button
                            type="button"
                            :disabled="ranking.current_page === ranking.last_page"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="irAPagina(ranking.current_page + 1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
