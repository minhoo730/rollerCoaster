<template>
  <div class="space-y-6">
    <BaseCard padding="lg" shadow class="bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)]">
      <p class="text-caption font-semibold uppercase tracking-[0.16em] text-text-muted">Community Radar</p>
      <h2 class="mt-2 text-title font-semibold">지금 많이 보는 토론</h2>

      <div class="mt-5 space-y-3">
        <article
          v-for="post in communityPosts"
          :key="post.title"
          class="rounded-2xl border border-border bg-surface-muted/70 p-4"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="font-semibold">{{ post.title }}</p>
              <p class="mt-2 text-body-sm text-text-secondary">{{ post.meta }}</p>
            </div>
            <BaseBadge :tone="post.tone" dot>
              핫이슈
            </BaseBadge>
          </div>
        </article>
      </div>
    </BaseCard>

    <BaseCard padding="lg" shadow class="bg-slate-950 ">
      <p class="text-caption font-semibold uppercase tracking-[0.16em] text-slate-400">Trading Checklist</p>
      <h2 class="mt-2 text-title font-semibold">장중 체크 포인트</h2>

      <div class="mt-5 space-y-4">
        <div
          v-for="item in checklistItems"
          :key="item.title"
          class="rounded-2xl border border-white/10 bg-white/5 p-4"
        >
          <p class="text-body-sm text-slate-400">{{ item.title }}</p>
          <p class="mt-2 text-body leading-7">
            {{ item.description }}
          </p>
        </div>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { BaseBadge, BaseCard } from '@/components/base'
import { checklistItems } from '../data/homeStocks'

defineProps({
  communityPosts: {
    type: Array,
    required: true,
  },
})
</script>
