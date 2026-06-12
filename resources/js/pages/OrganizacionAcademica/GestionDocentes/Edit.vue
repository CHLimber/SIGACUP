<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

interface Docente {
    user_id: number;
    username: string;
    email: string;
    ci: string | null;
    apellido: string | null;
    nombres: string | null;
    fecha_nacimiento: string | null;
    sexo: string | null;
    telefono: string | null;
    direccion: string | null;
    titulo: string;
    experiencia_anios: number;
    tiene_diplomado: boolean;
    tiene_maestria: boolean;
    activo: boolean;
    materias: string[];
}

interface MateriaOpt {
    codigo: string;
    nombre: string;
}

interface Documento {
    codigo: string;
    nombre: string;
    descripcion: string;
    obligatorio: boolean;
    archivo: {
        id: number;
        nombre_original: string;
        estado: string;
        subido_at: string | null;
    } | null;
}

const props = defineProps<{
    docente:             Docente;
    tieneDatosDocente:   boolean;
    materiasDisponibles: MateriaOpt[];
    documentos:          Documento[];
    candidatoId:         number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',   href: dashboard() },
            { title: 'Docentes', href: '/administracion/docentes' },
            { title: 'Editar',   href: '#' },
        ],
    },
});

const form = ref({
    telefono:  props.docente.telefono || '',
    direccion: props.docente.direccion || '',
    materias:  [...props.docente.materias],
    activo:    props.docente.activo,
});

function toggleMateria(codigo: string) {
    if (form.value.materias.includes(codigo)) {
        form.value.materias = form.value.materias.filter((c) => c !== codigo);
    } else {
        form.value.materias = [...form.value.materias, codigo];
    }
}

const procesando = ref(false);
const errors     = ref<Record<string, string>>({});

