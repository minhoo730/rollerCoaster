<template>
  <div class="space-y-2">
    <div v-if="label || $slots.label" class="flex items-center justify-between gap-3">
      <label
        :for="forId"
        class="text-body-sm font-semibold text-text-primary"
      >
        <slot name="label">
          {{ label }}
          <span v-if="required" class="text-rise" aria-hidden="true">*</span>
        </slot>
      </label>

      <slot name="aside"></slot>
    </div>

    <slot
      :id="forId"
      :invalid="Boolean(error)"
      :describedBy="describedBy"
    ></slot>

    <p
      v-if="error"
      :id="errorId"
      class="text-caption font-medium text-rise"
    >
      {{ error }}
    </p>
    <p
      v-else-if="description"
      :id="descriptionId"
      class="text-caption text-text-muted"
    >
      {{ description }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: {
    type: String,
    default: '',
  },
  forId: {
    type: String,
    default: undefined,
  },
  description: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
})

const descriptionId = computed(() => props.forId ? `${props.forId}-description` : undefined)
const errorId = computed(() => props.forId ? `${props.forId}-error` : undefined)
const describedBy = computed(() => {
  if (props.error) return errorId.value
  if (props.description) return descriptionId.value
  return undefined
})
</script>
