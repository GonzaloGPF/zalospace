import { computed, ref, watch } from 'vue'
import defaultTheme from 'tailwindcss/defaultTheme'

export default function useTailwind() {
  const screenWidth = ref(window.innerWidth)
  const isResizing = ref(false)

  window.onresize = () => {
    screenWidth.value = window.innerWidth || screen.width

    return screenWidth.value
  }

  const isMobile = computed(() => screenWidth.value <= getBreakpoint('md'))
  const isLg = computed(() => screenWidth.value >= getBreakpoint('lg'))

  const getBreakpoint = (breakpointName) =>
    parseInt(defaultTheme.screens[breakpointName])

  watch(screenWidth, () => {
    isResizing.value = true

    const lastValue = screenWidth.value

    const timer = setTimeout(() => {
      if (lastValue === screenWidth.value) {
        isResizing.value = false
        clearTimeout(timer)
      }
    }, 250)
  })

  return {
    isMobile,
    isLg,
    isResizing,
    getBreakpoint,
  }
}
