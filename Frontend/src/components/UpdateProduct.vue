<template>
  <div class="update-product animate-fade-in">
    <h3>Update Product Stock</h3>

    <div class="search-section">
      <div class="field">
        <label class="label">Search Product</label>
        <input
          v-model="searchQuery"
          @input="searchProducts"
          placeholder="Search by name or SKU"
          class="input"
        />
      </div>

      <div v-if="searchResults.length > 0" class="search-results">
        <div
          v-for="product in searchResults"
          :key="product.id"
          @click="selectProduct(product)"
          class="result-item"
          :class="{ selected: selectedProduct && selectedProduct.id === product.id }"
        >
          <div class="result-info">
            <strong>{{ product.name }}</strong>
            <div class="result-meta">SKU: {{ product.sku || '—' }} | Current Qty: {{ product.quantity || 0 }}</div>
          </div>
        </div>
      </div>

      <div v-if="searchQuery && searchResults.length === 0" class="no-results">
        No products found
      </div>
    </div>

    <div v-if="selectedProduct" class="update-section">
      <div class="selected-product-info">
        <h4>{{ selectedProduct.name }}</h4>
        <div class="product-details">
          <div class="detail-item">
            <span class="detail-label">SKU:</span>
            <span class="detail-value">{{ selectedProduct.sku || '—' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Current Stock:</span>
            <span class="detail-value">{{ selectedProduct.quantity || 0 }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Price:</span>
            <span class="detail-value">{{ formatPrice(selectedProduct.price) }}</span>
          </div>
        </div>
      </div>

      <div class="action-tabs">
        <button
          @click="actionType = 'sold'"
          :class="{ active: actionType === 'sold' }"
          class="tab-btn"
        >
          📉 Mark as Sold
        </button>
        <button
          @click="actionType = 'restock'"
          :class="{ active: actionType === 'restock' }"
          class="tab-btn"
        >
          📈 Restock
        </button>
      </div>

      <div class="field">
        <label class="label">
          {{ actionType === 'sold' ? 'Quantity Sold' : 'Quantity to Add' }}
        </label>
        <input
          v-model.number="updateQuantity"
          type="number"
          min="1"
          placeholder="Enter quantity"
          class="input"
        />
      </div>

      <div v-if="actionType === 'sold' && updateQuantity > (selectedProduct.quantity || 0)" class="warning-msg">
        ⚠️ Warning: Sold quantity exceeds current stock
      </div>

      <div class="action-buttons">
        <button
          @click="performUpdate"
          :disabled="!updateQuantity || updateQuantity <= 0"
          class="btn-primary"
        >
          {{ actionType === 'sold' ? 'Process Sale' : 'Add Stock' }}
        </button>
        <button @click="$emit('cancel')" class="btn-secondary">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script>
import store from '@/store/inventory.js'
import { ElMessage } from 'element-plus'

export default {
  name: 'UpdateProduct',
  data() {
    return {
      searchQuery: '',
      searchResults: [],
      selectedProduct: null,
      actionType: 'sold',
      updateQuantity: null,
    }
  },
  methods: {
    async searchProducts() {
      if (!this.searchQuery.trim()) {
        this.searchResults = []
        return
      }

      const query = this.searchQuery.toLowerCase()
      const allProducts = await store.all()

      this.searchResults = allProducts.filter(p => {
        const name = (p.name || '').toLowerCase()
        const sku = (p.sku || '').toLowerCase()
        return name.includes(query) || sku.includes(query)
      }).slice(0, 10) // Limit to 10 results
    },
    selectProduct(product) {
      this.selectedProduct = { ...product }
      this.updateQuantity = null
    },
    async performUpdate() {
      if (!this.selectedProduct || !this.updateQuantity || this.updateQuantity <= 0) {
        return
      }

      const currentQty = Number(this.selectedProduct.quantity || 0)
      let newQty

      if (this.actionType === 'sold') {
        newQty = Math.max(0, currentQty - this.updateQuantity)

        if (this.updateQuantity > currentQty) {
          ElMessage.warning({
            message: `Warning: Sold quantity (${this.updateQuantity}) exceeds current stock (${currentQty}). Stock set to 0.`,
            duration: 5000
          })
        }
      } else {
        newQty = currentQty + this.updateQuantity
      }

      // Update the product
      const updatedProduct = {
        ...this.selectedProduct,
        quantity: newQty,
        _updateType: this.actionType, // Add metadata for activity logging
        _quantityChange: this.updateQuantity
      }

      await store.update(updatedProduct)

      const actionText = this.actionType === 'sold' ? 'sold' : 'restocked'
      ElMessage.success({
        message: `Successfully ${actionText} ${this.updateQuantity} unit(s). New stock: ${newQty}`,
        duration: 3000
      })

      this.$emit('save')
    }
    ,
    formatPrice(v) {
      return v == null || v === '' ? '-' : Number(v).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    }
  }
}
</script>

<style scoped>
.update-product {
  padding: 8px 0;
}

h3 {
  margin: 0 0 20px 0;
  color: var(--color-heading);
  font-size: 20px;
}

h4 {
  margin: 0 0 12px 0;
  color: var(--color-heading);
  font-size: 16px;
}

.search-section {
  margin-bottom: 24px;
}

.field {
  margin-bottom: 16px;
}

.label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--color-text);
  font-size: 13px;
}

.input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-background-mute);
  color: var(--color-text);
  font-size: 14px;
  box-sizing: border-box;
}

.input:focus {
  outline: none;
  border-color: var(--color-border-hover);
}

.search-results {
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  max-height: 240px;
  overflow-y: auto;
  background: var(--color-background-mute);
  margin-top: 8px;
}

.result-item {
  padding: 12px;
  cursor: pointer;
  border-bottom: 1px solid var(--color-border);
  transition: background 0.2s;
}

.result-item:last-child {
  border-bottom: none;
}

.result-item:hover {
  background: var(--color-background-soft);
}

.result-item.selected {
  background: var(--vt-c-indigo);
  color: white;
}

.result-item.selected .result-meta {
  color: rgba(255, 255, 255, 0.8);
}

.result-info strong {
  display: block;
  margin-bottom: 4px;
}

.result-meta {
  font-size: 12px;
  color: var(--color-text-muted);
}

.no-results {
  padding: 16px;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 13px;
}

.update-section {
  border-top: 1px solid var(--color-border);
  padding-top: 20px;
}

.selected-product-info {
  background: var(--color-background-mute);
  padding: 16px;
  border-radius: var(--border-radius);
  margin-bottom: 20px;
}

.product-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.detail-label {
  color: var(--color-text-muted);
}

.detail-value {
  font-weight: 500;
  color: var(--color-text);
}

.action-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
}

.tab-btn {
  flex: 1;
  padding: 12px;
  border: 1px solid var(--color-border);
  background: var(--color-background-mute);
  color: var(--color-text);
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: var(--color-background-soft);
}

.tab-btn.active {
  background: var(--vt-c-indigo);
  color: white;
  border-color: var(--vt-c-indigo);
}

.warning-msg {
  padding: 12px;
  background: rgba(255, 193, 7, 0.1);
  border: 1px solid rgba(255, 193, 7, 0.3);
  border-radius: var(--border-radius);
  color: #ff9800;
  font-size: 13px;
  margin-bottom: 16px;
}

.action-buttons {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.btn-primary,
.btn-secondary {
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: var(--border-radius);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: var(--vt-c-indigo);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--vt-c-indigo-dark);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--color-background-mute);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.btn-secondary:hover {
  background: var(--color-background-soft);
}
</style>
