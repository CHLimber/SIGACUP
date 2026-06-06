<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, ClipboardList, Clock, LayoutGrid, Table2, Users } from 'lucide-vue-next';
import { dashboard } from '@/routes';

interface GrupoItem {
    id: number;
    nombre: string;
    materia: string;
    horario: string;
    postulaciones_count: number;
    docentes: string;
}

interface GestionItem {
    id: number;
    label: string;
    anio: number;
    semestre: number;
    grupos: GrupoItem[];
}

const props = defineProps<{
    gestiones: GestionItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',         href: dashboard() },
            { title: 'Calificaciones', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Calificaciones" />

    <div class="flex flex-col gap-8 p-6">
        <!-- Encabezado de página -->
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Calificaciones</h1>
            <p class="text-muted-foreground mt-2 text-sm">
                Selecciona un grupo para registrar o consultar las calificaciones de los estudiantes.
            </p>
        </div>

        <!-- Pestañas -->
        <div class="border-b">
            <nav class="-mb-px flex gap-6">
                <span
                    class="border-primary text-foreground flex items-center gap-1.5 border-b-2 pb-3 text-sm font-medium"
                >
                    <LayoutGrid class="h-4 w-4" />
                    Por grupo
                </span>
                <Link
                    href="/administracion/calificaciones/ponderadas"
                    class="text-muted-foreground hover:text-foreground flex items-center gap-1.5 border-b-2 border-transparent pb-3 text-sm font-medium transition-colors"
                >
                    <Table2 class="h-4 w-4" />
                    Ponderadas
                </Link>
            </nav>
        </div>

        <!-- Estado vacío -->
        <template v-if="gestiones.length === 0">
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed py-24 text-center">
                <ClipboardList class="text-muted-foreground mb-4 h-12 w-12" />
                <p class="text-muted-foreground text-sm">No hay grupos disponibles aún.</p>
            </div>
        </template>

        <!-- Gestiones con grupos -->
        <template v-else>
            <section v-for="gestion in gestiones" :key="gestion.id" class="space-y-5">
                <!-- Encabezado de gestión -->
                <div class="flex items-center gap-2.5 border-b pb-3">
                    <BookOpen class="text-primary h-5 w-5 shrink-0" />
                    <h2 class="text-base font-semibold">
                        Gestión {{ gestion.anio }} – {{ gestion.semestre === 1 ? '1er Semestre' : '2do Semestre' }}
                    </h2>
                    <span class="text-muted-foreground text-sm font-normal">
                        · {{ gestion.grupos.length }} {{ gestion.grupos.length === 1 ? 'grupo' : 'grupos' }}
                    </span>
                </div>

                <!-- Cards de grupos -->
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link
                        v-for="grupo in gestion.grupos"
                        :key="grupo.id"
                        :href="`/administracion/calificaciones/${grupo.id}`"
                        class="hover:border-primary/50 hover:bg-accent/30 group flex flex-col gap-4 rounded-xl border p-5 transition-colors"
                    >
                        <!-- Materia y badge de grupo -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold leading-snug">{{ grupo.materia }}</p>
                                <p class="text-muted-foreground mt-0.5 text-xs">Grupo {{ grupo.nombre }}</p>
                            </div>
                            <span
                                class="bg-primary/10 text-primary shrink-0 rounded-md px-2.5 py-1 text-xs font-bold"
                            >
                                {{ grupo.nombre }}
                            </span>
                        </div>

                        <!-- Detalles -->
                        <div class="text-muted-foreground space-y-2 text-xs">
                            <div class="flex items-center gap-2">
                                <Clock class="h-3.5 w-3.5 shrink-0" />
                                <span class="truncate">{{ grupo.horario }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Users class="h-3.5 w-3.5 shrink-0" />
                                <span>{{ grupo.postulaciones_count }} estudiantes</span>
                            </div>
                            <div v-if="grupo.docentes" class="truncate pl-0.5">
                                Docente: {{ grupo.docentes }}
                            </div>
                        </div>
                    </Link>
                </div>
            </section>
        </template>
    </div>
</template>
