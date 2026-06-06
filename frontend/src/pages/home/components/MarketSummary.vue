<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card lg:p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <p class="text-caption font-semibold uppercase text-primary">Today Market</p>
        <h1 class="mt-2 text-3xl font-semibold leading-tight text-text-primary sm:text-4xl">오늘의 시장</h1>
        <!-- <p class="mt-3 max-w-3xl text-body leading-7 text-text-secondary">{{ summary.brief.summary }}</p> -->
      </div>

      <div class="rounded-xl bg-surface-muted px-4 py-3 text-body-sm text-text-secondary">
        <span class="font-semibold text-text-primary">데이터 기준</span>
        <span class="ml-2">{{ summary.scope }}</span>
      </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
      <div class="grid gap-3 sm:grid-cols-2">
        <a
          v-for="index in summary.indices"
          :key="index.code"
          class="rounded-xl border border-border bg-surface p-4 transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-card"
          :href="`/markets/${index.code.toLowerCase()}`"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-body-sm font-semibold text-text-secondary">{{ index.name }}</p>
              <p class="mt-2 text-2xl font-semibold text-text-primary">{{ formatNumber(index.value) }}</p>
            </div>
            <span :class="['rounded-lg px-2 py-1 text-caption font-semibold', toneClass(index.changeRate)]">
              {{ formatPercent(index.changeRate) }}
            </span>
          </div>
          <p :class="['mt-3 text-body-sm font-semibold', index.changeRate >= 0 ? 'text-rise' : 'text-fall']">
            {{ formatSignedNumber(index.change) }}
          </p>
        </a>
      </div>

      <div class="grid gap-3 sm:grid-cols-3 lg:grid-rows-1">
        <div
          v-for="item in summary.breadth"
          :key="item.label"
          class="flex flex-col items-center justify-center gap-2 rounded-xl bg-surface-muted px-4 py-3"
        >
          <span class="text-body-sm text-text-secondary">{{ item.label }}</span>
          <span :class="['text-title-sm font-semibold', item.tone === 'rise' ? 'text-rise' : item.tone === 'fall' ? 'text-fall' : 'text-text-primary']">
            {{ item.value }}
          </span>
        </div>
      </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2 text-body-sm">
      <span class="rounded-lg bg-red-50 px-3 py-2 font-semibold text-rise">강세: {{ summary.brief.leadingSector }}</span>
      <span class="rounded-lg bg-blue-50 px-3 py-2 font-semibold text-fall">약세: {{ summary.brief.weakSector }}</span>
      <span v-if="lastUpdatedAt" class="rounded-lg bg-slate-100 px-3 py-2 text-text-secondary">{{ lastUpdatedAt }} 업데이트</span>
    </div>
  </section>
</template>

<script setup>
import { formatNumber, formatPercent, formatSignedNumber } from '@/utils/stockFormatters'

defineProps({
  summary: {
    type: Object,
    required: true,
  },
  lastUpdatedAt: {
    type: String,
    default: '',
  },
})

function toneClass(rate) {
  if (rate > 0) return 'bg-red-50 text-rise'
  if (rate < 0) return 'bg-blue-50 text-fall'
  return 'bg-slate-100 text-text-secondary'
}
</script>
