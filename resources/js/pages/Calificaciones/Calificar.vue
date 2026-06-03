<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

interface GrupoInfo {
    id: number;
    nombre: string;
    materia: string;
    codigo_materia: string;
    gestion: { id: number; label: string };
    horario: string;
}

interface Pesos {
    examen_1: number;
    examen_2: number;
    examen_3: number;
    nota_minima: number;
}

interface EstudianteBase {
    postulacion_id: number;
    nombre: string;
    examen_1: string | null;
    examen_2: string | null;
    examen_3: string | null;
}

const props = defineProps<{
    grupo: GrupoInfo;
    pesos: Pesos;
    estudiantes: EstudianteBase[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',         href: dashboard() },
            { title: 'Calificaciones', href: '/administracion/calificaciones' },
            { title: 'Calificar',      href: '#' },
        ],
    },
});

const notas = ref<Record<number, { e1: string; e2: string; e3: string }>>(
    Object.fromEntries(
        props.estudiantes.map((e) => [
            e.postulacion_id,
            {
                e1: e.examen_1 !== null ? String(e.examen_1) : '',
                e2: e.examen_2 !== null ? String(e.examen_2) : '',
                e3: e.examen_3 !== null ? String(e.examen_3) : '',
            },
        ]),
    ),
);

const guardando = ref(false);

function parseNota(v: string): number | null {
    if (v === '') return null;
    const n = parseFloat(v);
    return isNaN(n) ? null : Math.min(100, Math.max(0, n));
}

function pond(nota: string, peso: number): number | null {
    const n = parseNota(nota);
    return n === null ? null : (n * peso) / 100;
}

function notaFinal(pid: number): number | null {
    const fila = notas.value[pid];
    const p1 = pond(fila.e1, props.pesos.examen_1);
    const p2 = pond(fila.e2, props.pesos.examen_2);
    const p3 = pond(fila.e3, props.pesos.examen_3);
    if (p1 === null && p2 === null && p3 === null) return null;
    return (p1 ?? 0) + (p2 ?? 0) + (p3 ?? 0);
}

function estadoEstudiante(pid: number): 'aprobado' | 'reprobado' | null {
    const nf = notaFinal(pid);
    if (nf === null) return null;
    return nf >= props.pesos.nota_minima ? 'aprobado' : 'reprobado';
}

const totalAprobados = computed(
    () => props.estudiantes.filter((e) => estadoEstudiante(e.postulacion_id) === 'aprobado').length,
);
const totalReprobados = computed(
    () => props.estudiantes.filter((e) => estadoEstudiante(e.postulacion_id) === 'reprobado').length,
);

function fmt(v: number | null): string {
    return v === null ? '—' : v.toFixed(2);
}

function guardar() {
    guardando.value = true;
    const calificaciones = props.estudiantes.map((e) => {
        const fila = notas.value[e.postulacion_id];
        return {
            postulacion_id: e.postulacion_id,
            examen_1: fila.e1 !== '' ? fila.e1 : null,
            examen_2: fila.e2 !== '' ? fila.e2 : null,
            examen_3: fila.e3 !== '' ? fila.e3 : null,
        };
    });
    router.put(
        `/administracion/calificaciones/${props.grupo.id}`,
        { calificaciones },
        { preserveScroll: true, onFinish: () => { guardando.value = false; } },
    );
}
</script>

