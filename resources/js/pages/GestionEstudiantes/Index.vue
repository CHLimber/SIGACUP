<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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

interface Totales {
    total: number;
    aprobados: number;
    pagados: number;
}

const props = defineProps<{
    estudiantes: Estudiante[];
    totales:     Totales;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',      href: dashboard() },
            { title: 'Estudiantes', href: '/administracion/estudiantes' },
        ],
    },
});

const filtro   = ref<'todos' | 'aprobados' | 'pagados'>('todos');
const busqueda = ref('');

const estadoConfig: Record<EstadoEstudiante, { label: string; clases: string }> = {
    aprobado_pendiente_pago: { label: 'Aprobado · Pendiente de pago', clases: 'bg-yellow-100 text-yellow-700' },
    pagado:                  { label: 'Pagado',                       clases: 'bg-green-100 text-green-700' },
};

const estudiantesFiltrados = computed(() => {
    let lista = props.estudiantes;

    if (filtro.value === 'aprobados') {
        lista = lista.filter((e) => e.estado === 'aprobado_pendiente_pago');
    } else if (filtro.value === 'pagados') {
        lista = lista.filter((e) => e.estado === 'pagado');
    }

    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase().trim();
        lista = lista.filter((e) =>
            (e.ci || '').toLowerCase().includes(q)
            || (e.apellido || '').toLowerCase().includes(q)
            || (e.nombres || '').toLowerCase().includes(q)
            || (e.email || '').toLowerCase().includes(q),
        );
    }

    return lista;
});
</script>

<template>
    <Head title="Gestionar Estudiantes" />

    <div class="flex flex-col gap-6 p-6">
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
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total</p>
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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-1 border-b border-gray-200">
                <button
                    v-for="opt in [
                        { v: 'todos',     l: 'Todos' },
                        { v: 'aprobados', l: 'Aprobados' },
                        { v: 'pagados',   l: 'Pagados' },
                    ]"
                    :key="opt.v"
                    type="button"
                    :class="[
                        'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                        filtro === opt.v
                            ? 'border-[#073b75] text-[#073b75]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                    @click="filtro = opt.v as 'todos' | 'aprobados' | 'pagados'"
                >
                    {{ opt.l }}
                </button>
            </div>
            <input
                v-model="busqueda"
                placeholder="Buscar por CI, nombre o email…"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
            />
        </div>

        <!-- Tabla -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
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
                    <tr v-if="estudiantesFiltrados.length === 0">
                        <td colspan="6" class="px-5 py-14 text-center text-gray-400">
                            No hay estudiantes que coincidan con el filtro.
                        </td>
                    </tr>
                    <tr
                        v-for="e in estudiantesFiltrados"
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
    </div>
</template>
