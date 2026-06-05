<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';

interface Carrera {
    id: number;
    nombre: string;
}

const props = defineProps<{
    carreras: Carrera[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Gestiones', href: '/administracion/gestiones' },
            { title: 'Nueva Gestión', href: '/administracion/gestiones/create' },
        ],
    },
});

const form = useForm({
    anio:                     new Date().getFullYear(),
    semestre:                 1,
    fecha_inicio_inscripcion: '',
    fecha_fin_inscripcion:    '',
    fecha_inicio_cursado:     '',
    fecha_fin_cursado:        '',
    monto_matricula_bs:       800,
    capacidad_max_grupo:      70,
    peso_examen_1:            30,
    peso_examen_2:            30,
    peso_examen_3:            40,
    nota_minima_aprobacion:   60,
    cupos:                    Object.fromEntries(props.carreras.map((c) => [c.id, 30])) as Record<number, number>,
});

const sumaPesos = computed(() =>
    Number(form.peso_examen_1) + Number(form.peso_examen_2) + Number(form.peso_examen_3),
);
const sumaOk = computed(() => sumaPesos.value === 100);

function submit() {
    form.post('/administracion/gestiones');
}
</script>

<template>
    <Head title="Nueva Gestión" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Nueva Gestión Académica</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Define el período y configura los parámetros iniciales.
            </p>
        </div>

        <!-- Formulario -->
        <form class="max-w-2xl" @submit.prevent="submit">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <!-- ── Identificación ── -->
                <div class="border-b border-gray-100 px-6 py-4" style="background-color: #060041;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">
                        Identificación del Período
                    </h2>
                </div>
                <div class="grid grid-cols-2 gap-6 p-6">
                    <!-- Año -->
                    <div class="flex flex-col gap-1.5">
                        <label for="anio" class="text-sm font-medium text-gray-700">
                            Año <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="anio"
                            v-model="form.anio"
                            type="number"
                            min="2020"
                            max="2050"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            :class="{ 'border-red-400': form.errors.anio }"
                        />
                        <p v-if="form.errors.anio" class="text-xs text-red-600">{{ form.errors.anio }}</p>
                    </div>

                    <!-- Semestre -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">
                            Semestre <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-4 pt-1">
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.semestre" type="radio" :value="1" class="accent-[#073b75]" />
                                1er Semestre
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.semestre" type="radio" :value="2" class="accent-[#073b75]" />
                                2do Semestre
                            </label>
                        </div>
                        <p v-if="form.errors.semestre" class="text-xs text-red-600">{{ form.errors.semestre }}</p>
                    </div>
                </div>

                <!-- ── Inscripción ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Período de Inscripción</h2>
                </div>
                <div class="grid grid-cols-2 gap-6 p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="fi_insc" class="text-sm font-medium text-gray-700">
                            Fecha de inicio <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="fi_insc"
                            v-model="form.fecha_inicio_inscripcion"
                            type="date"
                            required
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            :class="{ 'border-red-400': form.errors.fecha_inicio_inscripcion }"
                        />
                        <p v-if="form.errors.fecha_inicio_inscripcion" class="text-xs text-red-600">
                            {{ form.errors.fecha_inicio_inscripcion }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="ff_insc" class="text-sm font-medium text-gray-700">
                            Fecha de fin <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="ff_insc"
                            v-model="form.fecha_fin_inscripcion"
                            type="date"
                            required
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            :class="{ 'border-red-400': form.errors.fecha_fin_inscripcion }"
                        />
                        <p v-if="form.errors.fecha_fin_inscripcion" class="text-xs text-red-600">
                            {{ form.errors.fecha_fin_inscripcion }}
                        </p>
                    </div>
                </div>

                <!-- ── Cursado ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Período de Cursado</h2>
                </div>
                <div class="grid grid-cols-2 gap-6 p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="fi_curs" class="text-sm font-medium text-gray-700">
                            Fecha de inicio <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="fi_curs"
                            v-model="form.fecha_inicio_cursado"
                            type="date"
                            required
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            :class="{ 'border-red-400': form.errors.fecha_inicio_cursado }"
                        />
                        <p v-if="form.errors.fecha_inicio_cursado" class="text-xs text-red-600">
                            {{ form.errors.fecha_inicio_cursado }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="ff_curs" class="text-sm font-medium text-gray-700">
                            Fecha de fin <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="ff_curs"
                            v-model="form.fecha_fin_cursado"
                            type="date"
                            required
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            :class="{ 'border-red-400': form.errors.fecha_fin_cursado }"
                        />
                        <p v-if="form.errors.fecha_fin_cursado" class="text-xs text-red-600">
                            {{ form.errors.fecha_fin_cursado }}
                        </p>
                    </div>
                </div>

                <!-- ── Grupos ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Grupos</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="cap" class="text-sm font-medium text-gray-700">
                            Capacidad máxima de alumnos por grupo <span class="text-red-500">*</span>
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

                <!-- ── Matrícula ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Matrícula</h2>
                </div>
                <div class="grid grid-cols-2 gap-6 p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="monto_bs" class="text-sm font-medium text-gray-700">
                            Monto matrícula (Bs) <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                id="monto_bs"
                                v-model="form.monto_matricula_bs"
                                type="number"
                                min="1"
                                step="0.01"
                                class="w-32 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                :class="{ 'border-red-400': form.errors.monto_matricula_bs }"
                            />
                            <span class="text-sm text-gray-500">Bs</span>
                        </div>
                        <p v-if="form.errors.monto_matricula_bs" class="text-xs text-red-600">
                            {{ form.errors.monto_matricula_bs }}
                        </p>
                    </div>
                </div>

                <!-- ── Ponderación de exámenes ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Ponderación de Exámenes</h2>
                </div>
                <div class="p-6">
                    <p class="mb-4 text-xs text-gray-500">
                        Los tres pesos deben sumar exactamente <strong>100 %</strong>.
                    </p>
                    <div class="grid grid-cols-3 gap-4">
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
                    <div
                        class="mt-4 inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium"
                        :class="sumaOk
                            ? 'border-green-200 bg-green-50 text-green-700'
                            : 'border-red-200 bg-red-50 text-red-700'"
                    >
                        <component :is="sumaOk ? Check : X" class="h-4 w-4" />
                        Suma actual: <strong>{{ sumaPesos }} %</strong>
                        <span v-if="!sumaOk" class="text-xs">(debe ser 100 %)</span>
                    </div>
                </div>

                <!-- ── Aprobación ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Aprobación</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col gap-1.5">
                        <label for="nota" class="text-sm font-medium text-gray-700">
                            Nota mínima para aprobar el CUP <span class="text-red-500">*</span>
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

                <!-- ── Cupos por carrera ── -->
                <div class="border-y border-gray-100 px-6 py-3" style="background-color: #073b75;">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Cupos por Carrera</h2>
                </div>
                <div class="p-6">
                    <p class="mb-4 text-xs text-gray-500">
                        Cantidad máxima de estudiantes admitidos por carrera en el proceso de admisión.
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="carrera in carreras" :key="carrera.id" class="flex flex-col gap-1.5">
                            <label :for="`cupo_${carrera.id}`" class="text-sm font-medium text-gray-700">
                                {{ carrera.nombre }}
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    :id="`cupo_${carrera.id}`"
                                    v-model="form.cupos[carrera.id]"
                                    type="number"
                                    min="0"
                                    max="10000"
                                    class="w-28 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                                />
                                <span class="text-sm text-gray-500">cupos</span>
                            </div>
                        </div>
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
                        class="rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60"
                        style="background-color: #c70e0a;"
                    >
                        {{ form.processing ? 'Guardando…' : 'Crear Gestión' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
