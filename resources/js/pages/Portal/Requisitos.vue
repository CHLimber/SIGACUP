<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, type Component } from 'vue';
import { Clock, Check, X, GraduationCap, UserCheck, ClipboardList, Paperclip, Loader2, RefreshCw, Upload, BriefcaseBusiness } from 'lucide-vue-next';

type EstadoArchivo = 'pendiente_revision' | 'aprobado' | 'rechazado';
type EstadoCandidato = 'pendiente' | 'en_revision' | 'requiere_correcciones' | 'aprobado_pendiente_pago' | 'pagado' | 'rechazado';

interface ArchivoSubido {
    id: number;
    nombre_original: string;
    estado: EstadoArchivo;
    motivo_rechazo: string | null;
    subido_at: string | null;
}

interface RequisitoItem {
    codigo: string;
    nombre: string;
    descripcion: string;
    obligatorio: boolean;
    mimes: string[];
    tamano_max_kb: number;
    archivo: ArchivoSubido | null;
}

interface CandidatoData {
    tipo: 'estudiante' | 'docente';
    nombre_completo: string;
    ci: string;
    email: string | null;
    estado: EstadoCandidato;
    motivo_rechazo: string | null;
}

interface DatosProfesionales {
    titulo: string;
    experiencia_anios: number;
    tiene_diplomado: boolean;
    tiene_maestria: boolean;
}

const props = defineProps<{
    token: string;
    candidato: CandidatoData;
    datosProfesionales: DatosProfesionales | null;
    requisitos: RequisitoItem[];
    puedeEnviar: boolean;
    bloqueado: boolean;
}>();

const subiendo = ref<string | null>(null);
const errores = ref<Record<string, string>>({});

const estadoCandidatoConfig: Record<EstadoCandidato, { label: string; descripcion: string; color: string }> = {
    pendiente: {
        label: 'Pendiente de envío',
        descripcion: 'Sube todos los requisitos obligatorios y envíalos para revisión.',
        color: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    },
    en_revision: {
        label: 'En revisión',
        descripcion: 'Tu documentación está siendo revisada por la coordinación.',
        color: 'bg-blue-100 text-blue-800 border-blue-300',
    },
    requiere_correcciones: {
        label: 'Requiere correcciones',
        descripcion: 'Hay requisitos rechazados. Revisa los motivos y vuelve a subirlos.',
        color: 'bg-orange-100 text-orange-800 border-orange-300',
    },
    aprobado_pendiente_pago: {
        label: 'Aprobado — pendiente de pago',
        descripcion: 'Tu solicitud fue aprobada. Te enviamos por correo el link para pagar tu matrícula.',
        color: 'bg-amber-100 text-amber-800 border-amber-300',
    },
    pagado: {
        label: 'Matrícula pagada',
        descripcion: '¡Felicidades! Tu inscripción está completa. Revisa tu correo para acceder al sistema.',
        color: 'bg-green-100 text-green-800 border-green-300',
    },
    rechazado: {
        label: 'Rechazado',
        descripcion: 'Tu solicitud fue rechazada definitivamente.',
        color: 'bg-red-100 text-red-800 border-red-300',
    },
};

const estadoArchivoConfig: Record<EstadoArchivo, { label: string; color: string; icono: Component }> = {
    pendiente_revision: { label: 'Pendiente de revisión', color: 'bg-gray-100 text-gray-700',   icono: Clock },
    aprobado:           { label: 'Aprobado',              color: 'bg-green-100 text-green-700', icono: Check },
    rechazado:          { label: 'Rechazado',             color: 'bg-red-100 text-red-700',     icono: X     },
};

const obligatoriosFaltantes = computed(() =>
    props.requisitos.filter((r) => r.obligatorio && !r.archivo).length,
);

const faltaDatosProfesionales = computed(() =>
    props.candidato.tipo === 'docente'
    && (!props.datosProfesionales?.titulo || props.datosProfesionales.titulo.trim() === ''),
);

