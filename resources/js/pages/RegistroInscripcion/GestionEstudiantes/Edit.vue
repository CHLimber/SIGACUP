<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type EstadoEstudiante = 'aprobado_pendiente_pago' | 'pagado';

interface Estudiante {
    id: number;
    ci: string | null;
    apellido: string | null;
    nombres: string | null;
    fecha_nacimiento: string | null;
    sexo: string | null;
    email: string;
    telefono: string;
    direccion: string;
    estado: EstadoEstudiante;
    gestion_label: string | null;
    carrera1: string | null;
    carrera2: string | null;
    carrera_asignada: string | null;
    anio_egreso: number | null;
    unidad_educativa: string | null;
    tipo_colegio: string | null;
}

const props = defineProps<{
    estudiante: Estudiante;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',      href: dashboard() },
            { title: 'Estudiantes', href: '/administracion/estudiantes' },
            { title: 'Editar',      href: '#' },
        ],
    },
});

const form = ref({
    email:     props.estudiante.email,
    telefono:  props.estudiante.telefono,
    direccion: props.estudiante.direccion,
});

const procesando = ref(false);
const errors     = ref<Record<string, string>>({});

const estadoConfig: Record<EstadoEstudiante, { label: string; clases: string }> = {
    aprobado_pendiente_pago: { label: 'Aprobado · Pendiente de pago', clases: 'bg-yellow-100 text-yellow-700' },
    pagado:                  { label: 'Pagado',                       clases: 'bg-green-100 text-green-700' },
};

function guardar() {
    procesando.value = true;
    errors.value = {};
    router.patch(
        `/administracion/estudiantes/${props.estudiante.id}`,
        form.value,
        {
            onError: (errs) => {
 errors.value = errs as Record<string, string>; 
},
            onFinish: () => {
 procesando.value = false; 
},
        },
    );
}

function fmtFecha(f: string | null): string {
    if (!f) {
return '—';
}

    return new Date(f).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
</script>

<template>
    <Head :title="`Estudiante — ${estudiante.apellido} ${estudiante.nombres}`" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <Link href="/administracion/estudiantes" class="text-sm text-gray-500 hover:text-gray-700">
                ← Volver a estudiantes
            </Link>
            <div class="mt-1 flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ estudiante.apellido }} {{ estudiante.nombres }}
                </h1>
                <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="estadoConfig[estudiante.estado].clases"
                >
                    {{ estadoConfig[estudiante.estado].label }}
                </span>
            </div>
            <p class="mt-0.5 text-sm text-gray-500 font-mono">{{ estudiante.ci || '—' }}</p>
        </div>

        <!-- Datos personales -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Datos personales</h2>
            <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Fecha de nacimiento</p>
                    <p class="mt-0.5 text-gray-900">{{ fmtFecha(estudiante.fecha_nacimiento) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Sexo</p>
                    <p class="mt-0.5 text-gray-900 capitalize">{{ estudiante.sexo || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Gestión</p>
                    <p class="mt-0.5 text-gray-900">{{ estudiante.gestion_label || '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Datos académicos -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Datos académicos</h2>
            <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Primera opción</p>
                    <p class="mt-0.5 text-gray-900">{{ estudiante.carrera1 || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Segunda opción</p>
                    <p class="mt-0.5 text-gray-900">{{ estudiante.carrera2 || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Asignada</p>
                    <p class="mt-0.5 font-semibold" :class="estudiante.carrera_asignada ? 'text-[#073b75]' : 'text-gray-400 italic'">
                        {{ estudiante.carrera_asignada || 'Sin asignar' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Unidad educativa</p>
                    <p class="mt-0.5 text-gray-900">{{ estudiante.unidad_educativa || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Año de egreso</p>
                    <p class="mt-0.5 text-gray-900">{{ estudiante.anio_egreso || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Tipo de colegio</p>
                    <p class="mt-0.5 text-gray-900 capitalize">{{ estudiante.tipo_colegio || '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Formulario editable -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Datos de contacto</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Actualiza la información de contacto del estudiante.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        maxlength="255"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                    />
                    <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Teléfono</label>
                    <input
                        v-model="form.telefono"
                        type="text"
                        maxlength="30"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                    />
                    <p v-if="errors.telefono" class="mt-1 text-xs text-red-600">{{ errors.telefono }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Dirección</label>
                    <input
                        v-model="form.direccion"
                        type="text"
                        maxlength="500"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#073b75] focus:outline-none focus:ring-1 focus:ring-[#073b75]"
                    />
                    <p v-if="errors.direccion" class="mt-1 text-xs text-red-600">{{ errors.direccion }}</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <Button
                    style="background-color: #073b75;"
                    class="text-white hover:brightness-110"
                    :disabled="procesando"
                    @click="guardar"
                >
                    {{ procesando ? 'Guardando…' : 'Guardar cambios' }}
                </Button>
            </div>
        </div>
    </div>
</template>
