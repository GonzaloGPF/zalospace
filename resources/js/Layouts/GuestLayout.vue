<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div>
            <Link href="/">
                <ApplicationLogo class="w-20 h-20 fill-current text-gray-500" />
            </Link>
        </div>

        <transition name="page" mode="out-in" appear>
            <div
                :key="$page.url"
                class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg"
            >
                <slot />
            </div>
        </transition>
    </div>
</template>
<style>
.page-enter-active,
.page-leave-active {
    transition: all .1s;
}

.page-enter,
.page-leave-active {
    opacity: 0;
}
</style>
