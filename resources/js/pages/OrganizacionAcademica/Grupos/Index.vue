<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';
import { Button } from '@/components/ui/button';
import { AlertTriangle } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Gestion {
    id: number;
    anio: number;
    semestre: number;
    estado: string;
}

interface GrupoResumen {
    nombre: string;
    estudiantes: number;
}

const props = defineProps<{
    gestion:      Gestion;
    totalPagados: number;
    capacidadMax: number;
    nCalculado:   number;
    grupos:       GrupoResumen[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',               href: dashboard() },
            { title: 'Gestiones Académicas', href: '/administracion/gestiones' },
            { title: 'Grupos',               href: '#' },
        ],
    },
});

const yaExisten    = computed(() => props.grupos.length > 0);
const confirmar    = ref(false);
const procesando   = ref(false);

function solicitarGenerar() {
    if (yaExisten.value) {
        confirmar.value = true;
    } else {
        ejecutarGenerar();
    }
}

function ejecutarGenerar() {
    confirmar.value = false;
    procesando.value = true;
    router.post(
        `/administracion/grupos/${props.gestion.id}/generar`,
        {},
        { onFinish: () => { procesando.value = false; } },
    );
}

const labelSemestre = computed(() =>
    props.gestion.semestre === 1 ? '1er Semestre' : '2do Semestre',
);

const puedeGenerar = computed(() =>
    ['cursado', 'admision', 'cerrada'].includes(props.gestion.estado),
);
</script>

<template>
    <Head :title="`Grupos — ${gestion.anio} ${labelSemestre}`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Grupos — {{ gestion.anio }} {{ labelSemestre }}
            </h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Distribución automática de postulantes en grupos de cursado.
            </p>
        </div>

        <!-- Tarjetas de resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Postulantes con pago</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ totalPagados }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Capacidad por grupo</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ capacidadMax }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Grupos a generar</p>
                <p class="mt-1 text-3xl font-bold" :class="nCalculado > 0 ? 'text-[#073b75]' : 'text-gray-400'">
                    {{ nCalculado > 0 ? nCalculado : '—' }}
                </p>
                <p v-if="nCalculado > 0" class="mt-0.5 text-xs text-gray-400">
                    ⌈{{ totalPagados }} / {{ capacidadMax }}⌉ = {{ nCalculado }}
                </p>
            </div>
        </div>

        <!-- Aviso si la gestión no está en cursado -->
        <div
            v-if="!puedeGenerar"
            class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        >
            <AlertTriangle class="mr-1.5 inline h-4 w-4 align-text-bottom" /> La gestión debe estar en estado <strong>Cursado</strong> para generar grupos.
            Avanza la gestión desde la pantalla de Gestiones Académicas.
        </div>

        <!-- Tabla de grupos existentes -->
        <div v-if="yaExisten" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #060041;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Grupo</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Postulantes asignados</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estado</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="g in grupos"
                        :key="g.nombre"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-4">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#073b75] font-bold text-white text-sm">
                                {{ g.nombre }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-2 flex-1 max-w-[160px] overflow-hidden rounded-full bg-gray-100">
                                    <div
                                        class="h-2 rounded-full bg-[#073b75]"
                                        :style="{ width: `${Math.min(100, Math.round((g.estudiantes / capacidadMax) * 100))}%` }"
                                    />
                                </div>
                                <span class="text-gray-700 font-medium">{{ g.estudiantes }}</span>
                                <span class="text-gray-400 text-xs">/ {{ capacidadMax }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-700">
                                Sin horario asignado
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <Link
                                :href="`/administracion/grupos/${gestion.id}/${g.nombre}/configurar`"
                                class="rounded-md bg-[#073b75] px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110"
                            >
                                Configurar →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado vacío -->
        <div
            v-else-if="puedeGenerar"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center"
        >
            <p class="text-gray-500">Aún no se han generado grupos para esta gestión.</p>
            <p class="text-sm text-gray-400">
                Se crearán <strong>{{ nCalculado }}</strong> grupo(s) con un máximo de
                <strong>{{ capacidadMax }}</strong> estudiantes cada uno.
            </p>
        </div>

        <!-- Botón de acción -->
        <div v-if="puedeGenerar" class="flex justify-end">
            <Button
                v-if="!yaExisten"
                :disabled="procesando || nCalculado === 0"
                style="background-color: #073b75;"
                class="text-white hover:brightness-110"
                @click="solicitarGenerar"
            >
                {{ procesando ? 'Generando…' : 'Generar grupos automáticamente' }}
            </Button>
            <Button
                v-else
                variant="outline"
                class="border-red-300 text-red-600 hover:bg-red-50"
                :disabled="procesando || nCalculado === 0"
                @click="solicitarGenerar"
            >
                {{ procesando ? 'Regenerando…' : 'Regenerar grupos' }}
            </Button>
        </div>
    </div>

    <!-- Diálogo de confirmación para regenerar -->
    <Dialog :open="confirmar" @update:open="v => !v && (confirmar = false)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>¿Regenerar grupos?</DialogTitle>
                <DialogDescription>
                    Esto eliminará la distribución actual de <strong>{{ grupos.length }}</strong> grupo(s)
                    y volverá a generarlos desde cero. Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmar = false">Cancelar</Button>
                <Button variant="destructive" @click="ejecutarGenerar">Sí, regenerar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
