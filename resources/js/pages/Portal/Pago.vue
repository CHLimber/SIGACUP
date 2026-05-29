<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface CandidatoData {
    nombre_completo: string;
    ci: string;
    email: string;
    carrera: string;
}

interface MatriculaData {
    monto_bs: number;
    monto_usd: number;
    tasa_cambio: number;
    descripcion: string;
}

const props = defineProps<{
    token: string;
    candidato: CandidatoData;
    matricula: MatriculaData;
}>();

const carreraLabels: Record<string, string> = {
    sistemas:    'Ing. Sistemas',
    informatica: 'Ing. Informática',
    redes:       'Ing. Redes y Telecomunicaciones',
    robotica:    'Ing. Robótica',
};

const form = useForm({});

function pagar() {
    form.post(`/matricula/${props.token}/iniciar`);
}

function fmtUSD(v: number): string {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v);
}

function fmtBs(v: number): string {
    return `Bs ${v.toFixed(2)}`;
}
</script>

<template>
    <Head title="Pago de matrícula — SIGACUP" />

    <div class="min-h-screen bg-gradient-to-br from-[#060041] via-[#073b75] to-[#0a4060] py-12 px-6">
        <div class="mx-auto max-w-2xl">

            <!-- Cabecera -->
            <div class="mb-6 text-center text-white">
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-200">SIGACUP — FICCT UAGRM</p>
                <h1 class="mt-2 text-3xl font-bold">Pago de matrícula CUP</h1>
                <p class="mt-1 text-sm text-blue-200">Estás a un paso de completar tu inscripción.</p>
            </div>

            <!-- Card principal -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">

                <!-- Header verde aprobado -->
                <div class="bg-green-500 px-6 py-4 text-white">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">✓</span>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider opacity-90">Solicitud aprobada</p>
                            <p class="text-xs opacity-80">Completa el pago para recibir tus credenciales de acceso.</p>
                        </div>
                    </div>
                </div>

                <!-- Datos candidato -->
                <div class="border-b border-gray-100 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Candidato</p>
                    <p class="mt-1 text-base font-bold text-gray-900">{{ candidato.nombre_completo }}</p>
                    <p class="text-xs text-gray-500">CI {{ candidato.ci }} · {{ candidato.email }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Carrera: <strong>{{ carreraLabels[candidato.carrera] || candidato.carrera }}</strong>
                    </p>
                </div>

                <!-- Detalle matrícula -->
                <div class="px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Concepto</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ matricula.descripcion }}</p>

                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600">Monto en bolivianos</p>
                            <p class="text-lg font-semibold text-gray-700">{{ fmtBs(matricula.monto_bs) }}</p>
                        </div>
                        <div class="my-3 border-t border-dashed border-gray-200"></div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600">A cobrar (Stripe USD)</p>
                            <p class="text-2xl font-bold text-[#073b75]">{{ fmtUSD(matricula.monto_usd) }}</p>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-400">
                            Tasa de conversión: 1 USD = {{ matricula.tasa_cambio }} Bs
                        </p>
                    </div>

                    <div class="mt-5 rounded-md border-l-4 border-yellow-400 bg-yellow-50 px-3 py-2 text-xs text-yellow-900">
                        <strong>Nota:</strong> serás redirigido a la pasarela segura de Stripe.
                        Una vez confirmado el pago, recibirás tus credenciales por correo electrónico
                        y podrás descargar tu comprobante.
                    </div>
                </div>

                <!-- Acción -->
                <div class="bg-gray-50 px-6 py-5">
                    <button
                        type="button"
                        class="w-full rounded-md bg-[#c70e0a] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#a00b08] disabled:opacity-60"
                        :disabled="form.processing"
                        @click="pagar"
                    >
                        <span v-if="form.processing">Redirigiendo a Stripe…</span>
                        <span v-else>Pagar {{ fmtUSD(matricula.monto_usd) }} con tarjeta</span>
                    </button>
                    <p class="mt-3 text-center text-[11px] text-gray-400">
                        Procesado por Stripe · Tu información de pago nunca pasa por nuestros servidores.
                    </p>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-blue-200/70">
                Link personal y único. Guárdalo: lo necesitas hasta completar el pago.
            </p>
        </div>
    </div>
</template>
