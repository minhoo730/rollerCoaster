<template>
  <textarea
    :id="id"
    :name="name"
    :value="modelValue"
    :placeholder="placeholder"
    :rows="rows"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    :aria-invalid="invalid || undefined"
    :aria-describedby="describedBy"
    :class="[
      'w-full resize-y rounded-md border bg-surface px-3 py-3 text-body-sm text-text-primary outline-none transition',
      'placeholder:text-text-muted focus:border-primary focus:ring-2 focus:ring-primary/15',
      disabled ? 'cursor-not-allowed bg-surface-muted opacity-70' : '',
      invalid ? 'border-rise focus:border-rise focus:ring-rise/15' : 'border-border'
    ]"
    @input="$emit('update:modelValue', $event.target.value)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  ></textarea>
</template>

<script setup>
defineEmits(['update:modelValue', 'blur', 'focus'])

defineProps({
  modelValue: {
    type: String,
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
  rows: {
    type: Number,
    default: 4,
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
</script>
