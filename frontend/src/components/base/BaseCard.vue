<template>
  <component
    :is="as"
    :class="[
      'rounded-sm border border-border bg-surface text-text-primary',
      interactive ? 'transition hover:border-border-strong hover:shadow-card' : '',
      padded ? paddingClass : '',
      shadow ? 'shadow-card' : ''
    ]"
  >
    <slot></slot>
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  as: {
    type: String,
    default: 'div',
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value),
  },
  padded: {
    type: Boolean,
    default: true,
  },
  shadow: {
    type: Boolean,
    default: false,
  },
  interactive: {
    type: Boolean,
    default: false,
  },
})

const paddingClass = computed(() => ({
  none: '',
  sm: 'p-3',
  md: 'p-4',
  lg: 'p-5',
}[props.padding]))
</script>
