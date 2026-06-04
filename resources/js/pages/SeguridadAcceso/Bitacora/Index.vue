<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight, Search, X } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import { dashboard } from '@/routes';

interface CambioCampo {
    campo: string;
    anterior: string | null;
    nuevo: string | null;
}
interface RegistroBitacora {
    id: number;
    accion: string;
    verbo: string;
    modulo: string | null;
    descripcion: string;
    usuario: string;
    cambios: CambioCampo[];
    ip: string | null;
    fecha: string | null;
}
interface PaginadorLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Paginador<T> {
    data: T[];
    links: PaginadorLink[];
    from: number | null;
    to: number | null;
    total: number;
}
interface UsuarioOpcion {
    id: number;
    name: string;
}
interface AccionOpcion {
    valor: string;
    label: string;
}

const props = defineProps<{
    registros: Paginador<RegistroBitacora>;
    filtros: {
        accion: string | null;
        modulo: string | null;
        usuario_id: number | null;
        desde: string | null;
        hasta: string | null;
        q: string | null;
    };
    opciones: {
        acciones: AccionOpcion[];
        modulos: string[];
        usuarios: UsuarioOpcion[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Bitácora', href: '/administracion/bitacora' },
        ],
    },
});

const filtros = reactive({
    accion: props.filtros.accion ?? '',
    modulo: props.filtros.modulo ?? '',
    usuario_id: props.filtros.usuario_id
        ? String(props.filtros.usuario_id)
        : '',
    desde: props.filtros.desde ?? '',
    hasta: props.filtros.hasta ?? '',
    q: props.filtros.q ?? '',
});

const expandidos = ref<Set<number>>(new Set());

function toggleExpandir(id: number) {
    const set = new Set(expandidos.value);

    if (set.has(id)) {
        set.delete(id);
    } else {
        set.add(id);
    }

    expandidos.value = set;
}

