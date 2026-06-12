<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    BarChart3,
    BookOpen,
    CalendarRange,
    ClipboardCheck,
    ClipboardList,
    FileText,
    GraduationCap,
    KeyRound,
    LayoutGrid,
    Presentation,
    ScrollText,
    Settings,
    ShieldCheck,
    Sparkles,
    UserCheck,
    UserCog,
    Users2,
} from 'lucide-vue-next';
import { computed } from 'vue';
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
import type { Auth, NavGroup, NavItem } from '@/types';

const page = usePage<{ auth: Auth }>();

const dashboardItem: NavItem = {
    title: 'Inicio',
    href: dashboard(),
    icon: LayoutGrid,
};

const allGroups: NavGroup[] = [
    {
        title: 'Administración del sistema',
        icon: Settings,
        items: [
            {
                title: 'Gestiones académicas',
                href: '/administracion/gestiones',
                icon: CalendarRange,
                permiso: 'gestiones.gestionar',
            },
        ],
    },
    {
        title: 'Registro e inscripción',
        icon: ClipboardList,
        items: [
            {
                title: 'Estudiantes',
                href: '/administracion/estudiantes',
                icon: GraduationCap,
                permiso: 'estudiantes.gestionar',
            },
        ],
    },
    {
        title: 'Organización académica',
        icon: BookOpen,
        items: [
            {
                title: 'Grupos',
                href: '/administracion/grupos',
                icon: Users2,
                permiso: 'grupos.gestionar',
            },
            {
                title: 'Docentes',
                href: '/administracion/docentes',
                icon: Presentation,
                permiso: 'docentes.gestionar',
            },
        ],
    },
    {
        title: 'Evaluación y admisión',
        icon: ClipboardCheck,
        items: [
            {
                title: 'Calificaciones',
                href: '/administracion/calificaciones',
                icon: ClipboardList,
                permiso: 'calificaciones.gestionar',
            },
            {
                title: 'Admisión de candidatos',
                href: '/administracion/admision',
                icon: UserCheck,
                permiso: 'admision.gestionar',
            },
            {
                title: 'Proceso de admisión',
                href: '/administracion/proceso-admision',
                icon: Award,
                permiso: 'proceso_admision.gestionar',
            },
        ],
    },
    {
        title: 'Reportes y notificaciones',
        icon: BarChart3,
        items: [
            {
                title: 'Reportes',
                href: '/administracion/reportes',
                icon: FileText,
                permiso: 'reportes.ver',
            },
            {
                title: 'Asistente IA',
                href: '/administracion/reportes/ia',
                icon: Sparkles,
                permiso: 'reportes.ver',
            },
        ],
    },
    {
        title: 'Seguridad y acceso',
        icon: ShieldCheck,
        items: [
            {
                title: 'Usuarios',
                href: '/administracion/usuarios',
                icon: UserCog,
                permiso: 'usuarios.gestionar',
            },
            {
                title: 'Roles y permisos',
                href: '/administracion/roles',
                icon: KeyRound,
                permiso: 'roles.gestionar',
            },
            {
                title: 'Bitácora',
                href: '/administracion/bitacora',
                icon: ScrollText,
                permiso: 'bitacora.ver',
            },
        ],
    },
];

const navGroups = computed<NavGroup[]>(() => {
    const { permisos = [], esAdmin = false } = page.props.auth ?? {};

    const puedeVer = (item: NavItem) =>
        !item.permiso || esAdmin || permisos.includes(item.permiso);

    return allGroups
        .map((grupo) => ({
            ...grupo,
            items: grupo.items.filter(puedeVer),
        }))
        .filter((grupo) => grupo.items.length > 0);
});
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
            <NavMain :items="[dashboardItem]" :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