const mensajeBotonEnviar = computed(() => {
    if (props.puedeEnviar) return 'Listo para enviar';
    const partes: string[] = [];
    if (obligatoriosFaltantes.value > 0) {
        partes.push(`${obligatoriosFaltantes.value} requisito(s) obligatorio(s)`);
    }
    if (faltaDatosProfesionales.value) {
        partes.push('datos profesionales');
    }
    return 'Faltan: ' + partes.join(' · ');
});

const totalRechazados = computed(() =>
    props.requisitos.filter((r) => r.archivo?.estado === 'rechazado').length,
);

function puedeEditarRequisito(req: RequisitoItem): boolean {
    if (props.candidato.estado === 'aprobado_pendiente_pago'
        || props.candidato.estado === 'pagado'
        || props.candidato.estado === 'rechazado') return false;
    if (props.candidato.estado === 'en_revision') return false;
    if (req.archivo?.estado === 'aprobado') return false;
    return true;
}

function urlSubir(codigo: string): string {
    return `/candidato/${props.token}/requisitos/${codigo}`;
}

function urlEnviar(): string {
    return `/candidato/${props.token}/enviar`;
}

function urlDescargar(codigo: string): string {
    return `/candidato/${props.token}/requisitos/${codigo}/descargar`;
}

function onArchivoSeleccionado(event: Event, codigo: string) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    subiendo.value = codigo;
    errores.value[codigo] = '';

    router.post(urlSubir(codigo), { archivo: file }, {
        forceFormData: true,
        preserveScroll: true,
        onError: (errs) => {
            errores.value[codigo] = (errs.archivo as string) || 'No se pudo subir el archivo.';
        },
        onFinish: () => {
            subiendo.value = null;
            input.value = '';
        },
    });
}

function eliminarArchivo(codigo: string) {
    if (!confirm('¿Eliminar este archivo? Tendrás que volver a subirlo.')) return;
    router.delete(`/candidato/${props.token}/requisitos/${codigo}`, { preserveScroll: true });
}

const enviarForm = useForm({});
function enviarRevision() {
    if (!confirm('¿Confirmas el envío? Una vez enviada no podrás modificar los archivos hasta que la coordinación responda.')) return;
    enviarForm.post(urlEnviar(), { preserveScroll: true });
}

const datosForm = useForm({
    titulo:            props.datosProfesionales?.titulo ?? '',
    experiencia_anios: props.datosProfesionales?.experiencia_anios ?? 0,
    tiene_diplomado:   props.datosProfesionales?.tiene_diplomado ?? false,
    tiene_maestria:    props.datosProfesionales?.tiene_maestria ?? false,
});

function puedeEditarDatosProfesionales(): boolean {
    return props.candidato.tipo === 'docente'
        && props.candidato.estado !== 'en_revision'
        && !props.bloqueado;
}

function guardarDatosProfesionales() {
    datosForm.post(`/candidato/${props.token}/datos-profesionales`, {
        preserveScroll: true,
    });
}

function mimeLabel(mimes: string[]): string {
    return mimes
        .map((m) => {
            if (m === 'application/pdf') return 'PDF';
            if (m === 'image/jpeg') return 'JPG';
            if (m === 'image/png') return 'PNG';
            if (m === 'image/webp') return 'WEBP';
            return m;
        })
        .join(' · ');
}
</script>

