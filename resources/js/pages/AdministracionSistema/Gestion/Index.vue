<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MoreHorizontal, Pencil, Users, ArrowRight, ArrowLeft, Trash2, Star } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

defineProps<{ gestiones: Gestion[]; activa_id: number | null }>();

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

const prevLabel: Partial<Record<Gestion['estado'], string>> = {
    inscripcion: 'Configuración',
    cursado:     'Inscripción',
    admision:    'Cursado',
    cerrada:     'Admisión',
};

function avanzar(g: Gestion) {
    const label = nextLabel[g.estado];

    if (!label) {
return;
}

    if (!confirm(`¿Avanzar la gestión ${g.anio}-${g.semestre} al estado "${label}"?\n\nEsta acción no se puede deshacer.`)) {
return;
}

    router.patch(`/administracion/gestiones/${g.id}/avanzar`);
}

function retroceder(g: Gestion) {
    const label = prevLabel[g.estado];

    if (!label) {
return;
}

    if (!confirm(`¿Retroceder la gestión ${g.anio}-${g.semestre} al estado "${label}"?\n\nÚsalo solo para corregir un avance hecho por error. Los datos generados durante la etapa actual podrían quedar inconsistentes.`)) {
return;
}

    router.patch(`/administracion/gestiones/${g.id}/retroceder`);
}

function eliminar(g: Gestion) {
    if (!confirm(`¿Eliminar la gestión ${g.anio}-${g.semestre === 1 ? '1er' : '2do'} Semestre?\n\nSe eliminarán también sus parámetros. Esta acción no se puede deshacer.`)) {
return;
}

    router.delete(`/administracion/gestiones/${g.id}`);
}

const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

function fmt(fecha: string | null): string {
    if (!fecha) {
return '—';
}

    const soloFecha = fecha.split('T')[0];
    const [y, m, d] = soloFecha.split('-');
    const mesIdx = Number(m) - 1;

    if (!y || !d || Number.isNaN(mesIdx) || !MESES[mesIdx]) {
return soloFecha;
}

    return `${Number(d)} ${MESES[mesIdx]} ${y}`;
}
</script>

<template>
    <Head title="Gestiones Académicas" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
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
            <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
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
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="estadoConfig[g.estado].clases"
                                >
                                    {{ estadoConfig[g.estado].label }}
                                </span>
                                <span
                                    v-if="g.id === activa_id"
                                    class="inline-flex items-center gap-1 rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700"
                                    title="Esta es la gestión activa que se muestra en el portal"
                                >
                                    <Star class="h-3 w-3 fill-green-500 text-green-500" />
                                    Activa
                                </span>
                            </div>
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
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300"
                                    aria-label="Acciones"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-48">
                                    <DropdownMenuItem
                                        v-if="g.estado !== 'cerrada'"
                                        as-child
                                    >
                                        <Link
                                            :href="`/administracion/gestiones/${g.id}/edit`"
                                            class="flex w-full cursor-pointer items-center"
                                        >
                                            <Pencil class="mr-2 h-4 w-4" />
                                            Editar
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="['cursado', 'admision', 'cerrada'].includes(g.estado)"
                                        as-child
                                    >
                                        <Link
                                            :href="`/administracion/grupos/${g.id}`"
                                            class="flex w-full cursor-pointer items-center"
                                        >
                                            <Users class="mr-2 h-4 w-4" />
                                            Grupos
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="nextLabel[g.estado]"
                                        class="cursor-pointer"
                                        @select="avanzar(g)"
                                    >
                                        <ArrowRight class="mr-2 h-4 w-4" />
                                        Avanzar a {{ nextLabel[g.estado] }}
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="prevLabel[g.estado]"
                                        class="cursor-pointer"
                                        @select="retroceder(g)"
                                    >
                                        <ArrowLeft class="mr-2 h-4 w-4" />
                                        Retroceder a {{ prevLabel[g.estado] }}
                                    </DropdownMenuItem>

                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        class="cursor-pointer text-red-600 focus:bg-red-50 focus:text-red-700"
                                        @select="eliminar(g)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
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
