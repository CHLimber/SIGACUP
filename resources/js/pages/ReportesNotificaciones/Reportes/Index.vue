<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowDownUp,
    BarChart3,
    Download,
    FileText,
    Filter,
    PieChart,
    Search,
    Table as TableIcon,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import { dashboard } from '@/routes';

interface ColumnaMeta {
    key: string;
    label: string;
    type: string;
}
interface FiltroOption {
    value: string;
    label: string;
}
interface FiltroMeta {
    key: string;
    label: string;
    type: 'text' | 'select' | 'daterange' | 'numberrange' | 'boolean';
    options?: FiltroOption[];
}
interface DimensionMeta {
    key: string;
    label: string;
}
interface ReporteMeta {
    key: string;
    label: string;
    descripcion: string;
    columnas: ColumnaMeta[];
    columnasPorDefecto: string[];
    filtros: FiltroMeta[];
    dimensiones: DimensionMeta[];
}
interface ResumenItem {
    label: string;
    total: number;
}
interface Resultado {
    reporte: string;
    rows: Record<string, unknown>[];
    total: number;
    resumen: { dimension: string | null; label: string | null; items: ResumenItem[] };
    columnas: ColumnaMeta[];
    sort: string;
    dir: string;
    dimension: string | null;
}

const props = defineProps<{
    reportes: ReporteMeta[];
    consulta: {
        reporte: string | null;
        filtros: Record<string, unknown>;
        columnas: string[];
        sort: string | null;
        dir: string;
        dimension: string | null;
    };
    resultado: Resultado | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Reportes', href: '#' },
        ],
    },
});

const reporteActivo = ref<string | null>(props.consulta.reporte ?? null);
const filtros = reactive<Record<string, any>>({ ...(props.consulta.filtros ?? {}) });
const columnas = ref<string[]>(props.consulta.columnas?.length ? [...props.consulta.columnas] : []);
const sort = ref<string | null>(props.consulta.sort);
const dir = ref<string>(props.consulta.dir ?? 'asc');
const dimension = ref<string | null>(props.consulta.dimension);
const generando = ref(false);

const reporteMeta = computed<ReporteMeta | null>(
    () => props.reportes.find((r) => r.key === reporteActivo.value) ?? null,
);

const COLORES = ['#073b75', '#c70e0a', '#0e9f6e', '#ff8a4c', '#9061f9', '#16bdca', '#e3a008', '#6875f5'];

function ensureRangos(meta: ReporteMeta) {
    for (const f of meta.filtros) {
        if (f.type === 'daterange' && typeof filtros[f.key] !== 'object') {
filtros[f.key] = { desde: '', hasta: '' };
}

        if (f.type === 'numberrange' && typeof filtros[f.key] !== 'object') {
filtros[f.key] = { min: '', max: '' };
}
    }
}

if (reporteMeta.value) {
ensureRangos(reporteMeta.value);
}

function seleccionarReporte(key: string) {
    if (key === reporteActivo.value) {
return;
}

    reporteActivo.value = key;
    const meta = props.reportes.find((r) => r.key === key);
    Object.keys(filtros).forEach((k) => delete filtros[k]);
    columnas.value = meta ? [...meta.columnasPorDefecto] : [];
    sort.value = meta?.columnas[0]?.key ?? null;
    dir.value = 'asc';
    dimension.value = meta?.dimensiones[0]?.key ?? null;

    if (meta) {
ensureRangos(meta);
}
}

function toggleColumna(key: string) {
    const i = columnas.value.indexOf(key);

    if (i === -1) {
columnas.value.push(key);
} else if (columnas.value.length > 1) {
columnas.value.splice(i, 1);
}
}

function limpiarFiltros() {
    if (!reporteMeta.value) {
return;
}

    Object.keys(filtros).forEach((k) => delete filtros[k]);
    ensureRangos(reporteMeta.value);
}

function construirData() {
    const limpios: Record<string, unknown> = {};

    for (const [k, v] of Object.entries(filtros)) {
        if (v === null || v === undefined || v === '') {
continue;
}

        if (typeof v === 'object') {
            const sub: Record<string, unknown> = {};

            for (const [sk, sv] of Object.entries(v as Record<string, unknown>)) {
                if (sv !== null && sv !== undefined && sv !== '') {
sub[sk] = sv;
}
            }

            if (Object.keys(sub).length) {
limpios[k] = sub;
}
        } else {
            limpios[k] = v;
        }
    }

    return {
        reporte: reporteActivo.value,
        filtros: limpios,
        columnas: columnas.value,
        sort: sort.value,
        dir: dir.value,
        dimension: dimension.value,
    };
}

