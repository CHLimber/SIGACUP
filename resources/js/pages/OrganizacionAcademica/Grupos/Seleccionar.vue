<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { Users2, Inbox } from 'lucide-vue-next';

interface GestionResumen {
    id: number;
    anio: number;
    semestre: number;
    estado: 'cursado' | 'admision' | 'cerrada';
    total_grupos: number;
    total_pagados: number;
}

defineProps<{ gestiones: GestionResumen[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',           href: dashboard() },
            { title: 'Gestionar Grupos', href: '/administracion/grupos' },
        ],
    },
});

const estadoConfig: Record<GestionResumen['estado'], { label: string; clases: string }> = {
    cursado:  { label: 'Cursado',  clases: 'bg-blue-100 text-blue-700' },
    admision: { label: 'Admisión', clases: 'bg-purple-100 text-purple-700' },
    cerrada:  { label: 'Cerrada',  clases: 'bg-red-100 text-red-700' },
};

function labelSemestre(s: number): string {
    return s === 1 ? '1er Semestre' : '2do Semestre';
}
</script>

<template>
    <Head title="Gestionar Grupos" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestionar Grupos</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Selecciona la gestión sobre la que quieres trabajar sus grupos de cursado.
            </p>
        </div>

        <!-- Lista -->
        <div
            v-if="gestiones.length > 0"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <Link
                v-for="g in gestiones"
                :key="g.id"
                :href="`/administracion/grupos/${g.id}`"
                class="group flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-[#073b75] hover:shadow-md"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Gestión
                        </p>
                        <p class="mt-0.5 text-xl font-bold text-gray-900">
                            {{ g.anio }} — {{ labelSemestre(g.semestre) }}
                        </p>
                    </div>
                    <span
                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="estadoConfig[g.estado].clases"
                    >
                        {{ estadoConfig[g.estado].label }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400">Grupos</p>
                        <p class="mt-0.5 text-lg font-semibold" :class="g.total_grupos > 0 ? 'text-[#073b75]' : 'text-gray-400'">
                            {{ g.total_grupos > 0 ? g.total_grupos : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-400">Postulantes</p>
                        <p class="mt-0.5 text-lg font-semibold text-gray-700">
                            {{ g.total_pagados }}
                        </p>
                    </div>
                </div>

                <div class="mt-1 flex items-center gap-2 text-sm font-medium text-[#073b75] opacity-80 transition group-hover:opacity-100">
                    <Users2 class="h-4 w-4" />
                    {{ g.total_grupos > 0 ? 'Configurar grupos' : 'Generar grupos' }}
                    <span class="ml-auto transition group-hover:translate-x-0.5">→</span>
                </div>
            </Link>
        </div>

        <!-- Estado vacío -->
        <div
            v-else
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center"
        >
            <Inbox class="h-10 w-10 text-gray-300" />
            <p class="text-gray-500">No hay gestiones en estado Cursado, Admisión o Cerrada.</p>
            <p class="text-sm text-gray-400">
                Los grupos solo se pueden gestionar a partir del estado Cursado.
            </p>
            <Link
                href="/administracion/gestiones"
                class="mt-2 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110"
                style="background-color: #073b75;"
            >
                Ir a Gestiones Académicas
            </Link>
        </div>
    </div>
</template>
