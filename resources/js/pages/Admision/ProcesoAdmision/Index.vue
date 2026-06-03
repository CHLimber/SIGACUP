<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck, Users } from 'lucide-vue-next';
import { dashboard } from '@/routes';

interface GestionItem {
    id: number;
    label: string;
    anio: number;
    semestre: number;
    estado: string;
    postulaciones_count: number;
}

defineProps<{
    gestiones: GestionItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Proceso de admisión', href: '#' },
        ],
    },
});

const estadoConfig: Record<string, { label: string; clases: string }> = {
    configuracion: { label: 'Configuración', clases: 'bg-gray-100 text-gray-700' },
    inscripcion:   { label: 'Inscripción',   clases: 'bg-yellow-100 text-yellow-700' },
    cursado:       { label: 'Cursado',       clases: 'bg-blue-100 text-blue-700' },
    admision:      { label: 'Admisión',      clases: 'bg-purple-100 text-purple-700' },
    cerrada:       { label: 'Cerrada',       clases: 'bg-red-100 text-red-700' },
};
</script>

<template>
    <Head title="Proceso de admisión" />

    <div class="flex flex-col gap-8 p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Proceso de admisión</h1>
            <p class="text-muted-foreground mt-2 text-sm">
                Selecciona una gestión para ejecutar el proceso de admisión. Los postulantes con mejor promedio
                tienen prioridad: se les asigna su primera carrera y, si ya no hay cupo, su segunda opción.
            </p>
        </div>

        <template v-if="gestiones.length === 0">
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed py-24 text-center">
                <ClipboardCheck class="text-muted-foreground mb-4 h-12 w-12" />
                <p class="text-muted-foreground text-sm">No hay gestiones disponibles aún.</p>
            </div>
        </template>

        <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="gestion in gestiones"
                :key="gestion.id"
                :href="`/administracion/proceso-admision/${gestion.id}`"
                class="hover:border-primary/50 hover:bg-accent/30 group flex flex-col gap-4 rounded-xl border p-5 transition-colors"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-snug">
                            Gestión {{ gestion.anio }} – {{ gestion.semestre === 1 ? '1er Semestre' : '2do Semestre' }}
                        </p>
                        <p class="text-muted-foreground mt-0.5 text-xs">{{ gestion.label }}</p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="estadoConfig[gestion.estado]?.clases ?? 'bg-gray-100 text-gray-700'"
                    >
                        {{ estadoConfig[gestion.estado]?.label ?? gestion.estado }}
                    </span>
                </div>

                <div class="text-muted-foreground flex items-center gap-2 text-xs">
                    <Users class="h-3.5 w-3.5 shrink-0" />
                    <span>{{ gestion.postulaciones_count }} postulación(es)</span>
                </div>
            </Link>
        </div>
    </div>
</template>