function generar() {
    if (!reporteActivo.value) {
return;
}

    router.get('/administracion/reportes', construirData() as unknown as Record<string, string>, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => (generando.value = true),
        onFinish: () => (generando.value = false),
    });
}

function ordenarPor(key: string) {
    if (sort.value === key) {
dir.value = dir.value === 'asc' ? 'desc' : 'asc';
} else {
        sort.value = key;
        dir.value = 'asc';
    }

    generar();
}

function toQuery(obj: Record<string, unknown>, prefix = ''): string {
    const parts: string[] = [];

    for (const [key, val] of Object.entries(obj)) {
        if (val === null || val === undefined || val === '') {
continue;
}

        const k = prefix ? `${prefix}[${key}]` : key;

        if (Array.isArray(val)) {
            val.forEach((v, i) => parts.push(`${encodeURIComponent(`${k}[${i}]`)}=${encodeURIComponent(String(v))}`));
        } else if (typeof val === 'object') {
            const nested = toQuery(val as Record<string, unknown>, k);

            if (nested) {
parts.push(nested);
}
        } else {
            parts.push(`${encodeURIComponent(k)}=${encodeURIComponent(String(val))}`);
        }
    }

    return parts.join('&');
}

function exportarCsv() {
    if (!reporteActivo.value) {
return;
}

    window.location.href = '/administracion/reportes/exportar/csv?' + toQuery(construirData());
}

function exportarPdf() {
    if (!reporteActivo.value) {
return;
}

    window.location.href = '/administracion/reportes/exportar/pdf?' + toQuery(construirData());
}

// ── Presentación de celdas ───────────────────────────────────────────────
const ESTADO_CLASES: Record<string, string> = {
    admitido: 'bg-green-100 text-green-700',
    pagado: 'bg-green-100 text-green-700',
    no_admitido: 'bg-red-100 text-red-700',
    rechazado: 'bg-red-100 text-red-700',
    fallido: 'bg-red-100 text-red-700',
    pendiente: 'bg-gray-100 text-gray-600',
    aprobado_pendiente_pago: 'bg-blue-100 text-blue-700',
    en_revision: 'bg-yellow-100 text-yellow-700',
    requiere_correcciones: 'bg-orange-100 text-orange-700',
};

function badgeClase(valor: unknown): string {
    return ESTADO_CLASES[String(valor)] ?? 'bg-slate-100 text-slate-600';
}

function mostrar(valor: unknown): string {
    if (valor === null || valor === undefined || valor === '') {
return '—';
}

    if (typeof valor === 'boolean') {
return valor ? 'Sí' : 'No';
}

    return String(valor);
}

const resumen = computed(() => props.resultado?.resumen ?? null);
const maxResumen = computed(() => Math.max(1, ...(resumen.value?.items.map((i) => i.total) ?? [1])));
const totalResumen = computed(() => resumen.value?.items.reduce((a, b) => a + b.total, 0) ?? 0);

// Segmentos para la dona (SVG).
const donut = computed(() => {
    const items = resumen.value?.items ?? [];
    const total = totalResumen.value || 1;
    const r = 60;
    const circ = 2 * Math.PI * r;
    let offset = 0;

    return items.slice(0, COLORES.length).map((item, i) => {
        const frac = item.total / total;
        const seg = {
            color: COLORES[i % COLORES.length],
            dash: frac * circ,
            gap: circ - frac * circ,
            offset: -offset,
            pct: Math.round(frac * 100),
            label: item.label,
            total: item.total,
        };
        offset += frac * circ;

        return seg;
    });
});

const filtrosActivos = computed(() => {
    const meta = reporteMeta.value;

    if (!meta || !props.resultado) {
return [] as { label: string; valor: string }[];
}

    const out: { label: string; valor: string }[] = [];

    for (const f of meta.filtros) {
        const v = props.consulta.filtros?.[f.key];

        if (v === null || v === undefined || v === '') {
continue;
}

        if (typeof v === 'object') {
            const o = v as Record<string, string>;
            const partes = Object.values(o).filter((x) => x !== '' && x != null);

            if (partes.length) {
out.push({ label: f.label, valor: partes.join(' — ') });
}
        } else {
            const opt = f.options?.find((o) => o.value === String(v));
            out.push({ label: f.label, valor: opt?.label ?? String(v) });
        }
    }

    return out;
});
</script>

