<script setup lang="ts">
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const emit = defineEmits<{
    success: [];
}>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;

    const browser = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'].find(
        (browser) => new RegExp(browser).test(ua),
    );

    const os = ['iPhone', 'iPad', 'Android', 'Mac', 'Windows'].find((os) =>
        new RegExp(os).test(ua),
    );

    return [browser, os].filter(Boolean).join(' on ') || '';
};

const name = ref(getDefaultPasskeyName());
const showForm = ref(false);

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        showForm.value = false;
        emit('success');
    },
});

const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!name.value.trim()) {
        return;
    }

    await register(name.value);
};

const handleCancel = () => {
    showForm.value = false;
    name.value = '';
};
</script>

<template>
    <div v-if="!isSupported" class="text-sm text-muted-foreground">
        Tu navegador no soporta passkeys.
    </div>

    <Button v-else-if="!showForm" variant="outline" @click="showForm = true">
        Agregar passkey
    </Button>

    <form
        v-else
        @submit="handleSubmit"
        class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4"
    >
        <div class="grid gap-2">
            <Label for="passkey-name">Nombre de la passkey</Label>
            <Input
                id="passkey-name"
                type="text"
                v-model="name"
                placeholder="Ej.: MacBook Pro, iPhone"
                class="mt-1 block w-full border-foreground/20"
                autofocus
            />
            <p class="text-xs text-muted-foreground">
                El nombre te ayuda a identificar la passkey luego.
            </p>
        </div>

        <InputError v-if="error" :message="error" />

        <div class="flex gap-2">
            <Button type="submit" :disabled="isLoading || !name.trim()">
                {{ isLoading ? 'Registrando…' : 'Registrar passkey' }}
            </Button>
            <Button type="button" variant="ghost" @click="handleCancel">
                Cancelar
            </Button>
        </div>
    </form>
</template>
