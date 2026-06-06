import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

import { marketBrief, marketIndices, turnoverLeaders, watchSeeds } from '../data/homeStocks'
import { stocksApi } from '@/api/stocks'
import { formatPercent, getToneByRate } from '@/utils/stockFormatters'

const stockRequestDelayMs = 350
const rankingTypes = ['gainers', 'losers', 'volume', 'turnover']
const rankingLabels = {
  gainers: '급등',
  losers: '급락',
  volume: '거래량',
  turnover: '거래대금',
}

export function useHomeStocks() {
  const activeRankingType = ref('gainers')
  const marketFilter = ref('all')
  const searchCode = ref('')
  const featuredCode = ref('')
  const featuredStock = ref(null)
  const watchlist = ref(watchSeeds.map((stock) => ({ ...stock, loading: true, error: false })))
  const rankings = ref(createEmptyRankings())
  const rankingsError = ref('')
  const isRankingsLoading = ref(false)
  const isFeaturedLoading = ref(true)
  const featuredError = ref('')
  const lastUpdatedAt = ref('')
  let featuredRequestId = 0
  let rankingRequestId = 0

  const marketSummary = computed(() => {
    const items = filteredMarketItems.value
    const rising = items.filter((item) => item.changeRate > 0).length
    const falling = items.filter((item) => item.changeRate < 0).length
    const average = items.length
      ? items.reduce((sum, item) => sum + item.changeRate, 0) / items.length
      : 0

    return {
      indices: marketIndices,
      breadth: [
        { label: '상승 종목', value: rising, tone: rising >= falling ? 'rise' : 'neutral' },
        { label: '하락 종목', value: falling, tone: falling > rising ? 'fall' : 'neutral' },
        { label: '평균 등락률', value: formatPercent(average), tone: getToneByRate(average) },
      ],
      brief: marketBrief,
      scope: marketBrief.scope,
    }
  })

  const marketPulse = computed(() => {
    const items = filteredMarketItems.value

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

  const filteredMarketItems = computed(() => {
    const items = watchlist.value.filter((item) => !item.error && typeof item.changeRate === 'number')

    if (marketFilter.value === 'all') {
      return items
    }

    return items.filter((item) => item.market === marketFilter.value)
  })

  const strongestStock = computed(() => {
    const items = filteredMarketItems.value
    return [...items].sort((a, b) => b.changeRate - a.changeRate)[0] ?? null
  })

  const weakestStock = computed(() => {
    const items = filteredMarketItems.value
    return [...items].sort((a, b) => a.changeRate - b.changeRate)[0] ?? null
  })

  const topGainers = computed(() => {
    if (rankings.value.gainers.length) {
      return rankings.value.gainers
    }

    return [...filteredMarketItems.value]
      .filter((item) => item.changeRate > 0)
      .sort((a, b) => b.changeRate - a.changeRate)
      .slice(0, 10)
  })

  const topLosers = computed(() => {
    if (rankings.value.losers.length) {
      return rankings.value.losers
    }

    return [...filteredMarketItems.value]
      .filter((item) => item.changeRate < 0)
      .sort((a, b) => a.changeRate - b.changeRate)
      .slice(0, 10)
  })

  const volumeStocks = computed(() => {
    if (rankings.value.volume.length) {
      return rankings.value.volume
    }

    return [...filteredMarketItems.value]
      .sort((a, b) => b.volume - a.volume)
      .slice(0, 10)
  })

  const turnoverStocks = computed(() => {
    if (rankings.value.turnover.length) {
      return rankings.value.turnover
    }

    return turnoverLeaders.map((leader) => {
      const liveStock = watchlist.value.find((item) => item.code === leader.code)

      return {
        ...leader,
        ...liveStock,
        turnover: leader.turnover,
        reason: leader.reason,
      }
    })
  })

  const communityStocks = computed(() => {
    return watchlist.value.reduce((stocks, stock) => {
      stocks[stock.code] = stock
      return stocks
    }, {})
  })

  onMounted(() => {
    loadRanking(activeRankingType.value)
    loadWatchlist()
  })

  async function loadRanking(type = activeRankingType.value) {
    const rankingType = rankingTypes.includes(type) ? type : 'gainers'
    const requestId = ++rankingRequestId

    activeRankingType.value = rankingType
    isRankingsLoading.value = true
    rankingsError.value = ''

    try {
      const response = await stocksApi.rankings({ limit: 10, type: rankingType })
      const rankingData = response.data?.data?.rankings ?? {}

      if (requestId !== rankingRequestId) {
        return
      }

      rankings.value = {
        ...rankings.value,
        [rankingType]: mapRankingItems(rankingData[rankingType]),
      }

      if (response.data?.data?.asOf) {
        lastUpdatedAt.value = formatKoreanTime(response.data.data.asOf)
      }
    } catch (error) {
      if (requestId !== rankingRequestId) {
        return
      }

      console.error(`${rankingLabels[rankingType]} 랭킹 로딩 실패:`, error)
      rankingsError.value = `${rankingLabels[rankingType]} 랭킹을 불러오지 못했습니다`
    } finally {
      if (requestId === rankingRequestId) {
        isRankingsLoading.value = false
      }
    }
  }

  async function loadWatchlist() {
    watchlist.value = watchSeeds.map((stock) => ({ ...stock, loading: true, error: false }))

    for (const [index, stock] of watchSeeds.entries()) {
      try {
        const data = await fetchPrice(stock.code)
        updateWatchlistItem(index, {
          ...mapStock(stock, data),
          loading: false,
          error: false,
        })
      } catch (error) {
        console.error(`${stock.name} 시세 로딩 실패:`, error)
        updateWatchlistItem(index, createEmptyStock(stock, true))
      }

      if (index < watchSeeds.length - 1) {
        await delay(stockRequestDelayMs)
      }
    }

    lastUpdatedAt.value = new Intl.DateTimeFormat('ko-KR', {
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date())
  }

  async function handleSearch() {
    const destination = resolveSearchDestination(searchCode.value)

    if (!destination) {
      return
    }

    window.location.assign(destination)
  }

  async function handleSelectStock(code) {
    searchCode.value = code
    featuredCode.value = code
    await loadFeaturedStock(code)
  }

  async function loadFeaturedStock(code) {
    const requestId = ++featuredRequestId

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

      if (requestId !== featuredRequestId) {
        return
      }

      featuredStock.value = mapStock(seed, data)
    } catch (error) {
      console.error('대표 종목 로딩 실패:', error)

      if (requestId !== featuredRequestId) {
        return
      }

      featuredStock.value = null
      featuredError.value = '대표 종목 정보를 불러오지 못했습니다'
    } finally {
      if (requestId === featuredRequestId) {
        isFeaturedLoading.value = false
      }
    }
  }

  function updateWatchlistItem(index, stock) {
    watchlist.value = watchlist.value.map((item, itemIndex) => (
      itemIndex === index ? stock : item
    ))
  }

  return {
    activeRankingType,
    featuredCode,
    featuredError,
    featuredStock,
    filteredWatchlist,
    communityStocks,
    handleSearch,
    handleSelectStock,
    isFeaturedLoading,
    isRankingsLoading,
    lastUpdatedAt,
    loadRanking,
    loadWatchlist,
    marketFilter,
    marketPulse,
    marketSummary,
    rankingsError,
    searchCode,
    strongestStock,
    topGainers,
    topLosers,
    turnoverStocks,
    volumeStocks,
    weakestStock,
  }
}

function resolveSearchDestination(keyword) {
  const query = keyword.trim()

  if (!query) {
    return ''
  }

  const normalizedQuery = query.toLowerCase()
  const matchedIndex = marketIndices.find((index) => (
    index.code.toLowerCase() === normalizedQuery ||
    index.name.toLowerCase() === normalizedQuery
  ))

  if (matchedIndex) {
    return `/markets/${matchedIndex.code.toLowerCase()}`
  }

  const matchedStock = watchSeeds.find((stock) => (
    stock.code === query ||
    stock.name.toLowerCase() === normalizedQuery
  ))

  if (matchedStock) {
    return `/stocks/${matchedStock.code}`
  }

  if (/^\d{6}$/.test(query)) {
    return `/stocks/${query}`
  }

  return `/stocks/search?query=${encodeURIComponent(query)}`
}

async function fetchPrice(code) {
  const response = await stocksApi.fetchPrice(code)
  console.log(response);
  if (response.data?.success === false) {
    throw new Error(response.data.message ?? '시세 조회에 실패했습니다')
  }

  return response.data
}

function createEmptyStock(seed, error = false) {
  return {
    ...seed,
    loading: false,
    error,
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
}

function createEmptyRankings() {
  return {
    gainers: [],
    losers: [],
    volume: [],
    turnover: [],
  }
}

function formatKoreanTime(value) {
  return new Intl.DateTimeFormat('ko-KR', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

function mapRankingItems(items = []) {
  return items.map((item) => ({
    code: item.code,
    name: item.name,
    market: item.market,
    venue: item.venue,
    sector: item.sector,
    price: Number(item.price ?? 0),
    change: Number(item.change ?? 0),
    changeRate: Number(item.changeRate ?? item.change_rate ?? 0),
    volume: Number(item.volume ?? 0),
    turnover: Number(item.turnover ?? 0),
    turnoverAmount: Number(item.turnoverAmount ?? item.turnover_amount ?? 0),
    reason: item.reason,
    asOf: item.asOf ?? item.as_of,
  }))
}

function delay(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms)
  })
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
