<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';

const carreras = [
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
                    <div v-for="carrera in carreras" :key="carrera.nombre"
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

    <!-- ─── REGISTRARSE (placeholder) ─── -->
    <section id="registrarse" class="bg-white py-20">
        <div class="mx-auto max-w-4xl px-6 text-center">
            <div class="rounded-2xl border-2 border-dashed border-gray-200 p-16">
                <p class="mb-4 text-5xl">📝</p>
                <h2 class="mb-3 text-2xl font-bold text-[#073b75]">Registro de Candidatos</h2>
                <p class="text-gray-400">Esta sección estará disponible próximamente.</p>
            </div>
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
