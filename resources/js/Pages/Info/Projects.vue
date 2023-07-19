<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue';
import Translator from '@/objects/Translator.js';
import projects from '@/data/projects.js';
import AppTimeline from '@/Components/core/AppTimeline.vue';
</script>
<template>
    <base-layout
        :title="Translator.tl('projects')"
        :description="Translator.t('help.projects_description')"
        icon="projects"
    >
        <app-timeline
            :items="projects"
            class="w-100"
        />
    </base-layout>
</template>
