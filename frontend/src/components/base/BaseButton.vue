<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-md font-semibold outline-none transition',
      'focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2 focus-visible:ring-offset-bg',
      'disabled:pointer-events-none disabled:opacity-50',
      block ? 'w-full' : '',
      sizeClass,
      variantClass
    ]"
  >
    <span
      v-if="loading"
      class="size-4 animate-spin rounded-full border-2 border-current border-r-transparent"
      aria-hidden="true"
    ></span>
    <slot v-else name="leading"></slot>
    <span class="truncate">
      <slot></slot>
    </span>
    <slot name="trailing"></slot>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'ghost', 'danger', 'rise', 'fall'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value),
  },
  block: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const variantClass = computed(() => ({
  primary: 'bg-primary text-text-inverse hover:bg-primary-hover',
  secondary: 'border border-border bg-surface text-text-primary hover:border-border-strong hover:bg-surface-muted',
  ghost: 'bg-transparent text-text-secondary hover:bg-surface-muted',
  danger: 'bg-rise text-text-inverse hover:bg-red-600',
  rise: 'bg-rise text-text-inverse hover:bg-red-600',
  fall: 'bg-fall text-text-inverse hover:bg-primary-hover',
}[props.variant]))

const sizeClass = computed(() => ({
  sm: 'h-8 px-3 text-caption',
  md: 'h-10 px-4 text-body-sm',
  lg: 'h-12 px-5 text-body',
}[props.size]))
</script>
