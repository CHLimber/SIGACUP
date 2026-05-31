<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dashboard } from '@/routes';

interface Docente {
    user_id: number;
    username: string;
    email: string;
    ci: string | null;
    apellido: string | null;
    nombres: string | null;
    telefono: string | null;
    titulo: string | null;
    experiencia_anios: number | null;
    tiene_diplomado: boolean;
    tiene_maestria: boolean;
    datos_completos: boolean;
    candidato_id: number | null;
}

interface Totales {
    total: number;
    completos: number;
    pendientes: number;
}

const props = defineProps<{
    docentes: Docente[];
    totales:  Totales;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio',   href: dashboard() },
            { title: 'Docentes', href: '/administracion/docentes' },
        ],
    },
});

const filtro       = ref<'todos' | 'completos' | 'pendientes'>('todos');
const busqueda     = ref('');

const docentesFiltrados = computed(() => {
    let lista = props.docentes;

    if (filtro.value === 'completos') {
        lista = lista.filter(d => d.datos_completos);
    } else if (filtro.value === 'pendientes') {
        lista = lista.filter(d => !d.datos_completos);
    }

    if (busqueda.value.trim()) {
        const q = busqueda.value.toLowerCase().trim();
        lista = lista.filter(d =>
            (d.ci || '').toLowerCase().includes(q)
            || (d.apellido || '').toLowerCase().includes(q)
            || (d.nombres || '').toLowerCase().includes(q)
            || (d.email || '').toLowerCase().includes(q),
        );
    }

    return lista;
});
</script>

<template>
    <Head title="Gestionar Docentes" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gestionar Docentes</h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Cuerpo docente registrado en el sistema. Edita sus datos profesionales y revisa su documentación.
            </p>
        </div>

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total docentes</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ totales.total }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos completos</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ totales.completos }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Datos pendientes</p>
                <p class="mt-1 text-3xl font-bold text-amber-600">{{ totales.pendientes }}</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-1 border-b border-gray-200">
                <button
                    v-for="opt in [
                        { v: 'todos', l: 'Todos' },
                        { v: 'completos', l: 'Con datos completos' },
                        { v: 'pendientes', l: 'Datos pendientes' },
                    ]"
                    :key="opt.v"
                    type="button"
                    :class="[
                        'border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                        filtro === opt.v
                            ? 'border-[#c70e0a] text-[#c70e0a]'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]"
                    @click="filtro = opt.v as 'todos' | 'completos' | 'pendientes'"
                >
                    {{ opt.l }}
                </button>
            </div>
            <input
                v-model="busqueda"
                placeholder="Buscar por CI, nombre o email…"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-[#c70e0a] focus:outline-none focus:ring-1 focus:ring-[#c70e0a]"
            />
        </div>

        <!-- Tabla -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #7b0000;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">CI</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Nombre</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Usuario</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Título</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Experiencia</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estudios</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="docentesFiltrados.length === 0">
                        <td colspan="7" class="px-5 py-14 text-center text-gray-400">
                            No hay docentes que coincidan con el filtro.
                        </td>
                    </tr>
                    <tr
                        v-for="d in docentesFiltrados"
                        :key="d.user_id"
                        class="transition-colors hover:bg-gray-50"
                    >
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ d.ci || '—' }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ d.apellido }} {{ d.nombres }}</p>
                            <p class="text-xs text-gray-500">{{ d.email }}</p>
                        </td>
                        <td class="px-5 py-4 font-mono text-xs text-gray-700">{{ d.username }}</td>
                        <td class="px-5 py-4 text-xs text-gray-700">
                            <span v-if="d.titulo">{{ d.titulo }}</span>
                            <span v-else class="italic text-amber-600">Sin registrar</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-700">
                            <span v-if="d.experiencia_anios !== null">{{ d.experiencia_anios }} año(s)</span>
                            <span v-else class="italic text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <span v-if="d.tiene_maestria" class="mr-1 inline-flex rounded-full bg-purple-100 px-2 py-0.5 font-semibold text-purple-700">
                                Maestría
                            </span>
                            <span v-if="d.tiene_diplomado" class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 font-semibold text-blue-700">
                                Diplomado
                            </span>
                            <span v-if="!d.tiene_diplomado && !d.tiene_maestria" class="text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <Link
                                :href="`/administracion/docentes/${d.user_id}/edit`"
                                class="rounded-md bg-[#c70e0a] px-3 py-1.5 text-xs font-semibold text-white transition hover:brightness-110"
                            >
                                {{ d.datos_completos ? 'Editar' : 'Completar datos' }} →
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
