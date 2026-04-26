<template>
  <div class="product-form animate-fade-in-scale">
    <h3>{{ isEdit ? 'Edit product' : 'Add product' }}</h3>
    <form @submit.prevent="onSubmit">
      <label>Name *</label>
      <input v-model="form.name" required />

      <label>SKU</label>
      <input v-model="form.sku" />

      <label>Type</label>
      <input v-model="form.type" placeholder="e.g., Electronics, Hardware, Office" />

      <label>Quantity</label>
      <input type="number" v-model.number="form.quantity" min="0" />

      <label>Price</label>
      <input type="number" step="0.01" v-model.number="form.price" min="0" />

      <label>Notes</label>
      <textarea v-model="form.notes"></textarea>

      <div class="form-actions">
        <button type="submit">Save</button>
        <button type="button" @click="$emit('cancel')">Cancel</button>
      </div>
    </form>
  </div>
</template>

<script>
import store from '../store/inventory'
import { ElMessageBox } from 'element-plus'

export default {
  name: 'ProductForm',
  props: {
    product: { type: Object, default: null },
  },
  data() {
    return {
      form: this.product
        ? Object.assign({}, this.product)
        : { name: '', sku: '', type: '', quantity: 0, price: '', notes: '' },
    }
  },
  computed: {
    isEdit() {
      return !!(this.product && this.product.id)
    },
  },
  methods: {
    async isSkuDuplicate(sku) {
      if (!sku || !sku.trim()) return false
      const allProducts = await store.all()
      return allProducts.some(p =>
        p.sku &&
        p.sku.toLowerCase() === sku.toLowerCase() &&
        p.id !== this.form.id
      )
    },
    async onSubmit() {
      if (!this.form.name || !this.form.name.trim()) {
        ElMessageBox.alert('Name is required', 'Validation Error', {
          type: 'warning',
          confirmButtonText: 'OK',
        })
        return
      }
      if (await this.isSkuDuplicate(this.form.sku)) {
        ElMessageBox.alert('This SKU already exists. Please use a unique SKU.', 'Validation Error', {
          type: 'warning',
          confirmButtonText: 'OK',
        })
        return
      }
      try {
        if (this.isEdit) {
          store.update(this.form)
        } else {
          store.add(this.form)
        }
        this.$emit('save')
      } catch (e) {
        console.error(e)
        ElMessageBox.alert('Failed to save product', 'Error', {
          type: 'error',
          confirmButtonText: 'OK',
        })
      }
    },
  },
  watch: {
    product: {
      immediate: true,
      handler(v) {
        this.form = v
          ? Object.assign({}, v)
          : { name: '', sku: '', quantity: 0, price: '', notes: '' }
      },
    },
  },
}
</script>

<style scoped>
.product-form {
  padding: 12px;
}
.product-form label {
  display: block;
  margin-top: 12px;
  margin-bottom: 6px;
  color: var(--color-text);
  font-size: 14px;
  font-weight: 500;
}
.product-form input,
.product-form textarea {
  width: 100%;
  padding: 8px 12px;
  box-sizing: border-box;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 14px;
  transition: border-color 0.2s;
}
.product-form input:focus,
.product-form textarea:focus {
  outline: none;
  border-color: var(--color-accent);
}
.product-form textarea {
  min-height: 80px;
  resize: vertical;
  font-family: inherit;
}
.form-actions {
  margin-top: 20px;
  display: flex;
  gap: 8px;
}
.form-actions button {
  flex: 1;
  background: var(--color-button-bg);
  border: none;
  color: var(--color-button-text);
  padding: 10px 16px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s;
  font-size: 14px;
  font-weight: 500;
}
.form-actions button:hover {
  background: var(--color-button-hover);
}
.form-actions button[type='submit'] {
  background: var(--color-accent);
}
.form-actions button[type='submit']:hover {
  background: var(--color-accent-hover);
}
</style>
