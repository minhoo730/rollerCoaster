<template>
  <section class="rounded-2xl border border-border bg-surface p-4 shadow-card lg:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-caption font-semibold uppercase text-primary">My Watchlist</p>
        <h2 class="mt-1 text-title font-semibold text-text-primary">관심종목</h2>
      </div>

      <div class="flex rounded-lg bg-surface-muted p-1">
        <button
          v-for="option in marketOptions"
          :key="option.value"
          :class="[
            'rounded-md px-3 py-2 text-caption font-semibold transition',
            marketFilter === option.value ? 'bg-white text-primary shadow-card' : 'text-text-secondary hover:text-text-primary'
          ]"
          type="button"
          @click="$emit('update:marketFilter', option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div class="mt-5 overflow-hidden rounded-xl border border-border">
      <a
        v-for="stock in stocks"
        :key="stock.code"
        class="grid grid-cols-[1fr_auto] gap-3 border-b border-border px-4 py-4 last:border-b-0 transition hover:bg-primary-light/35 sm:grid-cols-[1.1fr_0.8fr_0.8fr_auto]"
        :href="`/stocks/${stock.code}`"
      >
        <span>
          <span class="block text-body-sm font-semibold text-text-primary">{{ stock.name }}</span>
          <span class="mt-1 block text-caption text-text-muted">{{ stock.code }} · {{ marketLabel(stock.market) }}</span>
        </span>

        <span class="hidden text-body-sm text-text-secondary sm:block">
          {{ stock.loading ? '조회 중' : formatCurrency(stock.price) }}
        </span>

        <span class="hidden text-body-sm text-text-secondary sm:block">
          {{ stock.error ? '시세 오류' : formatNumber(stock.volume) }}
        </span>

        <span :class="['text-right text-body-sm font-semibold', stock.changeRate >= 0 ? 'text-rise' : 'text-fall']">
          {{ stock.error ? '-' : formatPercent(stock.changeRate) }}
        </span>
      </a>
    </div>
  </section>
</template>

<script setup>
import { formatCurrency, formatNumber, formatPercent } from '@/utils/stockFormatters'

const props = defineProps({
  stocks: {
    type: Array,
    required: true,
  },
  marketFilter: {
    type: String,
    required: true,
  },
  marketOptions: {
    type: Array,
    required: true,
  },
})

defineEmits(['update:marketFilter'])

function marketLabel(value) {
  return props.marketOptions.find((option) => option.value === value)?.label ?? value
}
</script>
