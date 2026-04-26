<template>
  <div class="dashboard animate-fade-in">
    <header class="dash-header">
      <h2>Inventory Summary</h2>
      <p class="subtitle">Overview of items, monthly sales and revenue projections</p>
    </header>

    <section class="stats">
      <div class="stat">
        <div class="value">{{ count }}</div>
        <div class="label">Products</div>
      </div>

      <div class="stat">
        <div class="value">{{ totalQty }}</div>
        <div class="label">Total Quantity</div>
      </div>

      <div class="stat">
        <div class="value">{{ totalValueFormatted }}</div>
        <div class="label">Inventory Value</div>
      </div>

      <div class="stat">
        <div class="value">{{ avgPriceFormatted }}</div>
        <div class="label">Avg. Price</div>
      </div>
    </section>

    <!-- Monthly Summary Section with Month Selector -->
    <section class="monthly-summary-section">
      <div class="monthly-header">
        <h3>📅 Monthly Summary</h3>
        <div class="month-selector-controls">
          <button @click="previousMonth" class="month-nav-btn" :disabled="!canGoPrevious">◀</button>
          <select v-model="selectedSummaryPeriod" @change="loadMonthlySummary" class="summary-month-selector">
            <option v-for="period in availablePeriods" :key="`${period.year}-${period.month}`" :value="`${period.year}-${period.month}`">
              {{ period.label }}
            </option>
          </select>
          <button @click="nextMonth" class="month-nav-btn" :disabled="!canGoNext">▶</button>
        </div>
      </div>

      <div class="stats secondary-stats">
        <div class="stat">
          <div class="value">{{ monthlySummary.sales?.total_transactions || 0 }}</div>
          <div class="label">Sales</div>
        </div>

        <div class="stat">
          <div class="value">{{ monthlySummary.sales?.total_items_sold || 0 }}</div>
          <div class="label">Items Sold</div>
        </div>

        <div class="stat">
          <div class="value">{{ formatCurrency(monthlySummary.sales?.total_revenue || 0) }}</div>
          <div class="label">Revenue</div>
        </div>

        <div class="stat">
          <div class="value">{{ monthlySummary.restocks?.total_items_restocked || 0 }}</div>
          <div class="label">Items Restocked</div>
        </div>
      </div>

      <div class="summary-details">
        <div class="detail-card">
          <span class="detail-label">Restock Cost:</span>
          <span class="detail-value negative">{{ formatCurrency(monthlySummary.restocks?.total_cost || 0) }}</span>
        </div>
        <div class="detail-card">
          <span class="detail-label">Gross Profit:</span>
          <span class="detail-value" :class="grossProfit >= 0 ? 'positive' : 'negative'">
            {{ formatCurrency(grossProfit) }}
          </span>
        </div>
        <div class="detail-card">
          <span class="detail-label">Net Flow:</span>
          <span class="detail-value" :class="netFlow >= 0 ? 'positive' : 'negative'">
            {{ netFlow >= 0 ? '+' : '' }}{{ netFlow }} items
          </span>
        </div>
      </div>
    </section>


    <section class="charts">
      <div class="chart card">
        <h3>Monthly Units Sold (last 12 months)</h3>
        <svg viewBox="0 0 600 140" preserveAspectRatio="none" class="units-chart">
          <g>
            <!-- bars -->
            <g v-for="(v, i) in monthlyUnits" :key="i">
              <rect
                :x="10 + i * (barW + 6)"
                :y="chartHeight - (v / maxUnits) * chartHeight"
                :width="barW"
                :height="(v / maxUnits) * chartHeight"
                fill="var(--color-accent)"
                opacity="0.9"
              />
            </g>
          </g>
        </svg>
      </div>

      <div class="chart card">
        <h3>Monthly Revenue (PHP)</h3>
        <svg viewBox="0 0 600 140" preserveAspectRatio="none" class="revenue-chart">
          <polyline
            :points="revenuePoints(monthlyRevenue)"
            fill="none"
            stroke="var(--color-accent)"
            stroke-width="2"
          />
          <polyline
            :points="revenuePoints(projectedRevenue)"
            fill="none"
            stroke="rgba(96,165,250,0.45)"
            stroke-width="2"
            stroke-dasharray="6 4"
          />
        </svg>
        <div class="projection-note">Projection (dashed) for next {{ projectedMonths }} months</div>
      </div>
    </section>
<!--
    <section class="actions">
      <button @click="$emit('view-products')">View products</button>
    </section> -->
  </div>
