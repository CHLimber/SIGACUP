<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MoreHorizontal, Pencil, Power, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';

interface UsuarioItem {
    id: number;
    name: string;
    username: string;
    email: string;
    role: string;
    rol_label: string;
    activo: boolean;
    es_actual: boolean;
}
interface RolOption {
    nombre: string;
    label: string;
}
interface GestionOption {
    id: number;
    anio: number;
    semestre: number;
}
interface Filtros {
    rol: string;
    estado: string;
    gestion_id: string;
}
interface Paginado {
    data: UsuarioItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

const props = defineProps<{
    usuarios: Paginado;
    resumen: { activos: number; inactivos: number };
    roles: RolOption[];
    gestiones: GestionOption[];
    filtros: Filtros;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Usuarios', href: '/administracion/usuarios' },
        ],
    },
});

const totales = computed(() => ({
    total: props.resumen.activos + props.resumen.inactivos,
    activos: props.resumen.activos,
    inactivos: props.resumen.inactivos,
}));

// ── Filtros ──────────────────────────────────────────────────────────────────
const filtroRol = ref(props.filtros.rol);
const filtroEstado = ref(props.filtros.estado);
const filtroGestionId = ref(props.filtros.gestion_id);

const hayFiltros = computed(() => !!(filtroRol.value || filtroEstado.value || filtroGestionId.value));

watch([filtroRol, filtroEstado, filtroGestionId], () => {
    const params: Record<string, string> = {};
    if (filtroRol.value) params.rol = filtroRol.value;
    if (filtroEstado.value) params.estado = filtroEstado.value;
    if (filtroGestionId.value) params.gestion_id = filtroGestionId.value;
    router.get('/administracion/usuarios', params, { preserveScroll: true, replace: true });
});

function limpiarFiltros() {
    filtroRol.value = '';
    filtroEstado.value = '';
    filtroGestionId.value = '';
}

// ── Paginación ────────────────────────────────────────────────────────────────
function irAPagina(page: number) {
    if (page < 1 || page > props.usuarios.last_page) return;
    const params: Record<string, string | number> = { page };
    if (filtroRol.value) params.rol = filtroRol.value;
    if (filtroEstado.value) params.estado = filtroEstado.value;
    if (filtroGestionId.value) params.gestion_id = filtroGestionId.value;
    router.get('/administracion/usuarios', params, { preserveScroll: true, replace: true });
}

const paginas = computed<(number | '...')[]>(() => {
    const total = props.usuarios.last_page;
    const actual = props.usuarios.current_page;
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    const pages: (number | '...')[] = [1];
    if (actual > 3) pages.push('...');
    for (let i = Math.max(2, actual - 1); i <= Math.min(total - 1, actual + 1); i++) {
        pages.push(i);
    }
    if (actual < total - 2) pages.push('...');
    pages.push(total);
    return pages;
});

// ── Formulario CRUD ───────────────────────────────────────────────────────────
const editando = ref<UsuarioItem | null>(null);
const panelAbierto = ref(false);

const form = useForm<{ name: string; username: string; email: string; password: string; role: string }>({
    name: '',
    username: '',
    email: '',
    password: '',
    role: props.roles[0]?.nombre ?? '',
});

function abrirNuevo() {
    editando.value = null;
    form.reset();
    form.clearErrors();
    form.role = props.roles[0]?.nombre ?? '';
    panelAbierto.value = true;
}

function abrirEditar(u: UsuarioItem) {
    editando.value = u;
    form.clearErrors();
    form.name = u.name;
    form.username = u.username;
    form.email = u.email;
    form.password = '';
    form.role = u.role;
    panelAbierto.value = true;
}

function cerrar() {
    panelAbierto.value = false;
    editando.value = null;
}

function guardar() {
    if (editando.value) {
        form.patch(`/administracion/usuarios/${editando.value.id}`, { preserveScroll: true, onSuccess: cerrar });
    } else {
        form.post('/administracion/usuarios', { preserveScroll: true, onSuccess: cerrar });
    }
}

function toggle(u: UsuarioItem) {
    router.patch(`/administracion/usuarios/${u.id}/toggle`, {}, { preserveScroll: true });
}

