<template>
  <div
    :class="[
      'inline-flex max-w-full gap-1 rounded-lg border border-border bg-surface-muted p-1',
      full ? 'w-full' : ''
    ]"
    role="tablist"
    :aria-label="ariaLabel"
  >
    <button
      v-for="option in options"
      :key="option.value"
      :ref="(element) => setTabRef(option.value, element)"
      role="tab"
      :aria-selected="isActive(option.value)"
      :tabindex="isActive(option.value) ? 0 : -1"
      :disabled="option.disabled"
      :class="[
        'rounded-md px-3 py-2 text-caption font-semibold transition',
        full ? 'flex-1' : '',
        sizeClass,
        isActive(option.value)
          ? 'bg-white text-primary shadow-card' : 'text-text-secondary hover:text-text-primary'
      ]"
      @click="selectTab(option)"
      @keydown="handleKeydown($event, option.value)"
    >
      <span class="block truncate">
        <slot name="option" :option="option" :active="isActive(option.value)">
          {{ option.label }}
        </slot>
      </span>
    </button>
  </div>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    required: true,
  },
  options: {
    type: Array,
    required: true,
  },
  ariaLabel: {
    type: String,
    default: '탭 메뉴',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md'].includes(value),
  },
  full: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'select'])
const tabRefs = ref({})

const sizeClass = computed(() => ({
  sm: 'h-8 px-3 text-caption',
  md: 'h-10 px-4 text-body-sm',
}[props.size]))

const enabledOptions = computed(() => props.options.filter((option) => !option.disabled))

function isActive(value) {
  return props.modelValue === value
}

function selectTab(option) {
  if (option.disabled) {
    return
  }

  emit('update:modelValue', option.value)
  emit('select', option.value)
}

function handleKeydown(event, value) {
  const nextValue = resolveNextValue(event.key, value)

  if (!nextValue) {
    return
  }

  event.preventDefault()
  emit('update:modelValue', nextValue)
  emit('select', nextValue)
  focusTab(nextValue)
}

function resolveNextValue(key, value) {
  const options = enabledOptions.value
  const currentIndex = options.findIndex((option) => option.value === value)

  if (!options.length || currentIndex === -1) {
    return ''
  }

  if (key === 'Home') {
    return options[0].value
  }

  if (key === 'End') {
    return options[options.length - 1].value
  }

  if (key !== 'ArrowRight' && key !== 'ArrowLeft') {
    return ''
  }

  const direction = key === 'ArrowRight' ? 1 : -1
  const nextIndex = (currentIndex + direction + options.length) % options.length

  return options[nextIndex].value
}

function setTabRef(value, element) {
  if (element) {
    tabRefs.value[value] = element
    return
  }

  delete tabRefs.value[value]
}

function focusTab(value) {
  nextTick(() => {
    tabRefs.value[value]?.focus()
  })
}
</script>
