<template>
  <section class="rounded-2xl border border-border bg-surface p-4 shadow-card lg:p-5">
    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-caption font-semibold uppercase text-primary">Community</p>
        <h2 class="mt-1 text-title font-semibold text-text-primary">인기 게시글</h2>
      </div>
      <a class="text-body-sm font-semibold text-primary hover:text-primary-hover" href="/community">전체 보기</a>
    </div>

    <div class="mt-5 space-y-3">
      <a
        v-for="post in posts"
        :key="post.id"
        class="block rounded-xl border border-border p-4 transition hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-card"
        :href="`/community/posts/${post.id}`"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <span class="inline-flex rounded-lg bg-surface-muted px-2 py-1 text-caption font-semibold text-text-secondary">
              #{{ post.stockName }}
            </span>
            <h3 class="mt-3 line-clamp-2 text-body font-semibold leading-6 text-text-primary">{{ post.title }}</h3>
          </div>

          <span :class="['shrink-0 rounded-lg px-2 py-1 text-caption font-semibold', stockTone(post.stockCode)]">
            {{ stockRate(post.stockCode) }}
          </span>
        </div>
        <p class="mt-3 text-body-sm text-text-muted">{{ post.meta }}</p>
      </a>
    </div>
  </section>
</template>

<script setup>
import { formatPercent } from '@/utils/stockFormatters'

const props = defineProps({
  posts: {
    type: Array,
    required: true,
  },
  stocksByCode: {
    type: Object,
    required: true,
  },
})

function stockRate(code) {
  const stock = props.stocksByCode[code]
  return stock && !stock.error ? formatPercent(stock.changeRate) : '-'
}

function stockTone(code) {
  const rate = props.stocksByCode[code]?.changeRate ?? 0
  if (rate > 0) return 'bg-red-50 text-rise'
  if (rate < 0) return 'bg-blue-50 text-fall'
  return 'bg-slate-100 text-text-secondary'
}
</script>
