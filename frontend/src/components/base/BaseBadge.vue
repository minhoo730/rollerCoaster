<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded-sm font-semibold',
      sizeClass,
      toneClass
    ]"
  >
    <span v-if="dot" class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    <slot></slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  tone: {
    type: String,
    default: 'neutral',
    validator: (value) => ['neutral', 'primary', 'rise', 'fall', 'warning'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md'].includes(value),
  },
  dot: {
    type: Boolean,
    default: false,
  },
})

const toneClass = computed(() => ({
  neutral: 'bg-surface-muted text-text-secondary',
  primary: 'bg-primary-light text-primary',
  rise: 'bg-red-50 text-rise',
  fall: 'bg-blue-50 text-fall',
  warning: 'bg-amber-50 text-amber-700',
}[props.tone]))

const sizeClass = computed(() => ({
  sm: 'px-2 py-0.5 text-caption',
  md: 'px-2.5 py-1 text-caption',
}[props.size]))
</script>
