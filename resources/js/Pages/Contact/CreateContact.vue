<script setup>
import BaseLayout from '@/Layouts/BaseLayout.vue'
import Translator from '@/objects/Translator.js'
import ContactForm from '@/Components/forms/ContactForm.vue'
</script>
<template>
  <BaseLayout
    :title="Translator.actionTitle('contact')"
    :description="Translator.t('help.contact_with_me')"
    icon="email"
  >
    <ContactForm class="max-w-xl mx-auto" />
  </BaseLayout>
</template>
