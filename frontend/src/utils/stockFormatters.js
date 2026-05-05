export function formatNumber(value) {
  return new Intl.NumberFormat('ko-KR').format(value ?? 0)
}

export function formatCurrency(value) {
  return `${formatNumber(value)}원`
}

export function formatPercent(value) {
  const sign = value > 0 ? '+' : ''
  return `${sign}${Number(value ?? 0).toFixed(2)}%`
}

export function formatSignedNumber(value) {
  const sign = value > 0 ? '+' : ''
  return `${sign}${formatNumber(value ?? 0)}`
}

export function getToneByRate(rate) {
  if (rate > 0) return 'rise'
  if (rate < 0) return 'fall'
  return 'neutral'
}
