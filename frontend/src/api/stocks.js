import axios from 'axios'

export const stocksApi = {
  rankings(params = {}) {
    return axios.get('/api/stocks/rankings', { params })
  },
}
