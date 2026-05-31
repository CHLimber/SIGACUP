<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <section v-if="canManageTwoFactor" class="space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Autenticación en dos pasos (2FA)</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Agrega una capa adicional de seguridad solicitando un código al iniciar sesión.
            </p>
        </div>

        <div
            v-if="!twoFactorEnabled"
            class="flex flex-col items-start gap-3 rounded-lg border border-gray-100 bg-gray-50 p-4"
        >
            <p class="text-sm text-gray-600">
                Al activar 2FA, se te pedirá un código numérico al iniciar sesión. Necesitas una app
                compatible con TOTP (Google Authenticator, Authy, 1Password, etc.).
            </p>

            <Button
                v-if="hasSetupData"
                class="text-white hover:brightness-110"
                style="background-color: #060041;"
                @click="showSetupModal = true"
            >
                <ShieldCheck class="mr-1.5 h-4 w-4" />
                Continuar configuración
            </Button>
            <Form
                v-else
                v-bind="enable.form()"
                @success="showSetupModal = true"
                #default="{ processing }"
            >
                <Button
                    type="submit"
                    :disabled="processing"
                    class="text-white hover:brightness-110"
                    style="background-color: #060041;"
                >
                    <ShieldCheck class="mr-1.5 h-4 w-4" />
                    Activar 2FA
                </Button>
            </Form>
        </div>

        <div v-else class="flex flex-col items-start gap-3 rounded-lg border border-green-100 bg-green-50 p-4">
            <p class="text-sm text-green-800">
                La autenticación en dos pasos está <span class="font-semibold">activada</span> en tu cuenta.
            </p>

            <Form v-bind="disable.form()" #default="{ processing }">
                <Button variant="destructive" type="submit" :disabled="processing">
                    Desactivar 2FA
                </Button>
            </Form>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </section>
</template>
