<script setup>
import { computed } from 'vue';
import AppSvg from '@/Components/core/AppSvg.vue';
import colors from '@/config/colors.js';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/buttons/PrimaryButton.vue';

defineEmits(['click']);
const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: null,
        validator: (value) => ['plain'].includes(value)
    },
    aspect: {
        type: String,
        default: null,
        validator: (value) => ['success', 'warning', 'info', 'danger'].includes(value)
    },
    icon: {
        type: String,
        default: null
    },
    label: {
        type: String,
        default: null
    },
    title: {
        type: String,
        default: null
    },
    radius: {
        type: String,
        default: null,
        validator: (value) => ['none', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    circle: {
        type: Boolean,
        default: null
    },
    stacked: {
        type: Boolean,
        default: null
    },
    loading: {
        type: Boolean,
        default: null
    },
    disabled: {
        type: Boolean,
        default: null
    },
    to: {
        type: String,
        default: null
    }
});
const border = computed(() => {
    if (props.variant === 'plain') {
        return;
    }

    return `border ${colors.getBorderColor(props.aspect)}`;
});
const borderRadius = computed(() => {
    if (props.circle) {
        return 'w-14 h-14 rounded-full';
    }

    const borders = {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-xl',
        full: 'rounded-full',
    }

    return borders[props.radius] || 'rounded';
});
const bgColor = computed(() => {
    return colors.getBgColor(props.aspect, 'bg-white dark:bg-gray-800');
});
const bgColorHover = computed(() => {
    return colors.getBgHoverColor(props.aspect);
})
const textColor = computed(() => {
    return colors.getTextColor(props.aspect, 'text-gray-700 dark:text-gray-300');
});
const flexDirection = computed(() => {
    if (props.stacked) {
        return 'flex flex-col';
    }

    return 'inline-flex';
});
const shadow = computed(() => {
    if (props.variant === 'plain') {
        return;
    }

    return 'shadow-sm';
});
const loadingClass = computed(() => props.loading ? 'opacity-25' : null);

const classes = computed(() => [flexDirection.value, bgColor.value, bgColorHover.value, border.value, textColor.value, borderRadius.value, shadow.value, loadingClass.value]);
</script>
<template>
    <button
        :title="title || label"
        :type="type"
        :class="classes"
        :disabled="disabled || loading"
        class="brand-font items-center px-2 py-1 lg:px-4 lg:py-2 font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150 space-x-3"
        @click="to ? router.visit(to) : $emit('click')"
    >
        <slot>
            <app-svg v-if="icon" :icon="icon" />
            <span v-if="label && !circle" v-text="label" />
        </slot>
        <slot name="modal" />
    </button>
</template>
