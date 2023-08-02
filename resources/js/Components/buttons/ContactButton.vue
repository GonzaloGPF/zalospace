<script setup>
import AppButton from '@/Components/core/AppButton.vue';
import Translator from '@/objects/Translator.js';
import { computed } from 'vue';
import Utils from '@/objects/Utils.js';
import useTailwind from '@/composables/useTailwind.js';

const { isLg } = useTailwind();
const label = computed(() => isLg.value ? Translator.actionTitle('contact') : null);
const canShow = computed(() => !Utils.isSamePath(route('contacts.create')));
</script>
<template>
  <app-button
    v-if="canShow"
    :title="label"
    :label="label"
    :to="route('contacts.create')"
    icon="email"
  />
</template>
