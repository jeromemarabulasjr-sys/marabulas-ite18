<template>
  <div class="product-list animate-fade-in">
    <div class="list-header">
      <div class="sort-controls">
        <span class="sort-label">Sort by:</span>
        <div class="dropdown" v-click-outside="closeDropdown">
          <button @click="toggleDropdown" class="dropdown-btn">
            {{ sortColumn ? getSortLabel(sortColumn) : 'Select' }}
            {{ sortColumn ? (sortDirection === 'asc' ? '▲' : '▼') : '▼' }}
          </button>
          <div v-if="showDropdown" class="dropdown-menu">
            <button @click="selectSort('name')" class="dropdown-item">
              Name
            </button>
            <button @click="selectSort('sku')" class="dropdown-item">
              SKU
            </button>
            <button @click="selectSort('type')" class="dropdown-item">
              Type
            </button>
            <button @click="selectSort('quantity')" class="dropdown-item">
              Quantity
            </button>
            <button @click="selectSort('price')" class="dropdown-item">
              Price
            </button>
          </div>
        </div>
      </div>
      <div class="actions">
        <button @click="reloadProducts" title="Reload products">⟳ Reload</button>
        <button @click="$emit('add')">Add product</button>
      </div>
    </div>

    <div v-if="filtered.length === 0" class="empty">No products found.</div>

    <div v-else class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th class="col-name">Name</th>
            <th class="col-sku">SKU</th>
            <th class="col-type">Type</th>
            <th class="col-qty">Qty</th>
            <th class="col-price">Price</th>
            <th class="col-actions"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in filtered" :key="p.id">
            <td @click="$emit('view', p.id)" class="link col-name">{{ p.name }}</td>
            <td class="col-sku">{{ p.sku || '-' }}</td>
            <td class="col-type">{{ p.type || '-' }}</td>
            <td class="col-qty">{{ p.quantity }}</td>
            <td class="col-price">{{ formatPrice(p.price) }}</td>
            <td class="row-actions col-actions">
              <button @click="$emit('edit', p.id)">Edit</button>
              <button @click="confirmDelete(p.id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { ElMessageBox } from 'element-plus'
import store from '../store/inventory'

