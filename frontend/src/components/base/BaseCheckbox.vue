<template>
  <label
    :class="[
      'flex cursor-pointer items-start gap-3 rounded-md border border-transparent p-1 text-body-sm',
      disabled ? 'cursor-not-allowed opacity-60' : ''
    ]"
  >
    <input
      :id="id"
      :name="name"
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      :required="required"
      :class="[
        'mt-0.5 size-4 rounded-sm border-border text-primary accent-primary',
        'focus-visible:ring-2 focus-visible:ring-primary/30 focus-visible:ring-offset-2 focus-visible:ring-offset-bg'
      ]"
      @change="$emit('update:modelValue', $event.target.checked)"
    />

    <span class="space-y-1">
      <span class="block font-medium text-text-primary">
        <slot>{{ label }}</slot>
      </span>
      <span v-if="description" class="block text-caption text-text-muted">
        {{ description }}
      </span>
    </span>
  </label>
</template>

<script setup>
defineEmits(['update:modelValue'])

defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: undefined,
  },
  name: {
    type: String,
    default: undefined,
  },
  label: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
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
</script>