function aplicar() {
    const params: Record<string, string> = {};

    if (filtros.accion) {
        params.accion = filtros.accion;
    }

    if (filtros.modulo) {
        params.modulo = filtros.modulo;
    }

    if (filtros.usuario_id) {
        params.usuario_id = filtros.usuario_id;
    }

    if (filtros.desde) {
        params.desde = filtros.desde;
    }

    if (filtros.hasta) {
        params.hasta = filtros.hasta;
    }

    if (filtros.q) {
        params.q = filtros.q;
    }

    router.get('/administracion/bitacora', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function limpiar() {
    filtros.accion = '';
    filtros.modulo = '';
    filtros.usuario_id = '';
    filtros.desde = '';
    filtros.hasta = '';
    filtros.q = '';
    aplicar();
}

const ACCION_ESTILOS: Record<string, string> = {
    crear: 'bg-green-100 text-green-700',
    actualizar: 'bg-blue-100 text-blue-700',
    eliminar: 'bg-red-100 text-red-700',
    login: 'bg-indigo-100 text-indigo-700',
    logout: 'bg-slate-100 text-slate-600',
};

function estiloAccion(accion: string): string {
    return ACCION_ESTILOS[accion] ?? 'bg-slate-100 text-slate-600';
}

function formatearFecha(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatearValor(valor: string | null): string {
    if (valor === null || valor === '') {
        return '∅';
    }

    return valor;
}
</script>

<template>
    <Head title="Bitácora" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Bitácora del sistema
            </h1>
            <p class="mt-0.5 text-sm text-gray-500">
                Registro de todas las acciones realizadas: creaciones,
                ediciones, eliminaciones y accesos.
            </p>
        </div>

        <!-- Filtros -->
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="flex flex-col gap-1.5 lg:col-span-2">
                    <label
                        class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Buscar</label
                    >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="filtros.q"
                            type="text"
                            placeholder="Buscar en la descripción…"
                            class="w-full rounded-md border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm shadow-sm focus:ring-2 focus:ring-[#073b75] focus:outline-none"
                            @keyup.enter="aplicar"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Acción</label
                    >
                    <select
                        v-model="filtros.accion"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-[#073b75] focus:outline-none"
                    >
                        <option value="">Todas</option>
                        <option
                            v-for="a in opciones.acciones"
                            :key="a.valor"
                            :value="a.valor"
                        >
                            {{ a.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Módulo</label
                    >
                    <select
                        v-model="filtros.modulo"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-[#073b75] focus:outline-none"
                    >
                        <option value="">Todos</option>
                        <option
                            v-for="m in opciones.modulos"
                            :key="m"
                            :value="m"
                        >
                            {{ m }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Usuario</label
                    >
                    <select
                        v-model="filtros.usuario_id"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-[#073b75] focus:outline-none"
                    >
                        <option value="">Todos</option>
                        <option
                            v-for="u in opciones.usuarios"
                            :key="u.id"
                            :value="String(u.id)"
                        >
                            {{ u.name }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Desde</label
                    >
                    <input
                        v-model="filtros.desde"
                        type="date"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-[#073b75] focus:outline-none"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Hasta</label
                    >
                    <input
                        v-model="filtros.hasta"
                        type="date"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-[#073b75] focus:outline-none"
                    />
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="limpiar"
                >
                    <X class="h-4 w-4" /> Limpiar
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110"
                    style="background-color: #c70e0a"
                    @click="aplicar"
                >
                    <Search class="h-4 w-4" /> Filtrar
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #060041">
                        <th class="w-8 px-3 py-3.5"></th>
                        <th
                            class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-white uppercase"
                        >
                            Fecha y hora
                        </th>
                        <th
                            class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-white uppercase"
                        >
                            Usuario
                        </th>
                        <th
                            class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-white uppercase"
                        >
                            Acción
                        </th>
                        <th
                            class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-white uppercase"
                        >
                            Módulo
                        </th>
                        <th
                            class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-white uppercase"
                        >
                            Descripción
                        </th>
                        <th
                            class="px-5 py-3.5 text-left text-xs font-semibold tracking-wider text-white uppercase"
                        >
                            IP
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="registros.data.length === 0">
                        <td
                            colspan="7"
                            class="px-5 py-14 text-center text-gray-400"
                        >
                            No hay registros que coincidan con los filtros
                            aplicados.
                        </td>
                    </tr>
                    <template v-for="r in registros.data" :key="r.id">
                        <tr class="transition-colors hover:bg-gray-50">
                            <td class="px-3 py-4 text-center align-top">
                                <button
                                    v-if="r.cambios.length"
                                    type="button"
                                    class="text-gray-400 transition hover:text-gray-700"
                                    aria-label="Ver cambios"
                                    @click="toggleExpandir(r.id)"
                                >
                                    <ChevronDown
                                        v-if="expandidos.has(r.id)"
                                        class="h-4 w-4"
                                    />
                                    <ChevronRight v-else class="h-4 w-4" />
                                </button>
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-gray-600"
                            >
                                {{ formatearFecha(r.fecha) }}
                            </td>
                            <td class="px-5 py-4 font-medium text-gray-900">
                                {{ r.usuario }}
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="estiloAccion(r.accion)"
                                >
                                    {{ r.verbo }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                {{ r.modulo ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-gray-700">
                                {{ r.descripcion }}
                            </td>
                            <td
                                class="px-5 py-4 text-xs whitespace-nowrap text-gray-400"
                            >
                                {{ r.ip ?? '—' }}
                            </td>
                        </tr>
                        <tr
                            v-if="expandidos.has(r.id) && r.cambios.length"
                            class="bg-gray-50/70"
                        >
                            <td></td>
                            <td colspan="6" class="px-5 pb-4">
                                <div
                                    class="overflow-hidden rounded-lg border border-gray-200 bg-white"
                                >
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr
                                                class="bg-gray-100 text-gray-500"
                                            >
                                                <th
                                                    class="px-3 py-2 text-left font-semibold"
                                                >
                                                    Campo
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-left font-semibold"
                                                >
                                                    Valor anterior
                                                </th>
                                                <th
                                                    class="px-3 py-2 text-left font-semibold"
                                                >
                                                    Valor nuevo
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr
                                                v-for="(c, i) in r.cambios"
                                                :key="i"
                                            >
                                                <td
                                                    class="px-3 py-2 font-medium text-gray-700"
                                                >
                                                    {{ c.campo }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-red-600"
                                                >
                                                    {{
                                                        formatearValor(
                                                            c.anterior,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-green-700"
                                                >
                                                    {{
                                                        formatearValor(c.nuevo)
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div
            v-if="registros.total > 0"
            class="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p class="text-sm text-gray-500">
                Mostrando {{ registros.from ?? 0 }}–{{ registros.to ?? 0 }} de
                {{ registros.total }} registros
            </p>
            <div class="flex flex-wrap gap-1">
                <component
                    :is="link.url ? 'button' : 'span'"
                    v-for="(link, i) in registros.links"
                    :key="i"
                    class="inline-flex min-w-9 items-center justify-center rounded-md border px-3 py-1.5 text-sm transition"
                    :class="[
                        link.active
                            ? 'border-[#060041] bg-[#060041] text-white'
                            : 'border-gray-300 text-gray-600',
                        link.url
                            ? 'hover:bg-gray-50'
                            : 'cursor-default opacity-40',
                    ]"
                    @click="
                        link.url &&
                        router.get(
                            link.url,
                            {},
                            { preserveState: true, preserveScroll: true },
                        )
                    "
                >
                    <span v-html="link.label" />
                </component>
            </div>
        </div>
    </div>
</template>
