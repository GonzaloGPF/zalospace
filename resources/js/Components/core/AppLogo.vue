<script setup>
import { gsap } from 'gsap'
import globalConfig from '@/config/globalConfig.js'
import { default as vTextSplit } from '@/directives/textSplit'
import AppSvg from '@/Components/core/AppSvg.vue'

const animateTitle = () => {
  gsap.set('#title span', {
    transformOrigin: 'center center',
  })

  const timeline = gsap.timeline()

  timeline
    .from('#title-ceil', {
      width: 0,
    })
    .from('#title span', {
      opacity: 0,
      yPercent: 50,
      stagger: 0.1,
    })
    .to('.title-character', {
      scale: 1.3,
      stagger: 0.1,
    })
    .to(
      '.title-character',
      {
        scale: 1,
        stagger: 0.1,
      },
      '-=90%'
    )
    .from(
      '#logo',
      {
        opacity: 0,
        rotationY: 180,
        rotateX: 90,
      },
      '-=25%'
    )
}
</script>
<template>
  <div class="flex justify-center flex-col mx-auto items-center">
    <div :title="globalConfig.getAppName()">
      <app-svg
        id="logo"
        :height="300"
        :width="300"
        :scale-x="0.25"
        :scale-y="0.25"
        color="#0e4178ff"
        class="rounded-full bg-white"
        icon="logo"
      />
    </div>
    <h1
      id="title"
      v-text-split="'inline-block m-0 md:mx-1 title-character'"
      class="brand-font text-blue-950 dark:text-white text-5xl lg:text-6xl mt-3 font-bold whitespace-nowrap"
      @split="animateTitle"
      v-text="globalConfig.getAppName()"
    />
    <span
      id="title-ceil"
      class="block w-100 border-b border-4 mt-5 border-blue-950 dark:border-white"
      style="width: 400px"
    ></span>
  </div>
</template>
