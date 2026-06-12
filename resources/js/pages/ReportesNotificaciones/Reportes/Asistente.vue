<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, FileSpreadsheet, FileText, Mic, MicOff, Send, Sparkles, Table as TableIcon } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { dashboard } from '@/routes';

interface ColumnaMeta {
    key: string;
    label: string;
    type: string;
}
interface AsistenteResultado {
    consulta: string;
    explicacion: string;
    reporte: { key: string; label: string };
    filtros: Record<string, unknown>;
    columnas: ColumnaMeta[];
    rows: Record<string, unknown>[];
    total: number;
}

defineProps<{
    configurado: boolean;
    ejemplos: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Reportes', href: '/administracion/reportes' },
            { title: 'Asistente IA', href: '#' },
        ],
    },
});

const page = usePage();
const consulta = ref('');
const procesando = ref(false);
const escuchando = ref(false);

const resultado = computed<AsistenteResultado | null>(
    () => (page.props.flash as { asistente_resultado?: AsistenteResultado } | undefined)?.asistente_resultado ?? null,
);

// ── Dictado por voz (Web Speech API del navegador, sin servidor) ──────────────
// El reconocimiento ocurre en el navegador (gratuito). Requiere Chrome/Edge oficial
// y contexto seguro (HTTPS o localhost). Si el navegador no puede contactar el
// servicio de reconocimiento de Google devuelve el error "network".
type SpeechRecognitionLike = {
    lang: string;
    interimResults: boolean;
    continuous: boolean;
    start: () => void;
    stop: () => void;
    onresult: ((e: { results: ArrayLike<ArrayLike<{ transcript: string }>> }) => void) | null;
    onerror: ((e: { error?: string }) => void) | null;
    onend: (() => void) | null;
};

// El acceso a `window` se difiere a onMounted: el componente también se renderiza
// en el servidor (SSR), donde `window` no existe.
let SpeechRecognitionCtor: (new () => SpeechRecognitionLike) | undefined;
const vozDisponible = ref(false);
const vozError = ref('');
let recognition: SpeechRecognitionLike | null = null;
let contextoSeguro = false;

onMounted(() => {
    SpeechRecognitionCtor =
        (window as unknown as { SpeechRecognition?: new () => SpeechRecognitionLike; webkitSpeechRecognition?: new () => SpeechRecognitionLike })
            .SpeechRecognition ??
        (window as unknown as { webkitSpeechRecognition?: new () => SpeechRecognitionLike }).webkitSpeechRecognition;
    vozDisponible.value = !!SpeechRecognitionCtor;
    contextoSeguro = window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname);
});

function mensajeError(codigo?: string): string {
    switch (codigo) {
        case 'not-allowed':
        case 'service-not-allowed':
            return 'Permiso de micrófono denegado. Habilitá el micrófono para este sitio en el navegador.';
        case 'no-speech':
            return 'No se detectó voz. Probá de nuevo y hablá cerca del micrófono.';
        case 'audio-capture':
            return 'No se encontró ningún micrófono. Verificá que haya uno conectado.';
        case 'network':
            return 'El navegador no pudo contactar el servicio de voz de Google. Usá Google Chrome oficial; si persiste, escribí la consulta.';
        case 'aborted':
            return '';
        default:
            return 'No se pudo iniciar el dictado por voz. Probá de nuevo.';
    }
}

function toggleVoz() {
    if (!SpeechRecognitionCtor) {
        return;
    }

    if (escuchando.value) {
        recognition?.stop();

        return;
    }

    if (!contextoSeguro) {
        vozError.value =
            'El dictado por voz requiere una conexión segura (HTTPS) o acceder por localhost. Si entraste por una IP de red, el navegador bloquea el micrófono.';

        return;
    }

    vozError.value = '';
    recognition = new SpeechRecognitionCtor();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;
    recognition.continuous = false;

    recognition.onresult = (e) => {
        const texto = Array.from(e.results)
            .map((r) => r[0].transcript)
            .join(' ');
        consulta.value = (consulta.value ? consulta.value + ' ' : '') + texto;
    };
    recognition.onerror = (e) => {
        vozError.value = mensajeError(e?.error);
        escuchando.value = false;
    };
    recognition.onend = () => {
        escuchando.value = false;
    };

    try {
        escuchando.value = true;
        recognition.start();
    } catch {
        escuchando.value = false;
        vozError.value = 'No se pudo acceder al micrófono. Probá de nuevo.';
    }
}

