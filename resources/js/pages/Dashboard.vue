<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    BarChart3,
    BookOpen,
    ClipboardCheck,
    ClipboardList,
    Layers,
    ScrollText,
    Settings,
    ShieldCheck,
    Users2,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

type Metricas = {
    postulaciones: number;
    admitidos: number;
    por_revisar: number;
    pagos: number;
    monto_recaudado: number;
    grupos: number;
    carreras: number;
    materias: number;
    carreras_nombres: string[];
};

const props = defineProps<{
    nota_minima: number;
    peso1: number;
    peso2: number;
    peso3: number;
    gestion_label: string | null;
    gestion_estado: string | null;
    metricas: Metricas;
}>();

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);

const roleLabel: Record<string, string> = {
    administrador: 'Administrador',
    coordinador: 'Coordinador',
    docente: 'Docente',
    autoridad: 'Autoridad',
};

const moneda = (valor: number) =>
    new Intl.NumberFormat('es-BO', { maximumFractionDigits: 0 }).format(valor);

// --- Estadísticas principales (datos reales de la gestión activa) ---
const stats = computed<
    { label: string; valor: string; detalle: string; icono: Component; color: string }[]
>(() => [
    {
        label: 'Postulaciones',
        valor: String(props.metricas.postulaciones),
        detalle: 'En la gestión activa',
        icono: ClipboardList,
        color: '#073b75',
    },
    {
        label: 'Admitidos',
        valor: String(props.metricas.admitidos),
        detalle: 'Por mérito académico',
        icono: Award,
        color: '#15803d',
    },
    {
        label: 'Por revisar',
        valor: String(props.metricas.por_revisar),
        detalle: 'Candidatos pendientes',
        icono: ClipboardCheck,
        color: '#b45309',
    },
    {
        label: 'Pagos completados',
        valor: String(props.metricas.pagos),
        detalle: `Bs ${moneda(props.metricas.monto_recaudado)} recaudados`,
        icono: Wallet,
        color: '#c70e0a',
    },
]);

// --- Tarjetas de módulo (navegación a los 6 paquetes, filtradas por permiso) ---
type Modulo = {
    nombre: string;
    descripcion: string;
    icono: Component;
    href: string;
    permiso: string;
};

const todosModulos: Modulo[] = [
    {
        nombre: 'Seguridad y acceso',
        descripcion: 'Usuarios, roles, permisos y bitácora de auditoría.',
        icono: ShieldCheck,
        href: '/administracion/usuarios',
        permiso: 'usuarios.gestionar',
    },
    {
        nombre: 'Administración del sistema',
        descripcion: 'Gestiones académicas, parámetros, carreras y cupos.',
        icono: Settings,
        href: '/administracion/gestiones',
        permiso: 'gestiones.gestionar',
    },
    {
        nombre: 'Registro e inscripción',
        descripcion: 'Candidatos, estudiantes, postulaciones y pagos.',
        icono: ClipboardList,
        href: '/administracion/estudiantes',
        permiso: 'estudiantes.gestionar',
    },
    {
        nombre: 'Organización académica',
        descripcion: 'Grupos, aulas, horarios y asignación de docentes.',
        icono: BookOpen,
        href: '/administracion/grupos',
        permiso: 'grupos.gestionar',
    },
    {
        nombre: 'Evaluación y admisión',
        descripcion: 'Admisión por mérito, proceso y calificaciones.',
        icono: ClipboardCheck,
        href: '/administracion/admision',
        permiso: 'admision.gestionar',
    },
    {
        nombre: 'Reportes y notificaciones',
        descripcion: 'Reportes, resúmenes e informes del proceso.',
        icono: BarChart3,
        href: '/administracion/reportes',
        permiso: 'reportes.ver',
    },
];

const modulos = computed<Modulo[]>(() => {
    const { permisos = [], esAdmin = false } = page.props.auth ?? {};

    return todosModulos.filter(
        (m) => esAdmin || permisos.includes(m.permiso),
    );
});

