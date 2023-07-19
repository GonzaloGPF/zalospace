<script setup>
import AppSvg from '@/Components/core/AppSvg.vue';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: null
    },
    description: {
        type: String,
        default: null
    },
    icon: {
        type: String,
        default: null
    },
    image: {
        type: String,
        default: null
    },
    plain: {
        type: Boolean,
        default: null
    }
});
const imageSize = computed(() => props.image ? 'h-32 w-32' : 'h-16 w-16');
const bg = computed(() => {
    if (props.plain) {
        return;
    }

    return 'bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent'
});
const classes = computed(() => [imageSize.value, bg.value])
</script>
<template>
    <div
        :class="bg"
        class="flex items-center px-6 pb-4 dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 dark:shadow-none focus:outline focus:outline-2 focus:outline-blue-500"
    >
        <div
            v-if="icon || image"
            :class="imageSize"
            class="bg-blue-50 dark:bg-blue-800/20 flex items-center justify-center rounded-full mr-5 mb-5 md:mb-0"
        >
            <app-svg
                v-if="icon"
                :icon="icon"
                class="w-7 h-7 stroke-blue-500"
                stroke-width="1.5"
                fill="none"
            />
            <img
                v-if="image"
                :src="image"
                :alt="title"
                class="rounded-full"
            />
        </div>
        <div class="flex flex-col flex-1 text-gray-500 dark:text-gray-400 text-sm leading-relaxed">
            <h2
                v-if="title"
                class="brand-font mt-6 mb-3 text-xl font-semibold text-gray-900 dark:text-white"
                v-text="title"
            />
            <slot name="description">
                <p
                    class="mt-4 text-gray-500 dark:text-gray-400 text-sm leading-relaxed"
                    v-text="description"
                />
            </slot>
        </div>
    </div>
</template>
