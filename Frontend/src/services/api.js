import axios from 'axios'

// API base URL - adjust this to match your Laravel server
const API_BASE_URL = 'http://localhost:8000/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Enable credentials for CORS
  timeout: 10000, // 10 second timeout for better UX
})

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Log error for debugging but don't block
    if (error.code === 'ECONNABORTED') {
      console.warn('API request timed out')
    }
    return Promise.reject(error)
  }
)

// Simple in-memory cache for GET requests
const cache = new Map()
const CACHE_TTL = 30000 // 30 seconds

function getCached(key) {
  const item = cache.get(key)
  if (item && Date.now() - item.timestamp < CACHE_TTL) {
    return item.data
  }
  cache.delete(key)
  return null
}

function setCache(key, data) {
  cache.set(key, { data, timestamp: Date.now() })
}

export function clearCache() {
  cache.clear()
}

// Get current username from localStorage
function getCurrentUsername() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null')
  return currentUser?.username || null
}

export default {
  // Get all products (with caching)
  async getProducts(forceRefresh = false) {
    const cacheKey = 'products'
    if (!forceRefresh) {
      const cached = getCached(cacheKey)
      if (cached) return cached
    }
    const response = await api.get('/products')
    setCache(cacheKey, response.data)
    return response.data
  },

  // Get single product
  async getProduct(id) {
    const response = await api.get(`/products/${id}`)
    return response.data
  },

  // Create product
  async createProduct(product) {
    const response = await api.post('/products', {
      ...product,
      _currentUsername: getCurrentUsername()
    })
    return response.data
  },

  // Update product
  async updateProduct(id, product) {
    const response = await api.put(`/products/${id}`, product)
    return response.data
  },

  // Delete product
  async deleteProduct(id) {
    await api.delete(`/products/${id}`, {
      data: { _currentUsername: getCurrentUsername() }
    })
  },

  // Get low stock products
  async getLowStock(threshold = 5) {
    const response = await api.get(`/products/low-stock?threshold=${threshold}`)
    return response.data
  },

  // Import products
  async importProducts(products, replace = false) {
    const response = await api.post('/products/import', {
      products: products,
      replace: replace,
      _currentUsername: getCurrentUsername()
    })
    return response.data
  },

  // Activity log endpoints
  async getActivity(limit = 100) {
    const response = await api.get(`/activity?limit=${limit}`)
    return response.data
  },

  async logActivity(activityData) {
    const response = await api.post('/activity', activityData)
    return response.data
  },

  async clearActivity() {
    const response = await api.delete('/activity/clear')
    return response.data
  },

  // Sales endpoints (with caching for statistics)
  async getSales(limit = 100) {
    const response = await api.get(`/sales?limit=${limit}`)
    return response.data
  },

  async getSalesStatistics(days = 30, forceRefresh = false) {
    const cacheKey = `sales-stats-${days}`
    if (!forceRefresh) {
      const cached = getCached(cacheKey)
      if (cached) return cached
    }
    const response = await api.get(`/sales/statistics?days=${days}`)
    setCache(cacheKey, response.data)
    return response.data
  },

  async getSalesByProduct(productId) {
    const response = await api.get(`/sales/product/${productId}`)
    return response.data
  },

  async getMonthlyStatistics(year, month) {
    const [sales, restocks] = await Promise.all([
      api.get(`/sales/monthly-statistics?year=${year}&month=${month}`),
      api.get(`/restocks/monthly-statistics?year=${year}&month=${month}`)
    ])
    return {
      sales: sales.data,
      restocks: restocks.data,
      period: sales.data.period,
      year,
      month,
    }
  },

  // Restock endpoints (with caching for statistics)
  async getRestocks(limit = 100) {
    const response = await api.get(`/restocks?limit=${limit}`)
    return response.data
  },

  async getRestockStatistics(days = 30, forceRefresh = false) {
    const cacheKey = `restock-stats-${days}`
    if (!forceRefresh) {
      const cached = getCached(cacheKey)
      if (cached) return cached
    }
    const response = await api.get(`/restocks/statistics?days=${days}`)
    setCache(cacheKey, response.data)
    return response.data
  },

  async getRestocksByProduct(productId) {
    const response = await api.get(`/restocks/product/${productId}`)
    return response.data
  },

  // Activity types endpoints (with caching - data rarely changes)
  async getActivityTypes(forceRefresh = false) {
    const cacheKey = 'activity-types'
    if (!forceRefresh) {
      const cached = getCached(cacheKey)
      if (cached) return cached
    }
    const response = await api.get('/activity-types')
    setCache(cacheKey, response.data)
    return response.data
  },


  // Inventory report endpoints
  async generateInventoryReport(startDate = null, endDate = null) {
    const params = {}
    if (startDate) params.start_date = startDate
    if (endDate) params.end_date = endDate
    const response = await api.get('/inventory-report/generate', { params })
    return response.data
  },

  async getAvailableReportMonths() {
    const response = await api.get('/inventory-report/available-months')
    return response.data
  },

  // Monthly Summary endpoints (auto-calculated in/out transactions)
  async getMonthlySummaries() {
    const response = await api.get('/monthly-summary')
    return response.data
  },

  async getMonthlySummary(year, month) {
    const response = await api.get(`/monthly-summary/${year}/${month}`)
    return response.data
  },

  async checkAutoCalculate() {
    const response = await api.get('/monthly-summary/check-auto-calculate')
    return response.data
  },

  async calculateMonthlySummary(year, month, userId = null) {
    const response = await api.post('/monthly-summary/calculate', {
      year,
      month,
      user_id: userId
    })
    return response.data
  },

  // Invalidate cache after mutations
  invalidateCache() {
    clearCache()
  },
}
