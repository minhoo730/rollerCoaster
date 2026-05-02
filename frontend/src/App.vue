<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

import {
  BaseBadge,
  BaseButton,
  BaseCard,
  BaseFormField,
  BaseInput,
  BaseSelect,
} from '@/components/base'

const marketOptions = [
  { label: '전체 시장', value: 'all' },
  { label: '코스피', value: 'kospi' },
  { label: '코스닥', value: 'kosdaq' },
  { label: '해외 관심종목', value: 'global' },
]

const watchSeeds = [
  { code: '005930', name: '삼성전자', market: 'kospi', thesis: '반도체 업황 반등과 외국인 수급 회복 여부' },
  { code: '000660', name: 'SK하이닉스', market: 'kospi', thesis: 'HBM 수요 강세와 AI 서버 투자 수혜' },
  { code: '035420', name: 'NAVER', market: 'kospi', thesis: '광고 회복과 커머스 수익성 개선 기대' },
  { code: '068270', name: '셀트리온', market: 'kospi', thesis: '합병 이후 실적 가시성과 바이오시밀러 모멘텀' },
  { code: '247540', name: '에코프로비엠', market: 'kosdaq', thesis: '2차전지 업황 회복 시 민감하게 반응하는 대표주' },
  { code: '091990', name: '셀트리온헬스케어', market: 'kosdaq', thesis: '헬스케어 섹터 회복 구간에서 거래대금 유입 주목' },
]

const communityPosts = [
  { title: '오늘 반도체 섹터 매매 포인트 정리', meta: '실시간 토론 · 128명 참여', tone: 'rise' },
  { title: '장 마감 후 체크할 공시 캘린더', meta: '정보 공유 · 42개 새 댓글', tone: 'primary' },
  { title: '외국인 수급 강한 종목만 모아보기', meta: '관심 리스트 · 9개 업데이트', tone: 'fall' },
]

const marketFilter = ref('all')
const searchCode = ref('005930')
const featuredCode = ref('005930')
const featuredStock = ref(null)
const watchlist = ref(watchSeeds.map((stock) => ({ ...stock, loading: true, error: false })))
const isFeaturedLoading = ref(false)
const featuredError = ref('')
const lastUpdatedAt = ref('')

const marketPulse = computed(() => {
  const items = watchlist.value.filter((item) => !item.error && typeof item.changeRate === 'number')

  if (!items.length) {
    return [
      { label: '상승 종목', value: '0', tone: 'neutral' },
      { label: '하락 종목', value: '0', tone: 'neutral' },
      { label: '평균 등락률', value: '0.00%', tone: 'neutral' },
    ]
  }

  const rising = items.filter((item) => item.changeRate > 0).length
  const falling = items.filter((item) => item.changeRate < 0).length
  const average = items.reduce((sum, item) => sum + item.changeRate, 0) / items.length

  return [
    { label: '상승 종목', value: String(rising), tone: rising >= falling ? 'rise' : 'neutral' },
    { label: '하락 종목', value: String(falling), tone: falling > rising ? 'fall' : 'neutral' },
    { label: '평균 등락률', value: formatPercent(average), tone: getToneByRate(average) },
  ]
})

const filteredWatchlist = computed(() => {
  if (marketFilter.value === 'all') {
    return watchlist.value
  }

  return watchlist.value.filter((item) => item.market === marketFilter.value)
})

const strongestStock = computed(() => {
  const items = watchlist.value.filter((item) => !item.error && typeof item.changeRate === 'number')
  return [...items].sort((a, b) => b.changeRate - a.changeRate)[0] ?? null
})

const weakestStock = computed(() => {
  const items = watchlist.value.filter((item) => !item.error && typeof item.changeRate === 'number')
  return [...items].sort((a, b) => a.changeRate - b.changeRate)[0] ?? null
})

onMounted(async () => {
  await Promise.all([loadWatchlist(), loadFeaturedStock(featuredCode.value)])
})

async function loadWatchlist() {
  const results = await Promise.allSettled(
    watchSeeds.map(async (stock) => {
      const data = await fetchPrice(stock.code)
      return mapStock(stock, data)
    }),
  )

  watchlist.value = results.map((result, index) => {
    const seed = watchSeeds[index]

    if (result.status === 'fulfilled') {
      return { ...result.value, loading: false, error: false }
    }

    return {
      ...seed,
      loading: false,
      error: true,
      price: 0,
      change: 0,
      changeRate: 0,
      volume: 0,
      open: 0,
      high: 0,
      low: 0,
      per: 0,
      pbr: 0,
    }
  })

  lastUpdatedAt.value = new Intl.DateTimeFormat('ko-KR', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date())
}

