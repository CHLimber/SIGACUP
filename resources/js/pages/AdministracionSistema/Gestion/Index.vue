<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

interface Gestion {
    id: number;
    anio: number;
    semestre: number;
    estado: 'configuracion' | 'inscripcion' | 'cursado' | 'admision' | 'cerrada';
    fecha_inicio_inscripcion: string | null;
    fecha_fin_inscripcion: string | null;
    fecha_inicio_cursado: string | null;
    fecha_fin_cursado: string | null;
}

defineProps<{ gestiones: Gestion[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Gestiones Académicas', href: '/administracion/gestiones' },
        ],
    },
});

const estadoConfig: Record<Gestion['estado'], { label: string; clases: string }> = {
    configuracion: { label: 'Configuración', clases: 'bg-gray-100 text-gray-700' },
    inscripcion:   { label: 'Inscripción',   clases: 'bg-yellow-100 text-yellow-700' },
    cursado:       { label: 'Cursado',       clases: 'bg-blue-100 text-blue-700' },
    admision:      { label: 'Admisión',      clases: 'bg-purple-100 text-purple-700' },
    cerrada:       { label: 'Cerrada',       clases: 'bg-red-100 text-red-700' },
};

const nextLabel: Partial<Record<Gestion['estado'], string>> = {
    configuracion: 'Inscripción',
    inscripcion:   'Cursado',
    cursado:       'Admisión',
    admision:      'Cerrar gestión',
};

function avanzar(g: Gestion) {
    const label = nextLabel[g.estado];
    if (!label) return;
    if (!confirm(`¿Avanzar la gestión ${g.anio}-${g.semestre} al estado "${label}"?\n\nEsta acción no se puede deshacer.`)) return;
    router.patch(`/administracion/gestiones/${g.id}/avanzar`);
}

function eliminar(g: Gestion) {
    if (!confirm(`¿Eliminar la gestión ${g.anio}-${g.semestre === 1 ? '1er' : '2do'} Semestre?\n\nSe eliminarán también sus parámetros. Esta acción no se puede deshacer.`)) return;
    router.delete(`/administracion/gestiones/${g.id}`);
}

function fmt(fecha: string | null): string {
    if (!fecha) return '—';
    const [y, m, d] = fecha.split('-');
    return `${d}/${m}/${y}`;
}
</script>

<template>
    <Head title="Gestiones Académicas" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestiones Académicas</h1>
                <p class="mt-0.5 text-sm text-gray-500">Períodos del Curso Preuniversitario (CUP)</p>
            </div>
            <Link
                href="/administracion/gestiones/create"
                class="mt-3 inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 sm:mt-0"
                style="background-color: #c70e0a;"
            >
                + Nueva Gestión
            </Link>
        </div>

        <!-- Tabla -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #060041;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">
                            Gestión
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">
                            Estado
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">
                            Período de Inscripción
                        </th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">
                            Período de Cursado
                        </th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="gestiones.length === 0">
                        <td colspan="5" class="px-5 py-14 text-center text-gray-400">
                            No hay gestiones registradas. Crea la primera usando el botón de arriba.
                        </td>
                    </tr>

                    <tr
                        v-for="g in gestiones"
                        :key="g.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <!-- Gestión -->
                        <td class="px-5 py-4">
                            <span class="font-semibold text-gray-900">{{ g.anio }}</span>
                            <span class="mx-1 text-gray-400">–</span>
                            <span class="text-gray-700">{{ g.semestre === 1 ? '1er Semestre' : '2do Semestre' }}</span>
                        </td>

                        <!-- Estado -->
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="estadoConfig[g.estado].clases"
                            >
                                {{ estadoConfig[g.estado].label }}
                            </span>
                        </td>

                        <!-- Inscripción -->
                        <td class="px-5 py-4 text-gray-600">
                            <template v-if="g.fecha_inicio_inscripcion">
                                {{ fmt(g.fecha_inicio_inscripcion) }}
                                <span class="mx-1 text-gray-400">→</span>
                                {{ fmt(g.fecha_fin_inscripcion) }}
                            </template>
                            <span v-else class="italic text-gray-400">No configurado</span>
                        </td>

                        <!-- Cursado -->
                        <td class="px-5 py-4 text-gray-600">
                            <template v-if="g.fecha_inicio_cursado">
                                {{ fmt(g.fecha_inicio_cursado) }}
                                <span class="mx-1 text-gray-400">→</span>
                                {{ fmt(g.fecha_fin_cursado) }}
                            </template>
                            <span v-else class="italic text-gray-400">No configurado</span>
                        </td>

                        <!-- Acciones -->
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <Link
                                    v-if="g.estado !== 'cerrada'"
                                    :href="`/administracion/gestiones/${g.id}/edit`"
                                    class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    Editar
                                </Link>

                                <button
                                    v-if="nextLabel[g.estado]"
                                    class="rounded-md px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110"
                                    style="background-color: #073b75;"
                                    @click="avanzar(g)"
                                >
                                    Avanzar →
                                </button>

                                <button
                                    class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50"
                                    @click="eliminar(g)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Leyenda de estados -->
        <div class="flex flex-wrap gap-3">
            <div
                v-for="(cfg, key) in estadoConfig"
                :key="key"
                class="flex items-center gap-1.5"
            >
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="cfg.clases">
                    {{ cfg.label }}
                </span>
            </div>
        </div>
    </div>
</template>