function eliminar(u: UsuarioItem) {
    if (!confirm(`¿Eliminar al usuario «${u.name}»? Esta acción no se puede deshacer.`)) {
return;
}
    router.delete(`/administracion/usuarios/${u.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Cuentas del personal: asigná roles y controlá el acceso al sistema.
                </p>
            </div>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 sm:mt-0"
                style="background-color: #c70e0a;"
                @click="abrirNuevo"
            >
                + Nuevo Usuario
            </button>
        </div>

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                    {{ hayFiltros ? 'Resultados' : 'Total' }}
                </p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ totales.total }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Activos</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ totales.activos }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Inactivos</p>
                <p class="mt-1 text-3xl font-bold text-red-600">{{ totales.inactivos }}</p>
            </div>
        </div>

        <!-- Barra de filtros -->
        <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Rol</label>
                    <select
                        v-model="filtroRol"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                    >
                        <option value="">Todos los roles</option>
                        <option v-for="r in roles" :key="r.nombre" :value="r.nombre">{{ r.label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estado</label>
                    <select
                        v-model="filtroEstado"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                    >
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gestión</label>
                    <select
                        v-model="filtroGestionId"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                    >
                        <option value="">Todas las gestiones</option>
                        <option v-for="g in gestiones" :key="g.id" :value="String(g.id)">
                            {{ g.anio }}-{{ g.semestre }}
                        </option>
                    </select>
                </div>

                <button
                    v-if="hayFiltros"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                    @click="limpiarFiltros"
                >
                    <X class="h-3.5 w-3.5" />
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Formulario (panel) -->
        <div v-if="panelAbierto" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between px-5 py-3.5" style="background-color: #060041;">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">
                    {{ editando ? `Editar: ${editando.name}` : 'Nuevo usuario' }}
                </h2>
                <button type="button" class="text-white/80 hover:text-white" @click="cerrar"><X class="h-4 w-4" /></button>
            </div>
            <form class="grid gap-4 p-6 sm:grid-cols-2" @submit.prevent="guardar">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Nombre completo <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]" :class="{ 'border-red-400': form.errors.name }" />
                    <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Usuario <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.username"
                        type="text"
                        :disabled="!!editando"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75] disabled:bg-gray-100 disabled:text-gray-500"
                        :class="{ 'border-red-400': form.errors.username }"
                    />
                    <p v-if="form.errors.username" class="text-xs text-red-600">{{ form.errors.username }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input v-model="form.email" type="email" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]" :class="{ 'border-red-400': form.errors.email }" />
                    <p v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Rol <span class="text-red-500">*</span></label>
                    <select v-model="form.role" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]">
                        <option v-for="r in roles" :key="r.nombre" :value="r.nombre">{{ r.label }}</option>
                    </select>
                    <p v-if="form.errors.role" class="text-xs text-red-600">{{ form.errors.role }}</p>
                </div>

                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="text-sm font-medium text-gray-700">
                        Contraseña <span v-if="!editando" class="text-red-500">*</span>
                        <span v-else class="text-xs font-normal text-gray-400">(dejar vacío para no cambiarla)</span>
                    </label>
                    <input v-model="form.password" type="password" autocomplete="new-password" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75] sm:max-w-xs" :class="{ 'border-red-400': form.errors.password }" />
                    <p v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</p>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 sm:col-span-2">
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50" @click="cerrar">Cancelar</button>
                    <button type="submit" :disabled="form.processing" class="rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60" style="background-color: #c70e0a;">
                        {{ form.processing ? 'Guardando…' : editando ? 'Guardar cambios' : 'Crear usuario' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background-color: #060041;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Usuario</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Email</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Rol</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Estado</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="usuarios.data.length === 0">
                        <td colspan="5" class="px-5 py-14 text-center text-gray-400">
                            <template v-if="hayFiltros">
                                No hay usuarios que coincidan con los filtros aplicados.
                            </template>
                            <template v-else>
                                No hay usuarios registrados. Crea el primero usando el botón de arriba.
                            </template>
                        </td>
                    </tr>
                    <tr v-for="u in usuarios.data" :key="u.id" class="transition-colors hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">
                                {{ u.name }}
                                <span v-if="u.es_actual" class="ml-1 text-xs font-normal text-gray-400">(tú)</span>
                            </p>
                            <p class="text-xs text-gray-500">@{{ u.username }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ u.email }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ u.rol_label }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="u.activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            >
                                {{ u.activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300"
                                    aria-label="Acciones"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-48">
                                    <DropdownMenuItem class="cursor-pointer" @select="abrirEditar(u)">
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Editar
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="!u.es_actual"
                                        class="cursor-pointer"
                                        @select="toggle(u)"
                                    >
                                        <Power class="mr-2 h-4 w-4" />
                                        {{ u.activo ? 'Desactivar' : 'Activar' }}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator v-if="!u.es_actual" />
                                    <DropdownMenuItem
                                        v-if="!u.es_actual"
                                        class="cursor-pointer text-red-600 focus:bg-red-50 focus:text-red-700"
                                        @select="eliminar(u)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pie de paginación -->
            <div
                v-if="usuarios.last_page > 1 || usuarios.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-5 py-3 sm:flex-row"
            >
                <p class="text-xs text-gray-500">
                    <template v-if="usuarios.total === 0">Sin resultados</template>
                    <template v-else>
                        Mostrando {{ usuarios.from }}–{{ usuarios.to }} de {{ usuarios.total }} usuarios
                    </template>
                </p>

                <div v-if="usuarios.last_page > 1" class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="usuarios.current_page === 1"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="irAPagina(usuarios.current_page - 1)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <template v-for="(p, i) in paginas" :key="i">
                        <span v-if="p === '...'" class="flex h-8 w-8 items-center justify-center text-xs text-gray-400">…</span>
                        <button
                            v-else
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border text-xs font-medium transition"
                            :class="p === usuarios.current_page
                                ? 'border-transparent text-white'
                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                            :style="p === usuarios.current_page ? 'background-color: #060041;' : ''"
                            @click="irAPagina(Number(p))"
                        >
                            {{ p }}
                        </button>
                    </template>

                    <button
                        type="button"
                        :disabled="usuarios.current_page === usuarios.last_page"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="irAPagina(usuarios.current_page + 1)"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
