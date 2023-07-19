<script setup>
import Translator from '@/objects/Translator.js';
import TextInput from '@/Components/inputs/TextInput.vue';
import InputError from '@/Components/inputs/InputError.vue';
import InputLabel from '@/Components/inputs/InputLabel.vue';
import { useForm } from '@inertiajs/vue3';
import AppButton from '@/Components/core/AppButton.vue';
import { computed } from 'vue';
import TextArea from '@/Components/inputs/TextArea.vue';

const form = useForm({
    name: '',
    email: '',
    message: '',
});

const title = computed(() => Translator.actionTitle('contact'))

const submit = () => {
    form.post(route('contacts.store'));
};
</script>
<template>
    <form @submit.prevent="submit" class="flex flex-col justify-center text-gray-500 dark:text-gray-400">
        <div>
            <InputLabel for="name" :value="Translator.ta('name')" />

            <TextInput
                id="name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.name"
                required
                autofocus
                autocomplete="name"
            />

            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div class="mt-4">
            <InputLabel for="email" :value="Translator.ta('email')" />

            <TextInput
                id="email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.email"
                required
                autocomplete="username"
            />

            <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div class="mt-4">
            <InputLabel for="message" :value="Translator.ta('message')" />

            <TextArea
                id="message"
                type="text"
                class="mt-1 block w-full"
                v-model="form.message"
                required
            />

            <InputError class="mt-2" :message="form.errors.message" />
        </div>

        <app-button
            type="submit"
            :label="title"
            :loading="form.processing"
            class="mx-auto mt-5"
        />
    </form>
</template>
