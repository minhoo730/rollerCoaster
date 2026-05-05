<template>
  <section class="grid gap-4 lg:grid-cols-3">
    <StockRankCard title="급등 TOP" :stocks="gainers" empty-label="상승 종목이 없습니다" />
    <StockRankCard title="급락 TOP" :stocks="losers" empty-label="하락 종목이 없습니다" />
    <StockRankCard title="거래대금 TOP" :stocks="turnoverStocks" metric="turnover" empty-label="거래대금 데이터가 없습니다" />
  </section>
</template>

<script setup>
import StockRankCard from './StockRankCard.vue'

defineProps({
  gainers: {
    type: Array,
    required: true,
  },
  losers: {
    type: Array,
    required: true,
  },
  turnoverStocks: {
    type: Array,
    required: true,
  },
})
</script>
