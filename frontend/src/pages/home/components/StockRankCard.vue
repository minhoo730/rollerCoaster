<template>
  <article class="rounded-2xl border border-border bg-surface p-4 shadow-card">
    <div class="flex items-center justify-between">
      <h2 class="text-title-sm font-semibold text-text-primary">{{ title }}</h2>
      <span class="text-caption text-text-muted">클릭해 상세 보기</span>
    </div>

    <div class="mt-4 space-y-2">
      <a
        v-for="(stock, index) in stocks"
        :key="stock.code"
        class="grid grid-cols-[2rem_1fr_auto] items-center gap-3 rounded-xl border border-transparent px-2 py-3 transition hover:border-primary/30 hover:bg-primary-light/40"
        :href="`/stocks/${stock.code}`"
      >
        <span class="text-body-sm font-semibold text-text-muted">{{ index + 1 }}</span>
        <span class="min-w-0">
          <span class="block truncate text-body-sm font-semibold text-text-primary">{{ stock.name }}</span>
          <span class="block text-caption text-text-secondary">{{ stock.code }} · {{ stock.market?.toUpperCase?.() ?? 'MARKET' }}</span>
        </span>
        <span class="text-right">
          <span v-if="metric === 'turnover'" class="block text-body-sm font-semibold text-text-primary">{{ formatTurnover(stock.turnover) }}</span>
          <span v-else-if="metric === 'volume'" class="block text-body-sm font-semibold text-text-primary">{{ formatVolume(stock.volume) }}</span>
          <span v-else :class="['block text-body-sm font-semibold', stock.changeRate >= 0 ? 'text-rise' : 'text-fall']">
            {{ formatPercent(stock.changeRate) }}
          </span>
          <span v-if="stock.reason" class="block max-w-28 truncate text-caption text-text-muted">{{ stock.reason }}</span>
        </span>
      </a>

      <div v-if="!stocks.length" class="rounded-xl bg-surface-muted px-3 py-6 text-center text-body-sm text-text-secondary">
        {{ emptyLabel }}
      </div>
    </div>
  </article>
</template>

<script setup>
import { formatNumber, formatPercent } from '@/utils/stockFormatters'

defineProps({
  title: {
    type: String,
    required: true,
  },
  stocks: {
    type: Array,
    required: true,
  },
  metric: {
    type: String,
    default: 'changeRate',
  },
  emptyLabel: {
    type: String,
    default: '표시할 종목이 없습니다',
  },
})

function formatTurnover(value) {
  return `${formatNumber(value)}억`
}

function formatVolume(value) {
  return `${formatNumber(value)}주`
}
</script>
