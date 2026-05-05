<template>
  <BaseCard padding="lg" shadow class="bg-[linear-gradient(160deg,#fff7ed_0%,#ffffff_50%,#eff6ff_100%)]">
    <p class="text-caption font-semibold uppercase tracking-[0.16em] text-orange-500">Momentum</p>
    <div class="mt-4 space-y-4 flex gap-4 divide-gray-200 divide-x">
      <div class="w-1/2">
        <p class="text-caption text-text-muted">가장 강한 흐름</p>
        <p class="mt-1 text-title-sm font-semibold">{{ strongestStock?.name ?? '집계 중' }}</p>
        <p class="mt-1 text-body-sm text-rise">
          {{ strongestStock ? formatPercent(strongestStock.changeRate) : '-' }}
        </p>
      </div>
      <div class="w-1/2 border-gray-100">
        <p class="text-caption text-text-muted">가장 약한 흐름</p>
        <p class="mt-1 text-title-sm font-semibold">{{ weakestStock?.name ?? '집계 중' }}</p>
        <p class="mt-1 text-body-sm text-fall">
          {{ weakestStock ? formatPercent(weakestStock.changeRate) : '-' }}
        </p>
      </div>
    </div>
  </BaseCard>

</template>

<script setup>
import { BaseCard } from '@/components/base'
import { formatPercent } from '@/utils/stockFormatters'

defineProps({
  strongestStock: {
    type: Object,
    default: null,
  },
  weakestStock: {
    type: Object,
    default: null,
  },
})
</script>
