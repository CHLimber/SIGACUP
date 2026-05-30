<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

interface CarreraDB {
    id: number;
    nombre: string;
}

const props = defineProps<{
    carreras: CarreraDB[];
}>();

const carrerasDisplay = [
    { nombre: 'Ing. en Sistemas', icono: '💻' },
    { nombre: 'Ing. Informática', icono: '🖥️' },
    { nombre: 'Ing. en Robótica', icono: '🤖' },
    { nombre: 'Ing. Redes y Telecomunicaciones', icono: '🌐' },
];

const materias = [
    { nombre: 'Matemáticas', icono: '📐' },
    { nombre: 'Computación', icono: '🖥️' },
    { nombre: 'Inglés', icono: '🔤' },
    { nombre: 'Física', icono: '⚛️' },
];

const distinciones = [
    {
        titulo: 'Liderazgo Tecnológico',
        descripcion: 'Primera facultad de este tipo en el sistema público boliviano, pionera en la formación de ingenieros que transforman el entorno digital.',
        icono: '🏆',
    },
    {
        titulo: 'Calidad Académica',
        descripcion: 'Programas diseñados para formar profesionales integrales, éticos y altamente competitivos a nivel nacional e internacional.',
        icono: '🎓',
    },
    {
        titulo: 'Innovación Constante',
        descripcion: 'Promovemos la investigación científica y el desarrollo de soluciones tecnológicas que responden a las demandas reales de la sociedad.',
        icono: '🔬',
    },
];

// ── Registro de candidato ───────────────────────────────────────────────
const tipoRegistro = ref<'estudiante' | 'docente'>('estudiante');

const form = useForm({
    ci:               '',
    apellido:         '',
    nombres:          '',
    fecha_nacimiento: '',
    sexo:             '',
    telefono:         '',
    email:            '',
    direccion:        '',
    carrera1_id:      null as number | null,
    carrera2_id:      null as number | null,
});

// Tema visual: rojo para estudiante, azul para docente
const tema = computed(() => {
    if (tipoRegistro.value === 'estudiante') {
        return {
            principal:    '#c70e0a',
            gradiente:    'linear-gradient(to right, #7b0000, #c70e0a)',
            avisoBg:      'bg-red-50',
            avisoText:    'text-red-900',
            ringClass:    'focus:ring-[#c70e0a]',
            aviso:        'Solicitud de inscripción al CUP. La coordinación revisará tus datos y te notificará. Conserva tu CI: es tu identificador en el portal público.',
            botonLabel:   'Enviar solicitud de inscripción',
        };
    }
    return {
        principal:    '#073b75',
        gradiente:    'linear-gradient(to right, #060041, #073b75)',
        avisoBg:      'bg-blue-50',
        avisoText:    'text-blue-900',
        ringClass:    'focus:ring-[#073b75]',
        aviso:        'Solicitud para integrarte al cuerpo docente. La administración revisará tus datos y, si es aprobada, recibirás credenciales por email.',
        botonLabel:   'Enviar solicitud de docencia',
    };
});

const maxFechaNacimiento = computed(() => {
    const hoy = new Date();
    hoy.setFullYear(hoy.getFullYear() - 10);
    return hoy.toISOString().slice(0, 10);
});

