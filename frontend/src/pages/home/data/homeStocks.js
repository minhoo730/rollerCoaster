export const marketOptions = [
  { label: '전체 시장', value: 'all' },
  { label: '코스피', value: 'kospi' },
  { label: '코스닥', value: 'kosdaq' },
  { label: '해외 관심종목', value: 'global' },
]

export const marketIndices = [
  {
    code: 'KOSPI',
    name: '코스피',
    value: 2876.42,
    change: 18.42,
    changeRate: 0.64,
  },
  {
    code: 'KOSDAQ',
    name: '코스닥',
    value: 914.31,
    change: -4.28,
    changeRate: -0.47,
  },
]

export const marketBrief = {
  scope: '관심종목 기준',
  summary: '반도체 대형주는 강세, 2차전지와 바이오는 종목별 차별화가 커지는 장세입니다.',
  leadingSector: '반도체',
  weakSector: '2차전지',
}

export const watchSeeds = [
  { code: '005930', name: '삼성전자', market: 'kospi', thesis: '반도체 업황 반등과 외국인 수급 회복 여부' },
  { code: '000660', name: 'SK하이닉스', market: 'kospi', thesis: 'HBM 수요 강세와 AI 서버 투자 수혜' },
  { code: '035420', name: 'NAVER', market: 'kospi', thesis: '광고 회복과 커머스 수익성 개선 기대' },
  { code: '068270', name: '셀트리온', market: 'kospi', thesis: '합병 이후 실적 가시성과 바이오시밀러 모멘텀' },
  { code: '247540', name: '에코프로비엠', market: 'kosdaq', thesis: '2차전지 업황 회복 시 민감하게 반응하는 대표주' },
  { code: '091990', name: '셀트리온헬스케어', market: 'kosdaq', thesis: '헬스케어 섹터 회복 구간에서 거래대금 유입 주목' },
]

export const turnoverLeaders = [
  { code: '005930', name: '삼성전자', market: 'kospi', turnover: 18420, reason: '외국인 순매수 재유입' },
  { code: '000660', name: 'SK하이닉스', market: 'kospi', turnover: 15780, reason: 'HBM 실적 기대' },
  { code: '247540', name: '에코프로비엠', market: 'kosdaq', turnover: 8420, reason: '2차전지 반등 시도' },
  { code: '035420', name: 'NAVER', market: 'kospi', turnover: 6120, reason: '플랫폼주 저가 매수' },
]

export const communityPosts = [
  { id: 1, title: '오늘 반도체 섹터 매매 포인트 정리', stockCode: '000660', stockName: 'SK하이닉스', meta: '실시간 토론 · 128명 참여' },
  { id: 2, title: '삼성전자 수급, 이번 주에도 이어질까요?', stockCode: '005930', stockName: '삼성전자', meta: '종목 토론 · 64개 댓글' },
  { id: 3, title: '2차전지 반등은 기술적 반등일까', stockCode: '247540', stockName: '에코프로비엠', meta: '섹터 토론 · 42개 새 댓글' },
]

export const checklistItems = [
  {
    title: '1분 점검',
    description: '대표 종목의 고가 돌파 여부와 거래량 급증 구간을 먼저 확인합니다.',
  },
  {
    title: '수급 체크',
    description: '상승률만 보지 말고 동일 섹터로 자금이 확산되는지 같이 봅니다.',
  },
  {
    title: '커뮤니티 확인',
    description: '거래 아이디어와 공시 반응 속도를 비교해서 과열 구간을 피합니다.',
  },
]
