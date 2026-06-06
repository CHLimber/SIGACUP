<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Configuración', href: edit() },
            { title: 'Perfil', href: edit() },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const rolLabel: Record<string, string> = {
    administrador: 'Administrador',
    coordinador:   'Coordinador',
    docente:       'Docente',
    autoridad:     'Autoridad',
    estudiante:    'Estudiante',
};

function fmtFecha(fecha: string | null | undefined): string {
    if (!fecha) {
return '—';
}

    const d = new Date(fecha);

    if (Number.isNaN(d.getTime())) {
return '—';
}

    return d.toLocaleDateString('es-BO', { year: 'numeric', month: 'long', day: 'numeric' });
}
</script>

<template>
    <Head title="Perfil" />

    <h1 class="sr-only">Configuración de perfil</h1>

    <!-- Resumen de la cuenta -->
    <section class="space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Información de la cuenta</h2>
            <p class="mt-0.5 text-sm text-gray-500">Datos asignados a tu usuario en el sistema</p>
        </div>

        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white"
                    style="background-color: #073b75;"
                >
                    {{ (user?.name ?? '?').charAt(0).toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ user?.name }}</p>
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                            style="background-color: #c70e0a;"
                        >
                            {{ rolLabel[user?.role as string] ?? user?.role }}
                        </span>
                    </div>
                    <p class="mt-0.5 truncate text-xs text-gray-500">{{ user?.email }}</p>
                    <p class="mt-2 text-xs text-gray-400">
                        Cuenta creada el <span class="font-medium text-gray-600">{{ fmtFecha(user?.created_at) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Editar perfil -->
    <section class="space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Editar perfil</h2>
            <p class="mt-0.5 text-sm text-gray-500">Actualiza tu nombre y correo electrónico</p>
        </div>

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nombre completo</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Nombre completo"
                />
                <InputError class="mt-1" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="correo@dominio.com"
                />
                <InputError class="mt-1" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at" class="rounded-md border border-yellow-200 bg-yellow-50 p-3">
                <p class="text-sm text-yellow-800">
                    Tu correo electrónico no está verificado.
                    <Link
                        :href="send()"
                        as="button"
                        class="font-medium text-yellow-900 underline decoration-yellow-400 underline-offset-2 hover:decoration-yellow-700"
                    >
                        Reenviar el enlace de verificación
                    </Link>
                </p>
                <p
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-xs font-medium text-green-700"
                >
                    Se envió un nuevo enlace de verificación a tu correo.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                    class="text-white hover:brightness-110"
                    style="background-color: #060041;"
                >
                    Guardar cambios
                </Button>
            </div>
        </Form>
    </section>

    <DeleteUser />
</template>
