<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';
import { Button } from '@/components/ui/button';

interface Gestion {
    id: number;
    anio: number;
    semestre: number;
}

interface GrupoMateria {
    id: number;
    codigo_materia: string;
    materia_nombre: string;
    horario_id: number | null;
    aula_id: number | null;
    horario_label: string | null;
    aula_label: string | null;
}

interface Aula {
    id: number;
    nombre: string;
    capacidad: number;
    modulo: string | null;
}

interface Horario {
    id: number;
    label: string;
}

interface Postulante {
    id: number;
    ci: string | null;
    apellido: string | null;
    nombres: string | null;
    carrera1: string | null;
    carrera2: string | null;
}

const props = defineProps<{
    gestion:      Gestion;
    nombre:       string;
    capacidadMax: number;
    grupos:       GrupoMateria[];
    aulas:        Aula[];
    horarios:     Horario[];
    postulantes:  Postulante[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',               href: dashboard() },
            { title: 'Gestiones Académicas', href: '/administracion/gestiones' },
            { title: 'Grupos',               href: '#' },
            { title: 'Configurar',           href: '#' },
        ],
    },
});

const asignaciones = ref(
    props.grupos.map(g => ({
        grupo_id:   g.id,
        horario_id: g.horario_id,
        aula_id:    g.aula_id,
    })),
);

const procesando = ref(false);
const errorMsg   = ref<string | null>(null);

const labelSemestre = computed(() => props.gestion.semestre === 1 ? '1er Sem' : '2do Sem');

const completos = computed(() =>
    asignaciones.value.every(a => a.horario_id !== null && a.aula_id !== null),
);

function guardar() {
    errorMsg.value = null;
    procesando.value = true;
    router.patch(
        `/administracion/grupos/${props.gestion.id}/${props.nombre}`,
        { asignaciones: asignaciones.value },
        {
            preserveScroll: true,
            onError: (errs) => {
                errorMsg.value = Object.values(errs)[0] as string || 'Error al guardar.';
            },
            onFinish: () => { procesando.value = false; },
        },
    );
}
</script>

<template>
    <Head :title="`Grupo ${nombre} — ${gestion.anio}-${gestion.semestre}`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Encabezado -->
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <Link
                        :href="`/administracion/grupos/${gestion.id}`"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        ← Volver a grupos
                    </Link>
                </div>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">
                    Grupo {{ nombre }}
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ gestion.anio }} {{ labelSemestre }} · {{ postulantes.length }} postulante(s) asignado(s)
                </p>
            </div>
        </div>

        <!-- Configuración por materia -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Horario y aula por materia</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Asigna a cada materia su horario y aula. El sistema validará que no haya conflictos de aula en el mismo horario.
            </p>

            <div v-if="errorMsg" class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ errorMsg }}
            </div>

            <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Materia</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Horario</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Aula</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(g, i) in grupos" :key="g.id">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900">{{ g.materia_nombre }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ g.codigo_materia }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <select
                                    v-model="asignaciones[i].horario_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                                >
                                    <option :value="null">— Sin asignar —</option>
                                    <option v-for="h in horarios" :key="h.id" :value="h.id">{{ h.label }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <select
                                    v-model="asignaciones[i].aula_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                                >
                                    <option :value="null">— Sin asignar —</option>
                                    <option v-for="a in aulas" :key="a.id" :value="a.id">
                                        {{ a.nombre }}<span v-if="a.modulo"> · {{ a.modulo }}</span> (cap. {{ a.capacidad }})
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-end gap-3">
                <p v-if="!completos" class="text-xs text-amber-600">
                    Aún hay materias sin horario o aula asignados.
                </p>
                <Button
                    style="background-color: #073b75;"
                    class="text-white hover:brightness-110"
                    :disabled="procesando"
                    @click="guardar"
                >
                    {{ procesando ? 'Guardando…' : 'Guardar configuración' }}
                </Button>
            </div>
        </div>

        <!-- Listado de postulantes -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">
                    Postulantes asignados ({{ postulantes.length }} / {{ capacidadMax }})
                </h2>
                <p class="mt-0.5 text-sm text-gray-500">Estudiantes que cursarán las cuatro materias en este grupo.</p>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">CI</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Nombre</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Carrera 1ra opción</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Carrera 2da opción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="postulantes.length === 0">
                        <td colspan="4" class="px-5 py-10 text-center text-gray-400">
                            Sin postulantes asignados a este grupo.
                        </td>
                    </tr>
                    <tr v-for="p in postulantes" :key="p.id" class="transition-colors hover:bg-gray-50">
                        <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ p.ci }}</td>
                        <td class="px-5 py-3 font-semibold text-gray-900">{{ p.apellido }} {{ p.nombres }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ p.carrera1 || '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ p.carrera2 || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
