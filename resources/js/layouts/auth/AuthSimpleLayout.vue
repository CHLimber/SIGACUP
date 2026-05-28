<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh">

        <!-- ── Panel izquierdo: branding FICCT ── -->
        <div
            class="hidden flex-col items-center justify-between px-12 py-10 lg:flex lg:w-1/2"
            style="background: linear-gradient(160deg, #060041 0%, #073b75 60%, #0a4060 100%);"
        >
            <!-- Logo + nombre -->
            <Link :href="home()" class="flex items-center gap-4 self-start">
                <img
                    src="/Escudo_FICCT.png"
                    alt="Escudo FICCT"
                    class="h-14 w-14 rounded-full object-contain"
                    onerror="this.style.display='none'"
                />
                <div class="leading-tight">
                    <span class="block text-xl font-bold tracking-wide text-white">SIGACUP</span>
                    <span class="block text-xs font-medium uppercase tracking-widest text-blue-300">
                        FICCT · UAGRM
                    </span>
                </div>
            </Link>

            <!-- Contenido central -->
            <div class="text-center">
                <!-- Patrón decorativo -->
                <div class="relative mx-auto mb-8 flex h-40 w-40 items-center justify-center">
                    <div class="absolute inset-0 rounded-full border border-white/10"></div>
                    <div class="absolute inset-4 rounded-full border border-white/10"></div>
                    <div class="absolute inset-8 rounded-full border border-white/10"></div>
                    <img
                        src="/Escudo_FICCT.png"
                        alt="FICCT"
                        class="relative z-10 h-20 w-20 rounded-full object-contain"
                        onerror="this.style.display='none'"
                    />
                </div>

                <h2 class="mb-3 text-2xl font-bold text-white">
                    Curso Preuniversitario
                </h2>
                <p class="mx-auto max-w-xs text-sm leading-relaxed text-blue-200/80">
                    Sistema de Gestión del Admisión al Curso Preuniversitario de la
                    Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones.
                </p>

                <!-- Divisor rojo -->
                <div class="mx-auto mt-6 h-1 w-12 rounded bg-[#c70e0a]"></div>

                <!-- Stats -->
                <div class="mt-8 flex justify-center gap-10 text-center">
                    <div>
                        <p class="text-2xl font-bold text-white">4</p>
                        <p class="text-xs text-blue-300">Carreras</p>
                    </div>
                    <div class="w-px bg-white/10"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">4</p>
                        <p class="text-xs text-blue-300">Materias</p>
                    </div>
                    <div class="w-px bg-white/10"></div>
                    <div>
                        <p class="text-2xl font-bold text-white">60</p>
                        <p class="text-xs text-blue-300">Pts. mín.</p>
                    </div>
                </div>
            </div>

            <!-- Footer del panel -->
            <p class="text-xs text-blue-400/50">
                © {{ new Date().getFullYear() }} UAGRM · Santa Cruz, Bolivia
            </p>
        </div>

        <!-- ── Panel derecho: formulario ── -->
        <div class="flex w-full flex-col items-center justify-center bg-white px-8 py-12 lg:w-1/2">
            <div class="w-full max-w-sm">

                <!-- Logo móvil (solo visible en pantallas pequeñas) -->
                <div class="mb-8 flex flex-col items-center gap-3 lg:hidden">
                    <Link :href="home()" class="flex items-center gap-3">
                        <img
                            src="/Escudo_FICCT.png"
                            alt="FICCT"
                            class="h-12 w-12 rounded-full object-contain"
                            onerror="this.style.display='none'"
                        />
                        <div>
                            <span class="block text-lg font-bold text-[#073b75]">SIGACUP</span>
                            <span class="block text-xs text-gray-400 uppercase tracking-widest">FICCT · UAGRM</span>
                        </div>
                    </Link>
                </div>

                <!-- Encabezado del formulario -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-[#073b75]">{{ title }}</h1>
                    <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
                    <div class="mt-3 h-1 w-10 rounded bg-[#c70e0a]"></div>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
