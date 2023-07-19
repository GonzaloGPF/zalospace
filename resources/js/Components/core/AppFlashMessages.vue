<script setup>
import AppAlert from '@/Components/core/AppAlert.vue';
import { useFlashMessages } from '@/stores/flashMessages';
import { storeToRefs } from 'pinia';
import { usePage } from '@inertiajs/vue3';

const { props } = usePage();

const { pushFlashMessage } = useFlashMessages();
const { flashMessages } = storeToRefs(useFlashMessages());

if (props.flash_message_data) {
    pushFlashMessage(props.flash_message_data);
}
</script>
<template>
    <div
        v-if="flashMessages && flashMessages.length"
        class="messages-container"
    >
        <TransitionGroup
            name="message"
            tag="ul"
            appear
            class="flex justify-center"
            style="list-style: none; position: relative; min-width: 300px; min-height: 300px"
        >
            <li
                v-for="flashMessage in flashMessages"
                :key="flashMessage.message"
                class="my-2"
            >
                <app-alert
                    :title="flashMessage.title"
                    :message="flashMessage.message"
                    :type="flashMessage.type"
                    :closable="flashMessage.closable"
                    :border="flashMessage.closable"
                    :icon="flashMessage.icon"
                    style="width: 300px"
                />
            </li>
        </TransitionGroup>
    </div>
</template>
<style>
.messages-container {
    position: fixed;
    top: 15px;
    left: 0;
    right: 0;
    width: 500px;
    margin: auto;
    max-width: calc(100% - 30px);
    z-index: 9999;
    overflow: hidden;
    transition: all 0.5s ease;
}

.message-move,
.message-enter-active,
.message-leave-active {
    transition: all 0.5s ease;
}

.message-enter-from,
.message-leave-to {
    opacity: 0;
    transform: translateY(-50px);
}

.message-leave-active {
    position: absolute;
}
</style>
