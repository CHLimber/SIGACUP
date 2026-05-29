<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard } from '@/routes';

interface Gestion {
    id: number;
    anio: number;
    semestre: number;
    estado: string;
}

const props = defineProps<{
    gestion: Gestion;
    parametros: Record<string, string>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',    href: dashboard() },
            { title: 'Gestiones', href: '/administracion/gestiones' },
            { title: 'Parámetros', href: '/administracion/gestiones' },
        ],
    },
});

const form = useForm({
    capacidad_max_grupo:    Number(props.parametros.capacidad_max_grupo    ?? 70),
    peso_examen_1:          Number(props.parametros.peso_examen_1          ?? 30),
    peso_examen_2:          Number(props.parametros.peso_examen_2          ?? 30),
    peso_examen_3:          Number(props.parametros.peso_examen_3          ?? 40),
    nota_minima_aprobacion: Number(props.parametros.nota_minima_aprobacion ?? 60),
});

const sumaPesos = computed(() =>
    Number(form.peso_examen_1) + Number(form.peso_examen_2) + Number(form.peso_examen_3),
);

const sumaOk = computed(() => sumaPesos.value === 100);

function submit() {
    form.put(`/administracion/gestiones/${props.gestion.id}/parametros`);
}
</script>

<template>
    <Head :title="`Parámetros — Gestión ${gestion.anio}-${gestion.semestre}`" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Parámetros de Gestión</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Gestión
                <strong>{{ gestion.anio }} — {{ gestion.semestre === 1 ? '1er' : '2do' }} Semestre</strong>
            </p>
        </div>

        <form class="max-w-2xl" @submit.prevent="submit">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <!-- ── Grupos ── -->
                <div class="border-b border-gray-100 px-6 py-4" style="background-color: #060041;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Grupos</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="cap" class="text-sm font-medium text-gray-700">
                            Capacidad máxima de alumnos por grupo
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                id="cap"
                                v-model="form.capacidad_max_grupo"
                                type="number"
                                min="1"
                                max="500"
                                class="w-32 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                :class="{ 'border-red-400': form.errors.capacidad_max_grupo }"
                            />
                            <span class="text-sm text-gray-500">alumnos</span>
                        </div>
                        <p v-if="form.errors.capacidad_max_grupo" class="text-xs text-red-600">
                            {{ form.errors.capacidad_max_grupo }}
                        </p>
                    </div>
                </div>

                <!-- ── Ponderación de exámenes ── -->
                <div class="border-y border-gray-100 px-6 py-4" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">
                        Ponderación de Exámenes
                    </h2>
                </div>
                <div class="p-6">
                    <p class="mb-4 text-xs text-gray-500">
                        Los tres pesos deben sumar exactamente <strong>100 %</strong>.
                    </p>

                    <div class="grid grid-cols-3 gap-4">
                        <!-- Examen 1 -->
                        <div class="flex flex-col gap-1.5">
                            <label for="p1" class="text-sm font-medium text-gray-700">
                                1er Examen <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-1">
                                <input
                                    id="p1"
                                    v-model="form.peso_examen_1"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-20 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                    :class="{ 'border-red-400': form.errors.peso_examen_1 }"
                                />
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <p v-if="form.errors.peso_examen_1" class="text-xs text-red-600">
                                {{ form.errors.peso_examen_1 }}
                            </p>
                        </div>

                        <!-- Examen 2 -->
                        <div class="flex flex-col gap-1.5">
                            <label for="p2" class="text-sm font-medium text-gray-700">
                                2do Examen <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-1">
                                <input
                                    id="p2"
                                    v-model="form.peso_examen_2"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-20 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                    :class="{ 'border-red-400': form.errors.peso_examen_2 }"
                                />
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <p v-if="form.errors.peso_examen_2" class="text-xs text-red-600">
                                {{ form.errors.peso_examen_2 }}
                            </p>
                        </div>

                        <!-- Examen final -->
                        <div class="flex flex-col gap-1.5">
                            <label for="p3" class="text-sm font-medium text-gray-700">
                                Examen Final <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-1">
                                <input
                                    id="p3"
                                    v-model="form.peso_examen_3"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-20 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                    :class="{ 'border-red-400': form.errors.peso_examen_3 }"
                                />
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                            <p v-if="form.errors.peso_examen_3" class="text-xs text-red-600">
                                {{ form.errors.peso_examen_3 }}
                            </p>
                        </div>
                    </div>

                    <!-- Indicador de suma en vivo -->
                    <div
                        class="mt-4 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium"
                        :class="sumaOk
                            ? 'bg-green-50 text-green-700 border border-green-200'
                            : 'bg-red-50 text-red-700 border border-red-200'"
                    >
                        <span class="text-base">{{ sumaOk ? '✓' : '✗' }}</span>
                        Suma actual: <strong>{{ sumaPesos }} %</strong>
                        <span v-if="!sumaOk" class="text-xs">(debe ser 100 %)</span>
                    </div>
                </div>

                <!-- ── Aprobación ── -->
                <div class="border-y border-gray-100 px-6 py-4" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Aprobación</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="nota" class="text-sm font-medium text-gray-700">
                            Nota mínima para aprobar el CUP
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                id="nota"
                                v-model="form.nota_minima_aprobacion"
                                type="number"
                                min="1"
                                max="100"
                                class="w-24 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                :class="{ 'border-red-400': form.errors.nota_minima_aprobacion }"
                            />
                            <span class="text-sm text-gray-500">puntos (sobre 100)</span>
                        </div>
                        <p v-if="form.errors.nota_minima_aprobacion" class="text-xs text-red-600">
                            {{ form.errors.nota_minima_aprobacion }}
                        </p>
                    </div>
                </div>

                <!-- ── Botones ── -->
                <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <Link
                        href="/administracion/gestiones"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !sumaOk"
                        class="rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-50"
                        style="background-color: #c70e0a;"
                    >
                        {{ form.processing ? 'Guardando…' : 'Guardar Parámetros' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
