<template>
  <div
    :class="[
      'flex items-center rounded-md border bg-surface text-text-primary transition',
      'focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15',
      disabled ? 'bg-surface-muted opacity-70' : '',
      invalid ? 'border-rise focus-within:border-rise focus-within:ring-rise/15' : 'border-border',
      sizeClass
    ]"
  >
    <span v-if="$slots.prefix" class="flex items-center pl-3 text-text-muted">
      <slot name="prefix"></slot>
    </span>

    <input
      v-bind="attrs"
      :id="id"
      :name="name"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :aria-invalid="invalid || undefined"
      :aria-describedby="describedBy"
      class="min-w-0 flex-1 bg-transparent px-3 text-body-sm outline-none placeholder:text-text-muted disabled:cursor-not-allowed"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />

    <span v-if="$slots.suffix" class="flex items-center pr-3 text-text-muted">
      <slot name="suffix"></slot>
    </span>
  </div>
</template>

<script setup>
defineOptions({
  inheritAttrs: false,
})

import { computed, useAttrs } from 'vue'

defineEmits(['update:modelValue', 'blur', 'focus'])

const attrs = useAttrs()

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
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
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
  readonly: {
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
</script>
