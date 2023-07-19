<script setup>
import { computed } from 'vue';
import AppSvg from '@/Components/core/AppSvg.vue';
import SecondaryButton from '@/Components/buttons/SecondaryButton.vue';
import colors from '@/config/colors.js';

defineEmits(['close']);
const props = defineProps({
    title: {
        type: String,
        default: null
    },
    message: {
        type: String,
        default: null
    },
    type: {
        type: String,
        default: null
    },
    closable: {
        type: String,
        default: null
    },
    border: {
        type: String,
        default: null
    },
    icon: {
        type: String,
        default: 'info'
    },
});
const bgColor = computed(() => colors.getBgColor(props.type));
const textColor = computed(() => colors.getTextColor(props.type));
const classes = computed(() => ({
    border: props.border,
    [bgColor.value]: true,
    [textColor.value]: true,
}));
</script>
<template>
<div :class="classes" class="flex items-center p-4 rounded-lg space-x-3">
    <app-svg v-if="icon" :icon="icon" />
    <div class="grow">
        <span
            v-if="title"
            class="font-bold"
            v-text="title"
        />
        <span
            v-if="message"
            v-text="message"
        />
    </div>
    <secondary-button
        v-if="closable"
        variant="plain"
        :class="classes"
        @click="$emit('close')"
    >
        X
    </secondary-button>
</div>
</template>
