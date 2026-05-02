<template>
  <div
    :class="[
      'relative rounded-md border bg-surface transition',
      'focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15',
      disabled ? 'bg-surface-muted opacity-70' : '',
      invalid ? 'border-rise focus-within:border-rise focus-within:ring-rise/15' : 'border-border',
      sizeClass
    ]"
  >
    <select
      :id="id"
      :name="name"
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      :aria-invalid="invalid || undefined"
      :aria-describedby="describedBy"
      class="h-full w-full appearance-none rounded-md bg-transparent px-3 pr-9 text-body-sm text-text-primary outline-none disabled:cursor-not-allowed"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="" disabled>
        {{ placeholder }}
      </option>
      <option
        v-for="option in options"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
        :disabled="getOptionDisabled(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>

    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-text-muted">
      <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
      </svg>
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineEmits(['update:modelValue'])

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  id: {
    type: String,
    default: undefined,
  },
  name: {
    type: String,
    default: undefined,
  },
  placeholder: {
    type: String,
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  describedBy: {
    type: String,
    default: undefined,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
})

const sizeClass = computed(() => ({
  sm: 'h-9',
  md: 'h-11',
  lg: 'h-12',
}[props.size]))

const isOptionObject = (option) => option !== null && typeof option === 'object'
const getOptionLabel = (option) => isOptionObject(option) ? option.label : option
const getOptionValue = (option) => isOptionObject(option) ? option.value : option
const getOptionDisabled = (option) => isOptionObject(option) ? Boolean(option.disabled) : false
</script>