<template>
    <Head title="Mis requisitos — SIGACUP" />

    <div class="min-h-screen bg-gray-50">
        <!-- Cabecera -->
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto max-w-4xl px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-[#c70e0a]">SIGACUP — FICCT UAGRM</p>
                        <h1 class="mt-1 text-2xl font-bold text-gray-900">Mis requisitos</h1>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Hola, <strong>{{ candidato.nombre_completo }}</strong> · CI {{ candidato.ci }}
                        </p>
                    </div>
                    <component :is="candidato.tipo === 'estudiante' ? GraduationCap : UserCheck" class="h-8 w-8 text-gray-400" />
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-6 py-8">
            <!-- Estado global -->
            <div
                class="rounded-xl border p-5 shadow-sm"
                :class="estadoCandidatoConfig[candidato.estado].color"
            >
                <div class="flex items-start gap-3">
                    <ClipboardList class="h-6 w-6 flex-shrink-0 opacity-75" />
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wider opacity-75">Estado de tu solicitud</p>
                        <p class="mt-0.5 text-lg font-bold">{{ estadoCandidatoConfig[candidato.estado].label }}</p>
                        <p class="mt-1 text-sm">{{ estadoCandidatoConfig[candidato.estado].descripcion }}</p>
                        <p
                            v-if="candidato.motivo_rechazo"
                            class="mt-2 rounded-md bg-white/60 px-3 py-2 text-sm"
                        >
                            <strong>Observación general:</strong> {{ candidato.motivo_rechazo }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Resumen para correcciones -->
            <div
                v-if="candidato.estado === 'requiere_correcciones' && totalRechazados > 0"
                class="mt-4 rounded-lg border border-orange-200 bg-orange-50 p-4"
            >
                <p class="text-sm text-orange-800">
                    <strong>{{ totalRechazados }}</strong> requisito{{ totalRechazados > 1 ? 's' : '' }} fueron rechazados.
                    Revisa el motivo en cada tarjeta y vuelve a subir solo esos archivos.
                </p>
            </div>

            <!-- Datos profesionales (solo docentes) -->
            <div
                v-if="candidato.tipo === 'docente' && datosProfesionales"
                class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <BriefcaseBusiness class="h-6 w-6 flex-shrink-0 text-[#073b75]" />
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-gray-900">Datos profesionales</h2>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Completa tu información profesional. Se asociará a tu cuenta cuando seas aprobado.
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700">Título profesional *</label>
                        <input
                            v-model="datosForm.titulo"
                            type="text"
                            maxlength="120"
                            placeholder="Lic. Ingeniería de Sistemas"
                            :disabled="!puedeEditarDatosProfesionales()"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75] disabled:bg-gray-50 disabled:text-gray-500"
                        />
                        <p v-if="datosForm.errors.titulo" class="mt-1 text-xs text-red-600">{{ datosForm.errors.titulo }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700">Años de experiencia *</label>
                        <input
                            v-model.number="datosForm.experiencia_anios"
                            type="number"
                            min="0"
                            max="60"
                            :disabled="!puedeEditarDatosProfesionales()"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75] disabled:bg-gray-50 disabled:text-gray-500"
                        />
                        <p v-if="datosForm.errors.experiencia_anios" class="mt-1 text-xs text-red-600">{{ datosForm.errors.experiencia_anios }}</p>
                    </div>
                    <div class="flex flex-col justify-center gap-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input
                                v-model="datosForm.tiene_diplomado"
                                type="checkbox"
                                :disabled="!puedeEditarDatosProfesionales()"
                                class="h-4 w-4 rounded border-gray-300 text-[#073b75] focus:ring-[#073b75]"
                            />
                            Tengo diplomado
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input
                                v-model="datosForm.tiene_maestria"
                                type="checkbox"
                                :disabled="!puedeEditarDatosProfesionales()"
                                class="h-4 w-4 rounded border-gray-300 text-[#073b75] focus:ring-[#073b75]"
                            />
                            Tengo maestría
                        </label>
                    </div>
                </div>

                <div v-if="puedeEditarDatosProfesionales()" class="mt-4 flex justify-end">
                    <button
                        type="button"
                        class="rounded-md bg-[#073b75] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#052a55] disabled:opacity-50"
                        :disabled="datosForm.processing"
                        @click="guardarDatosProfesionales"
                    >
                        {{ datosForm.processing ? 'Guardando…' : 'Guardar datos profesionales' }}
                    </button>
                </div>
            </div>

            <!-- Lista de requisitos -->
            <div class="mt-6 space-y-4">
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
                            <p class="mt-1 text-sm text-gray-500">{{ req.descripcion }}</p>
                            <p class="mt-1 text-xs text-gray-400">
                                Formatos: {{ mimeLabel(req.mimes) }} · Máx {{ req.tamano_max_kb / 1024 }} MB
                            </p>
                        </div>

                        <div v-if="req.archivo" class="text-right">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="estadoArchivoConfig[req.archivo.estado].color"
                            >
                                <component :is="estadoArchivoConfig[req.archivo.estado].icono" class="h-3 w-3" />
                                {{ estadoArchivoConfig[req.archivo.estado].label }}
                            </span>
                        </div>
                    </div>

                    <!-- Motivo de rechazo del requisito -->
                    <div
                        v-if="req.archivo?.estado === 'rechazado' && req.archivo.motivo_rechazo"
                        class="mt-3 rounded-md border-l-4 border-red-400 bg-red-50 px-3 py-2 text-sm text-red-800"
                    >
                        <strong>Motivo del rechazo:</strong> {{ req.archivo.motivo_rechazo }}
                    </div>

                    <!-- Archivo subido -->
                    <div
                        v-if="req.archivo"
                        class="mt-4 flex items-center justify-between gap-3 rounded-md bg-gray-50 px-3 py-2"
                    >
                        <div class="flex items-center gap-2 text-sm text-gray-700 truncate">
                            <Paperclip class="h-4 w-4 flex-shrink-0" />
                            <a
                                :href="urlDescargar(req.codigo)"
                                target="_blank"
                                class="truncate hover:underline"
                            >
                                {{ req.archivo.nombre_original }}
                            </a>
                        </div>
                        <button
                            v-if="puedeEditarRequisito(req)"
                            type="button"
                            class="text-xs font-medium text-red-600 hover:underline"
                            @click="eliminarArchivo(req.codigo)"
                        >
                            Eliminar
                        </button>
                    </div>

                    <!-- Input de subida -->
                    <div v-if="puedeEditarRequisito(req)" class="mt-3">
                        <label
                            class="inline-flex cursor-pointer items-center gap-2 rounded-md bg-[#073b75] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#052a55]"
                            :class="{ 'opacity-50': subiendo === req.codigo }"
                        >
                            <span v-if="subiendo === req.codigo" class="inline-flex items-center gap-1.5"><Loader2 class="h-4 w-4 animate-spin" /> Subiendo...</span>
                            <span v-else-if="req.archivo" class="inline-flex items-center gap-1.5"><RefreshCw class="h-4 w-4" /> Reemplazar archivo</span>
                            <span v-else class="inline-flex items-center gap-1.5"><Upload class="h-4 w-4" /> Subir archivo</span>
                            <input
                                type="file"
                                class="hidden"
                                :accept="req.mimes.join(',')"
                                :disabled="subiendo !== null"
                                @change="onArchivoSeleccionado($event, req.codigo)"
                            />
                        </label>
                        <p v-if="errores[req.codigo]" class="mt-2 text-xs text-red-600">{{ errores[req.codigo] }}</p>
                    </div>
                </div>
            </div>

            <!-- Botón enviar -->
            <div
                v-if="!bloqueado"
                class="mt-8 rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ mensajeBotonEnviar }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Una vez enviada, la solicitud quedará bloqueada hasta que la coordinación responda.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-md bg-[#c70e0a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#a00b08] disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!puedeEnviar || enviarForm.processing"
                        @click="enviarRevision"
                    >
                        {{ enviarForm.processing ? 'Enviando...' : 'Enviar requisitos para revisión' }}
                    </button>
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-gray-400">
                Guarda este enlace, es personal y único. Cualquier consulta, escribe a coordinacion@ficct.uagrm.edu.bo.
            </p>
        </main>
    </div>
</template>