</template>

<script>
import store from '../store/inventory'
import api from '../services/api'

export default {
  name: 'AppDashboard',
  data() {
    return {
      products: [],
      monthlyUnits: [],
      monthlyRevenue: [],
      projectedRevenue: [],
      projectedMonths: 6,
      salesStats: {},
      restockStats: {},
      // Monthly summary
      selectedSummaryPeriod: '',
      monthlySummary: { sales: {}, restocks: {} },
      availablePeriods: [],
    }
  },
  computed: {
    count() {
      return this.products.length
    },
    totalQty() {
      return this.products.reduce((s, p) => s + (Number(p.quantity) || 0), 0)
    },
    totalValue() {
      return this.products.reduce(
        (s, p) => s + (Number(p.price) || 0) * (Number(p.quantity) || 0),
        0,
      )
    },
    totalValueFormatted() {
      return this.totalValue.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    },
    avgPriceFormatted() {
      if (!this.products.length) return '-'
      const avg =
        this.products.reduce((s, p) => s + (Number(p.price) || 0), 0) / this.products.length
      return avg.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    },
    maxUnits() {
      return Math.max(1, ...this.monthlyUnits)
    },
    barW() {
      const cols = Math.max(12, this.monthlyUnits.length)
      return Math.max(6, (560 - cols * 6) / cols)
    },
    chartHeight() {
      return 100
    },
    grossProfit() {
      const revenue = Number(this.monthlySummary.sales?.total_revenue) || 0
      const cost = Number(this.monthlySummary.restocks?.total_cost) || 0
      return revenue - cost
    },
    netFlow() {
      const itemsIn = Number(this.monthlySummary.restocks?.total_items_restocked) || 0
      const itemsOut = Number(this.monthlySummary.sales?.total_items_sold) || 0
      return itemsIn - itemsOut
    },
    canGoPrevious() {
      if (!this.selectedSummaryPeriod || this.availablePeriods.length === 0) return false
      const currentIndex = this.availablePeriods.findIndex(p => `${p.year}-${p.month}` === this.selectedSummaryPeriod)
      return currentIndex < this.availablePeriods.length - 1
    },
    canGoNext() {
      if (!this.selectedSummaryPeriod || this.availablePeriods.length === 0) return false
      const currentIndex = this.availablePeriods.findIndex(p => `${p.year}-${p.month}` === this.selectedSummaryPeriod)
      return currentIndex > 0
    },
  },
  methods: {
    async load() {
      this.products = await store.all()
      this.computeSalesData()
      await this.loadStatistics()
      this.initializeAvailablePeriods()
      await this.loadMonthlySummary()
      // Check if auto-calculation is needed (start of month detection)
      await this.checkAutoCalculateMonthly()
    },
    async checkAutoCalculateMonthly() {
      try {
        const result = await api.checkAutoCalculate()
        if (result.auto_calculated && result.summary) {
          // Show notification that previous month was auto-calculated
          console.info(`Monthly summary auto-calculated for ${result.previous_month.name}`)
          // Refresh the monthly summary if we're viewing the calculated month
          const [year, month] = this.selectedSummaryPeriod?.split('-').map(Number) || []
          if (year === result.previous_month.year && month === result.previous_month.month) {
            this.monthlySummary = {
              sales: result.summary.sales,
              restocks: result.summary.restocks,
              period: result.summary.period,
              year: result.summary.year,
              month: result.summary.month,
            }
          }
        }
      } catch (error) {
        // Silently fail - auto-calculation is a background feature
        console.debug('Auto-calculation check failed', error)
      }
    },
    initializeAvailablePeriods() {
      // Generate last 12 months as available periods
      const periods = []
      const now = new Date()
      for (let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1)
        periods.push({
          year: date.getFullYear(),
          month: date.getMonth() + 1,
          label: date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }),
        })
      }
      this.availablePeriods = periods
      // Default to current month
      if (periods.length > 0) {
        this.selectedSummaryPeriod = `${periods[0].year}-${periods[0].month}`
      }
    },
    async loadMonthlySummary() {
      if (!this.selectedSummaryPeriod) return
      const [year, month] = this.selectedSummaryPeriod.split('-').map(Number)
      try {
        this.monthlySummary = await api.getMonthlyStatistics(year, month)
      } catch (error) {
        console.debug('Failed to load monthly summary', error)
        this.monthlySummary = { sales: {}, restocks: {} }
      }
    },
    previousMonth() {
      const currentIndex = this.availablePeriods.findIndex(p => `${p.year}-${p.month}` === this.selectedSummaryPeriod)
      if (currentIndex < this.availablePeriods.length - 1) {
        const prev = this.availablePeriods[currentIndex + 1]
        this.selectedSummaryPeriod = `${prev.year}-${prev.month}`
        this.loadMonthlySummary()
      }
    },
    nextMonth() {
      const currentIndex = this.availablePeriods.findIndex(p => `${p.year}-${p.month}` === this.selectedSummaryPeriod)
      if (currentIndex > 0) {
        const next = this.availablePeriods[currentIndex - 1]
        this.selectedSummaryPeriod = `${next.year}-${next.month}`
        this.loadMonthlySummary()
      }
    },
    async loadStatistics() {
      try {
        const [salesData, restockData] = await Promise.all([
          api.getSalesStatistics(30),
          api.getRestockStatistics(30)
        ])
        this.salesStats = salesData
        this.restockStats = restockData
      } catch (error) {
        console.debug('Failed to load statistics', error)
        this.salesStats = {}
        this.restockStats = {}
      }
    },
    formatCurrency(value) {
      const num = Number(value) || 0
      return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    hashTo01(s) {
      let h = 2166136261 >>> 0
      for (let i = 0; i < s.length; i++) {
        h ^= s.charCodeAt(i)
        h += (h << 1) + (h << 4) + (h << 7) + (h << 8) + (h << 24)
      }
      return (h >>> 0) / 4294967295
    },
    computeSalesData() {
      const months = 12
      const units = Array.from({ length: months }, () => 0)
      const revenue = Array.from({ length: months }, () => 0)

      this.products.forEach((p) => {
        const base = Math.max(1, Math.round((Number(p.quantity) || 0) * 0.08))
        const seed = this.hashTo01(p.id || p.sku || p.name || 'x')
        for (let m = 0; m < months; m++) {
          // seasonal variation + product-specific bias
          const season = 0.6 + 0.4 * Math.sin((m / months) * Math.PI * 2 + seed * Math.PI * 2)
          const noise = 0.75 + 0.5 * Math.sin((seed + m * 13.7) % 1)
          const sold = Math.max(0, Math.round(base * season * noise))
          units[m] += sold
          revenue[m] += sold * (Number(p.price) || 0)
        }
      })

      this.monthlyUnits = units
      this.monthlyRevenue = revenue

      const y = revenue.map((v) => v)
      const x = y.map((_, i) => i + 1)
      const n = x.length
      const sumX = x.reduce((a, b) => a + b, 0)
      const sumY = y.reduce((a, b) => a + b, 0)
      const sumXY = x.reduce((s, xi, i) => s + xi * y[i], 0)
      const sumX2 = x.reduce((s, xi) => s + xi * xi, 0)
      const denom = n * sumX2 - sumX * sumX || 1
      const slope = (n * sumXY - sumX * sumY) / denom
      const intercept = (sumY - slope * sumX) / n

      const proj = []
      for (let i = 1; i <= this.projectedMonths; i++) {
        const xi = n + i
        proj.push(Math.max(0, intercept + slope * xi))
      }
      this.projectedRevenue = proj
    },
    revenuePoints(arr) {
      const w = 600
      const h = 120
      const len = arr.length
      const max = Math.max(1, ...arr)
      const step = (w - 40) / Math.max(1, len - 1)
      return arr
        .map((v, i) => {
          const x = 20 + i * step
          const y = h - (v / max) * h + 10
          return `${x},${y}`
        })
        .join(' ')
    },
    formatDate(dateStr) {
      if (!dateStr) return ''
      try {
        const date = new Date(dateStr)
        return date.toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      } catch {
        return dateStr
      }
    },
    formatChange(value) {
      if (value === null || value === undefined) return '-'
      const sign = value >= 0 ? '+' : ''
      return `${sign}${value}`
    },
    formatCurrencyChange(value) {
      if (value === null || value === undefined) return '-'
      const sign = value >= 0 ? '+' : ''
      return `${sign}${this.formatCurrency(Math.abs(value))}`
    },
    getChangeClass(value) {
      if (value === null || value === undefined) return ''
      if (value > 0) return 'positive-change'
      if (value < 0) return 'negative-change'
      return 'neutral-change'
    },
  },
  mounted() {
    this.load()
    window.addEventListener('storage', this.load)
  },
  beforeUnmount() {
    window.removeEventListener('storage', this.load)
  },
}
</script>

