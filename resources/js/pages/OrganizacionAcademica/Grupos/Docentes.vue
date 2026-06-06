<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Shuffle, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { dashboard } from '@/routes';

interface Gestion {
    id: number;
    anio: number;
    semestre: number;
    estado: string;
}

interface GrupoMateria {
    id: number;
    nombre: string;
    codigo_materia: string;
    materia_nombre: string;
    horario_id: number | null;
    horario_label: string | null;
    docente_id: number | null;
}

interface DocenteOpt {
    id: number;
    nombre: string;
    titulo: string | null;
}

const props = defineProps<{
    gestion:       Gestion;
    grupos:        GrupoMateria[];
    docentes:      DocenteOpt[];
    maxPorDocente: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',               href: dashboard() },
            { title: 'Gestiones Académicas', href: '/administracion/gestiones' },
            { title: 'Grupos',               href: '#' },
            { title: 'Asignar docentes',     href: '#' },
        ],
    },
});

// Mapa reactivo grupo_id → docente_id
const seleccion = ref<Record<number, number | null>>(
    Object.fromEntries(props.grupos.map(g => [g.id, g.docente_id])),
);

const procesando = ref(false);
const confirmar  = ref(false);

const labelSemestre = computed(() => props.gestion.semestre === 1 ? '1er Sem' : '2do Sem');

const hayDocentes = computed(() => props.docentes.length > 0);

// Agrupa las materias por nombre de grupo (A, B, C…)
const gruposPorNombre = computed(() => {
    const mapa = new Map<string, GrupoMateria[]>();

    for (const g of props.grupos) {
        if (!mapa.has(g.nombre)) {
mapa.set(g.nombre, []);
}

        mapa.get(g.nombre)!.push(g);
    }

    return Array.from(mapa.entries()).map(([nombre, materias]) => ({ nombre, materias }));
});

// Carga actual por docente, derivada de la selección
const cargaPorDocente = computed(() => {
    const mapa = new Map<number, GrupoMateria[]>();

    for (const g of props.grupos) {
        const d = seleccion.value[g.id];

        if (d == null) {
continue;
}

        if (!mapa.has(d)) {
mapa.set(d, []);
}

        mapa.get(d)!.push(g);
    }

    return mapa;
});

// Detecta choque de horarios (mismo slot) por docente — indicativo en cliente
const conflictoHorarioDocente = (id: number): boolean => {
    const asignados = cargaPorDocente.value.get(id) ?? [];
    const vistos = new Set<number>();

    for (const g of asignados) {
        if (g.horario_id == null) {
continue;
}

        if (vistos.has(g.horario_id)) {
return true;
}

        vistos.add(g.horario_id);
    }

    return false;
};

type Tono = 'red' | 'amber' | 'green' | 'gray';

const tonoClases: Record<Tono, { dot: string; text: string }> = {
    red:   { dot: 'bg-red-500',     text: 'text-red-700' },
    amber: { dot: 'bg-amber-500',   text: 'text-amber-700' },
    green: { dot: 'bg-emerald-500', text: 'text-emerald-700' },
    gray:  { dot: 'border border-gray-300 bg-white', text: 'text-gray-500' },
};

function estadoCarga(total: number, excede: boolean, choca: boolean): { label: string; tono: Tono } {
    if (excede) {
        return { label: 'Excede', tono: 'red' };
    }

    if (choca) {
        return { label: 'Choque horario', tono: 'red' };
    }

    if (total === 0) {
        return { label: 'Libre', tono: 'gray' };
    }

    if (total >= props.maxPorDocente) {
        return { label: 'Lleno', tono: 'amber' };
    }

    return { label: 'Normal', tono: 'green' };
}

const resumenDocentes = computed(() =>
    props.docentes.map(d => {
        const asignados = cargaPorDocente.value.get(d.id) ?? [];
        const total     = asignados.length;
        const excede    = total > props.maxPorDocente;
        const choca     = conflictoHorarioDocente(d.id);

        return {
            ...d,
            total,
            excede,
            choca,
            estado: estadoCarga(total, excede, choca),
        };
    }),
);

const totalAsignados   = computed(() => props.grupos.filter(g => seleccion.value[g.id] != null).length);
const hayProblemas     = computed(() => resumenDocentes.value.some(r => r.excede || r.choca));
const hayAsignaciones  = computed(() => props.grupos.some(g => g.docente_id != null));

// Marca si una opción de docente generaría choque de horario para un grupo dado
const opcionChoca = (grupo: GrupoMateria, docenteId: number): boolean => {
    if (grupo.horario_id == null) {
return false;
}

    const asignados = cargaPorDocente.value.get(docenteId) ?? [];

    return asignados.some(g => g.id !== grupo.id && g.horario_id === grupo.horario_id);
};

function guardar() {
    procesando.value = true;
    const asignaciones = props.grupos.map(g => ({
        grupo_id:   g.id,
        docente_id: seleccion.value[g.id] ?? null,
    }));
    router.patch(
        `/administracion/grupos/${props.gestion.id}/asignar-docentes`,
        { asignaciones },
        { preserveScroll: true, onFinish: () => {
 procesando.value = false; 
} },
    );
}

function solicitarAuto() {
    if (hayAsignaciones.value) {
        confirmar.value = true;
    } else {
        ejecutarAuto();
    }
}

function ejecutarAuto() {
    confirmar.value = false;
    procesando.value = true;
    router.post(
        `/administracion/grupos/${props.gestion.id}/asignar-docentes/auto`,
        {},
        { preserveScroll: true, onFinish: () => {
 procesando.value = false; 
} },
    );
}
</script>