// --- Resumen institucional (datos reales) ---
const resumen = computed<{ label: string; valor: string; icono: Component }[]>(() => [
    { label: 'Carreras', valor: String(props.metricas.carreras), icono: Layers },
    { label: 'Materias', valor: String(props.metricas.materias), icono: BookOpen },
    { label: 'Grupos (gestión)', valor: String(props.metricas.grupos), icono: Users2 },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Bienvenida -->
        <div
            class="rounded-xl p-6 text-white"
            style="background: linear-gradient(135deg, #060041 0%, #073b75 100%)"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-300">Bienvenido de vuelta</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ user?.name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-block rounded-full border border-white/20 bg-white/10 px-3 py-0.5 text-xs font-medium"
                        >
                            {{ roleLabel[user?.role] ?? user?.role }}
                        </span>
                        <span
                            v-if="props.gestion_label"
                            class="inline-flex items-center gap-1 rounded-full border border-white/20 bg-white/10 px-3 py-0.5 text-xs font-medium"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                            Gestión {{ props.gestion_label }}
                        </span>
                    </div>
                </div>
                <div class="hidden text-right md:block">
                    <p class="text-sm text-blue-300">Sistema de Gestión</p>
                    <p class="text-lg font-bold">CUP · FICCT</p>
                    <p class="text-xs text-blue-400/70">UAGRM</p>
                </div>
            </div>
        </div>

        <!-- Sin gestión activa -->
        <div
            v-if="!props.gestion_label"
            class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
        >
            <ScrollText class="h-5 w-5 shrink-0" />
            <span>
                No hay ninguna gestión activa. Las estadísticas del proceso se mostrarán
                cuando se inicie una gestión.
            </span>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div class="mb-3">
                    <component :is="stat.icono" class="h-8 w-8" :style="{ color: stat.color }" />
                </div>
                <p class="text-2xl font-bold" :style="{ color: stat.color }">{{ stat.valor }}</p>
                <p class="mt-1 text-sm font-medium text-gray-700">{{ stat.label }}</p>
                <p class="mt-0.5 text-xs text-gray-400">{{ stat.detalle }}</p>
            </div>
        </div>

        <!-- Módulos del sistema (navegables, según permisos) -->
        <div v-if="modulos.length">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-base font-bold text-[#073b75]">Módulos del sistema</h2>
                <div class="h-px flex-1 bg-gray-100"></div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="modulo in modulos"
                    :key="modulo.nombre"
                    :href="modulo.href"
                    class="group rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition duration-200 hover:border-[#073b75] hover:shadow-md"
                >
                    <div class="mb-3">
                        <component :is="modulo.icono" class="h-8 w-8 text-[#073b75]" />
                    </div>
                    <h3 class="mb-1 font-semibold text-[#073b75]">{{ modulo.nombre }}</h3>
                    <p class="text-xs leading-relaxed text-gray-400">{{ modulo.descripcion }}</p>
                    <div
                        class="mt-3 h-0.5 w-6 rounded bg-[#c70e0a] transition-all duration-200 group-hover:w-12"
                    ></div>
                </Link>
            </div>
        </div>

        <!-- Info CUP -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-base font-bold text-[#073b75]">Estado del CUP</h2>
                <span
                    v-if="props.gestion_label"
                    class="rounded-full border border-[#073b75]/20 bg-[#073b75]/5 px-3 py-0.5 text-xs font-medium text-[#073b75]"
                >
                    {{ props.gestion_label }}
                </span>
                <div class="h-px flex-1 bg-gray-100"></div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-xs text-gray-500">Ponderación de exámenes</p>
                    <p class="mt-2 text-lg font-bold text-[#073b75]">
                        {{ props.peso1 }}% · {{ props.peso2 }}% · {{ props.peso3 }}%
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Parcial 1 · Parcial 2 · Final</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-xs text-gray-500">Nota mínima de aprobación</p>
                    <p class="mt-2 text-lg font-bold text-[#c70e0a]">
                        {{ props.nota_minima }} puntos
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Por materia — de forma independiente</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-xs text-gray-500">Carreras disponibles</p>
                    <p class="mt-2 text-lg font-bold text-[#073b75]">
                        {{ props.metricas.carreras }}
                        {{ props.metricas.carreras === 1 ? 'carrera' : 'carreras' }}
                    </p>
                    <p class="mt-1 truncate text-xs text-gray-400">
                        {{ props.metricas.carreras_nombres.join(' · ') || 'Sin carreras registradas' }}
                    </p>
                </div>
            </div>

            <!-- Resumen institucional -->
            <div class="mt-4 grid grid-cols-3 gap-4 border-t border-gray-100 pt-4">
                <div
                    v-for="item in resumen"
                    :key="item.label"
                    class="flex items-center justify-center gap-2 text-sm text-gray-600"
                >
                    <component :is="item.icono" class="h-4 w-4 text-[#073b75]" />
                    <span class="font-bold text-[#073b75]">{{ item.valor }}</span>
                    <span class="text-gray-400">{{ item.label }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