async function handleSearch() {
  const nextCode = searchCode.value.trim()

  if (!nextCode) {
    return
  }

  featuredCode.value = nextCode
  await loadFeaturedStock(nextCode)
}

async function handleSelectStock(code) {
  searchCode.value = code
  featuredCode.value = code
  await loadFeaturedStock(code)
}

async function loadFeaturedStock(code) {
  isFeaturedLoading.value = true
  featuredError.value = ''

  try {
    const seed = watchSeeds.find((item) => item.code === code) ?? {
      code,
      name: `종목 ${code}`,
      market: 'all',
      thesis: '직접 조회한 종목입니다.',
    }
    const data = await fetchPrice(code)
    featuredStock.value = mapStock(seed, data)
  } catch (error) {
    console.error('대표 종목 로딩 실패:', error)
    featuredStock.value = null
    featuredError.value = '대표 종목 정보를 불러오지 못했습니다'
  } finally {
    isFeaturedLoading.value = false
  }
}

async function fetchPrice(code) {
  const response = await axios.get(`/api/stocks/${code}/price`)
  return response.data
}

function formatNumber(value) {
  return new Intl.NumberFormat('ko-KR').format(value ?? 0)
}

function formatCurrency(value) {
  return `${formatNumber(value)}원`
}

function formatPercent(value) {
  const sign = value > 0 ? '+' : ''
  return `${sign}${Number(value ?? 0).toFixed(2)}%`
}

function formatSignedNumber(value) {
  const sign = value > 0 ? '+' : ''
  return `${sign}${formatNumber(value ?? 0)}`
}

function getToneByRate(rate) {
  if (rate > 0) return 'rise'
  if (rate < 0) return 'fall'
  return 'neutral'
}

function getMarketLabel(value) {
  return marketOptions.find((option) => option.value === value)?.label ?? '기타'
}

function mapStock(seed, data) {
  return {
    ...seed,
    code: data.code ?? seed.code,
    price: Number(data.price ?? 0),
    change: Number(data.change ?? 0),
    changeRate: Number(data.changeRate ?? 0),
    volume: Number(data.volume ?? 0),
    open: Number(data.open ?? 0),
    high: Number(data.high ?? 0),
    low: Number(data.low ?? 0),
    per: Number(data.per ?? 0),
    pbr: Number(data.pbr ?? 0),
  }
}

</script>