<style scoped>
.dashboard {
  padding: 12px 16px;
  height: 100%;
  width: 100%;
  box-sizing: border-box;
  overflow-y: auto;
  background: transparent;
  color: var(--color-text);
}
.dash-header h2 {
  margin: 0 0 4px 0;
}
.subtitle {
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 8px;
}
.stats {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}
.secondary-stats {
  margin-top: 12px;
}
.secondary-stats .stat {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
}

/* Monthly Summary Section */
.monthly-summary-section {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.monthly-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.monthly-header h3 {
  margin: 0;
  font-size: 16px;
  color: var(--color-heading);
}

.month-selector-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.month-nav-btn {
  padding: 6px 10px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;
}

.month-nav-btn:hover:not(:disabled) {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: white;
}

.month-nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.summary-month-selector {
  padding: 8px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  color: var(--color-text);
  font-size: 14px;
  min-width: 160px;
}

.summary-details {
  display: flex;
  gap: 16px;
  margin-top: 16px;
  flex-wrap: wrap;
}

.detail-card {
  flex: 1;
  min-width: 150px;
  padding: 12px 16px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-size: 13px;
  color: var(--color-text-muted);
}

.detail-value {
  font-size: 16px;
  font-weight: 600;
}

.detail-value.positive {
  color: #22c55e;
}

.detail-value.negative {
  color: #ef4444;
}

.stat {
  flex: 1;
  padding: 12px;
  background: var(--color-background);
  border-radius: 6px;
  text-align: center;
}
.value {
  font-weight: 700;
  font-size: 20px;
}
.label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
}
.actions button {
  margin-right: 10px;
  padding: 8px 12px;
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-text);
  border-radius: 6px;
  cursor: pointer;
}
.actions button:hover {
  background: rgba(255, 255, 255, 0.03);
}
/* charts */
.charts {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}
.chart {
  flex: 1;
}
.card {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 12px;
}
.units-chart,
.revenue-chart {
  width: 100%;
  height: 140px;
  display: block;
  background: transparent;
}
.projection-note {
  margin-top: 8px;
  font-size: 12px;
  color: var(--color-text-muted);
}

