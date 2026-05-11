<template>
  <div class="min-h-screen bg-bg text-text-primary">
    <MarketHeader
      v-model:search-code="searchCode"
      @search="handleSearch"
    />

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <MarketSummary
        :summary="marketSummary"
        :last-updated-at="lastUpdatedAt"
      />

      <div class="mt-6 space-y-6">
        <TopMovers
          v-model:active-type="activeRankingType"
          :gainers="topGainers"
          :losers="topLosers"
          :volume-stocks="volumeStocks"
          :turnover-stocks="turnoverStocks"
          :loading="isRankingsLoading"
          :error="rankingsError"
          @select="loadRanking"
        />

        <section class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]">
          <PersonalWatchlist
            v-model:market-filter="marketFilter"
            :stocks="filteredWatchlist"
            :market-options="marketOptions"
          />

          <CommunityHighlights
            :posts="communityPosts"
            :stocks-by-code="communityStocks"
          />
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import CommunityHighlights from './components/CommunityHighlights.vue'
import MarketHeader from './components/MarketHeader.vue'
import MarketSummary from './components/MarketSummary.vue'
import PersonalWatchlist from './components/PersonalWatchlist.vue'
import TopMovers from './components/TopMovers.vue'
import { useHomeStocks } from './composables/useHomeStocks'
import { communityPosts, marketOptions } from './data/homeStocks'

const {
  activeRankingType,
  communityStocks,
  filteredWatchlist,
  handleSearch,
  isRankingsLoading,
  lastUpdatedAt,
  loadRanking,
  marketFilter,
  marketSummary,
  rankingsError,
  searchCode,
  topGainers,
  topLosers,
  turnoverStocks,
  volumeStocks,
} = useHomeStocks()
</script>
