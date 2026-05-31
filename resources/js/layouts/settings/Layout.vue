<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShieldCheck, UserCircle2 } from 'lucide-vue-next';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: (NavItem & { icon: typeof UserCircle2; description: string })[] = [
    {
        title: 'Perfil',
        href: editProfile(),
        icon: UserCircle2,
        description: 'Tu información personal y de cuenta',
    },
    {
        title: 'Seguridad',
        href: editSecurity(),
        icon: ShieldCheck,
        description: 'Contraseña, 2FA y passkeys',
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="flex flex-col gap-6 p-6">
        <!-- Encabezado -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Configuración</h1>
            <p class="mt-0.5 text-sm text-gray-500">Administra tu perfil y opciones de tu cuenta</p>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <!-- Sidebar -->
            <aside class="w-full lg:w-64 lg:shrink-0">
                <nav class="flex flex-col gap-1" aria-label="Configuración">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'group flex items-start gap-3 rounded-lg border px-3 py-2.5 text-left transition',
                            isCurrentOrParentUrl(item.href)
                                ? 'border-transparent text-white shadow-sm'
                                : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50',
                        ]"
                        :style="isCurrentOrParentUrl(item.href) ? { backgroundColor: '#060041' } : {}"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'mt-0.5 h-5 w-5 shrink-0',
                                isCurrentOrParentUrl(item.href) ? 'text-white' : 'text-[#073b75]',
                            ]"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold">{{ item.title }}</p>
                            <p
                                :class="[
                                    'mt-0.5 text-xs',
                                    isCurrentOrParentUrl(item.href) ? 'text-blue-200' : 'text-gray-400',
                                ]"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </Link>
                </nav>
            </aside>

            <!-- Contenido -->
            <div class="min-w-0 flex-1">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <section class="max-w-2xl space-y-12">
                        <slot />
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
