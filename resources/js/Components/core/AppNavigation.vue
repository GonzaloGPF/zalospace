<script setup>
import AppLink from '@/Components/core/AppLink.vue';
import Translator from '@/objects/Translator.js';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppSvg from '@/Components/core/AppSvg.vue';
import Utils from '@/objects/Utils.js';
import AppButton from '@/Components/core/AppButton.vue';
import useTailwind from '@/composables/useTailwind.js';
import { default as vClickOutside } from '@/directives/clickOutside.js';

defineProps({
    canLogin: {
        type: Boolean,
        default: false
    },
    canRegister: {
        type: Boolean,
        default: false
    }
});
const { isMobile, isResizing } = useTailwind(); // TODO: for some reason, only at this component, useTailwind is not reactive
const { props } = usePage();
const items = computed(() => [
    {
        href: route('welcome'),
        label: Translator.tl('home'),
        icon: 'home',
        visible: () => isMobile.value
    },
    {
        href: route('info.about_me'),
        label: Translator.tl('about_me'),
        icon: 'user'
    },
    {
        href: route('info.projects'),
        label: Translator.tl('projects'),
        icon: 'projects'
    },
    {
        href: route('info.tutorials'),
        label: Translator.tl('tutorials'),
        icon: 'tutorials',
    },
    {
        href: route('info.configurator'),
        label: Translator.tl('configurator'),
        icon: 'configurator',
    },
    // {
    //     href: route('register'),
    //     label: Translator.actionTitle('register'),
    //     visible: !props.auth?.user
    // },
    // {
    //     href: route('contacts.create'),
    //     label: Translator.actionTitle('contact'),
    //     icon: 'email',
    //     visible: Utils.isSamePath(route('contacts.create')),
    //     class: 'px-4 py-2 font-semibold text-xs uppercas'
    // },
].filter(item => {
    if ('visible' in item) {
        if (typeof item.visible === 'function') {
            return typeof item.visible === 'function'
                ? item.visible()
                : item.visible;
        } else {
            return item.visible;
        }
    }
    return true;
}));
const openMenu = ref(false);

const showMenu = computed(() => {
    if (isMobile.value) {
        return openMenu.value;
    }
    return true;
});
</script>
<template>
  <div
    v-click-outside
    class="flex"
    @click-outside="openMenu = false"
  >
    <app-button
      icon="menu"
      class="md:hidden"
      variant="plain"
      @click="openMenu = !openMenu"
    />
    <teleport to="body">
      <transition
        v-if="isMobile"
        enter-active-class="ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-show="openMenu" class="fixed inset-0 transform transition-all" @click.stop="openMenu = false" style="pointer-events: none">
          <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75" />
        </div>
      </transition>
    </teleport>
    <transition
      :css="!isResizing"
      enter-active-class="ease-out duration-200"
      enter-from-class="opacity-0 -translate-x-4"
      enter-to-class="opacity-100 -translate-x-0"
      leave-active-class="ease-in duration-200"
      leave-from-class="opacity-100 -translate-x-0"
      leave-to-class="opacity-0 -translate-x-4"
    >
      <div
        v-if="showMenu"
        v-click-outside
        class="flex flex-col md:flex-row absolute md:static left-0 bg-blue-950 dark:bg-gray-800 text-xs md:text-sm lg:text-base"
        style="top: 42px;"
      >
        <app-link
          v-for="item in items"
          :key="item.href"
          :href="item.href"
          :label="item.label"
          :class="Utils.isSamePath(item.href) ? 'border-b-2' : null"
          class="flex md:items-center space-x-2 text-white dark:text-gray-500 p-3 border-white-700 dark:border-white-300 px-4 whitespace-nowrap"
        >
          <app-svg
            v-if="item.icon"
            :icon="item.icon"
          />
          <span
            v-if="item.label"
            class="brand-font"
            v-text="item.label"
          />
        </app-link>
      </div>
    </transition>
  </div>
</template>
