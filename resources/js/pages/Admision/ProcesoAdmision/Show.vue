<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { AlertTriangle, ArrowLeft, Play, Users } from 'lucide-vue-next';
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

interface Gestion {
    id: number;
    label: string;
    estado: string;
    nota_minima: number;
}

const props = defineProps<{
    gestion: Gestion;
    cupos: CupoCarrera[];
    ranking: RankingItem[];
    ejecutado: boolean;
    cuposTotales: number;
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
    pendiente:    { label: 'Pendiente',    clases: 'bg-gray-100 text-gray-600' },
    admitido:     { label: 'Admitido',     clases: 'bg-green-100 text-green-700' },
    no_admitido:  { label: 'No admitido',  clases: 'bg-red-100 text-red-700' },
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
        {
            onStart: () => (procesando.value = true),
            onFinish: () => (procesando.value = false),
        },
    );
}

function fmt(n: number | null): string {
    return n === null ? '—' : n.toFixed(2);
}
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
                        <span class="text-2xl font-bold" :class="cupo.asignados >= cupo.cupo_max && cupo.cupo_max > 0 ? 'text-red-600' : 'text-[#073b75]'">
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
                <Users class="h-4 w-4" /> Ranking de postulantes ({{ ranking.length }})
            </h2>

            <div v-if="ranking.length === 0" class="rounded-xl border border-dashed py-16 text-center text-sm text-gray-500">
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
                        <tr
                            v-for="item in ranking"
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
            </div>
        </div>
    </div>
</template>
