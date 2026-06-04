<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { dashboard } from '@/routes';

interface Docente {
    user_id: number;
    username: string;
    email: string;
    ci: string | null;
    apellido: string | null;
    nombres: string | null;
    telefono: string | null;
    titulo: string | null;
    experiencia_anios: number | null;
    tiene_diplomado: boolean;
    tiene_maestria: boolean;
    datos_completos: boolean;
    candidato_id: number | null;
}
interface Paginado {
    data: Docente[];
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
    datos: string;
    busqueda: string;
}

const props = defineProps<{
    docentes:  Paginado;
    resumen:   { completos: number; pendientes: number };
    gestiones: GestionOption[];
    filtros:   Filtros;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',   href: dashboard() },
            { title: 'Docentes', href: '/administracion/docentes' },
        ],
    },
});

const totales = computed(() => ({
    total:     props.resumen.completos + props.resumen.pendientes,
    completos: props.resumen.completos,
    pendientes: props.resumen.pendientes,
}));

// ── Filtros ──────────────────────────────────────────────────────────────────
const filtroDatos     = ref(props.filtros.datos);
const filtroGestionId = ref(props.filtros.gestion_id);
const busqueda        = ref(props.filtros.busqueda);

const hayFiltros = computed(() => !!(filtroDatos.value || filtroGestionId.value || busqueda.value));

function aplicarFiltros(page?: number) {
    const params: Record<string, string | number> = {};
    if (filtroDatos.value)     params.datos      = filtroDatos.value;
    if (filtroGestionId.value) params.gestion_id = filtroGestionId.value;
    if (busqueda.value.trim()) params.busqueda   = busqueda.value.trim();
    if (page && page > 1)      params.page       = page;
    router.get('/administracion/docentes', params, { preserveScroll: true, replace: true });
}

watch([filtroDatos, filtroGestionId], () => aplicarFiltros());

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
watch(busqueda, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => aplicarFiltros(), 400);
});

function limpiarFiltros() {
    filtroDatos.value     = '';
    filtroGestionId.value = '';
    busqueda.value        = '';
}

// ── Paginación ────────────────────────────────────────────────────────────────
function irAPagina(page: number) {
    if (page < 1 || page > props.docentes.last_page) return;
    aplicarFiltros(page);
}

const paginas = computed<(number | '...')[]>(() => {
    const total = props.docentes.last_page;
    const actual = props.docentes.current_page;
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
    <Head title="Gestionar Docentes" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestionar Docentes</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Cuerpo docente registrado en el sistema. Edita sus datos profesionales y revisa su documentación.
            </p>
        </div>

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                    {{ hayFiltros ? 'Resultados' : 'Total docentes' }}
                </p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ totales.total }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos completos</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ totales.completos }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos pendientes</p>
                <p class="mt-1 text-3xl font-bold text-amber-600">{{ totales.pendientes }}</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-end justify-between gap-3">
            <!-- Tabs datos completos -->
            <div class="flex gap-1 border-b border-gray-200">
                <button
                    v-for="opt in [
                        { v: '',           l: 'Todos' },
                        { v: 'completos',  l: 'Con datos completos' },
                        { v: 'pendientes', l: 'Datos pendientes' },
                    ]"
                    :key="opt.v"
                    type="button"
                    :class="[
                        'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                        filtroDatos === opt.v
                            ? 'border-[#c70e0a] text-[#c70e0a]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                    @click="filtroDatos = opt.v"
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
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#c70e0a]"
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
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#c70e0a] focus:outline-none focus:ring-1 focus:ring-[#c70e0a]"
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
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #7b0000;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">CI</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Nombre</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Usuario</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Título</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Experiencia</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estudios</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="docentes.data.length === 0">
                        <td colspan="7" class="px-5 py-14 text-center text-gray-400">
                            No hay docentes que coincidan con los filtros aplicados.
                        </td>
                    </tr>
                    <tr
                        v-for="d in docentes.data"
                        :key="d.user_id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ d.ci || '—' }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ d.apellido }} {{ d.nombres }}</p>
                            <p class="text-xs text-gray-500">{{ d.email }}</p>
                        </td>
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ d.username }}</td>
                        <td class="px-5 py-4 text-xs text-gray-700">
                            <span v-if="d.titulo">{{ d.titulo }}</span>
                            <span v-else class="italic text-amber-600">Sin registrar</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-700">
                            <span v-if="d.experiencia_anios !== null">{{ d.experiencia_anios }} año(s)</span>
                            <span v-else class="italic text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <span v-if="d.tiene_maestria" class="mr-1 inline-flex rounded-full bg-purple-100 px-2 py-0.5 font-semibold text-purple-700">
                                Maestría
                            </span>
                            <span v-if="d.tiene_diplomado" class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 font-semibold text-blue-700">
                                Diplomado
                            </span>
                            <span v-if="!d.tiene_diplomado && !d.tiene_maestria" class="text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <Link
                                :href="`/administracion/docentes/${d.user_id}/edit`"
                                class="rounded-md bg-[#c70e0a] px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110"
                            >
                                {{ d.datos_completos ? 'Editar' : 'Completar datos' }} →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pie de paginación -->
            <div
                v-if="docentes.last_page > 1 || docentes.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row"
            >
                <p class="text-xs text-gray-500">
                    <template v-if="docentes.total === 0">Sin resultados</template>
                    <template v-else>
                        Mostrando {{ docentes.from }}–{{ docentes.to }} de {{ docentes.total }} docentes
                    </template>
                </p>

                <div v-if="docentes.last_page > 1" class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="docentes.current_page === 1"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="irAPagina(docentes.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <template v-for="(p, i) in paginas" :key="i">
                        <span v-if="p === '...'" class="flex h-8 w-8 items-center justify-center text-xs text-gray-400">…</span>
                        <button
                            v-else
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border text-xs font-medium transition"
                            :class="p === docentes.current_page
                                ? 'border-transparent text-white'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                            :style="p === docentes.current_page ? 'background-color: #7b0000;' : ''"
                            @click="irAPagina(Number(p))"
                        >
                            {{ p }}
                        </button>
                    </template>

                    <button
                        type="button"
                        :disabled="docentes.current_page === docentes.last_page"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="irAPagina(docentes.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
