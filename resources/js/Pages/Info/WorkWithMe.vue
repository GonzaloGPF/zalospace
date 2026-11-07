<script setup>
import { computed } from 'vue'
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Translator from '@/objects/Translator.js'
import AppCard from '@/Components/core/AppCard.vue'
import AppButton from '@/Components/core/AppButton.vue'
import AppLink from '@/Components/core/AppLink.vue'
import AppTimeLineItem from '@/Components/core/AppTimeLineItem.vue'
import projects from '@/data/projects.js'

const featuredProjects = computed(() =>
  projects.value.filter((project) => project.featured)
)
</script>
<template>
  <BaseLayout
    :title="Translator.tl('work_with_me')"
    :description="Translator.t('help.work_with_me_description')"
    icon="work"
  >
    <p
      class="max-w-2xl mx-auto text-center text-gray-500 dark:text-gray-400"
      v-text="Translator.t('help.work_with_me_intro')"
    />

    <AppCard
      :title="Translator.tl('services')"
      class="max-w-2xl mx-auto mt-10"
      shine
    >
      <template #description>
        <ul class="list-disc list-inside mt-3 space-y-1">
          <li v-text="Translator.t('help.work_with_me_service_1')" />
          <li v-text="Translator.t('help.work_with_me_service_2')" />
          <li v-text="Translator.t('help.work_with_me_service_3')" />
          <li v-text="Translator.t('help.work_with_me_service_4')" />
          <li v-text="Translator.t('help.work_with_me_service_5')" />
        </ul>
      </template>
    </AppCard>

    <div class="max-w-5xl mx-auto mt-16">
      <h2
        class="brand-font text-xl font-semibold text-center text-gray-900 dark:text-white"
        v-text="Translator.tl('featured_projects')"
      />
      <p
        class="mt-3 text-center text-gray-500 dark:text-gray-400"
        v-text="Translator.t('help.featured_projects_intro')"
      />
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <AppTimeLineItem
          v-for="project in featuredProjects"
          :key="project.title"
          :title="project.title"
          :subtitle="project.subtitle"
          :url="project.url"
          :url-label="project.title"
          :image="project.image"
          :paragraphs="[project.paragraphs[0]]"
          :technologies="project.technologies"
        />
      </div>
      <div class="flex justify-center mt-6">
        <AppLink :href="route('info.projects')">
          {{ Translator.tl('view_all_projects') }}
        </AppLink>
      </div>
    </div>

    <AppCard
      :title="Translator.tl('availability')"
      class="max-w-2xl mx-auto mt-16"
      shine
    >
      <template #description>
        <p v-text="Translator.t('help.work_with_me_availability')" />
      </template>
    </AppCard>

    <div class="flex justify-center mt-10">
      <AppButton
        :to="route('contacts.create')"
        :label="Translator.actionTitle('contact')"
        icon="email"
      />
    </div>
  </BaseLayout>
</template>
