<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inicio', href: dashboard() },
            { title: 'Configuración', href: editProfile() },
            { title: 'Seguridad', href: edit() },
        ],
    },
});
</script>

<template>
    <Head title="Seguridad" />

    <h1 class="sr-only">Configuración de seguridad</h1>

    <!-- Contraseña -->
    <section class="space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Cambiar contraseña</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Usa una contraseña larga y aleatoria para mantener tu cuenta segura.
            </p>
        </div>

        <Form
            v-bind="SecurityController.update.form()"
            :options="{ preserveScroll: true }"
            reset-on-success
            :reset-on-error="['password', 'password_confirmation', 'current_password']"
            class="space-y-5"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Contraseña actual</Label>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Contraseña actual"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Nueva contraseña</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Nueva contraseña"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar nueva contraseña</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Repite la nueva contraseña"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-3">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                    class="text-white hover:brightness-110"
                    style="background-color: #060041;"
                >
                    Guardar contraseña
                </Button>
            </div>
        </Form>
    </section>

    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />

    <ManagePasskeys :canManagePasskeys="canManagePasskeys" :passkeys="passkeys" />
</template>
