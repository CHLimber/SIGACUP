<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { GraduationCap, UserCheck, AlertTriangle, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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

type EstadoEstudiante = 'pendiente' | 'en_revision' | 'requiere_correcciones' | 'aprobado_pendiente_pago' | 'pagado' | 'rechazado';
type EstadoDocente    = 'pendiente' | 'en_revision' | 'requiere_correcciones' | 'aprobado' | 'rechazado';
type Estado = EstadoEstudiante | EstadoDocente;

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

interface GestionOption {
    id: number;
    label: string;
}

const props = defineProps<{
    candidatosEstudiante: CandidatoEstudiante[];
    candidatosDocente:    CandidatoDocente[];
    gestiones:            GestionOption[];
    filtros:              { gestion_id: number | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Admisión', href: '/administracion/admision' },
        ],
    },
});

const tab            = ref<'estudiantes' | 'docentes'>('estudiantes');
const filtroEstado   = ref<'activos' | 'todos'>('activos');
const filtroGestionId = ref(props.filtros.gestion_id ? String(props.filtros.gestion_id) : '');
const busqueda       = ref('');

// Recarga el servidor al cambiar la gestión
watch(filtroGestionId, (val) => {
    const params: Record<string, string> = {};
    if (val) params.gestion_id = val;
    router.get('/administracion/admision', params, { preserveScroll: true, replace: true });
});

const hayFiltros = computed(() => !!(filtroGestionId.value || busqueda.value || filtroEstado.value === 'todos'));

function limpiarFiltros() {
    filtroGestionId.value = '';
    busqueda.value = '';
    filtroEstado.value = 'activos';
}

const estadoConfig: Record<Estado, { label: string; clases: string }> = {
    pendiente:               { label: 'Sin enviar',             clases: 'bg-gray-100 text-gray-700' },
    en_revision:             { label: 'En revisión',            clases: 'bg-blue-100 text-blue-700' },
    requiere_correcciones:   { label: 'Requiere correcciones',  clases: 'bg-orange-100 text-orange-700' },
    aprobado_pendiente_pago: { label: 'Esperando pago',         clases: 'bg-amber-100 text-amber-700' },
    pagado:                  { label: 'Pagado',                 clases: 'bg-green-100 text-green-700' },
    aprobado:                { label: 'Aprobado',               clases: 'bg-green-100 text-green-700' },
    rechazado:               { label: 'Rechazado',              clases: 'bg-red-100 text-red-700' },
};

const estadoFallback = { label: 'Desconocido', clases: 'bg-gray-100 text-gray-500' };

function infoEstado(e: string) {
    return estadoConfig[e as Estado] ?? estadoFallback;
}

const estadosActivos: Estado[] = ['en_revision', 'requiere_correcciones'];

function matchBusqueda(c: CandidatoBase): boolean {
    if (!busqueda.value.trim()) return true;
    const q = busqueda.value.toLowerCase();
    return (
        c.ci?.toLowerCase().includes(q) ||
        c.apellido?.toLowerCase().includes(q) ||
        c.nombres?.toLowerCase().includes(q) ||
        (c as CandidatoEstudiante).email?.toLowerCase().includes(q) ||
        false
    );
}

const candidatosEstudianteFiltrados = computed(() =>
    props.candidatosEstudiante
        .filter(c => filtroEstado.value === 'activos' ? estadosActivos.includes(c.estado) : true)
        .filter(matchBusqueda),
);

