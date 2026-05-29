<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';

type Estado = 'pendiente' | 'en_revision' | 'requiere_correcciones' | 'aprobado_pendiente_pago' | 'pagado' | 'rechazado';

interface CandidatoBase {
    id: number;
    ci: string;
    apellido: string;
    nombres: string;
    fecha_nacimiento: string;
    sexo: string;
    telefono: string;
    direccion: string;
    estado: Estado;
    motivo_rechazo: string | null;
    requisitos_pendientes_revision_count: number;
    created_at: string;
}

interface CandidatoEstudiante extends CandidatoBase {
    email: string | null;
    carrera_primera_opcion: string;
    carrera_segunda_opcion: string;
}

interface CandidatoDocente extends CandidatoBase {
    email: string;
}

const props = defineProps<{
    candidatosEstudiante: CandidatoEstudiante[];
    candidatosDocente:    CandidatoDocente[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Admisión', href: '/administracion/admision' },
        ],
    },
});

const tab = ref<'estudiantes' | 'docentes'>('estudiantes');
const filtroEstado = ref<'activos' | 'todos'>('activos');

const carreraLabels: Record<string, string> = {
    sistemas:    'Ing. Sistemas',
    informatica: 'Ing. Informática',
    redes:       'Ing. Redes',
    robotica:    'Ing. Robótica',
};

const estadoConfig: Record<Estado, { label: string; clases: string }> = {
    pendiente:               { label: 'Sin enviar',             clases: 'bg-gray-100 text-gray-700' },
    en_revision:             { label: 'En revisión',            clases: 'bg-blue-100 text-blue-700' },
    requiere_correcciones:   { label: 'Requiere correcciones',  clases: 'bg-orange-100 text-orange-700' },
    aprobado_pendiente_pago: { label: 'Esperando pago',         clases: 'bg-amber-100 text-amber-700' },
    pagado:                  { label: 'Pagado',                 clases: 'bg-green-100 text-green-700' },
    rechazado:               { label: 'Rechazado',              clases: 'bg-red-100 text-red-700' },
};

const estadosActivos: Estado[] = ['en_revision', 'requiere_correcciones'];

const candidatosEstudianteFiltrados = computed(() =>
    filtroEstado.value === 'activos'
        ? props.candidatosEstudiante.filter(c => estadosActivos.includes(c.estado))
        : props.candidatosEstudiante,
);

const candidatosDocenteFiltrados = computed(() =>
    filtroEstado.value === 'activos'
        ? props.candidatosDocente.filter(c => estadosActivos.includes(c.estado))
        : props.candidatosDocente,
);

const totales = computed(() => ({
    estudiantesPorRevisar: props.candidatosEstudiante.filter(c => c.estado === 'en_revision').length,
    docentesPorRevisar:    props.candidatosDocente.filter(c => c.estado === 'en_revision').length,
}));

