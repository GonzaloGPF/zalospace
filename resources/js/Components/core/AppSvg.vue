<script setup>
import { computed } from "vue";
import icons from '@/config/icons.js';

const props = defineProps({
    vector: {
        type: String,
        default: null,
    },
    icon: {
        type: String,
        default: null,
    },
    width: {
        type: Number,
        default: 24
    },
    height: {
        type: Number,
        default: 24
    },
    viewBox: {
        type: String,
        default: '0 0 24 24'
    },
    scaleX: {
        type: Number,
        default: 1
    },
    scaleY: {
        type: Number,
        default: 1
    },
    fill:{
        type: String,
        default: 'currentColor'
    }
});
const style = computed(() => ({
    width: `${ props.width * props.scaleX }px`,
    height: `${ props.height * props.scaleY }px`,
}));
const viewBox = computed(() => `0 0 ${props.width} ${props.height}`);
</script>
<template>
    <svg
        :viewBox="viewBox"
        :style="style"
        xmlns="http://www.w3.org/2000/svg"
    >
        <slot>
            <path
                :fill="fill"
                :d="vector ?? icons[icon]"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </slot>
    </svg>
</template>