function guardar() {
    procesando.value = true;
    errors.value = {};
    router.patch(
        `/administracion/docentes/${props.docente.user_id}`,
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

function descargar(id: number) {
    window.location.href = `/administracion/docentes/documento/descargar?id=${id}`;
}

function fmtFecha(f: string | null): string {
    if (!f) {
return '—';
}

    return new Date(f).toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function estadoArchivo(e: string): { label: string; clases: string } {
    if (e === 'aprobado')             {
return { label: 'Aprobado',   clases: 'bg-green-100 text-green-700' };
}

    if (e === 'rechazado')            {
return { label: 'Rechazado',  clases: 'bg-red-100 text-red-700' };
}

    if (e === 'pendiente_revision')   {
return { label: 'Pendiente',  clases: 'bg-yellow-100 text-yellow-700' };
}

    return { label: e, clases: 'bg-gray-100 text-gray-700' };
}
</script>

<template>
    <Head :title="`Docente — ${docente.apellido} ${docente.nombres}`" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Encabezado -->
        <div class="flex items-start justify-between">
            <div>
                <Link href="/administracion/docentes" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Volver a docentes
                </Link>
                <h1 class="mt-1 flex items-center gap-2 text-2xl font-bold text-gray-900">
                    {{ docente.apellido }} {{ docente.nombres }}
                    <span
                        v-if="tieneDatosDocente"
                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="docente.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >
                        {{ docente.activo ? 'Activo' : 'Deshabilitado' }}
                    </span>
                </h1>
                <p class="mt-0.5 text-sm text-gray-500 font-mono">
                    {{ docente.ci }} · {{ docente.email }} · @{{ docente.username }}
                </p>
            </div>
        </div>

        <!-- Datos personales (solo lectura) -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Datos personales</h2>
            <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Fecha de nacimiento</p>
                    <p class="mt-0.5 text-gray-900">{{ fmtFecha(docente.fecha_nacimiento) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Sexo</p>
                    <p class="mt-0.5 text-gray-900 capitalize">{{ docente.sexo || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Email</p>
                    <p class="mt-0.5 text-gray-900">{{ docente.email }}</p>
                </div>
            </div>
        </div>

        <!-- Datos profesionales (solo lectura) -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Datos profesionales</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Estos datos los completó el docente durante su postulación.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Título profesional</p>
                    <p class="mt-0.5 text-gray-900">
                        <span v-if="docente.titulo">{{ docente.titulo }}</span>
                        <span v-else class="italic text-amber-600">Sin registrar</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500">Años de experiencia</p>
                    <p class="mt-0.5 text-gray-900">
                        <span v-if="docente.experiencia_anios !== null">{{ docente.experiencia_anios }} año(s)</span>
                        <span v-else class="italic text-gray-400">—</span>
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wider text-gray-500">Formación complementaria</p>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <span v-if="docente.tiene_maestria" class="inline-flex rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-700">
                            Maestría
                        </span>
                        <span v-if="docente.tiene_diplomado" class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                            Diplomado
                        </span>
                        <span v-if="!docente.tiene_diplomado && !docente.tiene_maestria" class="text-xs text-gray-400">
                            Ninguno declarado
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Materias y disponibilidad -->
        <div v-if="tieneDatosDocente" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Materias y disponibilidad</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Materias que el docente está habilitado para dictar. Solo recibirá grupos de estas materias al asignar docentes.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label
                    v-for="m in materiasDisponibles"
                    :key="m.codigo"
                    class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-gray-700 transition"
                    :class="form.materias.includes(m.codigo) ? 'border-[#c70e0a] bg-red-50/40' : 'border-gray-200'"
                >
                    <input
                        type="checkbox"
                        :checked="form.materias.includes(m.codigo)"
                        class="h-4 w-4 rounded border-gray-300 text-[#c70e0a] focus:ring-[#c70e0a]"
                        @change="toggleMateria(m.codigo)"
                    />
                    <span>{{ m.nombre }} <span class="font-mono text-xs text-gray-400">({{ m.codigo }})</span></span>
                </label>
            </div>
            <p v-if="errors.materias" class="mt-1 text-xs text-red-600">{{ errors.materias }}</p>

            <label class="mt-4 flex items-center gap-2 text-sm text-gray-700">
                <input
                    v-model="form.activo"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-[#c70e0a] focus:ring-[#c70e0a]"
                />
                Docente activo (disponible para la asignación de grupos)
            </label>

            <div class="mt-4 flex justify-end">
                <Button
                    style="background-color: #c70e0a;"
                    class="text-white hover:brightness-110"
                    :disabled="procesando"
                    @click="guardar"
                >
                    {{ procesando ? 'Guardando…' : 'Guardar cambios' }}
                </Button>
            </div>
        </div>

        <!-- Formulario editable: solo contacto -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Datos de contacto</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Actualiza el teléfono y dirección del docente.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Teléfono</label>
                    <input
                        v-model="form.telefono"
                        type="text"
                        maxlength="30"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#c70e0a] focus:outline-none focus:ring-1 focus:ring-[#c70e0a]"
                    />
                    <p v-if="errors.telefono" class="mt-1 text-xs text-red-600">{{ errors.telefono }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700">Dirección</label>
                    <input
                        v-model="form.direccion"
                        type="text"
                        maxlength="500"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-[#c70e0a] focus:outline-none focus:ring-1 focus:ring-[#c70e0a]"
                    />
                    <p v-if="errors.direccion" class="mt-1 text-xs text-red-600">{{ errors.direccion }}</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <Button
                    style="background-color: #c70e0a;"
                    class="text-white hover:brightness-110"
                    :disabled="procesando"
                    @click="guardar"
                >
                    {{ procesando ? 'Guardando…' : 'Guardar contacto' }}
                </Button>
            </div>
        </div>

        <!-- Documentos -->
        <div v-if="documentos.length > 0" class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Documentos del candidato</h2>
                <p class="mt-0.5 text-sm text-gray-500">
                    Documentación cargada durante el proceso de admisión.
                </p>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Documento</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Archivo</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">Estado</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="d in documentos" :key="d.codigo">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-gray-900">
                                {{ d.nombre }}
                                <span v-if="d.obligatorio" class="ml-1 text-xs text-red-600">*</span>
                            </p>
                            <p class="text-xs text-gray-500">{{ d.descripcion }}</p>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-700">
                            <span v-if="d.archivo">{{ d.archivo.nombre_original }}</span>
                            <span v-else class="italic text-gray-400">No cargado</span>
                        </td>
                        <td class="px-5 py-3">
                            <span
                                v-if="d.archivo"
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="estadoArchivo(d.archivo.estado).clases"
                            >
                                {{ estadoArchivo(d.archivo.estado).label }}
                            </span>
                            <span v-else class="text-xs text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <button
                                v-if="d.archivo"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="descargar(d.archivo.id)"
                            >
                                Descargar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
