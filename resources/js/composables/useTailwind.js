import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import defaultTheme from 'tailwindcss/defaultTheme'

export default function useTailwind() {
  const screenWidth = ref(window.innerWidth)
  const isResizing = ref(false)

  const handleResize = () => {
    screenWidth.value = window.innerWidth || screen.width
  }

  onMounted(() => window.addEventListener('resize', handleResize))
  onUnmounted(() => window.removeEventListener('resize', handleResize))

  const isMobile = computed(() => screenWidth.value <= getBreakpoint('md'))
  const isLg = computed(() => screenWidth.value >= getBreakpoint('lg'))

  const getBreakpoint = (breakpointName) => {
    const value = defaultTheme.screens[breakpointName]
    const numericValue = parseFloat(value)

    return value.endsWith('rem') ? numericValue * 16 : numericValue
  }

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