<template>
    <Head :title="`Calificar — ${grupo.materia} Grupo ${grupo.nombre}`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Encabezado -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link
                    href="/administracion/calificaciones"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Volver a calificaciones
                </Link>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">
                    {{ grupo.materia }}
                    <span class="font-normal text-gray-500">— Grupo {{ grupo.nombre }}</span>
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Gestión {{ grupo.gestion.label }} &nbsp;·&nbsp; {{ grupo.horario }}
                </p>
            </div>
            <Button
                style="background-color: #073b75;"
                class="text-white hover:brightness-110"
                :disabled="guardando"
                @click="guardar"
            >
                <Save class="mr-1.5 h-4 w-4" />
                {{ guardando ? 'Guardando…' : 'Guardar calificaciones' }}
            </Button>
        </div>

        <!-- Tarjetas de resumen -->
        <div v-if="estudiantes.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total estudiantes</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ estudiantes.length }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aprobados</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ totalAprobados }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reprobados</p>
                <p class="mt-1 text-3xl font-bold text-red-600">{{ totalReprobados }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Nota mínima</p>
                <p class="mt-1 text-3xl font-bold" style="color: #073b75;">{{ pesos.nota_minima }}</p>
            </div>
        </div>

        <!-- Sin estudiantes -->
        <div
            v-if="estudiantes.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center"
        >
            <p class="text-gray-500">Este grupo no tiene estudiantes asignados.</p>
        </div>

        <!-- Tabla de calificaciones -->
        <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <!-- Fila de grupos de columnas -->
                    <tr style="background-color: #060041;">
                        <th colspan="2" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">
                            Estudiante
                        </th>
                        <th colspan="3" class="border-l border-white/20 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white">
                            Notas (sobre 100)
                        </th>
                        <th colspan="3" class="border-l border-white/20 bg-[#0a2f5e] px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white/80">
                            Ponderados
                        </th>
                        <th colspan="2" class="border-l border-white/20 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-white">
                            Resultado
                        </th>
                    </tr>
                    <!-- Fila de encabezados de columna -->
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="w-12 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                        <th class="min-w-[200px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Nombre</th>

                        <th class="border-l border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                            1er Parcial
                            <span class="block text-[10px] font-normal normal-case text-gray-400">{{ pesos.examen_1 }}%</span>
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                            2do Parcial
                            <span class="block text-[10px] font-normal normal-case text-gray-400">{{ pesos.examen_2 }}%</span>
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Examen Final
                            <span class="block text-[10px] font-normal normal-case text-gray-400">{{ pesos.examen_3 }}%</span>
                        </th>

                        <th class="border-l border-gray-200 bg-gray-100 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            P. 1er Parc.
                        </th>
                        <th class="bg-gray-100 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            P. 2do Parc.
                        </th>
                        <th class="bg-gray-100 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            P. Ex. Final
                        </th>

                        <th class="border-l border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700">
                            Nota Final
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Estado
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="(est, idx) in estudiantes"
                        :key="est.postulacion_id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-3.5 text-center text-sm text-gray-400 tabular-nums">
                            {{ idx + 1 }}
                        </td>
                        <td class="px-5 py-3.5 font-medium text-gray-900">{{ est.nombre }}</td>

                        <!-- Inputs de notas -->
                        <td class="border-l border-gray-100 px-5 py-3.5 text-center">
                            <input
                                v-model="notas[est.postulacion_id].e1"
                                type="number" min="0" max="100" step="0.01" placeholder="—"
                                class="w-24 rounded-md border border-gray-300 px-3 py-1.5 text-center tabular-nums focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                            />
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <input
                                v-model="notas[est.postulacion_id].e2"
                                type="number" min="0" max="100" step="0.01" placeholder="—"
                                class="w-24 rounded-md border border-gray-300 px-3 py-1.5 text-center tabular-nums focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                            />
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <input
                                v-model="notas[est.postulacion_id].e3"
                                type="number" min="0" max="100" step="0.01" placeholder="—"
                                class="w-24 rounded-md border border-gray-300 px-3 py-1.5 text-center tabular-nums focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                            />
                        </td>

                        <!-- Ponderados -->
                        <td class="border-l border-gray-100 bg-gray-50 px-5 py-3.5 text-center tabular-nums text-gray-700">
                            {{ fmt(pond(notas[est.postulacion_id].e1, pesos.examen_1)) }}
                        </td>
                        <td class="bg-gray-50 px-5 py-3.5 text-center tabular-nums text-gray-700">
                            {{ fmt(pond(notas[est.postulacion_id].e2, pesos.examen_2)) }}
                        </td>
                        <td class="bg-gray-50 px-5 py-3.5 text-center tabular-nums text-gray-700">
                            {{ fmt(pond(notas[est.postulacion_id].e3, pesos.examen_3)) }}
                        </td>

                        <!-- Nota final -->
                        <td class="border-l border-gray-100 px-5 py-3.5 text-center text-base font-bold tabular-nums text-gray-900">
                            {{ fmt(notaFinal(est.postulacion_id)) }}
                        </td>

                        <!-- Estado -->
                        <td class="px-5 py-3.5 text-center">
                            <span
                                v-if="estadoEstudiante(est.postulacion_id) === 'aprobado'"
                                class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700"
                            >
                                Aprobado
                            </span>
                            <span
                                v-else-if="estadoEstudiante(est.postulacion_id) === 'reprobado'"
                                class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700"
                            >
                                Reprobado
                            </span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Botón guardar inferior -->
        <div v-if="estudiantes.length > 0" class="flex justify-end">
            <Button
                style="background-color: #073b75;"
                class="text-white hover:brightness-110"
                :disabled="guardando"
                @click="guardar"
            >
                <Save class="mr-1.5 h-4 w-4" />
                {{ guardando ? 'Guardando…' : 'Guardar calificaciones' }}
            </Button>
        </div>

    </div>
</template>
