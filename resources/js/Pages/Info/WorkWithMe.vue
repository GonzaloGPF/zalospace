<script setup>
import { computed } from 'vue'
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Translator from '@/objects/Translator.js'
import AppCard from '@/Components/core/AppCard.vue'
import AppButton from '@/Components/core/AppButton.vue'
import AppLink from '@/Components/core/AppLink.vue'
import AppTimeLineItem from '@/Components/core/AppTimeLineItem.vue'
import FaqItem from '@/Components/FaqItem.vue'
import projects from '@/data/projects.js'

const featuredProjects = computed(() =>
  projects.value.filter((project) => project.featured)
)

const processSteps = computed(() => [
  {
    title: Translator.tl('process_idea'),
    description: Translator.t('help.process_idea_description'),
  },
  {
    title: Translator.tl('process_planning'),
    description: Translator.t('help.process_planning_description'),
  },
  {
    title: Translator.tl('process_building'),
    description: Translator.t('help.process_building_description'),
  },
  {
    title: Translator.tl('process_delivery'),
    description: Translator.t('help.process_delivery_description'),
  },
])

const faqs = computed(() => [
  {
    question: Translator.t('help.faq_billing_q'),
    answer: Translator.t('help.faq_billing_a'),
  },
  {
    question: Translator.t('help.faq_availability_q'),
    answer: Translator.t('help.faq_availability_a'),
  },
  {
    question: Translator.t('help.faq_minimum_q'),
    answer: Translator.t('help.faq_minimum_a'),
  },
  {
    question: Translator.t('help.faq_remote_q'),
    answer: Translator.t('help.faq_remote_a'),
  },
  {
    question: Translator.t('help.faq_start_q'),
    answer: Translator.t('help.faq_start_a'),
  },
])
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

    <div class="flex justify-center mt-6">
      <AppButton
        :to="route('info.cv')"
        :label="Translator.tl('my_cv')"
        icon="user"
      />
    </div>

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

    <div class="max-w-5xl mx-auto mt-16">
      <h2
        class="brand-font text-xl font-semibold text-center text-gray-900 dark:text-white"
        v-text="Translator.tl('development_process')"
      />
      <p
        class="mt-3 text-center text-gray-500 dark:text-gray-400"
        v-text="Translator.t('help.process_intro')"
      />
      <div class="mt-8 relative grid grid-cols-1 md:grid-cols-4 gap-8">
        <div
          class="hidden md:block absolute top-6 left-0 right-0 h-px bg-gray-300 dark:bg-gray-600"
        />
        <div
          v-for="(step, index) in processSteps"
          :key="step.title"
          class="relative z-10 flex flex-col items-center text-center"
        >
          <div
            class="brand-font flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-800/20 text-blue-500 font-semibold text-lg"
          >
            {{ index + 1 }}
          </div>
          <h3
            class="mt-4 font-semibold text-gray-900 dark:text-white"
            v-text="step.title"
          />
          <p
            class="mt-2 text-sm text-gray-500 dark:text-gray-400"
            v-text="step.description"
          />
        </div>
      </div>
    </div>

    <div class="max-w-2xl mx-auto mt-16">
      <h2
        class="brand-font text-xl font-semibold text-center text-gray-900 dark:text-white"
        v-text="Translator.tl('faq')"
      />
      <div class="mt-6">
        <FaqItem
          v-for="faq in faqs"
          :key="faq.question"
          :question="faq.question"
          :answer="faq.answer"
        />
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
