import axios from 'axios'

export const stocksApi = {
  rankings(params = {}) {
    return axios.get('/api/stocks/rankings', { params })
  },
  fetchPrice(code){
      return axios.get(`/api/stocks/${code}/price`)
  }
}
