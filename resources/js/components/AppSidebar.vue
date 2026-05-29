<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    CreditCard,
    FileText,
    LayoutGrid,
    Settings,
    UserCheck,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Inicio',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Inscripción y Pagos',
        href: dashboard(),
        icon: CreditCard,
    },
    {
        title: 'Organización Académica',
        href: dashboard(),
        icon: BookOpen,
    },
    {
        title: 'Evaluación y Resultados',
        href: dashboard(),
        icon: BarChart3,
    },
    {
        title: 'Admisión de candidatos',
        href: '/administracion/admision',
        icon: UserCheck,
    },
    {
        title: 'Reportes',
        href: dashboard(),
        icon: FileText,
    },
    {
        title: 'Administración',
        href: '/administracion/gestiones',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