onBeforeUnmount(() => recognition?.stop());

// ── Envío ────────────────────────────────────────────────────────────────
function enviar() {
    if (!consulta.value.trim() || procesando.value) {
        return;
    }

    router.post(
        '/administracion/reportes/ia/consultar',
        { consulta: consulta.value },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (procesando.value = true),
            onFinish: () => (procesando.value = false),
        },
    );
}

function usarEjemplo(texto: string) {
    consulta.value = texto;
    enviar();
}

// ── Exportación (reutiliza las rutas de exportación de reportes) ───────────
// Aplana el objeto de filtros a pares filtros[key][sub]=valor para el query string.
function aplanarFiltros(obj: Record<string, unknown>, prefijo: string, params: URLSearchParams) {
    for (const [key, valor] of Object.entries(obj)) {
        if (valor === null || valor === undefined || valor === '') {
            continue;
        }

        const clave = `${prefijo}[${key}]`;

        if (typeof valor === 'object' && !Array.isArray(valor)) {
            aplanarFiltros(valor as Record<string, unknown>, clave, params);
        } else {
            params.set(clave, String(valor));
        }
    }
}

function exportar(formato: 'csv' | 'pdf') {
    if (!resultado.value) {
        return;
    }

    const params = new URLSearchParams();
    params.set('tipo', 'personalizado');
    params.set('reporte', resultado.value.reporte.key);
    aplanarFiltros(resultado.value.filtros, 'filtros', params);

    window.location.href = `/administracion/reportes/exportar/${formato}?${params.toString()}`;
}

// ── Presentación de celdas ───────────────────────────────────────────────
const ESTADO_CLASES: Record<string, string> = {
    admitido: 'bg-green-100 text-green-700',
    pagado: 'bg-green-100 text-green-700',
    completado: 'bg-green-100 text-green-700',
    sin_cupo: 'bg-amber-100 text-amber-700',
    no_admitido: 'bg-red-100 text-red-700',
    reprobado: 'bg-red-100 text-red-700',
    rechazado: 'bg-red-100 text-red-700',
    pendiente: 'bg-gray-100 text-gray-600',
};

function badgeClase(valor: unknown): string {
    return ESTADO_CLASES[String(valor)] ?? 'bg-slate-100 text-slate-600';
}

function mostrar(valor: unknown): string {
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    if (typeof valor === 'boolean') {
        return valor ? 'Sí' : 'No';
    }

    return String(valor);
}
</script>