export default {
  name: 'ProductList',
  emits: ['add', 'edit', 'view'],
  directives: {
    clickOutside: {
      mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
          if (!(el === event.target || el.contains(event.target))) {
            binding.value()
          }
        }
        document.addEventListener('click', el.clickOutsideEvent)
      },
      unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent)
      },
    },
  },
  data() {
    return {
      products: [],
      query: '',
      sortColumn: null,
      sortDirection: 'asc',
      showDropdown: false,
    }
  },
  computed: {
    filtered() {
      let result = this.products

      // Filter by search query
      if (this.query && this.query.trim()) {
        const q = this.query.toLowerCase().trim()
        result = result.filter(p => {
          return (
            (p.name && p.name.toLowerCase().includes(q)) ||
            (p.sku && p.sku.toLowerCase().includes(q)) ||
            (p.type && p.type.toLowerCase().includes(q))
          )
        })
      }

      // Sort if a column is selected
      if (this.sortColumn) {
        result = [...result].sort((a, b) => {
          let aVal = a[this.sortColumn]
          let bVal = b[this.sortColumn]

          // Handle null/undefined values
          if (aVal == null) aVal = ''
          if (bVal == null) bVal = ''

          // Convert to lowercase for string comparison
          if (typeof aVal === 'string') aVal = aVal.toLowerCase()
          if (typeof bVal === 'string') bVal = bVal.toLowerCase()

          // Compare
          if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1
          if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1
          return 0
        })
      }

      return result
    },
  },
  methods: {
    toggleDropdown() {
      this.showDropdown = !this.showDropdown
    },
    closeDropdown() {
      this.showDropdown = false
    },
    selectSort(column) {
      if (this.sortColumn === column) {
        // Toggle direction if clicking the same column
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        // Set new column and default to ascending
        this.sortColumn = column
        this.sortDirection = 'asc'
      }
      this.showDropdown = false
    },
    getSortLabel(column) {
      const labels = {
        name: 'Name',
        sku: 'SKU',
        type: 'Type',
        quantity: 'Quantity',
        price: 'Price'
      }
      return labels[column] || 'Select'
    },
    async load() {
      this.products = await store.all()
    },
    async reloadProducts() {
      // Force refresh from API
      this.products = await store.all(true)
    },
    formatPrice(v) {
      if (v == null || v === '') return '-'
      return Number(v).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    },
    async confirmDelete(id) {
      try {
        await ElMessageBox.confirm(
          'This will permanently delete this product. Continue?',
          'Delete this product?',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
          },
        )
        await store.remove(id)
        this.load()
      } catch {
        // User cancelled
      }
    },
    seedSample() {
      const sample = [
        {
          id: 'seed-1',
          name: 'AA Batteries (4-pack)',
          sku: 'BAT-4',
          quantity: 42,
          price: 4.99,
          location: 'A1',
        },
        {
          id: 'seed-2',
          name: 'USB-C Cable',
          sku: 'CAB-UC',
          quantity: 13,
          price: 6.5,
          location: 'B3',
        },
        {
          id: 'seed-3',
          name: 'Wireless Mouse',
          sku: 'MOU-01',
          quantity: 8,
          price: 19.99,
          location: 'C2',
        },
      ]
      store.seed(sample)
      this.load()
    },
    async clearAll() {
      // Check if there are products to delete
      if (this.products.length === 0) {
        ElMessageBox.alert('There are no products to delete.', 'No Products', {
          type: 'warning',
        })
        return
      }

      try {
        await ElMessageBox.confirm(
          'This will permanently delete all products. Continue?',
          'Clear all products?',
          {
            confirmButtonText: 'Clear All',
            cancelButtonText: 'Cancel',
            type: 'warning',
          },
        )
        store.clear()
        this.load()
      } catch {
        // User cancelled
      }
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
.product-list {
  padding: 16px;
  height: 100%;
  width: 100%;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
}
.list-header {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
  padding: 12px 16px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}
.table-wrapper {
  flex: 1;
  overflow-y: auto;
  overflow-x: auto;
  min-height: 0;
  width: 100%;
}
.table-wrapper::-webkit-scrollbar {
  height: 12px;
  width: 12px;
}
.table-wrapper::-webkit-scrollbar-track {
  background: var(--color-background);
  border-radius: 6px;
}
.table-wrapper::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 6px;
  border: 2px solid var(--color-background);
}
.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: var(--color-accent);
}
.table-wrapper::-webkit-scrollbar-corner {
  background: var(--color-background);
}
.sort-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}
.sort-label {
  font-size: 13px;
  color: var(--color-text-muted);
  font-weight: 500;
}
.dropdown {
  position: relative;
}
.dropdown-btn {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  padding: 6px 12px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all 0.2s;
  font-size: 13px;
  min-width: 100px;
  text-align: left;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.dropdown-btn:hover {
  background: var(--color-background-mute);
  border-color: var(--color-accent);
}
.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  min-width: 100px;
}
.dropdown-item {
  width: 100%;
  padding: 8px 12px;
  background: transparent;
  border: none;
  color: var(--color-text);
  text-align: left;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 13px;
}
.dropdown-item:hover {
  background: var(--color-background-mute);
}
.dropdown-item:first-child {
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}
.dropdown-item:last-child {
  border-radius: 0 0 var(--border-radius) var(--border-radius);
}
.actions {
  display: flex;
  gap: 6px;
  margin-left: auto;
}
.actions button,
.row-actions button {
  margin-left: 6px;
  background: var(--color-button-bg);
  border: none;
  color: var(--color-button-text);
  padding: 6px 12px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s;
  font-size: 13px;
}
.actions button:hover,
.row-actions button:hover {
  background: var(--color-button-hover);
}
.table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  min-width: 800px;
}
.col-name {
  width: 26%;
}
.col-sku {
  width: 16%;
}
.col-type {
  width: 14%;
}
.col-qty {
  width: 10%;
}
.col-price {
  width: 12%;
}
.col-actions {
  width: 22%;
}
.table th,
.table td {
  padding: 12px 8px;
  border-bottom: 1px solid var(--color-border);
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.col-actions {
  overflow: visible;
  white-space: normal;
}
.link {
  cursor: pointer;
  color: var(--color-accent);
}
.empty {
  padding: 20px;
  color: var(--color-text-muted);
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