function submit() {
    const url = tipoRegistro.value === 'estudiante'
        ? '/registro/estudiante'
        : '/registro/docente';
    form.post(url, {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Bienvenido - SIGACUP" />

    <!-- ─── NAVBAR ─── -->
    <nav class="fixed top-0 z-50 w-full shadow-lg" style="background: linear-gradient(to right, #060041, #073b75);">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">

            <!-- Logo + Identidad -->
            <a href="#inicio" class="flex items-center gap-3">
                <img
                    src="/Escudo_FICCT.png"
                    alt="Escudo FICCT"
                    class="h-11 w-11 rounded-full object-contain"
                    onerror="this.style.display='none'"
                />
                <div class="leading-tight">
                    <span class="block text-lg font-bold tracking-wide text-white">SIGACUP</span>
                    <span class="block text-[10px] font-medium uppercase tracking-widest text-blue-300">
                        FICCT · UAGRM
                    </span>
                </div>
            </a>

            <!-- Navegación central -->
            <div class="hidden items-center gap-8 md:flex">
                <a href="#inicio"
                   class="text-sm font-medium text-blue-200 transition hover:text-white">
                    Inicio
                </a>
                <a href="#registrarse"
                   class="text-sm font-medium text-blue-200 transition hover:text-white">
                    Registrarse como candidato
                </a>
                <a href="#notas"
                   class="text-sm font-medium text-blue-200 transition hover:text-white">
                    Ver notas
                </a>
            </div>

            <!-- Botón de acceso -->
            <div v-if="$page.props.auth.user">
                <Link
                    :href="dashboard()"
                    class="rounded bg-[#c70e0a] px-5 py-2 text-sm font-bold text-white shadow transition hover:bg-[#a50c09]"
                >
                    IR AL SISTEMA
                </Link>
            </div>
            <div v-else>
                <Link
                    :href="login()"
                    class="rounded bg-[#c70e0a] px-5 py-2 text-sm font-bold text-white shadow transition hover:bg-[#a50c09]"
                >
                    ACCEDER
                </Link>
            </div>
        </div>
    </nav>

    <!-- ─── HERO ─── -->
    <section id="inicio"
             class="relative flex min-h-screen items-center justify-center overflow-hidden pt-16"
             style="background: linear-gradient(135deg, #060041 0%, #073b75 60%, #0a4060 100%);">

        <!-- Patrón de puntos -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle, white 1px, transparent 1px);
                    background-size: 40px 40px;">
        </div>

        <!-- Línea roja decorativa -->
        <div class="absolute left-0 top-0 h-full w-1 bg-[#c70e0a]"></div>
        <div class="absolute right-0 top-0 h-full w-1 bg-[#c70e0a]"></div>

        <div class="relative z-10 mx-auto max-w-5xl px-6 text-center">
            <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-5 py-2">
                <span class="h-2 w-2 rounded-full bg-[#c70e0a]"></span>
                <p class="text-sm font-bold uppercase tracking-widest text-white/80">
                    Curso Preuniversitario · FICCT · UAGRM
                </p>
            </div>
            <h1 class="mb-6 text-4xl font-extrabold leading-tight text-white md:text-5xl lg:text-6xl">
                ¡CONSTRUYE TU FUTURO<br />
                <span class="text-white">CON NOSOTROS!</span>
            </h1>
            <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-blue-200">
                Sistema de Gestión del Curso Preuniversitario de la
                <strong class="text-white">
                    Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones
                </strong>.
            </p>
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                <a href="#registrarse"
                   class="w-full rounded bg-[#c70e0a] px-8 py-3.5 text-base font-bold text-white shadow-lg transition hover:bg-[#a50c09] sm:w-auto">
                    Registrarse como candidato
                </a>
                <a href="#notas"
                   class="w-full rounded border-2 border-white/60 px-8 py-3.5 text-base font-semibold text-white transition hover:bg-white hover:text-[#073b75] sm:w-auto">
                    Consultar notas
                </a>
            </div>
        </div>

        <!-- Flecha scroll -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce text-white/40">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </section>

    <!-- ─── BIENVENIDA / QUÉ ES LA FICCT ─── -->
    <section class="bg-white py-20">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-[#073b75]">Bienvenidos a la FICCT</h2>
                <div class="mx-auto h-1 w-16 rounded bg-[#c70e0a]"></div>
            </div>

            <div class="grid items-center gap-12 md:grid-cols-2">
                <div>
                    <p class="mb-4 text-base leading-relaxed text-gray-600">
                        Nos complace recibirte en este espacio digital, el punto de encuentro para la
                        <strong class="text-[#073b75]">innovación, la tecnología y el desarrollo académico</strong>
                        de nuestra región.
                    </p>
                    <p class="mb-4 text-base leading-relaxed text-gray-600">
                        Al formar parte de la FICCT, te integras a la gran familia de la
                        <strong class="text-[#073b75]">Universidad Autónoma Gabriel René Moreno (UAGRM)</strong>,
                        una institución con más de 140 años de historia y prestigio.
                    </p>
                    <p class="mb-6 text-base leading-relaxed text-gray-600">
                        El <strong class="text-[#073b75]">Curso Preuniversitario (CUP)</strong> es el proceso de admisión
                        mediante el cual los bachilleres demuestran competencias básicas en cuatro áreas.
                        La aprobación requiere <strong>60 puntos o más en cada materia</strong>,
                        con ponderación 30% · 30% · 40%.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <span class="rounded border border-[#073b75]/20 bg-[#073b75]/5 px-4 py-1.5 text-sm font-medium text-[#073b75]">
                            Fundada en 1987
                        </span>
                        <span class="rounded border border-[#c70e0a]/20 bg-[#c70e0a]/5 px-4 py-1.5 text-sm font-medium text-[#c70e0a]">
                            1ª Facultad en Bolivia
                        </span>
                    </div>
                </div>

                <!-- Cards de carreras -->
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="carrera in carrerasDisplay" :key="carrera.nombre"
                         class="group rounded-xl border border-gray-100 bg-gray-50 p-5 text-center shadow-sm
                                transition duration-200 hover:border-[#073b75] hover:bg-[#073b75] hover:shadow-md">
                        <div class="mb-2 text-4xl">{{ carrera.icono }}</div>
                        <h3 class="text-sm font-semibold text-[#073b75] transition group-hover:text-white">
                            {{ carrera.nombre }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── ¿QUÉ NOS DISTINGUE? ─── -->
    <section style="background: linear-gradient(to right, #060041, #073b75);" class="py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mb-10 text-center">
                <h2 class="mb-4 text-2xl font-bold text-white">¿Qué nos distingue?</h2>
                <div class="mx-auto h-1 w-16 rounded bg-[#c70e0a]"></div>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                <div v-for="d in distinciones" :key="d.titulo"
                     class="rounded-xl border border-white/10 bg-white/5 p-6 text-center
                            transition hover:border-white/30 hover:bg-white/10">
                    <div class="mb-3 text-4xl">{{ d.icono }}</div>
                    <h3 class="mb-2 font-bold text-white">{{ d.titulo }}</h3>
                    <p class="text-sm leading-relaxed text-blue-200/80">{{ d.descripcion }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── MATERIAS ─── -->
    <section class="bg-gray-50 py-16">
        <div class="mx-auto max-w-6xl px-6">
            <div class="mb-10 text-center">
                <h2 class="mb-4 text-2xl font-bold text-[#073b75]">Materias del CUP</h2>
                <div class="mx-auto h-1 w-16 rounded bg-[#c70e0a]"></div>
                <p class="mt-4 text-sm text-gray-500">3 exámenes por materia — Ponderación 30% · 30% · 40%</p>
            </div>
            <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
                <div v-for="materia in materias" :key="materia.nombre"
                     class="rounded-xl bg-white p-6 text-center shadow-sm transition hover:shadow-md">
                    <div class="mb-3 text-4xl">{{ materia.icono }}</div>
                    <h3 class="font-bold text-[#073b75]">{{ materia.nombre }}</h3>
                    <p class="mt-1 text-xs text-gray-400">Mín. 60 puntos</p>
                    <div class="mx-auto mt-3 h-0.5 w-8 rounded bg-[#c70e0a]"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── REGISTRARSE ─── -->
    <section id="registrarse" class="bg-white py-20">
        <div class="mx-auto max-w-2xl px-6">

            <!-- Encabezado dinámico -->
            <div class="mb-10 text-center">
                <h2
                    class="mb-4 text-3xl font-bold transition-colors duration-300"
                    :style="{ color: tema.principal }"
                >
                    Registro de candidato
                </h2>
                <div
                    class="mx-auto h-1 w-16 rounded transition-colors duration-300"
                    :style="{ backgroundColor: tema.principal }"
                ></div>
                <p class="mt-4 text-sm text-gray-500">Elige el rol al que estás aplicando</p>
            </div>

            <!-- Selector estudiante / docente -->
            <div class="mb-8 flex gap-3">
                <button
                    type="button"
                    @click="tipoRegistro = 'estudiante'"
                    :class="[
                        'flex-1 rounded-xl border-2 px-6 py-4 text-sm font-semibold transition',
                        tipoRegistro === 'estudiante'
                            ? 'border-[#c70e0a] bg-[#c70e0a] text-white shadow-md'
                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-[#c70e0a]/50 hover:bg-gray-100',
                    ]"
                >
                    Estudiante CUP
                </button>
                <button
                    type="button"
                    @click="tipoRegistro = 'docente'"
                    :class="[
                        'flex-1 rounded-xl border-2 px-6 py-4 text-sm font-semibold transition',
                        tipoRegistro === 'docente'
                            ? 'border-[#073b75] bg-[#073b75] text-white shadow-md'
                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-[#073b75]/50 hover:bg-gray-100',
                    ]"
                >
                    Docente
                </button>
            </div>

            <!-- ── Formulario único ── -->
            <form
                @submit.prevent="submit"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- Aviso dinámico -->
                <div
                    class="px-6 py-3 text-xs transition-colors duration-300"
                    :class="[tema.avisoBg, tema.avisoText]"
                >
                    ℹ️ {{ tema.aviso }}
                </div>

                <!-- Header datos personales (gradiente dinámico) -->
                <div
                    class="px-6 py-4 transition-colors duration-300"
                    :style="{ background: tema.gradiente }"
                >
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Datos personales</h3>
                </div>

                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                    <!-- CI -->
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Carnet de Identidad (CI) <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.ci"
                            type="text"
                            required
                            maxlength="20"
                            placeholder="ej. 12345678"
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.ci },
                            ]"
                        />
                        <p v-if="form.errors.ci" class="text-xs text-red-600">{{ form.errors.ci }}</p>
                    </div>

                    <!-- Apellido -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Apellido <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.apellido"
                            type="text"
                            required
                            placeholder="ej. Torrez"
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.apellido },
                            ]"
                        />
                        <p v-if="form.errors.apellido" class="text-xs text-red-600">{{ form.errors.apellido }}</p>
                    </div>

                    <!-- Nombres -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Nombres <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.nombres"
                            type="text"
                            required
                            placeholder="ej. María José"
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.nombres },
                            ]"
                        />
                        <p v-if="form.errors.nombres" class="text-xs text-red-600">{{ form.errors.nombres }}</p>
                    </div>

                    <!-- Fecha nacimiento -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Fecha de nacimiento <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.fecha_nacimiento"
                            type="date"
                            min="1930-01-01"
                            :max="maxFechaNacimiento"
                            required
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.fecha_nacimiento },
                            ]"
                        />
                        <p v-if="form.errors.fecha_nacimiento" class="text-xs text-red-600">{{ form.errors.fecha_nacimiento }}</p>
                    </div>

                    <!-- Sexo -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Sexo <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.sexo"
                            required
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.sexo },
                            ]"
                        >
                            <option value="" disabled>Seleccionar</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                        </select>
                        <p v-if="form.errors.sexo" class="text-xs text-red-600">{{ form.errors.sexo }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">Teléfono <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.telefono"
                            type="tel"
                            required
                            maxlength="30"
                            placeholder="ej. 77712345"
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.telefono },
                            ]"
                        />
                        <p v-if="form.errors.telefono" class="text-xs text-red-600">{{ form.errors.telefono }}</p>
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-gray-700">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="correo@ejemplo.com"
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.email },
                            ]"
                        />
                        <p v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</p>
                        <p v-if="tipoRegistro === 'docente'" class="text-xs text-gray-400">
                            A este correo enviaremos las credenciales si tu solicitud es aprobada.
                        </p>
                    </div>

                    <!-- Dirección -->
                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-gray-700">Dirección <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.direccion"
                            type="text"
                            required
                            placeholder="ej. Av. Brasil N° 123, Santa Cruz"
                            :class="[
                                'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2',
                                tipoRegistro === 'estudiante' ? 'focus:ring-[#c70e0a]' : 'focus:ring-[#073b75]',
                                { 'border-red-400': form.errors.direccion },
                            ]"
                        />
                        <p v-if="form.errors.direccion" class="text-xs text-red-600">{{ form.errors.direccion }}</p>
                    </div>
                </div>

                <!-- Sección Carreras (solo estudiante) -->
                <template v-if="tipoRegistro === 'estudiante'">
                    <div class="border-y border-gray-100 px-6 py-3" style="background-color: #c70e0a;">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-white">Preferencia de carrera</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                        <!-- 1ra opción -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">1ra opción <span class="text-red-500">*</span></label>
                            <select
                                v-model="form.carrera1_id"
                                required
                                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#c70e0a]"
                                :class="{ 'border-red-400': form.errors.carrera1_id }"
                            >
                                <option :value="null" disabled>Selecciona una carrera</option>
                                <option
                                    v-for="c in carreras"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.id === form.carrera2_id"
                                >{{ c.nombre }}</option>
                            </select>
                            <p v-if="form.errors.carrera1_id" class="text-xs text-red-600">
                                {{ form.errors.carrera1_id }}
                            </p>
                        </div>

                        <!-- 2da opción -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-gray-700">2da opción <span class="text-red-500">*</span></label>
                            <select
                                v-model="form.carrera2_id"
                                required
                                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#c70e0a]"
                                :class="{ 'border-red-400': form.errors.carrera2_id }"
                            >
                                <option :value="null" disabled>Selecciona una carrera</option>
                                <option
                                    v-for="c in carreras"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.id === form.carrera1_id"
                                >{{ c.nombre }}</option>
                            </select>
                            <p v-if="form.errors.carrera2_id" class="text-xs text-red-600">
                                {{ form.errors.carrera2_id }}
                            </p>
                        </div>

                        <p class="text-xs text-gray-400 sm:col-span-2">
                            Las dos opciones deben ser distintas. Si hay cupos disponibles, se asignará la carrera en orden de preferencia.
                        </p>
                    </div>
                </template>

                <!-- Botones -->
                <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                    <button
                        type="button"
                        @click="form.reset()"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Limpiar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 disabled:opacity-60"
                        :style="{ backgroundColor: tema.principal }"
                    >
                        {{ form.processing ? 'Enviando…' : tema.botonLabel }}
                    </button>
                </div>
            </form>

        </div>
    </section>

    <!-- ─── VER NOTAS (placeholder) ─── -->
    <section id="notas" class="bg-gray-50 py-20">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="rounded-2xl border-2 border-dashed border-gray-200 p-16">
                <p class="mb-4 text-5xl">📊</p>
                <h2 class="mb-3 text-2xl font-bold text-[#073b75]">Consulta de Notas</h2>
                <p class="text-gray-400">Esta sección estará disponible una vez publicados los resultados.</p>
            </div>
        </div>
    </section>

    <!-- ─── FOOTER ─── -->
    <footer style="background: linear-gradient(to right, #060041, #073b75);" class="py-10">
        <div class="mx-auto max-w-6xl px-6">
            <div class="flex flex-col items-center gap-6 md:flex-row md:justify-between">
                <div class="flex items-center gap-3">
                    <img
                        src="/Escudo_FICCT.png"
                        alt="Escudo FICCT"
                        class="h-12 w-12 rounded-full object-contain"
                        onerror="this.style.display='none'"
                    />
                    <div>
                        <p class="font-bold text-white">SIGACUP</p>
                        <p class="text-xs text-blue-300">Sistema de Gestión del Curso Preuniversitario</p>
                        <p class="text-xs text-blue-400/70">FICCT · UAGRM</p>
                    </div>
                </div>
                <div class="text-center text-sm text-blue-300/70">
                    <p>© {{ new Date().getFullYear() }} FICCT · Universidad Autónoma Gabriel René Moreno</p>
                    <p class="mt-1 text-xs">Santa Cruz de la Sierra, Bolivia</p>
                </div>
            </div>
            <div class="mt-6 border-t border-white/10 pt-4 text-center text-xs text-blue-400/50">
                Sistema de Gestión del Curso Preuniversitario — Grupo 8 · Sistemas de Información 1
            </div>
        </div>
    </footer>
</template>
