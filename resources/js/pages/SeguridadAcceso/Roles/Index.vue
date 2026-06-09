<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Lock, MoreHorizontal, Pencil, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';

interface RolItem {
    id: number;
    nombre: string;
    label: string;
    descripcion: string | null;
    es_sistema: boolean;
    usuarios_count: number;
    permisos: number[];
}
interface PermisoItem {
    id: number;
    nombre: string;
    label: string;
}
interface GrupoPermisos {
    grupo: string;
    permisos: PermisoItem[];
}

const props = defineProps<{
    roles: RolItem[];
    permisos: GrupoPermisos[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Roles y permisos', href: '/administracion/roles' },
        ],
    },
});

const editando = ref<RolItem | null>(null);
const panelAbierto = ref(false);

const form = useForm<{ label: string; descripcion: string; permisos: number[] }>({
    label: '',
    descripcion: '',
    permisos: [],
});

const esNuevo = computed(() => editando.value === null);
const totalPermisos = computed(() => props.permisos.reduce((a, g) => a + g.permisos.length, 0));

function abrirNuevo() {
    editando.value = null;
    form.clearErrors();
    form.label = '';
    form.descripcion = '';
    form.permisos = [];
    panelAbierto.value = true;
}

function abrirEditar(rol: RolItem) {
    editando.value = rol;
    form.clearErrors();
    form.label = rol.label;
    form.descripcion = rol.descripcion ?? '';
    form.permisos = [...rol.permisos];
    panelAbierto.value = true;
}

function cerrar() {
    panelAbierto.value = false;
    editando.value = null;
}

function togglePermiso(id: number) {
    const i = form.permisos.indexOf(id);

    if (i === -1) {
form.permisos.push(id);
} else {
form.permisos.splice(i, 1);
}
}

function guardar() {
    if (editando.value) {
        form.patch(`/administracion/roles/${editando.value.id}`, { preserveScroll: true, onSuccess: cerrar });
    } else {
        form.post('/administracion/roles', { preserveScroll: true, onSuccess: cerrar });
    }
}

function eliminar(rol: RolItem) {
    if (!confirm(`¿Eliminar el rol «${rol.label}»? Esta acción no se puede deshacer.`)) {
return;
}

    router.delete(`/administracion/roles/${rol.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Roles y permisos" />

    <div class="flex flex-col gap-6 p-4 sm:p-6">
        <!-- Encabezado -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Roles y permisos</h1>
                <p class="mt-0.5 text-sm text-gray-500">
                    Definí roles y marcá qué permisos tiene cada uno. El administrador siempre tiene acceso total.
                </p>
            </div>
            <button
                type="button"
                class="mt-3 inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 sm:mt-0"
                style="background-color: #c70e0a;"
                @click="abrirNuevo"
            >
                + Nuevo Rol
            </button>
        </div>

        <!-- Formulario (panel) -->
        <div v-if="panelAbierto" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between px-5 py-3.5" style="background-color: #060041;">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">
                    {{ esNuevo ? 'Nuevo rol' : `Editar: ${editando?.label}` }}
                </h2>
                <button type="button" class="text-white/80 hover:text-white" @click="cerrar"><X class="h-4 w-4" /></button>
            </div>

            <form class="flex flex-col gap-5 p-6" @submit.prevent="guardar">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Nombre del rol <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.label"
                            type="text"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                            :class="{ 'border-red-400': form.errors.label }"
                        />
                        <p v-if="form.errors.label" class="text-xs text-red-600">{{ form.errors.label }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Descripción</label>
                        <input
                            v-model="form.descripcion"
                            type="text"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-[#073b75]"
                        />
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Permisos</h3>
                        <span class="text-xs text-gray-400">{{ form.permisos.length }} de {{ totalPermisos }}</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div v-for="g in permisos" :key="g.grupo" class="rounded-lg border border-gray-100 p-4">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ g.grupo }}</p>
                            <div class="flex flex-col gap-2">
                                <label
                                    v-for="p in g.permisos"
                                    :key="p.id"
                                    class="flex cursor-pointer items-center gap-2 text-sm text-gray-700"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 accent-[#073b75]"
                                        :checked="form.permisos.includes(p.id)"
                                        @change="togglePermiso(p.id)"
                                    />
                                    {{ p.label }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50" @click="cerrar">Cancelar</button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60"
                        style="background-color: #c70e0a;"
                    >
                        {{ form.processing ? 'Guardando…' : esNuevo ? 'Crear rol' : 'Guardar cambios' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm">
                <thead>
                    <tr style="background-color: #060041;">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Rol</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white">Descripción</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-white">Usuarios</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-white">Permisos</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-white">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="rol in roles" :key="rol.id" class="transition-colors hover:bg-gray-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">{{ rol.label }}</span>
                                <span
                                    v-if="rol.es_sistema"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500"
                                >
                                    <Lock class="h-2.5 w-2.5" /> Sistema
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">{{ rol.nombre }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            <span v-if="rol.descripcion">{{ rol.descripcion }}</span>
                            <span v-else class="italic text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-center text-gray-700">{{ rol.usuarios_count }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex rounded-full bg-[#073b75]/10 px-2.5 py-0.5 text-xs font-semibold text-[#073b75]">
                                {{ rol.permisos.length }}
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
                                    <DropdownMenuItem class="cursor-pointer" @select="abrirEditar(rol)">
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Editar permisos
                                    </DropdownMenuItem>
                                    <template v-if="!rol.es_sistema">
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="cursor-pointer text-red-600 focus:bg-red-50 focus:text-red-700"
                                            @select="eliminar(rol)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Eliminar
                                        </DropdownMenuItem>
                                    </template>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</template>
