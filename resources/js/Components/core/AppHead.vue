<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: null,
  },
  description: {
    type: String,
    default: null,
  },
})

const page = usePage()
const pageTitle = computed(() =>
  props.title ? `${props.title} · ${page.props.appName}` : page.props.appName
)
const url = computed(() => page.props.ziggy?.location)
</script>
<template>
  <Head :title="pageTitle">
    <meta
      v-if="description"
      head-key="description"
      name="description"
      :content="description"
    />
    <meta head-key="og:title" property="og:title" :content="pageTitle" />
    <meta
      v-if="description"
      head-key="og:description"
      property="og:description"
      :content="description"
    />
    <meta v-if="url" head-key="og:url" property="og:url" :content="url" />
    <meta head-key="twitter:title" name="twitter:title" :content="pageTitle" />
    <meta
      v-if="description"
      head-key="twitter:description"
      name="twitter:description"
      :content="description"
    />
    <link v-if="url" head-key="canonical" rel="canonical" :href="url" />
  </Head>
</template>
