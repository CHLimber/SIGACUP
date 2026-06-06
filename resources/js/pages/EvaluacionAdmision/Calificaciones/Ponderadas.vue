<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { LayoutGrid, Search, Table2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';

interface MateriaCol {
    codigo: string;
    nombre: string;
}

interface EstudianteRow {
    postulacion_id: number;
    nombre: string;
    grupo: string;
    ponderadas: Record<string, number>;
    promedio: number | null;
    estado: 'aprobado' | 'reprobado' | 'reprobado_materia' | null;
}

type EstadoFiltro = '' | 'aprobado' | 'reprobado' | 'reprobado_materia';

interface GestionItem {
    id: number;
    label: string;
    anio: number;
    semestre: number;
}

const props = defineProps<{
    gestiones: GestionItem[];
    gestionSeleccionada: number | null;
    materias: MateriaCol[];
    estudiantes: EstudianteRow[];
    notaMinima: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',         href: dashboard() },
            { title: 'Calificaciones', href: '/administracion/calificaciones' },
            { title: 'Ponderadas',     href: '#' },
        ],
    },
});

// --- Filtros ---
const gestionId = ref<number | null>(props.gestionSeleccionada);
const grupoFiltro = ref<string>('');
const estadoFiltro = ref<EstadoFiltro>('');
const busqueda = ref<string>('');

function cambiarGestion() {
    router.get(
        '/administracion/calificaciones/ponderadas',
        { gestion: gestionId.value },
        { preserveState: false, preserveScroll: true },
    );
}

// Lista de grupos presentes (para el filtro), partiendo de los estudiantes cargados.
const grupos = computed(() => {
    const set = new Set<string>();

    for (const e of props.estudiantes) {
        if (e.grupo && e.grupo !== '—') {
            e.grupo.split(', ').forEach((g) => set.add(g));
        }
    }

    return Array.from(set).sort();
});

const estudiantesFiltrados = computed(() =>
    props.estudiantes.filter((e) => {
        const coincideGrupo = !grupoFiltro.value || e.grupo.split(', ').includes(grupoFiltro.value);
        const coincideEstado = !estadoFiltro.value || e.estado === estadoFiltro.value;
        const coincideNombre = !busqueda.value || e.nombre.toLowerCase().includes(busqueda.value.toLowerCase());

        return coincideGrupo && coincideEstado && coincideNombre;
    }),
);

// Los totales se calculan sobre todos los estudiantes (no sobre el filtro de
// estado) para que las tarjetas sirvan como resumen global de la gestión.
const baseResumen = computed(() =>
    props.estudiantes.filter((e) => {
        const coincideGrupo = !grupoFiltro.value || e.grupo.split(', ').includes(grupoFiltro.value);
        const coincideNombre = !busqueda.value || e.nombre.toLowerCase().includes(busqueda.value.toLowerCase());

        return coincideGrupo && coincideNombre;
    }),
);

const totalAprobados = computed(() => baseResumen.value.filter((e) => e.estado === 'aprobado').length);
const totalReprobados = computed(() => baseResumen.value.filter((e) => e.estado === 'reprobado').length);
const totalReprobadosMateria = computed(
    () => baseResumen.value.filter((e) => e.estado === 'reprobado_materia').length,
);

function fmt(v: number | null | undefined): string {
    return v === null || v === undefined ? '—' : v.toFixed(2);
}
</script>

