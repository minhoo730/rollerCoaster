<template>
  <section class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <BaseTabs
        :model-value="activeType"
        :options="rankingTabs"
        aria-label="주식 랭킹 탭"
        full
        class="sm:w-auto"
        @update:model-value="$emit('update:activeType', $event)"
        @select="$emit('select', $event)"
      />

      <!-- <span v-if="loading" class="text-caption font-semibold text-text-muted">조회 중</span> -->
    </div>

    <p v-if="error" class="rounded-lg border border-rise/20 bg-rise/5 px-3 py-2 text-body-sm text-rise">
      {{ error }}
    </p>

    <StockRankCard
      :title="currentTab.title"
      :stocks="loading ? [] : currentStocks"
      :metric="currentTab.metric"
      :empty-label="loading ? '랭킹을 불러오는 중입니다' : currentTab.emptyLabel"
    />
  </section>
</template>

<script setup>
import { computed } from 'vue'

import BaseTabs from '@/components/base/BaseTabs.vue'
import StockRankCard from './StockRankCard.vue'

const rankingTabs = [
  { label: '급등', value: 'gainers', title: '급등 TOP', metric: 'changeRate', emptyLabel: '상승 종목이 없습니다' },
  { label: '급락', value: 'losers', title: '급락 TOP', metric: 'changeRate', emptyLabel: '하락 종목이 없습니다' },
  { label: '거래량', value: 'volume', title: '거래량 TOP', metric: 'volume', emptyLabel: '거래량 데이터가 없습니다' },
  { label: '거래대금', value: 'turnover', title: '거래대금 TOP', metric: 'turnover', emptyLabel: '거래대금 데이터가 없습니다' },
]

const props = defineProps({
  activeType: {
    type: String,
    default: 'gainers',
  },
  gainers: {
    type: Array,
    required: true,
  },
  losers: {
    type: Array,
    required: true,
  },
  volumeStocks: {
    type: Array,
    required: true,
  },
  turnoverStocks: {
    type: Array,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['update:activeType', 'select'])

const currentTab = computed(() => (
  rankingTabs.find((tab) => tab.value === props.activeType) ?? rankingTabs[0]
))

const currentStocks = computed(() => ({
  gainers: props.gainers,
  losers: props.losers,
  volume: props.volumeStocks,
  turnover: props.turnoverStocks,
}[currentTab.value.value] ?? []))
</script>
