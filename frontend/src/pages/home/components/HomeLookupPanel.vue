<template>
  <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.8fr)]">
    <BaseCard padding="lg" shadow>
      <div>
        <p class="text-caption font-semibold uppercase tracking-[0.16em] text-primary">Featured Lookup</p>
        <h2 class="mt-2 text-title font-semibold">대표 종목 조회</h2>
        <p class="mt-2 text-body-sm text-text-secondary">상단 대표 종목 카드에만 반영됩니다.</p>
      </div>

      <div class="mt-5 grid gap-4 md:grid-cols-[minmax(0,1fr)_120px]">
        <BaseFormField label="종목 코드">
          <template #default>
            <BaseInput
              v-model="searchCodeProxy"
              placeholder="예: 005930"
              @keyup.enter="$emit('search')"
            />
          </template>
        </BaseFormField>

        <div class="flex items-end">
          <BaseButton block @click="$emit('search')">대표 조회</BaseButton>
        </div>
      </div>
    </BaseCard>

    <BaseCard padding="lg" shadow>
      <div>
        <p class="text-caption font-semibold uppercase tracking-[0.16em] text-text-muted">Board Scope</p>
        <h2 class="mt-2 text-title-sm font-semibold">관심종목 보드 조회</h2>
        <p class="mt-2 text-body-sm text-text-secondary">상승/하락/평균, 모멘텀, 관심종목 보드에 반영됩니다.</p>
      </div>

      <div class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_150px]">
        <BaseFormField label="보드 시장 필터">
          <template #default>
            <BaseSelect v-model="marketFilterProxy" :options="marketOptions" />
          </template>
        </BaseFormField>

        <div class="flex items-end">
          <BaseButton block variant="secondary" @click="$emit('refresh')">보드 새로고침</BaseButton>
        </div>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import {
  BaseButton,
  BaseCard,
  BaseFormField,
  BaseInput,
  BaseSelect,
} from '@/components/base'

const props = defineProps({
  searchCode: {
    type: String,
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

const emit = defineEmits(['update:searchCode', 'update:marketFilter', 'search', 'refresh'])

const searchCodeProxy = computed({
  get: () => props.searchCode,
  set: (value) => emit('update:searchCode', value),
})

const marketFilterProxy = computed({
  get: () => props.marketFilter,
  set: (value) => emit('update:marketFilter', value),
})
</script>
