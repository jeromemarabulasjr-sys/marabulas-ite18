<template>
  <div class="product-details animate-fade-in-scale">
    <h3>{{ product.name }}</h3>
    <dl>
      <dt>SKU</dt><dd>{{ product.sku || '-' }}</dd>
      <dt>Type</dt><dd>{{ product.type || '-' }}</dd>
      <dt>Quantity</dt><dd>{{ product.quantity }}</dd>
      <dt>Price</dt><dd>{{ formatPrice(product.price) }}</dd>
      <dt>Notes</dt><dd>{{ product.notes || '-' }}</dd>
    </dl>
    <div class="actions">
      <button @click="$emit('close')">Close</button>
      <button @click="$emit('edit', product.id)">Edit</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductDetails',
  props: { product: { type: Object, required: true } },
  methods: {
    formatPrice(v) {
      if (v == null || v === '') return '-'
      return Number(v).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    }
  }
}
</script>

<style scoped>
.product-details { padding:12px }
.product-details dl { display:grid; grid-template-columns:120px 1fr; gap:6px 12px }
.actions { margin-top:12px }
.actions button { margin-right:6px }
</style>