function fmtFecha(f: string): string {
    return new Date(f).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function revisar(tipo: 'estudiante' | 'docente', id: number) {
    router.visit(`/administracion/admision/candidato-${tipo}/${id}`);
}
</script>

<template>
    <Head title="Admisión — SIGACUP" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Admisión de Candidatos</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Revisa los requisitos enviados por los candidatos. Aprueba o rechaza cada documento
                y cierra la solicitud con aprobación, correcciones o rechazo definitivo.
            </p>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estudiantes en revisión</p>
                        <p class="mt-1 text-3xl font-bold text-[#073b75]">{{ totales.estudiantesPorRevisar }}</p>
                    </div>
                    <div class="text-4xl">🎓</div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Docentes en revisión</p>
                        <p class="mt-1 text-3xl font-bold text-[#c70e0a]">{{ totales.docentesPorRevisar }}</p>
                    </div>
                    <div class="text-4xl">👨‍🏫</div>
                </div>
            </div>
        </div>

        <!-- Tabs + Filtro -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200">
            <div class="flex gap-1">
                <button
                    type="button"
                    @click="tab = 'estudiantes'"
                    :class="[
                        'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                        tab === 'estudiantes'
                            ? 'border-[#073b75] text-[#073b75]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Candidatos a estudiante ({{ candidatosEstudiante.length }})
                </button>
                <button
                    type="button"
                    @click="tab = 'docentes'"
                    :class="[
                        'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                        tab === 'docentes'
                            ? 'border-[#c70e0a] text-[#c70e0a]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                >
                    Candidatos a docente ({{ candidatosDocente.length }})
                </button>
            </div>
            <div class="flex gap-2 pb-2">
                <button
                    type="button"
                    @click="filtroEstado = 'activos'"
                    :class="[
                        'rounded-md px-3 py-1.5 text-xs font-medium transition',
                        filtroEstado === 'activos'
                            ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-300'
                            : 'text-gray-500 hover:bg-gray-100',
                    ]"
                >
                    Por revisar
                </button>
                <button
                    type="button"
                    @click="filtroEstado = 'todos'"
                    :class="[
                        'rounded-md px-3 py-1.5 text-xs font-medium transition',
                        filtroEstado === 'todos'
                            ? 'bg-gray-200 text-gray-800'
                            : 'text-gray-500 hover:bg-gray-100',
                    ]"
                >
                    Todos
                </button>
            </div>
        </div>

        <!-- TAB: estudiantes -->
        <div v-if="tab === 'estudiantes'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #060041;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">CI</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Nombre</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Carreras</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estado</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Por revisar</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="candidatosEstudianteFiltrados.length === 0">
                        <td colspan="6" class="px-5 py-14 text-center text-gray-400">
                            No hay candidatos a estudiante {{ filtroEstado === 'activos' ? 'por revisar' : '' }}.
                        </td>
                    </tr>

                    <tr
                        v-for="c in candidatosEstudianteFiltrados"
                        :key="c.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ c.ci }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ c.apellido }} {{ c.nombres }}</p>
                            <p class="text-xs text-gray-500">{{ fmtFecha(c.fecha_nacimiento) }} · {{ c.email || 'sin email' }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <p>{{ carreraLabels[c.carrera_primera_opcion] }}</p>
                            <p class="text-gray-500">{{ carreraLabels[c.carrera_segunda_opcion] }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="estadoConfig[c.estado].clases"
                            >
                                {{ estadoConfig[c.estado].label }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600">
                            <span
                                v-if="c.requisitos_pendientes_revision_count > 0"
                                class="rounded-full bg-yellow-100 px-2 py-0.5 font-semibold text-yellow-700"
                            >
                                {{ c.requisitos_pendientes_revision_count }} documento(s)
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button
                                v-if="c.estado !== 'pendiente'"
                                class="rounded-md bg-[#073b75] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#052a55]"
                                @click="revisar('estudiante', c.id)"
                            >
                                Revisar →
                            </button>
                            <span v-else class="text-xs italic text-gray-400">Sin requisitos</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TAB: docentes -->
        <div v-if="tab === 'docentes'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #7b0000;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">CI</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Nombre</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Email</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estado</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Por revisar</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="candidatosDocenteFiltrados.length === 0">
                        <td colspan="6" class="px-5 py-14 text-center text-gray-400">
                            No hay candidatos a docente {{ filtroEstado === 'activos' ? 'por revisar' : '' }}.
                        </td>
                    </tr>

                    <tr
                        v-for="c in candidatosDocenteFiltrados"
                        :key="c.id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ c.ci }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ c.apellido }} {{ c.nombres }}</p>
                            <p class="text-xs text-gray-500">{{ fmtFecha(c.fecha_nacimiento) }} · {{ c.sexo }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600">{{ c.email }}</td>
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="estadoConfig[c.estado].clases"
                            >
                                {{ estadoConfig[c.estado].label }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-600">
                            <span
                                v-if="c.requisitos_pendientes_revision_count > 0"
                                class="rounded-full bg-yellow-100 px-2 py-0.5 font-semibold text-yellow-700"
                            >
                                {{ c.requisitos_pendientes_revision_count }} documento(s)
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button
                                v-if="c.estado !== 'pendiente'"
                                class="rounded-md bg-[#c70e0a] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#a00b08]"
                                @click="revisar('docente', c.id)"
                            >
                                Revisar →
                            </button>
                            <span v-else class="text-xs italic text-gray-400">Sin requisitos</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
