<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, type Component } from 'vue';
import { GraduationCap, UserCheck, Clock, Check, X, Paperclip, Undo2 } from 'lucide-vue-next';
import { dashboard } from '@/routes';

type EstadoArchivo = 'pendiente_revision' | 'aprobado' | 'rechazado';
type EstadoCandidato = 'pendiente' | 'en_revision' | 'requiere_correcciones' | 'aprobado' | 'aprobado_pendiente_pago' | 'pagado' | 'rechazado';

interface ArchivoRequisito {
    id: number;
    nombre_original: string;
    mime_type: string;
    tamano: number;
    estado: EstadoArchivo;
    motivo_rechazo: string | null;
    subido_at: string | null;
}

interface RequisitoItem {
    codigo: string;
    nombre: string;
    descripcion: string;
    obligatorio: boolean;
    archivo: ArchivoRequisito | null;
}

interface CandidatoData {
    id: number;
    ci: string;
    apellido: string;
    nombres: string;
    nombre_completo: string;
    fecha_nacimiento: string;
    sexo: string;
    telefono: string;
    email: string | null;
    direccion: string;
    estado: EstadoCandidato;
    motivo_rechazo: string | null;
    carrera_primera_opcion?: string;
    carrera_segunda_opcion?: string;
    titulo?: string | null;
    experiencia_anios?: number | null;
    tiene_diplomado?: boolean;
    tiene_maestria?: boolean;
    created_at: string;
}

const props = defineProps<{
    tipo: 'estudiante' | 'docente';
    candidato: CandidatoData;
    requisitos: RequisitoItem[];
    puedeAprobar: boolean;
    tieneRechazados: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Admisión', href: '/administracion/admision' },
            { title: 'Revisar candidato', href: '' },
        ],
    },
});

const estadoCandidatoConfig: Record<EstadoCandidato, { label: string; color: string }> = {
    pendiente:               { label: 'Pendiente de envío',     color: 'bg-yellow-100 text-yellow-700' },
    en_revision:             { label: 'En revisión',            color: 'bg-blue-100 text-blue-700'    },
    requiere_correcciones:   { label: 'Requiere correcciones',  color: 'bg-orange-100 text-orange-700' },
    aprobado:                { label: 'Aprobado',               color: 'bg-green-100 text-green-700'  },
    aprobado_pendiente_pago: { label: 'Esperando pago',         color: 'bg-amber-100 text-amber-700'  },
    pagado:                  { label: 'Pagado',                 color: 'bg-green-100 text-green-700'  },
    rechazado:               { label: 'Rechazado',              color: 'bg-red-100 text-red-700'      },
};

const estadoArchivoConfig: Record<EstadoArchivo, { label: string; color: string; icono: Component }> = {
    pendiente_revision: { label: 'Pendiente de revisión', color: 'bg-gray-100 text-gray-700',   icono: Clock },
    aprobado:           { label: 'Aprobado',              color: 'bg-green-100 text-green-700', icono: Check },
    rechazado:          { label: 'Rechazado',             color: 'bg-red-100 text-red-700',     icono: X     },
};

const totales = computed(() => ({
    aprobados:  props.requisitos.filter(r => r.archivo?.estado === 'aprobado').length,
    rechazados: props.requisitos.filter(r => r.archivo?.estado === 'rechazado').length,
    pendientes: props.requisitos.filter(r => r.archivo?.estado === 'pendiente_revision').length,
    sinSubir:   props.requisitos.filter(r => !r.archivo).length,
}));

const cerrado = computed(() =>
    props.candidato.estado === 'aprobado'
    || props.candidato.estado === 'aprobado_pendiente_pago'
    || props.candidato.estado === 'pagado'
    || props.candidato.estado === 'rechazado',
);