<template>
    <Head :title="`Asignar docentes — ${gestion.anio} ${labelSemestre}`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Encabezado -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    :href="`/administracion/grupos/${gestion.id}`"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Volver a grupos
                </Link>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">Asignar docentes a grupos</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ gestion.anio }} {{ labelSemestre }} · {{ totalAsignados }} / {{ grupos.length }} grupo(s) con docente
                </p>
            </div>

            <Button
                v-if="hayDocentes"
                style="background-color: #073b75;"
                class="text-white hover:brightness-110"
                :disabled="procesando"
                @click="solicitarAuto"
            >
                <Shuffle class="mr-1.5 h-4 w-4" />
                {{ procesando ? 'Asignando…' : 'Asignar automáticamente' }}
            </Button>
        </div>

        <!-- Reglas -->
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            <p class="font-semibold">Reglas de asignación</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                <li>Cada docente puede dictar como máximo <strong>{{ maxPorDocente }}</strong> grupos.</li>
                <li>Los horarios de los grupos de un mismo docente <strong>no pueden chocar</strong>.</li>
                <li>Usa <strong>Asignar automáticamente</strong> para un reparto aleatorio que respeta ambas reglas, o ajusta manualmente cada materia.</li>
            </ul>
        </div>

        <!-- Sin docentes -->
        <div
            v-if="!hayDocentes"
            class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center"
        >
            <Users class="h-8 w-8 text-gray-300" />
            <p class="text-gray-500">No hay docentes registrados todavía.</p>
            <p class="text-sm text-gray-400">Aprueba candidatos a docente para poder asignarlos a los grupos.</p>
        </div>

        <!-- Sin grupos -->
        <div
            v-else-if="grupos.length === 0"
            class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center"
        >
            <p class="text-gray-500">Aún no se han generado grupos para esta gestión.</p>
        </div>

        <template v-else>
            <!-- Resumen de carga por docente -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <h2 class="text-base font-semibold text-gray-900">Carga por docente</h2>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Docente</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Grupos</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="r in resumenDocentes"
                            :key="r.id"
                            class="transition-colors hover:bg-gray-50"
                            :class="(r.excede || r.choca) ? 'bg-red-50/40' : ''"
                        >
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900">{{ r.nombre }}</p>
                                <p v-if="r.titulo" class="text-xs text-gray-500">{{ r.titulo }}</p>
                            </td>
                            <td class="px-5 py-3 tabular-nums text-gray-700">
                                <span class="font-semibold text-gray-900">{{ r.total }}</span>
                                <span class="text-gray-400"> / {{ maxPorDocente }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium" :class="tonoClases[r.estado.tono].text">
                                    <AlertTriangle v-if="r.estado.tono === 'red'" class="h-3.5 w-3.5" />
                                    <span v-else class="h-2 w-2 rounded-full" :class="tonoClases[r.estado.tono].dot" />
                                    {{ r.estado.label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Grupos -->
            <div
                v-for="grupo in gruposPorNombre"
                :key="grupo.nombre"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="flex items-center gap-3 border-b border-gray-100 px-5 py-3.5">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#073b75] text-sm font-bold text-white">
                        {{ grupo.nombre }}
                    </span>
                    <h3 class="text-base font-semibold text-gray-900">Grupo {{ grupo.nombre }}</h3>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Materia</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Horario</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Docente</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in grupo.materias" :key="m.id">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-gray-900">{{ m.materia_nombre }}</p>
                                <p class="font-mono text-xs text-gray-500">{{ m.codigo_materia }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span v-if="m.horario_label" class="text-gray-700">{{ m.horario_label }}</span>
                                <span v-else class="text-xs italic text-amber-600">Sin horario</span>
                            </td>
                            <td class="px-5 py-3">
                                <select
                                    v-model="seleccion[m.id]"
                                    class="w-full rounded-md border px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                                    :class="seleccion[m.id] != null && opcionChoca(m, seleccion[m.id] as number)
                                        ? 'border-red-300 bg-red-50'
                                        : 'border-gray-300 focus:border-[#073b75]'"
                                >
                                    <option :value="null">— Sin asignar —</option>
                                    <option v-for="d in docentes" :key="d.id" :value="d.id">
                                        {{ d.nombre }}{{ opcionChoca(m, d.id) ? '  ⚠ choque' : '' }}
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Barra de guardado -->
            <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white/95 p-4 shadow-sm backdrop-blur">
                <p v-if="hayProblemas" class="text-xs text-red-600">
                    <AlertTriangle class="mr-1 inline h-3.5 w-3.5 align-text-bottom" />
                    Hay docentes que exceden el máximo o tienen choque de horario. Corrige antes de guardar.
                </p>
                <Button
                    style="background-color: #073b75;"
                    class="text-white hover:brightness-110"
                    :disabled="procesando || hayProblemas"
                    @click="guardar"
                >
                    {{ procesando ? 'Guardando…' : 'Guardar asignación' }}
                </Button>
            </div>
        </template>
    </div>

    <!-- Confirmación de reasignación automática -->
    <Dialog :open="confirmar" @update:open="v => !v && (confirmar = false)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Reasignar automáticamente?</DialogTitle>
                <DialogDescription>
                    Esto reemplazará las asignaciones de docentes actuales por un reparto aleatorio
                    que respeta el máximo de {{ maxPorDocente }} grupos por docente y evita choques de horario.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmar = false">Cancelar</Button>
                <Button style="background-color: #073b75;" class="text-white hover:brightness-110" @click="ejecutarAuto">
                    Sí, reasignar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
