<template>
  <section class="overflow-hidden rounded-[28px] border border-slate-200/70 bg-[linear-gradient(135deg,#0f172a_0%,#10264f_55%,#1d4ed8_100%)] text-white shadow-floating">
    <div class="grid gap-8 px-6 py-8 lg:grid-cols-[1.3fr_0.9fr] lg:px-8 lg:py-10">
      <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-3 text-caption font-semibold uppercase tracking-[0.18em] text-blue-100">
          <span>Market Pulse</span>
          <span class="rounded-full border border-white/20 px-2 py-1 text-[11px] tracking-[0.16em]">
            {{ lastUpdatedAt ? `${lastUpdatedAt} 업데이트` : '데이터 동기화 중' }}
          </span>
        </div>

        <div class="space-y-3">
          <h1 class="max-w-2xl text-4xl font-semibold leading-tight sm:text-5xl">
            오늘의 시장
          </h1>
          <p class="max-w-2xl text-base leading-7 text-blue-50/88">
            실시간으로 집계되는 시장의 흐름과 대표 종목을 확인해보세요.🔥
          </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
          <BaseCard
            v-for="pulse in marketPulse"
            :key="pulse.label"
            padding="md"
            class="border-white/12 bg-white/8 text-white backdrop-blur-sm"
          >
            <p class="text-caption uppercase tracking-[0.16em] text-blue-100">{{ pulse.label }}</p>
            <div class="mt-3 flex items-center justify-between">
              <p class="text-2xl font-semibold">{{ pulse.value }}</p>
              <BaseBadge :tone="pulse.tone" class="bg-white/10 text-white">
                {{ pulse.tone === 'rise' ? '강세' : pulse.tone === 'fall' ? '약세' : '중립' }}
              </BaseBadge>
            </div>
          </BaseCard>
        </div>
      </div>

      <div class="rounded-[24px] border border-white/12 bg-slate-950/24 p-5 backdrop-blur-sm">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-caption uppercase tracking-[0.16em] text-blue-100">대표 종목</p>
            <p class="mt-2 text-title-sm font-semibold">
              {{ featuredStock?.name ?? '실시간 조회' }}
            </p>
          </div>
          <BaseBadge
            :tone="featuredStock ? getToneByRate(featuredStock.changeRate) : 'neutral'"
            :class="isFeaturedLoading ? 'bg-white/10 text-white' : ''"
          >
            {{ isFeaturedLoading ? '조회 중' : featuredStock?.code ?? featuredCode }}
          </BaseBadge>
        </div>

        <div v-if="isFeaturedLoading" class="mt-8 text-body-sm text-blue-100">
          종목 정보를 불러오는 중입니다.
        </div>
        <div v-else-if="featuredError" class="mt-8 text-body-sm text-red-200">
          {{ featuredError }}
        </div>
        <div v-else-if="featuredStock" class="mt-8 space-y-5">
          <div class="flex flex-col items-start justify-between gap-4">
            <div class="flex flex-col">
              <p class="text-4xl font-semibold">{{ formatCurrency(featuredStock.price) }}</p>
              <p
                :class="[
                  'mt-2 text-body font-semibold',
                  featuredStock.changeRate > 0 ? 'text-red-300' : featuredStock.changeRate < 0 ? 'text-blue-200' : 'text-slate-200'
                ]"
              >
                {{ formatSignedNumber(featuredStock.change) }} / {{ formatPercent(featuredStock.changeRate) }}
              </p>
            </div>
            <p class="w-full text-left text-body-sm leading-6 text-blue-50/85">
              {{ featuredStock.thesis }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3 text-body-sm sm:grid-cols-4">
            <div
              v-for="stat in featuredStats"
              :key="stat.label"
              class="rounded-2xl bg-white/8 p-3"
            >
              <p class="text-caption text-blue-100">{{ stat.label }}</p>
              <p class="mt-2 font-semibold">{{ stat.value }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

import { BaseBadge, BaseCard } from '@/components/base'
import {
  formatCurrency,
  formatNumber,
  formatPercent,
  formatSignedNumber,
  getToneByRate,
} from '@/utils/stockFormatters'

const props = defineProps({
  marketPulse: {
    type: Array,
    required: true,
  },
  lastUpdatedAt: {
    type: String,
    default: '',
  },
  featuredStock: {
    type: Object,
    default: null,
  },
  featuredCode: {
    type: String,
    required: true,
  },
  isFeaturedLoading: {
    type: Boolean,
    default: false,
  },
  featuredError: {
    type: String,
    default: '',
  },
})

const featuredStats = computed(() => {
  if (!props.featuredStock) {
    return []
  }

  return [
    { label: '시가', value: formatCurrency(props.featuredStock.open) },
    { label: '고가', value: formatCurrency(props.featuredStock.high) },
    { label: '저가', value: formatCurrency(props.featuredStock.low) },
    { label: '거래량', value: formatNumber(props.featuredStock.volume) },
  ]
})
</script>