function fmtFecha(f: string): string {
    return new Date(f).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function fmtTamano(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function urlDescargar(archivoId: number): string {
    return `/administracion/admision/requisitos/descargar?tipo=${props.tipo}&id=${archivoId}`;
}

function aprobarRequisito(req: RequisitoItem) {
    if (!req.archivo) return;
    router.patch('/administracion/admision/requisitos/aprobar', { tipo: props.tipo, id: req.archivo.id }, { preserveScroll: true });
}

function rechazarRequisito(req: RequisitoItem) {
    if (!req.archivo) return;
    const motivo = prompt(`Indica el motivo del rechazo de "${req.nombre}".\n\nEl candidato verá este mensaje y deberá corregir el archivo.`);
    if (motivo === null) return;
    if (motivo.trim().length < 5) {
        alert('El motivo debe tener al menos 5 caracteres.');
        return;
    }
    router.patch(
        '/administracion/admision/requisitos/rechazar',
        { tipo: props.tipo, id: req.archivo.id, motivo: motivo.trim() },
        { preserveScroll: true },
    );
}

function aprobarCandidato() {
    const msg = props.tipo === 'docente'
        ? `¿Aprobar al candidato ${props.candidato.nombre_completo} como docente?\n\nSe creará una cuenta de usuario Docente y se enviarán las credenciales por email.`
        : `¿Aprobar al candidato ${props.candidato.nombre_completo} como estudiante CUP?\n\nSe le enviará un link de pago de matrícula por correo. Tras el pago se le crearán las credenciales.`;
    if (!confirm(msg)) return;
    router.patch(`/administracion/admision/candidato-${props.tipo}/${props.candidato.id}/aprobar`);
}

function rechazarCandidato() {
    const motivo = prompt(`Rechazo DEFINITIVO del candidato ${props.candidato.nombre_completo}.\n\nMotivo (obligatorio, mínimo 5 caracteres):`);
    if (motivo === null) return;
    if (motivo.trim().length < 5) {
        alert('Debes indicar un motivo.');
        return;
    }
    router.patch(
        `/administracion/admision/candidato-${props.tipo}/${props.candidato.id}/rechazar`,
        { motivo: motivo.trim() },
    );
}

function solicitarCorrecciones() {
    if (!confirm(`Solicitar correcciones a ${props.candidato.nombre_completo}.\n\nSe enviará un email con los motivos de rechazo de cada requisito. El candidato podrá volver a subir solo los archivos rechazados.`)) return;
    router.patch(`/administracion/admision/candidato-${props.tipo}/${props.candidato.id}/solicitar-correcciones`);
}

function volver() {
    router.visit('/administracion/admision');
}
</script>

<template>
    <Head :title="`Revisar — ${candidato.nombre_completo}`" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Volver -->
        <div>
            <button
                type="button"
                class="text-sm font-medium text-gray-500 hover:text-gray-700"
                @click="volver"
            >
                ← Volver a admisión
            </button>
        </div>

        <!-- Encabezado -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <component :is="tipo === 'estudiante' ? GraduationCap : UserCheck" class="h-6 w-6 text-gray-500" />
                        <h1 class="text-xl font-bold text-gray-900">{{ candidato.nombre_completo }}</h1>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        CI <span class="font-mono">{{ candidato.ci }}</span>
                        · {{ fmtFecha(candidato.fecha_nacimiento) }}
                        · {{ candidato.sexo }}
                    </p>
                    <div class="mt-3 grid grid-cols-1 gap-1 text-sm text-gray-600 sm:grid-cols-2">
                        <p><strong>Email:</strong> {{ candidato.email || '—' }}</p>
                        <p><strong>Teléfono:</strong> {{ candidato.telefono }}</p>
                        <p class="sm:col-span-2"><strong>Dirección:</strong> {{ candidato.direccion }}</p>
                        <template v-if="tipo === 'estudiante'">
                            <p><strong>1ra opción:</strong> {{ candidato.carrera_primera_opcion }}</p>
                            <p><strong>2da opción:</strong> {{ candidato.carrera_segunda_opcion }}</p>
                        </template>
                        <template v-if="tipo === 'docente'">
                            <p>
                                <strong>Título:</strong>
                                <span v-if="candidato.titulo">{{ candidato.titulo }}</span>
                                <span v-else class="italic text-amber-600">No declarado</span>
                            </p>
                            <p>
                                <strong>Experiencia:</strong>
                                <span v-if="candidato.experiencia_anios !== null && candidato.experiencia_anios !== undefined">{{ candidato.experiencia_anios }} año(s)</span>
                                <span v-else class="italic text-gray-400">—</span>
                            </p>
                            <p class="sm:col-span-2">
                                <strong>Formación:</strong>
                                <span v-if="candidato.tiene_maestria" class="ml-1 inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700">Maestría</span>
                                <span v-if="candidato.tiene_diplomado" class="ml-1 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Diplomado</span>
                                <span v-if="!candidato.tiene_diplomado && !candidato.tiene_maestria" class="ml-1 text-xs text-gray-400">Ninguna declarada</span>
                            </p>
                        </template>
                    </div>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="estadoCandidatoConfig[candidato.estado].color"
                >
                    {{ estadoCandidatoConfig[candidato.estado].label }}
                </span>
            </div>
            <div
                v-if="candidato.motivo_rechazo"
                class="mt-4 rounded-md border-l-4 border-red-400 bg-red-50 px-3 py-2 text-sm text-red-800"
            >
                <strong>Observación general:</strong> {{ candidato.motivo_rechazo }}
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center">
                <p class="text-xs font-medium text-green-700">Aprobados</p>
                <p class="mt-1 text-2xl font-bold text-green-800">{{ totales.aprobados }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center">
                <p class="text-xs font-medium text-red-700">Rechazados</p>
                <p class="mt-1 text-2xl font-bold text-red-800">{{ totales.rechazados }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center">
                <p class="text-xs font-medium text-gray-700">Pendientes</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ totales.pendientes }}</p>
            </div>
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-center">
                <p class="text-xs font-medium text-yellow-700">Sin subir</p>
                <p class="mt-1 text-2xl font-bold text-yellow-800">{{ totales.sinSubir }}</p>
            </div>
        </div>

        <!-- Lista de requisitos -->
        <div class="space-y-3">
            <div
                v-for="req in requisitos"
                :key="req.codigo"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-900">{{ req.nombre }}</h3>
                            <span
                                v-if="!req.obligatorio"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium uppercase text-gray-600"
                            >
                                Opcional
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ req.descripcion }}</p>
                    </div>
                    <span
                        v-if="req.archivo"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                        :class="estadoArchivoConfig[req.archivo.estado].color"
                    >
                        <component :is="estadoArchivoConfig[req.archivo.estado].icono" class="h-3 w-3" />
                        {{ estadoArchivoConfig[req.archivo.estado].label }}
                    </span>
                    <span
                        v-else
                        class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700"
                    >
                        Sin subir
                    </span>
                </div>

                <div
                    v-if="req.archivo"
                    class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-md bg-gray-50 px-3 py-2"
                >
                    <div class="flex items-center gap-2 text-sm text-gray-700 truncate">
                        <Paperclip class="h-4 w-4 flex-shrink-0" />
                        <a
                            :href="urlDescargar(req.archivo.id)"
                            target="_blank"
                            class="truncate font-medium hover:underline"
                        >
                            {{ req.archivo.nombre_original }}
                        </a>
                        <span class="text-xs text-gray-400">({{ fmtTamano(req.archivo.tamano) }})</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="!cerrado"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700 disabled:opacity-40"
                            :disabled="req.archivo.estado === 'aprobado'"
                            @click="aprobarRequisito(req)"
                        >
                            <Check class="h-3 w-3" /> Aprobar
                        </button>
                        <button
                            v-if="!cerrado"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-40"
                            :disabled="req.archivo.estado === 'rechazado'"
                            @click="rechazarRequisito(req)"
                        >
                            <X class="h-3 w-3" /> Rechazar
                        </button>
                    </div>
                </div>

                <div
                    v-if="req.archivo?.estado === 'rechazado' && req.archivo.motivo_rechazo"
                    class="mt-3 rounded-md border-l-4 border-red-400 bg-red-50 px-3 py-2 text-sm text-red-800"
                >
                    <strong>Motivo del rechazo:</strong> {{ req.archivo.motivo_rechazo }}
                </div>
            </div>
        </div>

        <!-- Acciones finales -->
        <div
            v-if="!cerrado"
            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
        >
            <h2 class="text-base font-semibold text-gray-900">Cerrar la revisión</h2>
            <p class="mt-1 text-sm text-gray-500">
                Aprueba al candidato si todos los requisitos obligatorios están aprobados,
                solicita correcciones si hay rechazados, o rechaza definitivamente la solicitud.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!puedeAprobar"
                    :title="!puedeAprobar ? 'Debes aprobar todos los requisitos obligatorios' : ''"
                    @click="aprobarCandidato"
                >
                    <Check class="h-4 w-4" /> Aprobar candidato
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="!tieneRechazados || candidato.estado !== 'en_revision'"
                    :title="!tieneRechazados ? 'No hay requisitos rechazados' : ''"
                    @click="solicitarCorrecciones"
                >
                    <Undo2 class="h-4 w-4" /> Solicitar correcciones
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50"
                    @click="rechazarCandidato"
                >
                    <X class="h-4 w-4" /> Rechazar definitivamente
                </button>
            </div>
        </div>
    </div>
</template>
