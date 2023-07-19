<script setup>
import AppButton from '@/Components/core/AppButton.vue';
import Translator from '@/objects/Translator.js';
import { computed, ref } from 'vue';
import Utils from '@/objects/Utils.js';

const show = ref(false);
const label = computed(() => Translator.actionTitle('contact'));
const canShow = computed(() => !Utils.isSamePath(route('contacts.create')));
</script>
<template>
    <app-button
        v-if="canShow"
        :label="label"
        :to="route('contacts.create')"
        icon="email"
    />
</template>
