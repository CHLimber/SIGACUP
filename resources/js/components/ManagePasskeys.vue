<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <section v-if="canManagePasskeys" class="space-y-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900">Llaves de acceso (passkeys)</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Inicia sesión sin contraseña usando tu dispositivo o llave de seguridad.
            </p>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-100">
            <template v-if="passkeys.length">
                <PasskeyItem
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    :passkey="passkey"
                    @remove="handleDelete"
                />
            </template>

            <div v-else class="p-8 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100"
                >
                    <KeyRound class="h-6 w-6 text-gray-400" />
                </div>
                <p class="font-medium text-gray-700">Sin llaves de acceso</p>
                <p class="mt-1 text-sm text-gray-500">
                    Agrega una passkey para iniciar sesión sin contraseña.
                </p>
            </div>
        </div>

        <PasskeyRegister @success="handleRegisterSuccess" />
    </section>
</template>
