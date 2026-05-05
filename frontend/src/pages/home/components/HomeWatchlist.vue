<template>
  <div>
    <div class="mb-4 flex items-center justify-between gap-4">
      <div>
        <p class="text-caption font-semibold uppercase tracking-[0.16em] text-text-muted">Watchlist</p>
        <h2 class="mt-2 text-title font-semibold">관심 종목 보드</h2>
      </div>
      <p class="text-body-sm text-text-secondary">
        {{ getMarketLabel(marketFilter) }}
      </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <BaseCard
        v-for="stock in stocks"
        :key="stock.code"
        padding="lg"
        shadow
        interactive
        class="cursor-pointer"
        @click="$emit('select-stock', stock.code)"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-title-sm font-semibold">{{ stock.name }}</p>
            <p class="mt-1 text-caption uppercase tracking-[0.16em] text-text-muted">{{ stock.code }}</p>
          </div>
          <BaseBadge :tone="stock.error ? 'neutral' : getToneByRate(stock.changeRate)">
            {{ getMarketLabel(stock.market) }}
          </BaseBadge>
        </div>

        <div v-if="stock.loading" class="mt-6 text-body-sm text-text-muted">
          데이터 수집 중
        </div>
        <div v-else-if="stock.error" class="mt-6 text-body-sm text-text-muted">
          데이터를 아직 불러오지 못했습니다.
        </div>
        <div v-else class="mt-6">
          <div class="flex flex-col items-start justify-between gap-1">
            <p class="text-3xl font-semibold">{{ formatCurrency(stock.price) }}</p>
            <p
              :class="[
                'text-body-sm font-semibold',
                stock.changeRate > 0 ? 'text-rise' : stock.changeRate < 0 ? 'text-fall' : 'text-text-secondary'
              ]"
            >
              {{ formatPercent(stock.changeRate) }}
            </p>
          </div>

          <p class="mt-4 text-body-sm leading-6 text-text-secondary">
            {{ stock.thesis }}
          </p>

          <div class="mt-5 grid grid-cols-2 gap-3 border-t border-border pt-4 text-body-sm">
            <div>
              <p class="text-caption text-text-muted">거래량</p>
              <p class="mt-1 font-semibold">{{ formatNumber(stock.volume) }}</p>
            </div>
            <div>
              <p class="text-caption text-text-muted">PER / PBR</p>
              <p class="mt-1 font-semibold">{{ Number(stock.per).toFixed(2) }} / {{ Number(stock.pbr).toFixed(2) }}</p>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { BaseBadge, BaseCard } from '@/components/base'
import {
  formatCurrency,
  formatNumber,
  formatPercent,
  getToneByRate,
} from '@/utils/stockFormatters'

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

defineEmits(['select-stock'])

function getMarketLabel(value) {
  return props.marketOptions.find((option) => option.value === value)?.label ?? '기타'
}
</script>
