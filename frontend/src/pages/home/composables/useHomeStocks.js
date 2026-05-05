import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

import { marketBrief, marketIndices, turnoverLeaders, watchSeeds } from '../data/homeStocks'
import { formatPercent, getToneByRate } from '@/utils/stockFormatters'

const stockRequestDelayMs = 350

export function useHomeStocks() {
  const marketFilter = ref('all')
  const searchCode = ref('')
  const featuredCode = ref('')
  const featuredStock = ref(null)
  const watchlist = ref(watchSeeds.map((stock) => ({ ...stock, loading: true, error: false })))
  const isFeaturedLoading = ref(true)
  const featuredError = ref('')
  const lastUpdatedAt = ref('')
  let featuredRequestId = 0

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
    return [...filteredMarketItems.value]
      .filter((item) => item.changeRate > 0)
      .sort((a, b) => b.changeRate - a.changeRate)
      .slice(0, 5)
  })

  const topLosers = computed(() => {
    return [...filteredMarketItems.value]
      .filter((item) => item.changeRate < 0)
      .sort((a, b) => a.changeRate - b.changeRate)
      .slice(0, 5)
  })

  const turnoverStocks = computed(() => {
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

  onMounted(async () => {
    await loadWatchlist()
  })

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
    featuredCode,
    featuredError,
    featuredStock,
    filteredWatchlist,
    communityStocks,
    handleSearch,
    handleSelectStock,
    isFeaturedLoading,
    lastUpdatedAt,
    loadWatchlist,
    marketFilter,
    marketPulse,
    marketSummary,
    searchCode,
    strongestStock,
    topGainers,
    topLosers,
    turnoverStocks,
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
  const response = await axios.get(`/api/stocks/${code}/price`)

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
