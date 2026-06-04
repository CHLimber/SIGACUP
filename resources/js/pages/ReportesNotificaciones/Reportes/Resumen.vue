<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    BarChart3,
    CheckCircle2,
    DollarSign,
    GraduationCap,
    Printer,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';

interface Kpis {
    postulaciones: number;
    admitidos: number;
    no_admitidos: number;
    pendientes: number;
    tasa_admision: number;
    promedio_general: number | null;
    recaudacion_bs: number;
    pagos_pagados: number;
    pagos_pendientes: number;
    grupos: number;
    docentes: number;
}
interface FilaCarrera {
    carrera: string;
    primera_opcion: number;
    admitidos: number;
    cupo: number;
    ocupacion: number | null;
    promedio: number | null;
}
interface FilaGestion {
    gestion: string;
    estado: string;
    postulaciones: number;
    admitidos: number;
    tasa_admision: number;
    recaudacion: number;
    pagos: number;
}
interface FilaMateria {
    materia: string;
    evaluaciones: number;
    promedio: number;
    maxima: number;
    minima: number;
}

const props = defineProps<{
    resumen: {
        kpis: Kpis;
        porCarrera: FilaCarrera[];
        porGestion: FilaGestion[];
        porMateria: FilaMateria[];
        admisionDist: { label: string; total: number }[];
    };
    gestiones: { value: string; label: string }[];
    gestionId: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Reportes', href: '/administracion/reportes' },
            { title: 'Resumen estadístico', href: '#' },
        ],
    },
});

const gestion = ref<string>(props.gestionId ?? '');