<template>
    <Head title="Asistente IA de reportes" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900">
                <Sparkles class="h-6 w-6 text-[#073b75]" /> Asistente IA de reportes
            </h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Escribí o dictá tu consulta en lenguaje natural y la IA elegirá el reporte adecuado.
                Ejemplo: «alumnos admitidos en Ingeniería en Sistemas».
            </p>
        </div>

        <!-- Aviso si no está configurado -->
        <div
            v-if="!configurado"
            class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
        >
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
            <span>
                El asistente de IA aún no está configurado. Definí <code class="font-mono">ANTHROPIC_API_KEY</code>
                en el archivo <code class="font-mono">.env</code> para habilitarlo.
            </span>
        </div>

        <!-- Caja de consulta -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3">
                <div class="relative">
                    <textarea
                        v-model="consulta"
                        rows="3"
                        placeholder="Ej.: ¿Cuántos postulantes mujeres aprobaron en la gestión 2025-2?"
                        class="w-full resize-none rounded-lg border border-gray-300 bg-white px-4 py-3 pr-12 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                        @keydown.enter.exact.prevent="enviar"
                    />
                    <button
                        v-if="vozDisponible"
                        type="button"
                        class="absolute right-3 top-3 rounded-full p-2 transition"
                        :class="escuchando ? 'animate-pulse bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                        :title="escuchando ? 'Detener dictado' : 'Dictar por voz'"
                        @click="toggleVoz"
                    >
                        <MicOff v-if="escuchando" class="h-4 w-4" />
                        <Mic v-else class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p v-if="escuchando" class="text-xs font-medium text-red-600">
                        🎙 Escuchando… hablá ahora.
                    </p>
                    <p v-else-if="vozError" class="text-xs font-medium text-amber-600">
                        {{ vozError }}
                    </p>
                    <p v-else-if="!vozDisponible" class="text-xs text-gray-400">
                        El dictado por voz no está disponible en este navegador (usá Chrome/Edge).
                    </p>
                    <span v-else></span>

                    <button
                        type="button"
                        :disabled="procesando || !consulta.trim()"
                        class="inline-flex items-center gap-2 rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-50"
                        style="background-color: #c70e0a;"
                        @click="enviar"
                    >
                        <Send class="h-4 w-4" /> {{ procesando ? 'Consultando…' : 'Consultar' }}
                    </button>
                </div>
            </div>

            <!-- Ejemplos -->
            <div class="mt-4 border-t border-gray-100 pt-4">
                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Ejemplos</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="ej in ejemplos"
                        :key="ej"
                        type="button"
                        class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs text-gray-600 transition hover:border-[#073b75]/40 hover:bg-[#073b75]/5"
                        @click="usarEjemplo(ej)"
                    >
                        {{ ej }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Resultado -->
        <template v-if="resultado">
            <!-- Interpretación de la IA -->
            <div class="rounded-xl border border-[#073b75]/20 bg-[#073b75]/5 p-4">
                <div class="flex items-start gap-3">
                    <Sparkles class="mt-0.5 h-5 w-5 shrink-0 text-[#073b75]" />
                    <div class="text-sm">
                        <p class="text-gray-800">{{ resultado.explicacion }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            Reporte: <strong>{{ resultado.reporte.label }}</strong> · {{ resultado.total }} registro(s)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <TableIcon class="h-4 w-4 text-[#073b75]" /> Resultados
                    </h3>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="resultado.total > 0"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 transition hover:bg-green-100"
                            title="Exportar a Excel (CSV)"
                            @click="exportar('csv')"
                        >
                            <FileSpreadsheet class="h-3.5 w-3.5" /> Excel
                        </button>
                        <button
                            v-if="resultado.total > 0"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 transition hover:bg-red-100"
                            title="Exportar a PDF"
                            @click="exportar('pdf')"
                        >
                            <FileText class="h-3.5 w-3.5" /> PDF
                        </button>
                        <span class="text-xs text-gray-500">{{ resultado.total }} registro(s)</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wider text-gray-500">
                                <th v-for="c in resultado.columnas" :key="c.key" class="px-4 py-3 font-semibold">
                                    {{ c.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="resultado.rows.length === 0">
                                <td :colspan="resultado.columnas.length" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No se encontraron registros para tu consulta.
                                </td>
                            </tr>
                            <tr
                                v-for="(row, ri) in resultado.rows"
                                :key="ri"
                                class="border-b border-gray-50 last:border-0 hover:bg-gray-50"
                            >
                                <td v-for="c in resultado.columnas" :key="c.key" class="px-4 py-2.5 text-gray-700">
                                    <span
                                        v-if="c.type === 'badge' && row[c.key]"
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="badgeClase(row[c.key])"
                                    >
                                        {{ mostrar(row[c.key]) }}
                                    </span>
                                    <span v-else>{{ mostrar(row[c.key]) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