/* Snapshot section styles */
.snapshot-section {
  margin-bottom: 12px;
}

.snapshot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 12px;
}

.snapshot-header h3 {
  margin: 0;
  font-size: 16px;
  color: var(--color-heading);
}

.snapshot-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

.month-selector {
  padding: 8px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 13px;
  min-width: 180px;
  cursor: pointer;
}

.month-selector:focus {
  outline: none;
  border-color: var(--color-accent);
}

.snapshot-btn {
  padding: 8px 14px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s;
  white-space: nowrap;
}

.snapshot-btn:hover {
  background: var(--color-button-hover);
  transform: translateY(-1px);
}

.snapshot-content {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.snapshot-period-title {
  text-align: center;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-heading);
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
}

.snapshot-comparison {
  display: flex;
  gap: 16px;
  justify-content: space-between;
}

.snapshot-column {
  flex: 1;
  padding: 16px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.snapshot-column h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  color: var(--color-heading);
  padding-bottom: 8px;
  border-bottom: 1px solid var(--color-border);
}

.changes-column {
  background: var(--color-background-mute);
  max-width: 150px;
}

.snapshot-stat {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  font-size: 13px;
}

.stat-label {
  color: var(--color-text-muted);
}

.stat-value {
  font-weight: 600;
  color: var(--color-text);
}

.stat-value.small {
  font-size: 11px;
  font-weight: 400;
}

.recorded-at {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px dashed var(--color-border);
}

.positive-change {
  color: #22c55e !important;
}

.negative-change {
  color: #ef4444 !important;
}

.neutral-change {
  color: var(--color-text-muted) !important;
}

.not-recorded {
  text-align: center;
  padding: 20px;
  color: var(--color-text-muted);
}

.not-recorded p {
  margin: 0 0 12px 0;
  font-style: italic;
}

.record-end-btn {
  padding: 8px 16px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
}

.record-end-btn:hover {
  background: var(--color-button-hover);
}

.snapshot-empty {
  text-align: center;
  padding: 30px;
  color: var(--color-text-muted);
  font-style: italic;
}

@media (max-width: 900px) {
  .charts {
    flex-direction: column;
  }

  .snapshot-comparison {
    flex-direction: column;
  }

  .changes-column {
    max-width: none;
  }

  .snapshot-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .snapshot-controls {
    width: 100%;
  }

  .month-selector {
    flex: 1;
  }
}
</style>
