<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { dashboard } from '@/routes';

type EstadoEstudiante = 'aprobado_pendiente_pago' | 'pagado';

interface Estudiante {
    id: number;
    ci: string | null;
    apellido: string | null;
    nombres: string | null;
    email: string | null;
    telefono: string | null;
    estado: EstadoEstudiante;
    gestion_label: string | null;
    carrera1: string | null;
    carrera2: string | null;
    carrera_asignada: string | null;
}
interface Paginado {
    data: Estudiante[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
interface GestionOption {
    id: number;
    anio: number;
    semestre: number;
}
interface Filtros {
    gestion_id: string;
    estado: string;
    busqueda: string;
}

const props = defineProps<{
    estudiantes: Paginado;
    resumen: { aprobados: number; pagados: number };
    gestiones: GestionOption[];
    filtros: Filtros;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',      href: dashboard() },
            { title: 'Estudiantes', href: '/administracion/estudiantes' },
        ],
    },
});

const estadoConfig: Record<EstadoEstudiante, { label: string; clases: string }> = {
    aprobado_pendiente_pago: { label: 'Aprobado · Pendiente de pago', clases: 'bg-yellow-100 text-yellow-700' },
    pagado:                  { label: 'Pagado',                       clases: 'bg-green-100 text-green-700' },
};

const totales = computed(() => ({
    total:     props.resumen.aprobados + props.resumen.pagados,
    aprobados: props.resumen.aprobados,
    pagados:   props.resumen.pagados,
}));

// ── Filtros ──────────────────────────────────────────────────────────────────
const filtroEstado    = ref(props.filtros.estado);
const filtroGestionId = ref(props.filtros.gestion_id);
const busqueda        = ref(props.filtros.busqueda);

const hayFiltros = computed(() => !!(filtroEstado.value || filtroGestionId.value || busqueda.value));

function aplicarFiltros(page?: number) {
    const params: Record<string, string | number> = {};

    if (filtroEstado.value)    {
params.estado     = filtroEstado.value;
}

    if (filtroGestionId.value) {
params.gestion_id = filtroGestionId.value;
}

    if (busqueda.value.trim()) {
params.busqueda   = busqueda.value.trim();
}

    if (page && page > 1)      {
params.page       = page;
}

    router.get('/administracion/estudiantes', params, { preserveScroll: true, replace: true });
}

watch([filtroEstado, filtroGestionId], () => aplicarFiltros());

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
watch(busqueda, () => {
    if (debounceTimer) {
clearTimeout(debounceTimer);
}

    debounceTimer = setTimeout(() => aplicarFiltros(), 400);
});

function limpiarFiltros() {
    filtroEstado.value    = '';
    filtroGestionId.value = '';
    busqueda.value        = '';
}

// ── Paginación ────────────────────────────────────────────────────────────────
function irAPagina(page: number) {
    if (page < 1 || page > props.estudiantes.last_page) {
return;
}

    aplicarFiltros(page);
}

const paginas = computed<(number | '...')[]>(() => {
    const total = props.estudiantes.last_page;
    const actual = props.estudiantes.current_page;

    if (total <= 7) {
return Array.from({ length: total }, (_, i) => i + 1);
}

    const pages: (number | '...')[] = [1];

    if (actual > 3) {
pages.push('...');
}

    for (let i = Math.max(2, actual - 1); i <= Math.min(total - 1, actual + 1); i++) {
pages.push(i);
}

    if (actual < total - 2) {
pages.push('...');
}

    pages.push(total);

    return pages;
});
</script>

<template>
    <Head title="Gestionar Estudiantes" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestionar Estudiantes</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Candidatos aprobados y estudiantes con pago confirmado. Edita sus datos de contacto.
            </p>
        </div>

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                    {{ hayFiltros ? 'Resultados' : 'Total' }}
                </p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ totales.total }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aprobados (sin pago)</p>
                <p class="mt-1 text-3xl font-bold text-amber-600">{{ totales.aprobados }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Pagados</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ totales.pagados }}</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-end justify-between gap-3">
            <!-- Tabs estado -->
            <div class="flex gap-1 border-b border-gray-200">
                <button
                    v-for="opt in [
                        { v: '',          l: 'Todos' },
                        { v: 'aprobados', l: 'Aprobados' },
                        { v: 'pagados',   l: 'Pagados' },
                    ]"
                    :key="opt.v"
                    type="button"
                    :class="[
                        'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                        filtroEstado === opt.v
                            ? 'border-[#073b75] text-[#073b75]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                    @click="filtroEstado = opt.v"
                >
                    {{ opt.l }}
                </button>
            </div>

            <!-- Gestión + búsqueda -->
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gestión</label>
                    <select
                        v-model="filtroGestionId"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                    >
                        <option value="">Todas las gestiones</option>
                        <option v-for="g in gestiones" :key="g.id" :value="String(g.id)">
                            {{ g.anio }}-{{ g.semestre }}
                        </option>
                    </select>
                </div>

                <input
                    v-model="busqueda"
                    placeholder="Buscar por CI, nombre o email…"
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

        <!-- Tabla -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr style="background-color: #060041;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">CI</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Nombre</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Gestión</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Carrera</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estado</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="estudiantes.data.length === 0">
                        <td colspan="6" class="px-5 py-14 text-center text-gray-400">
                            No hay estudiantes que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                    <tr
                        v-for="e in estudiantes.data"
                        :key="e.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ e.ci || '—' }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ e.apellido }} {{ e.nombres }}</p>
                            <p class="text-xs text-gray-500">{{ e.email || '—' }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-700">
                            <span v-if="e.gestion_label">{{ e.gestion_label }}</span>
                            <span v-else class="italic text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-700">
                            <p v-if="e.carrera_asignada" class="font-semibold text-[#073b75]">
                                {{ e.carrera_asignada }}
                            </p>
                            <template v-else>
                                <p>{{ e.carrera1 || '—' }}</p>
                                <p v-if="e.carrera2" class="text-gray-400">{{ e.carrera2 }}</p>
                            </template>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="estadoConfig[e.estado].clases"
                            >
                                {{ estadoConfig[e.estado].label }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <Link
                                :href="`/administracion/estudiantes/${e.id}/edit`"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110"
                                style="background-color: #073b75;"
                            >
                                Editar →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>

            <!-- Pie de paginación -->
            <div
                v-if="estudiantes.last_page > 1 || estudiantes.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row"
            >
                <p class="text-xs text-gray-500">
                    <template v-if="estudiantes.total === 0">Sin resultados</template>
                    <template v-else>
                        Mostrando {{ estudiantes.from }}–{{ estudiantes.to }} de {{ estudiantes.total }} estudiantes
                    </template>
                </p>

                <div v-if="estudiantes.last_page > 1" class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="estudiantes.current_page === 1"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="irAPagina(estudiantes.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <template v-for="(p, i) in paginas" :key="i">
                        <span v-if="p === '...'" class="flex h-8 w-8 items-center justify-center text-xs text-gray-400">…</span>
                        <button
                            v-else
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border text-xs font-medium transition"
                            :class="p === estudiantes.current_page
                                ? 'border-transparent text-white'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                            :style="p === estudiantes.current_page ? 'background-color: #060041;' : ''"
                            @click="irAPagina(Number(p))"
                        >
                            {{ p }}
                        </button>
                    </template>

                    <button
                        type="button"
                        :disabled="estudiantes.current_page === estudiantes.last_page"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="irAPagina(estudiantes.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
