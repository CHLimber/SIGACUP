<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, type Component } from 'vue';
import { dashboard } from '@/routes';
import { User, BookOpen, FileText, CheckCircle2, CreditCard, Calendar, BarChart2 } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const props = defineProps<{
    nota_minima: number;
    peso1: number;
    peso2: number;
    peso3: number;
    gestion_label: string | null;
    gestion_estado: string | null;
    candidatos: number;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const roleLabel: Record<string, string> = {
    administrador: 'Administrador',
    coordinador:   'Coordinador',
    docente:       'Docente',
    autoridad:     'Autoridad',
};

const stats = computed<{ label: string; valor: string; icono: Component; color: string }[]>(() => [
    { label: 'Candidatos inscritos', valor: String(props.candidatos), icono: User,         color: '#073b75' },
    { label: 'Materias activas',     valor: '4',                      icono: BookOpen,     color: '#073b75' },
    { label: 'Nota mínima',          valor: `${props.nota_minima} pts`, icono: CheckCircle2, color: '#15803d' },
    { label: 'Ponderación exámenes', valor: `${props.peso1}·${props.peso2}·${props.peso3}`, icono: FileText, color: '#c70e0a' },
]);

const modulos: { nombre: string; descripcion: string; icono: Component }[] = [
    { nombre: 'Inscripción y Pagos',     descripcion: 'Gestión de candidatos y pagos del CUP.',        icono: CreditCard },
    { nombre: 'Organización Académica',  descripcion: 'Horarios, docentes y materias del curso.',       icono: Calendar  },
    { nombre: 'Evaluación y Resultados', descripcion: 'Registro de notas y resultados de exámenes.',    icono: BarChart2  },
    { nombre: 'Reportes',               descripcion: 'Generación de reportes e informes del proceso.',  icono: FileText  },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Bienvenida -->
        <div class="rounded-xl p-6 text-white" style="background: linear-gradient(135deg, #060041 0%, #073b75 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-300">Bienvenido de vuelta</p>
                    <h1 class="mt-1 text-2xl font-bold">{{ user?.name }}</h1>
                    <span class="mt-2 inline-block rounded-full border border-white/20 bg-white/10 px-3 py-0.5 text-xs font-medium">
                        {{ roleLabel[user?.role] ?? user?.role }}
                    </span>
                </div>
                <div class="hidden text-right md:block">
                    <p class="text-sm text-blue-300">Sistema de Gestión</p>
                    <p class="text-lg font-bold">CUP · FICCT</p>
                    <p class="text-xs text-blue-400/70">UAGRM</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
            >
                <div class="mb-3"><component :is="stat.icono" class="h-8 w-8" :style="{ color: stat.color }" /></div>
                <p class="text-2xl font-bold" :style="{ color: stat.color }">{{ stat.valor }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ stat.label }}</p>
            </div>
        </div>

        <!-- Módulos del sistema -->
        <div>
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-base font-bold text-[#073b75]">Módulos del sistema</h2>
                <div class="h-px flex-1 bg-gray-100"></div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="modulo in modulos"
                    :key="modulo.nombre"
                    class="group cursor-pointer rounded-xl border border-gray-100 bg-white p-5 shadow-sm
                           transition duration-200 hover:border-[#073b75] hover:shadow-md"
                >
                    <div class="mb-3"><component :is="modulo.icono" class="h-8 w-8 text-[#073b75]" /></div>
                    <h3 class="mb-1 font-semibold text-[#073b75]">{{ modulo.nombre }}</h3>
                    <p class="text-xs leading-relaxed text-gray-400">{{ modulo.descripcion }}</p>
                    <div class="mt-3 h-0.5 w-6 rounded bg-[#c70e0a] transition-all duration-200 group-hover:w-12"></div>
                </div>
            </div>
        </div>

        <!-- Info CUP -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center gap-3">
                <h2 class="text-base font-bold text-[#073b75]">Estado del CUP</h2>
                <span v-if="props.gestion_label" class="rounded-full border border-[#073b75]/20 bg-[#073b75]/5 px-3 py-0.5 text-xs font-medium text-[#073b75]">
                    {{ props.gestion_label }}
                </span>
                <div class="h-px flex-1 bg-gray-100"></div>
            </div>
            <div v-if="props.gestion_label" class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-xs text-gray-500">Ponderación de exámenes</p>
                    <p class="mt-2 text-lg font-bold text-[#073b75]">{{ props.peso1 }}% · {{ props.peso2 }}% · {{ props.peso3 }}%</p>
                    <p class="mt-1 text-xs text-gray-400">Parcial 1 · Parcial 2 · Final</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-xs text-gray-500">Nota mínima de aprobación</p>
                    <p class="mt-2 text-lg font-bold text-[#c70e0a]">{{ props.nota_minima }} puntos</p>
                    <p class="mt-1 text-xs text-gray-400">Por materia — de forma independiente</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-xs text-gray-500">Carreras disponibles</p>
                    <p class="mt-2 text-lg font-bold text-[#073b75]">4 carreras</p>
                    <p class="mt-1 text-xs text-gray-400">Ing. Sistemas · Informática · Robótica · Redes</p>
                </div>
            </div>
            <div v-else class="rounded-lg bg-gray-50 p-8 text-center text-sm text-gray-400">
                No hay ninguna gestión activa en este momento.
            </div>
        </div>

    </div>
</template>
