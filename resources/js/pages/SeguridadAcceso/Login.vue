<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Iniciar sesión',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Iniciar sesión" />

    <div v-if="status" class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-center text-sm text-green-700">
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-1.5">
            <Label for="username" class="text-sm font-medium text-gray-700">Usuario</Label>
            <Input
                id="username"
                type="text"
                name="username"
                required
                autofocus
                :tabindex="1"
                autocomplete="username"
                placeholder="nombre de usuario"
                class="border-gray-300 bg-white text-black placeholder:text-gray-400 focus:border-[#073b75] focus:ring-[#073b75]"
            />
            <InputError :message="errors.username" />
        </div>

        <div class="grid gap-1.5">
            <Label for="password" class="text-sm font-medium text-gray-700">Contraseña</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="Contraseña"
                class="border-gray-300 bg-white text-black placeholder:text-gray-400 focus:border-[#073b75] focus:ring-[#073b75]"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox id="remember" name="remember" :tabindex="3" />
            <Label for="remember" class="cursor-pointer text-sm font-normal text-gray-600">
                Recordarme
            </Label>
        </div>

        <Button
            type="submit"
            class="w-full font-bold text-white"
            style="background-color: #c70e0a;"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            Iniciar sesión
        </Button>

        <div class="text-center">
            <Link
                :href="home()"
                class="text-xs text-black transition hover:text-[#073b75]"
            >
                ← Volver al inicio
            </Link>
        </div>
    </Form>
</template>