<template>
    <Head title="Calificaciones ponderadas" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Calificaciones</h1>
            <p class="text-muted-foreground mt-2 text-sm">
                Nota final ponderada de cada estudiante por materia, según la gestión seleccionada.
            </p>
        </div>

        <!-- Pestañas -->
        <div class="border-b">
            <nav class="-mb-px flex gap-6">
                <Link
                    href="/administracion/calificaciones"
                    class="text-muted-foreground hover:text-foreground flex items-center gap-1.5 border-b-2 border-transparent pb-3 text-sm font-medium transition-colors"
                >
                    <LayoutGrid class="h-4 w-4" />
                    Por grupo
                </Link>
                <span
                    class="border-primary text-foreground flex items-center gap-1.5 border-b-2 pb-3 text-sm font-medium"
                >
                    <Table2 class="h-4 w-4" />
                    Ponderadas
                </span>
            </nav>
        </div>

        <!-- Sin gestiones -->
        <template v-if="gestiones.length === 0">
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed py-24 text-center">
                <Table2 class="text-muted-foreground mb-4 h-12 w-12" />
                <p class="text-muted-foreground text-sm">No hay gestiones registradas aún.</p>
            </div>
        </template>

        <template v-else>
            <!-- Filtros -->
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-muted-foreground text-xs font-semibold uppercase tracking-wider">Gestión</label>
                    <select
                        v-model="gestionId"
                        class="h-9 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                        @change="cambiarGestion"
                    >
                        <option v-for="g in gestiones" :key="g.id" :value="g.id">
                            {{ g.anio }} – {{ g.semestre === 1 ? '1er Semestre' : '2do Semestre' }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-muted-foreground text-xs font-semibold uppercase tracking-wider">Grupo</label>
                    <select
                        v-model="grupoFiltro"
                        class="h-9 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                    >
                        <option value="">Todos</option>
                        <option v-for="g in grupos" :key="g" :value="g">Grupo {{ g }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-muted-foreground text-xs font-semibold uppercase tracking-wider">Estado</label>
                    <select
                        v-model="estadoFiltro"
                        class="h-9 rounded-md border border-gray-300 bg-white px-3 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                    >
                        <option value="">Todos</option>
                        <option value="aprobado">Aprobados</option>
                        <option value="reprobado">Reprobados (por promedio)</option>
                        <option value="reprobado_materia">Reprobados por materia</option>
                    </select>
                </div>

                <div class="flex flex-1 flex-col gap-1.5 sm:max-w-xs">
                    <label class="text-muted-foreground text-xs font-semibold uppercase tracking-wider">Buscar</label>
                    <div class="relative">
                        <Search class="text-muted-foreground absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" />
                        <input
                            v-model="busqueda"
                            type="text"
                            placeholder="Nombre del estudiante…"
                            class="h-9 w-full rounded-md border border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                        />
                    </div>
                </div>
            </div>

            <!-- Tarjetas de resumen -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estudiantes</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900">{{ baseResumen.length }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aprobados</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">{{ totalAprobados }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reprob. promedio</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">{{ totalReprobados }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reprob. materia</p>
                    <p class="mt-1 text-3xl font-bold text-amber-600">{{ totalReprobadosMateria }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Nota mínima</p>
                    <p class="mt-1 text-3xl font-bold" style="color: #073b75;">{{ notaMinima }}</p>
                </div>
            </div>

            <!-- Sin estudiantes -->
            <div
                v-if="estudiantesFiltrados.length === 0"
                class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center"
            >
                <p class="text-gray-500">No hay estudiantes que coincidan con los filtros.</p>
            </div>

            <!-- Tabla de ponderadas -->
            <div v-else class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background-color: #060041;">
                            <th class="sticky left-0 z-10 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white" style="background-color: #060041;">
                                Estudiante
                            </th>
                            <th class="border-l border-white/20 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white">
                                Grupo
                            </th>
                            <th
                                v-for="materia in materias"
                                :key="materia.codigo"
                                class="border-l border-white/20 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white"
                            >
                                {{ materia.nombre }}
                                <span class="block text-[10px] font-normal normal-case text-white/60">Ponderada</span>
                            </th>
                            <th class="border-l border-white/20 bg-[#0a2f5e] px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white">
                                Promedio
                            </th>
                            <th class="border-l border-white/20 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white">
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="est in estudiantesFiltrados"
                            :key="est.postulacion_id"
                            class="transition-colors hover:bg-gray-50"
                        >
                            <td class="sticky left-0 z-10 bg-white px-5 py-3.5 font-medium text-gray-900">
                                {{ est.nombre }}
                            </td>
                            <td class="border-l border-gray-100 px-5 py-3.5 text-center text-gray-600">
                                {{ est.grupo }}
                            </td>
                            <td
                                v-for="materia in materias"
                                :key="materia.codigo"
                                class="border-l border-gray-100 px-4 py-3.5 text-center tabular-nums"
                                :class="
                                    est.ponderadas[materia.codigo] === undefined
                                        ? 'text-gray-300'
                                        : est.ponderadas[materia.codigo] < notaMinima
                                          ? 'bg-red-50 font-semibold text-red-600'
                                          : 'text-gray-700'
                                "
                            >
                                {{ fmt(est.ponderadas[materia.codigo]) }}
                            </td>
                            <td class="border-l border-gray-100 bg-gray-50 px-5 py-3.5 text-center text-base font-bold tabular-nums text-gray-900">
                                {{ fmt(est.promedio) }}
                            </td>
                            <td class="border-l border-gray-100 px-5 py-3.5 text-center">
                                <span
                                    v-if="est.estado === 'aprobado'"
                                    class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700"
                                >
                                    Aprobado
                                </span>
                                <span
                                    v-else-if="est.estado === 'reprobado'"
                                    class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700"
                                >
                                    Reprobado
                                </span>
                                <span
                                    v-else-if="est.estado === 'reprobado_materia'"
                                    class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700"
                                    title="Promedio suficiente, pero tiene una materia por debajo de la mínima"
                                >
                                    Reprobado por materia
                                </span>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