<template>
    <Head title="Reportes" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado (oculto al imprimir) -->
        <div class="flex flex-col gap-3 print:hidden sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reportes</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Generá reportes con filtros dinámicos, elegí las columnas, ordená y exportá a CSV o PDF.
                </p>
            </div>
            <Link
                href="/administracion/reportes/resumen"
                class="inline-flex shrink-0 items-center gap-2 rounded-md border border-[#073b75] px-4 py-2 text-sm font-semibold text-[#073b75] transition hover:bg-[#073b75]/5"
            >
                <BarChart3 class="h-4 w-4" /> Resumen estadístico
            </Link>
        </div>

        <!-- Selección de reporte -->
        <div class="print:hidden">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <button
                    v-for="r in reportes"
                    :key="r.key"
                    type="button"
                    class="flex flex-col gap-1 rounded-xl border p-4 text-left transition-colors"
                    :class="reporteActivo === r.key
                        ? 'border-[#073b75] bg-[#073b75]/5 ring-1 ring-[#073b75]'
                        : 'border-gray-200 hover:border-[#073b75]/40 hover:bg-gray-50'"
                    @click="seleccionarReporte(r.key)"
                >
                    <span class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                        <FileText class="h-4 w-4 text-[#073b75]" /> {{ r.label }}
                    </span>
                    <span class="text-xs text-gray-500">{{ r.descripcion }}</span>
                </button>
            </div>
        </div>

        <template v-if="reporteMeta">
            <!-- Panel de configuración -->
            <div class="grid gap-6 lg:grid-cols-[1fr_320px] print:hidden">
                <!-- Filtros -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3" style="background-color: #073b75;">
                        <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-white">
                            <Filter class="h-4 w-4" /> Filtros
                        </h2>
                        <button type="button" class="text-xs font-medium text-white/80 hover:text-white" @click="limpiarFiltros">
                            Limpiar
                        </button>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div v-for="f in reporteMeta.filtros" :key="f.key" class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-gray-700">{{ f.label }}</label>

                            <!-- Texto -->
                            <div v-if="f.type === 'text'" class="relative">
                                <Search class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                                <input
                                    v-model="filtros[f.key]"
                                    type="text"
                                    placeholder="Buscar…"
                                    class="w-full rounded-md border border-gray-300 bg-white py-2 pl-8 pr-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                    @keyup.enter="generar"
                                />
                            </div>

                            <!-- Select -->
                            <select
                                v-else-if="f.type === 'select'"
                                v-model="filtros[f.key]"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            >
                                <option value="">Todos</option>
                                <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
                            </select>

                            <!-- Booleano -->
                            <select
                                v-else-if="f.type === 'boolean'"
                                v-model="filtros[f.key]"
                                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            >
                                <option value="">Todos</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>

                            <!-- Rango numérico -->
                            <div v-else-if="f.type === 'numberrange'" class="flex items-center gap-2">
                                <input
                                    v-model="filtros[f.key].min"
                                    type="number"
                                    placeholder="mín"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                />
                                <span class="text-gray-400">–</span>
                                <input
                                    v-model="filtros[f.key].max"
                                    type="number"
                                    placeholder="máx"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                />
                            </div>

                            <!-- Rango de fechas -->
                            <div v-else-if="f.type === 'daterange'" class="flex items-center gap-2">
                                <input
                                    v-model="filtros[f.key].desde"
                                    type="date"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                />
                                <span class="text-gray-400">–</span>
                                <input
                                    v-model="filtros[f.key].hasta"
                                    type="date"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columnas + orden -->
                <div class="flex flex-col gap-4">
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-5 py-3" style="background-color: #073b75;">
                            <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-white">
                                <TableIcon class="h-4 w-4" /> Columnas
                            </h2>
                        </div>
                        <div class="flex flex-wrap gap-2 p-4">
                            <button
                                v-for="c in reporteMeta.columnas"
                                :key="c.key"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                :class="columnas.includes(c.key)
                                    ? 'border-[#073b75] bg-[#073b75] text-white'
                                    : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'"
                                @click="toggleColumna(c.key)"
                            >
                                {{ c.label }}
                            </button>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-5 py-3" style="background-color: #073b75;">
                            <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-white">
                                <ArrowDownUp class="h-4 w-4" /> Orden y gráfico
                            </h2>
                        </div>
                        <div class="flex flex-col gap-3 p-4">
                            <div class="flex items-center gap-2">
                                <select
                                    v-model="sort"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                >
                                    <option v-for="c in reporteMeta.columnas" :key="c.key" :value="c.key">{{ c.label }}</option>
                                </select>
                                <select
                                    v-model="dir"
                                    class="rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                >
                                    <option value="asc">Asc</option>
                                    <option value="desc">Desc</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-700">Agrupar gráfico por</label>
                                <select
                                    v-model="dimension"
                                    class="w-full rounded-md border border-gray-300 bg-white px-2 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                >
                                    <option v-for="d in reporteMeta.dimensiones" :key="d.key" :value="d.key">{{ d.label }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex flex-wrap items-center gap-3 print:hidden">
                <button
                    type="button"
                    :disabled="generando"
                    class="inline-flex items-center gap-2 rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60"
                    style="background-color: #c70e0a;"
                    @click="generar"
                >
                    <Search class="h-4 w-4" /> {{ generando ? 'Generando…' : 'Generar reporte' }}
                </button>
                <button
                    v-if="resultado"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="exportarCsv"
                >
                    <Download class="h-4 w-4" /> CSV / Excel
                </button>
                <button
                    v-if="resultado"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="exportarPdf"
                >
                    <FileText class="h-4 w-4" /> PDF
                </button>
            </div>

            <!-- Resultados -->
            <template v-if="resultado">
                <!-- Encabezado imprimible -->
                <div class="hidden print:block">
                    <h1 class="text-xl font-bold">{{ reporteMeta.label }}</h1>
                    <p class="text-sm text-gray-600">
                        Generado el {{ new Date().toLocaleString() }} · {{ resultado.total }} registro(s)
                    </p>
                    <p v-if="filtrosActivos.length" class="mt-1 text-xs text-gray-500">
                        <span v-for="(fa, i) in filtrosActivos" :key="i">
                            <strong>{{ fa.label }}:</strong> {{ fa.valor }}<span v-if="i < filtrosActivos.length - 1"> · </span>
                        </span>
                    </p>
                </div>

                <!-- Gráficos -->
                <div v-if="resumen && resumen.items.length" class="grid gap-5 lg:grid-cols-2 print:hidden">
                    <!-- Barras -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <BarChart3 class="h-4 w-4 text-[#073b75]" /> Por {{ resumen.label }}
                        </h3>
                        <div class="flex flex-col gap-2.5">
                            <div v-for="(item, i) in resumen.items" :key="item.label" class="flex items-center gap-3">
                                <span class="w-32 shrink-0 truncate text-xs text-gray-600" :title="item.label">{{ item.label }}</span>
                                <div class="h-5 flex-1 overflow-hidden rounded bg-gray-100">
                                    <div
                                        class="flex h-full items-center justify-end rounded px-2 text-[10px] font-semibold text-white"
                                        :style="{ width: Math.max(8, (item.total / maxResumen) * 100) + '%', backgroundColor: COLORES[i % COLORES.length] }"
                                    >
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dona -->
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <PieChart class="h-4 w-4 text-[#073b75]" /> Distribución
                        </h3>
                        <div class="flex items-center gap-6">
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
                    </div>
                </div>

                <!-- Tabla -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 print:hidden">
                        <h3 class="text-sm font-semibold text-gray-700">Resultados</h3>
                        <span class="text-xs text-gray-500">{{ resultado.total }} registro(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-500">
                                    <th
                                        v-for="c in resultado.columnas"
                                        :key="c.key"
                                        class="cursor-pointer select-none px-4 py-3 font-semibold hover:text-[#073b75] print:cursor-auto"
                                        @click="ordenarPor(c.key)"
                                    >
                                        <span class="inline-flex items-center gap-1">
                                            {{ c.label }}
                                            <ArrowDownUp v-if="resultado.sort === c.key" class="h-3 w-3" />
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="resultado.rows.length === 0">
                                    <td :colspan="resultado.columnas.length" class="px-4 py-10 text-center text-sm text-gray-500">
                                        No hay registros que coincidan con los filtros.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(row, ri) in resultado.rows"
                                    :key="ri"
                                    class="border-b border-gray-50 last:border-0 hover:bg-gray-50"
                                >
                                    <td v-for="c in resultado.columnas" :key="c.key" class="px-4 py-2.5 text-gray-700">
                                        <span
                                            v-if="c.type === 'badge' && row[c.key]"
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="badgeClase(row[c.key])"
                                        >
                                            {{ mostrar(row[c.key]) }}
                                        </span>
                                        <span v-else>{{ mostrar(row[c.key]) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div v-else class="rounded-xl border border-dashed py-16 text-center text-sm text-gray-500 print:hidden">
                Configurá los filtros y pulsá <strong>Generar reporte</strong> para ver los resultados.
            </div>
        </template>

        <div v-else class="rounded-xl border border-dashed py-16 text-center text-sm text-gray-500 print:hidden">
            Seleccioná un tipo de reporte para comenzar.
        </div>
    </div>
</template>
