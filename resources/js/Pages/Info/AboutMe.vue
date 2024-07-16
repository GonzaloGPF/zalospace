<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue';
import Translator from '@/objects/Translator.js';
import AppPanel from '@/Components/core/AppPanel.vue';
import { computed } from 'vue';
import Time from '@/objects/Time.js';
import AppCard from '@/Components/core/AppCard.vue';
import AppTimeline from '@/Components/core/AppTimeline.vue';
import jobs from '@/data/jobs.js';
import technologies from '@/data/technologies.js';
import TechList from '@/Components/TechList.vue';

const age = computed(() => {
    const now = new Date();
    const birthdate = new Date('1989-10-01');

    return Time.differenceInYears(now, birthdate);
});

</script>
<template>
    <base-layout
        :title="Translator.tl('about_me')"
        :description="Translator.t('help.about_me_description')"
        icon="user"
    >
        <div class="flex flex-col md:flex-row justify-between items-center mb-16">
            <app-panel
                :title="Translator.tl('who_am_i')"
                image="/images/profile_photo.jpg"
            >
                <template #description>
                    <p v-text="Translator.t('help.who_am_i_1', { age })" />
                    <p v-text="Translator.t('help.who_am_i_2')" />
                    <p v-text="Translator.t('help.who_am_i_3')" />
                </template>
            </app-panel>
            <app-card
                :title="Translator.tl('hobbies')"
                class="md:w-80"
                shine
            >
                <template #description>
                    <p v-text="Translator.t('help.hobbies_1')" />
                    <ul class="list-disc list-inside mt-3">
                        <li class="" v-text="Translator.tl('music_production')" />
                        <li class="" v-text="Translator.tl('motorbike')" />
                        <li class="" v-text="Translator.tl('gym')" />
                    </ul>
                </template>
            </app-card>
        </div>
        <div class="flex flex-col md:flex-row justify-between items-center mb-16">
            <app-card
                :title="Translator.tl('my_jobs')"
                class="md:w-96"
                shine
            >
                <template #description>
                    <p v-text="Translator.t('help.my_jobs')" />
                    <ul class="list-disc list-inside mt-3">
                        <li class="" v-text="Translator.tl('fullstack_developer')" />
                        <li class="" v-text="Translator.tl('consultor')" />
                        <li class="" v-text="Translator.tl('team_lead')" />
                    </ul>
                </template>
            </app-card>
            <app-panel
                :title="Translator.tl('professional_profile')"
                class="max-w-5xl"
            >
                <template #description>
                    <p v-text="Translator.t('help.professional_profile_1')" />
                    <p v-text="Translator.t('help.professional_profile_2')" />
                    <p v-text="Translator.t('help.professional_profile_3')" />
                </template>
            </app-panel>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-16">
            <app-panel
                :title="Translator.tl('academic_training')"
                class="mt-5"
            >
                <template #description>
                    <p v-text="Translator.t('help.academic_training_1')" />
                    <p v-text="Translator.t('help.academic_training_2')" />
                    <p v-text="Translator.t('help.academic_training_3')" />
                </template>
            </app-panel>
            <app-card
                :title="'Laravel'"
                class="md:w-80"
                shine
            >
                <template #description>
                    <p v-text="Translator.t('help.laracasts')" />
                </template>
            </app-card>
        </div>

        <app-panel
            :title="Translator.tl('technologies')"
            class="mt-5"
        >
            <template #description>
                <p
                    class="mb-5"
                    v-text="Translator.t('help.technologies')"
                />
                <tech-list
                    :technologies="technologies"
                    :limit="null"
                />
            </template>
        </app-panel>

        <app-panel
            :title="Translator.tl('experience')"
            class="mt-5"
        >
            <template #description>
                <p
                    class="mb-3"
                    v-text="Translator.t('help.experience')"
                />
                <app-timeline
                    :items="jobs"
                    class="w-100"
                />
            </template>
        </app-panel>
    </base-layout>
</template>
