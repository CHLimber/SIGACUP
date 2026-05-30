<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeElements, type StripePaymentElement } from '@stripe/stripe-js';
import { onMounted, ref } from 'vue';

interface CandidatoData {
    nombre_completo: string;
    ci: string;
    email: string;
    carrera: string;
}

interface MatriculaData {
    monto_bs: number;
    monto_usd: number;
    descripcion: string;
}

const props = defineProps<{
    token: string;
    candidato: CandidatoData;
    matricula: MatriculaData;
    stripeKey: string;
}>();

let stripe: Stripe | null = null;
let elements: StripeElements | null = null;
let paymentElement: StripePaymentElement | null = null;

const cargando = ref(true);
const procesando = ref(false);
const errorMsg = ref<string | null>(null);

onMounted(async () => {
    try {
        stripe = await loadStripe(props.stripeKey);
        if (! stripe) {
            errorMsg.value = 'No se pudo cargar el procesador de pagos.';
            return;
        }

        const resp = await fetch(`/matricula/${props.token}/payment-intent`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('XSRF-TOKEN=')[1]?.split(';')[0] ?? ''),
            },
            credentials: 'same-origin',
        });

        if (! resp.ok) {
            const body = await resp.json().catch(() => ({}));
            errorMsg.value = body.error || 'No se pudo inicializar el pago.';
            return;
        }

        const { client_secret } = await resp.json();

        elements = stripe.elements({
            clientSecret: client_secret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#073b75',
                    colorBackground: '#ffffff',
                    colorText: '#1f2937',
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    borderRadius: '6px',
                },
            },
        });

        paymentElement = elements.create('payment', {
            layout: 'tabs',
        });
        paymentElement.mount('#stripe-payment-element');
    } catch (e) {
        console.error(e);
        errorMsg.value = 'Error al iniciar el formulario de pago.';
    } finally {
        cargando.value = false;
    }
});

async function pagar() {
    if (! stripe || ! elements) return;
    procesando.value = true;
    errorMsg.value = null;

    const { error, paymentIntent } = await stripe.confirmPayment({
        elements,
        redirect: 'if_required',
    });

    if (error) {
        errorMsg.value = error.message ?? 'No se pudo completar el pago.';
        procesando.value = false;
        return;
    }

    if (paymentIntent && paymentIntent.status === 'succeeded') {
        router.post(`/matricula/${props.token}/confirmar`, {
            payment_intent_id: paymentIntent.id,
        }, {
            onError: () => {
                errorMsg.value = 'El pago se realizó pero falló la confirmación. Contacta a coordinación.';
                procesando.value = false;
            },
        });
    } else {
        errorMsg.value = 'El pago no se completó.';
        procesando.value = false;
    }
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
                        Carrera: <strong>{{ candidato.carrera }}</strong>
                    </p>
                </div>

                <!-- Detalle matrícula -->
                <div class="border-b border-gray-100 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Concepto</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ matricula.descripcion }}</p>

                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600">Monto a pagar</p>
                            <p class="text-2xl font-bold text-[#073b75]">{{ fmtBs(matricula.monto_bs) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Formulario Stripe Elements -->
                <div class="px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Datos de la tarjeta</p>

                    <div v-if="cargando" class="mt-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        Cargando formulario de pago…
                    </div>

                    <div v-show="!cargando" id="stripe-payment-element" class="mt-4"></div>

                    <div v-if="errorMsg" class="mt-3 rounded-md border-l-4 border-red-500 bg-red-50 px-3 py-2 text-xs text-red-800">
                        {{ errorMsg }}
                    </div>

                    <div class="mt-4 rounded-md border-l-4 border-blue-400 bg-blue-50 px-3 py-2 text-xs text-blue-900">
                        <strong>Modo prueba:</strong> usa la tarjeta <code class="font-mono">4242 4242 4242 4242</code>,
                        cualquier fecha futura, CVC de 3 dígitos y código postal cualquiera.
                    </div>
                </div>

                <!-- Acción -->
                <div class="bg-gray-50 px-6 py-5">
                    <button
                        type="button"
                        class="w-full rounded-md bg-[#c70e0a] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#a00b08] disabled:opacity-60"
                        :disabled="cargando || procesando"
                        @click="pagar"
                    >
                        <span v-if="procesando">Procesando pago…</span>
                        <span v-else>Pagar</span>
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