const candidatosDocenteFiltrados = computed(() =>
    props.candidatosDocente
        .filter(c => filtroEstado.value === 'activos' ? estadosActivos.includes(c.estado) : true)
        .filter(matchBusqueda),
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

interface ConfirmEliminar {
    id: number;
    nombre: string;
    estado: Estado;
    tipo: 'estudiante' | 'docente';
}

const candidatoAEliminar = ref<ConfirmEliminar | null>(null);
const eliminando = ref(false);

function confirmarEliminar(tipo: 'estudiante' | 'docente', c: CandidatoBase) {
    candidatoAEliminar.value = { id: c.id, nombre: `${c.apellido} ${c.nombres}`, estado: c.estado, tipo };
}

function cancelarEliminar() {
    if (!eliminando.value) candidatoAEliminar.value = null;
}

function ejecutarEliminar() {
    if (!candidatoAEliminar.value) return;
    const { id, tipo } = candidatoAEliminar.value;
    eliminando.value = true;
    router.delete(`/administracion/admision/candidato-${tipo}/${id}`, {
        onFinish: () => {
            eliminando.value = false;
            candidatoAEliminar.value = null;
        },
    });
}
</script>

<template>
    <Head title="Admisión — SIGACUP" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
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
                    <GraduationCap class="h-10 w-10 text-[#073b75]" />
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Docentes en revisión</p>
                        <p class="mt-1 text-3xl font-bold text-[#c70e0a]">{{ totales.docentesPorRevisar }}</p>
                    </div>
                    <UserCheck class="h-10 w-10 text-[#c70e0a]" />
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-1 border-b border-gray-200">
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

        <!-- Barra de filtros -->
        <div class="flex flex-wrap items-end gap-3">
            <!-- Gestión (solo estudiantes) -->
            <div v-if="tab === 'estudiantes'" class="flex flex-col gap-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gestión</label>
                <select
                    v-model="filtroGestionId"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                >
                    <option value="">Todas las gestiones</option>
                    <option v-for="g in gestiones" :key="g.id" :value="String(g.id)">{{ g.label }}</option>
                </select>
            </div>

            <!-- Búsqueda -->
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Buscar</label>
                <input
                    v-model="busqueda"
                    placeholder="CI, nombre o email…"
                    class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75] w-48 sm:w-64"
                />
            </div>

            <!-- Estado -->
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estado</label>
                <div class="flex gap-1">
                    <button
                        type="button"
                        @click="filtroEstado = 'activos'"
                        :class="[
                            'rounded-md px-3 py-2 text-xs font-medium transition',
                            filtroEstado === 'activos'
                                ? 'bg-blue-100 text-blue-700 ring-1 ring-blue-300'
                                : 'border border-gray-300 text-gray-500 hover:bg-gray-50',
                        ]"
                    >
                        Por revisar
                    </button>
                    <button
                        type="button"
                        @click="filtroEstado = 'todos'"
                        :class="[
                            'rounded-md px-3 py-2 text-xs font-medium transition',
                            filtroEstado === 'todos'
                                ? 'bg-gray-200 text-gray-800'
                                : 'border border-gray-300 text-gray-500 hover:bg-gray-50',
                        ]"
                    >
                        Todos
                    </button>
                </div>
            </div>

            <!-- Limpiar -->
            <button
                v-if="hayFiltros"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                @click="limpiarFiltros"
            >
                <X class="h-3.5 w-3.5" /> Limpiar
            </button>
        </div>

        <!-- TAB: estudiantes -->
        <div v-if="tab === 'estudiantes'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
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
                                <p>{{ c.carrera_primera_opcion }}</p>
                                <p class="text-gray-500">{{ c.carrera_segunda_opcion }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="infoEstado(c.estado).clases"
                                >
                                    {{ infoEstado(c.estado).label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600">
                                <span
                                    v-if="c.requisitos_pendientes_revision_count > 0"
                                    class="rounded-full bg-yellow-100 px-2 py-0.5 font-semibold text-yellow-700"
                                >
                                    {{ c.requisitos_pendientes_revision_count }} doc.
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="c.estado !== 'pendiente'"
                                        class="rounded-md bg-[#073b75] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#052a55]"
                                        @click="revisar('estudiante', c.id)"
                                    >
                                        Revisar →
                                    </button>
                                    <span v-else class="text-xs italic text-gray-400">Sin requisitos</span>
                                    <button
                                        class="rounded-md bg-red-100 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-200"
                                        @click="confirmarEliminar('estudiante', c)"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Diálogo de confirmación de eliminación -->
        <Dialog :open="candidatoAEliminar !== null" @update:open="v => !v && cancelarEliminar()">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Eliminar candidato</DialogTitle>
                    <DialogDescription>
                        ¿Estás seguro de que deseas eliminar a
                        <strong>{{ candidatoAEliminar?.nombre }}</strong>?
                        Esta acción eliminará todos sus documentos y no se puede deshacer.
                    </DialogDescription>
                </DialogHeader>
                <div
                    v-if="candidatoAEliminar?.estado === 'pagado'"
                    class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    <AlertTriangle class="mr-1.5 inline h-4 w-4 align-text-bottom" /> Este candidato tiene un pago confirmado. Al eliminar se perderá también el registro del pago.
                </div>
                <div
                    v-if="candidatoAEliminar?.estado === 'aprobado'"
                    class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    <AlertTriangle class="mr-1.5 inline h-4 w-4 align-text-bottom" /> Este docente ya fue aprobado y tiene una cuenta de acceso al sistema. Al eliminar se borrará también su cuenta de usuario.
                </div>
                <DialogFooter>
                    <Button variant="outline" :disabled="eliminando" @click="cancelarEliminar">
                        Cancelar
                    </Button>
                    <Button variant="destructive" :disabled="eliminando" @click="ejecutarEliminar">
                        {{ eliminando ? 'Eliminando…' : 'Sí, eliminar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- TAB: docentes -->
        <div v-if="tab === 'docentes'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
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
                                    :class="infoEstado(c.estado).clases"
                                >
                                    {{ infoEstado(c.estado).label }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600">
                                <span
                                    v-if="c.requisitos_pendientes_revision_count > 0"
                                    class="rounded-full bg-yellow-100 px-2 py-0.5 font-semibold text-yellow-700"
                                >
                                    {{ c.requisitos_pendientes_revision_count }} doc.
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="c.estado !== 'pendiente'"
                                        class="rounded-md bg-[#c70e0a] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#a00b08]"
                                        @click="revisar('docente', c.id)"
                                    >
                                        Revisar →
                                    </button>
                                    <span v-else class="text-xs italic text-gray-400">Sin requisitos</span>
                                    <button
                                        class="rounded-md bg-red-100 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-200"
                                        @click="confirmarEliminar('docente', c)"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