<template>
  <div class="min-h-screen bg-[linear-gradient(180deg,#f5f9ff_0%,#f8fafc_48%,#eef4f8_100%)] text-text-primary">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
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
                거래 흐름과 커뮤니티 온도를 한 화면에서 보는 주식 홈
              </h1>
              <p class="max-w-2xl text-base leading-7 text-blue-50/88">
                오늘 강한 종목, 실시간 등락률, 거래량, 그리고 바로 이어서 볼 토론 주제까지 메인에서 빠르게 훑을 수 있게 구성했습니다.
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
              <BaseBadge :tone="featuredStock ? getToneByRate(featuredStock.changeRate) : 'neutral'">
                {{ featuredStock?.code ?? featuredCode }}
              </BaseBadge>
            </div>

            <div v-if="isFeaturedLoading" class="mt-8 text-body-sm text-blue-100">
              종목 정보를 불러오는 중입니다.
            </div>
            <div v-else-if="featuredError" class="mt-8 text-body-sm text-red-200">
              {{ featuredError }}
            </div>
            <div v-else-if="featuredStock" class="mt-8 space-y-5">
              <div class="flex items-end justify-between gap-4">
                <div>
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
                <p class="max-w-[15rem] text-right text-body-sm leading-6 text-blue-50/85">
                  {{ featuredStock.thesis }}
                </p>
              </div>

              <div class="grid grid-cols-2 gap-3 text-body-sm sm:grid-cols-4">
                <div class="rounded-2xl bg-white/8 p-3">
                  <p class="text-caption text-blue-100">시가</p>
                  <p class="mt-2 font-semibold">{{ formatCurrency(featuredStock.open) }}</p>
                </div>
                <div class="rounded-2xl bg-white/8 p-3">
                  <p class="text-caption text-blue-100">고가</p>
                  <p class="mt-2 font-semibold">{{ formatCurrency(featuredStock.high) }}</p>
                </div>
                <div class="rounded-2xl bg-white/8 p-3">
                  <p class="text-caption text-blue-100">저가</p>
                  <p class="mt-2 font-semibold">{{ formatCurrency(featuredStock.low) }}</p>
                </div>
                <div class="rounded-2xl bg-white/8 p-3">
                  <p class="text-caption text-blue-100">거래량</p>
                  <p class="mt-2 font-semibold">{{ formatNumber(featuredStock.volume) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-8 grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
        <div class="space-y-6">
          <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
            <BaseCard padding="lg" shadow>
              <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                  <p class="text-caption font-semibold uppercase tracking-[0.16em] text-text-muted">Quick Lookup</p>
                  <h2 class="mt-2 text-title font-semibold">대표 종목 바로 조회</h2>
                </div>

                <div class="flex gap-2">
                  <BaseButton variant="secondary" @click="loadWatchlist">관심종목 새로고침</BaseButton>
                </div>
              </div>

              <div class="mt-5 grid gap-4 md:grid-cols-[minmax(0,1fr)_180px_120px]">
                <BaseFormField label="종목 코드">
                  <template #default>
                    <BaseInput
                      v-model="searchCode"
                      placeholder="005930"
                      @keyup.enter="handleSearch"
                    />
                  </template>
                </BaseFormField>

                <BaseFormField label="시장 보기">
                  <template #default>
                    <BaseSelect v-model="marketFilter" :options="marketOptions" />
                  </template>
                </BaseFormField>

                <div class="flex items-end">
                  <BaseButton block @click="handleSearch">조회</BaseButton>
                </div>
              </div>
            </BaseCard>

            <BaseCard padding="lg" shadow class="bg-[linear-gradient(160deg,#fff7ed_0%,#ffffff_100%)]">
              <p class="text-caption font-semibold uppercase tracking-[0.16em] text-orange-500">Momentum</p>
              <div class="mt-4 space-y-4">
                <div>
                  <p class="text-caption text-text-muted">가장 강한 흐름</p>
                  <p class="mt-1 text-title-sm font-semibold">{{ strongestStock?.name ?? '집계 중' }}</p>
                  <p class="mt-1 text-body-sm text-rise">
                    {{ strongestStock ? formatPercent(strongestStock.changeRate) : '-' }}
                  </p>
                </div>
                <div class="border-t border-orange-100 pt-4">
                  <p class="text-caption text-text-muted">가장 약한 흐름</p>
                  <p class="mt-1 text-title-sm font-semibold">{{ weakestStock?.name ?? '집계 중' }}</p>
                  <p class="mt-1 text-body-sm text-fall">
                    {{ weakestStock ? formatPercent(weakestStock.changeRate) : '-' }}
                  </p>
                </div>
              </div>
            </BaseCard>
          </div>

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
                v-for="stock in filteredWatchlist"
                :key="stock.code"
                padding="lg"
                shadow
                interactive
                class="cursor-pointer"
                @click="handleSelectStock(stock.code)"
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
                  <div class="flex items-end justify-between gap-4">
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
        </div>

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

          <BaseCard padding="lg" shadow class="bg-slate-950 text-white">
            <p class="text-caption font-semibold uppercase tracking-[0.16em] text-slate-400">Trading Checklist</p>
            <h2 class="mt-2 text-title font-semibold">장중 체크 포인트</h2>

            <div class="mt-5 space-y-4">
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="text-body-sm text-slate-300">1분 점검</p>
                <p class="mt-2 text-body leading-7 text-white/92">
                  대표 종목의 고가 돌파 여부와 거래량 급증 구간을 먼저 확인합니다.
                </p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="text-body-sm text-slate-300">수급 체크</p>
                <p class="mt-2 text-body leading-7 text-white/92">
                  상승률만 보지 말고 동일 섹터로 자금이 확산되는지 같이 봅니다.
                </p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <p class="text-body-sm text-slate-300">커뮤니티 확인</p>
                <p class="mt-2 text-body leading-7 text-white/92">
                  거래 아이디어와 공시 반응 속도를 비교해서 과열 구간을 피합니다.
                </p>
              </div>
            </div>
          </BaseCard>
        </div>
      </section>
    </div>
  </div>
</template>