function cambiarGestion() {
    router.get('/administracion/reportes/resumen', gestion.value ? { gestion_id: gestion.value } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

function imprimir() {
    window.print();
}

const COLORES = ['#073b75', '#0e9f6e', '#c70e0a', '#ff8a4c', '#9061f9', '#16bdca'];

function bs(n: number): string {
    return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

const ESTADO_LABEL: Record<string, string> = {
    admitido: 'Admitidos',
    no_admitido: 'No admitidos',
    pendiente: 'Pendientes',
};

const distTotal = computed(() => props.resumen.admisionDist.reduce((a, b) => a + b.total, 0) || 1);

const donut = computed(() => {
    const r = 60;
    const circ = 2 * Math.PI * r;
    let offset = 0;

    return props.resumen.admisionDist.map((item, i) => {
        const frac = item.total / distTotal.value;
        const seg = {
            color: COLORES[i % COLORES.length],
            dash: frac * circ,
            gap: circ - frac * circ,
            offset: -offset,
            pct: Math.round(frac * 100),
            label: ESTADO_LABEL[item.label] ?? item.label,
            total: item.total,
        };
        offset += frac * circ;

        return seg;
    });
});

const maxPromMateria = computed(() => Math.max(1, ...props.resumen.porMateria.map((m) => m.promedio)));

const kpiCards = computed(() => {
    const k = props.resumen.kpis;

    return [
        { label: 'Postulaciones', valor: k.postulaciones, icon: Users, color: '#073b75' },
        { label: 'Admitidos', valor: k.admitidos, icon: CheckCircle2, color: '#0e9f6e' },
        { label: 'Tasa de admisión', valor: k.tasa_admision + ' %', icon: Award, color: '#9061f9' },
        { label: 'Promedio general', valor: k.promedio_general ?? '—', icon: BarChart3, color: '#ff8a4c' },
        { label: 'Recaudación', valor: bs(k.recaudacion_bs) + ' Bs', icon: DollarSign, color: '#16bdca' },
        { label: 'Docentes', valor: k.docentes, icon: GraduationCap, color: '#c70e0a' },
    ];
});
</script>

<template>
    <Head title="Resumen estadístico" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <Link
                    href="/administracion/reportes"
                    class="text-muted-foreground hover:text-foreground mb-1 inline-flex items-center gap-1 text-xs print:hidden"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Volver a reportes
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Resumen estadístico</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Indicadores agregados, tasas de admisión por carrera, recaudación por gestión y promedios por materia.
                </p>
            </div>
            <div class="flex items-center gap-3 print:hidden">
                <select
                    v-model="gestion"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                    @change="cambiarGestion"
                >
                    <option value="">Todas las gestiones</option>
                    <option v-for="g in gestiones" :key="g.value" :value="g.value">{{ g.label }}</option>
                </select>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="imprimir"
                >
                    <Printer class="h-4 w-4" /> Imprimir / PDF
                </button>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div
                v-for="card in kpiCards"
                :key="card.label"
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ card.label }}</span>
                    <component :is="card.icon" class="h-4 w-4" :style="{ color: card.color }" />
                </div>
                <p class="mt-2 text-2xl font-bold" :style="{ color: card.color }">{{ card.valor }}</p>
            </div>
        </div>

        <!-- Distribución de admisión + por materia -->
        <div class="grid gap-5 lg:grid-cols-2">
            <!-- Dona admisión -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">Distribución de admisión</h3>
                <div v-if="distTotal > 1" class="flex items-center gap-6">
                    <svg viewBox="0 0 160 160" class="h-40 w-40 shrink-0 -rotate-90">
                        <circle cx="80" cy="80" r="60" fill="none" stroke="#f1f5f9" stroke-width="22" />
                        <circle
                            v-for="(seg, i) in donut"
                            :key="i"
                            cx="80"
                            cy="80"
                            r="60"
                            fill="none"
                            :stroke="seg.color"
                            stroke-width="22"
                            :stroke-dasharray="`${seg.dash} ${seg.gap}`"
                            :stroke-dashoffset="seg.offset"
                        />
                    </svg>
                    <div class="flex flex-col gap-1.5">
                        <div v-for="(seg, i) in donut" :key="i" class="flex items-center gap-2 text-xs">
                            <span class="h-3 w-3 rounded-sm" :style="{ backgroundColor: seg.color }" />
                            <span class="text-gray-700">{{ seg.label }}</span>
                            <span class="font-semibold text-gray-900">{{ seg.total }} ({{ seg.pct }}%)</span>
                        </div>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-gray-400">Sin datos de admisión.</p>
            </div>

            <!-- Promedio por materia -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-gray-700">Promedio de nota por materia</h3>
                <div v-if="resumen.porMateria.length" class="flex flex-col gap-2.5">
                    <div v-for="(m, i) in resumen.porMateria" :key="m.materia" class="flex items-center gap-3">
                        <span class="w-28 shrink-0 truncate text-xs text-gray-600" :title="m.materia">{{ m.materia }}</span>
                        <div class="h-5 flex-1 overflow-hidden rounded bg-gray-100">
                            <div
                                class="flex h-full items-center justify-end rounded px-2 text-[10px] font-semibold text-white"
                                :style="{ width: Math.max(8, (m.promedio / maxPromMateria) * 100) + '%', backgroundColor: COLORES[i % COLORES.length] }"
                            >
                                {{ m.promedio }}
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="py-8 text-center text-sm text-gray-400">Sin calificaciones registradas.</p>
            </div>
        </div>

        <!-- Tabla por carrera -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3" style="background-color: #073b75;">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Admisión por carrera</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 font-semibold">Carrera</th>
                            <th class="px-4 py-3 text-center font-semibold">1ª opción</th>
                            <th class="px-4 py-3 text-center font-semibold">Admitidos</th>
                            <th class="px-4 py-3 text-center font-semibold">Cupo</th>
                            <th class="px-4 py-3 text-center font-semibold">Ocupación</th>
                            <th class="px-4 py-3 text-center font-semibold">Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="fila in resumen.porCarrera" :key="fila.carrera" class="border-b border-gray-50 last:border-0 hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-medium text-gray-900">{{ fila.carrera }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.primera_opcion }}</td>
                            <td class="px-4 py-2.5 text-center font-semibold text-[#073b75]">{{ fila.admitidos }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.cupo }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span v-if="fila.ocupacion !== null" :class="fila.ocupacion >= 100 ? 'font-semibold text-red-600' : 'text-gray-700'">
                                    {{ fila.ocupacion }} %
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.promedio ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla por gestión -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-3" style="background-color: #073b75;">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Resumen por gestión</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 font-semibold">Gestión</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 text-center font-semibold">Postulaciones</th>
                            <th class="px-4 py-3 text-center font-semibold">Admitidos</th>
                            <th class="px-4 py-3 text-center font-semibold">Tasa admisión</th>
                            <th class="px-4 py-3 text-center font-semibold">Pagos</th>
                            <th class="px-4 py-3 text-right font-semibold">Recaudación (Bs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="fila in resumen.porGestion" :key="fila.gestion" class="border-b border-gray-50 last:border-0 hover:bg-gray-50">
                            <td class="px-4 py-2.5 font-medium text-gray-900">{{ fila.gestion }}</td>
                            <td class="px-4 py-2.5 capitalize text-gray-600">{{ fila.estado }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.postulaciones }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.admitidos }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.tasa_admision }} %</td>
                            <td class="px-4 py-2.5 text-center text-gray-700">{{ fila.pagos }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-900">{{ bs(fila.recaudacion) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
