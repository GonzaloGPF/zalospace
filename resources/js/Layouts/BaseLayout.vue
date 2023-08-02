<script setup>
import AppFlashMessages from '@/Components/core/AppFlashMessages.vue';
import AppNavigation from '@/Components/core/AppNavigation.vue';
import AppFooter from '@/Components/core/AppFooter.vue';
import ContactButton from '@/Components/buttons/ContactButton.vue';
import { Head } from '@inertiajs/vue3';
import AppSvg from '@/Components/core/AppSvg.vue';
import AppLink from '@/Components/core/AppLink.vue';
import AppCard from '@/Components/core/AppCard.vue';
import globalConfig from '@/config/globalConfig.js';
import LanguageButton from '@/Components/buttons/LanguageButton.vue';

defineProps({
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
});
</script>
<template>
    <div
        class="relative sm:flex flex-col sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-blue-800 selection:text-white">
        <Head
            v-if="title"
            :title="title"
        />
        <app-flash-messages />
        <div
            class="flex items-center dark:border-gray-700 fixed top-0 right-0 text-right bg-blue-950 dark:bg-gray-800 w-full px-2 md:px-5 z-10 drop-shadow-md">
            <app-link
                :href="route('welcome')"
                :title="globalConfig.getAppName()"
                class="hidden md:block"
            >
                <app-svg
                    icon="logo"
                    :height="300"
                    :width="300"
                    :scale-x=".125"
                    :scale-y=".125"
                    color="#0e4178ff"
                    class="rounded-full bg-white mr-1"
                />
            </app-link>
            <app-navigation class="flex-1" />
            <contact-button class="my-1 mr-2" />
            <language-button class="my-1" />
        </div>
        <!-- Page Heading -->
        <header v-if="description" class="pt-5 mt-6 md:mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <app-card
                    :title="title"
                    :description="description"
                    :icon="icon"
                />
            </div>
        </header>
        <Transition name="page" mode="out-in" enter-from-class="opacity-0" leave-to-class="opacity-0"
                    class="transition ease-in-out duration-500" appear>
            <main :key="$page.url" class="container mx-auto px-6 lg:px-8 mb-auto text-gray-900 dark:text-white pt-16">
                <slot />
            </main>
        </Transition>
        <app-footer />
    </div>
</template>
<style>
.page-enter-active,
.page-leave-active {
    transition: all 1s;
}

.page-enter,
.page-leave-active {
    opacity: 0;
}
</style>
