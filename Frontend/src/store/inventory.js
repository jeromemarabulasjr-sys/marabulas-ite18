import api from '../services/api.js'

const STORAGE_KEY = 'inventory-products-v1'

// Cache for localStorage to reduce parsing overhead
let cachedProducts = null
let cacheValid = false

function load() {
  if (cacheValid && cachedProducts !== null) {
    return cachedProducts
  }
  try {
    cachedProducts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    cacheValid = true
    return cachedProducts
  } catch (e) {
    console.debug('Failed to parse inventory from localStorage', e)
    cachedProducts = []
    cacheValid = true
    return cachedProducts
  }
}

function saveAll(list) {
  cachedProducts = list
  cacheValid = true
  localStorage.setItem(STORAGE_KEY, JSON.stringify(list))
}

function invalidateCache() {
  cacheValid = false
  cachedProducts = null
}

async function logActivity(entry) {
  try {
    // Get current user from localStorage
    const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null')

    // Send to backend API with user info
    await api.logActivity({
      type: entry.type,
      details: {
        ...entry,
        madeBy: currentUser ? {
          username: currentUser.username,
          email: currentUser.email
        } : null
      }
    })
  } catch (e) {
    console.debug('logActivity failed', e)
  }
}

function generateId() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 8)
}

export default {
  async all(forceRefresh = false) {
    // Invalidate cache if force refresh requested
    if (forceRefresh) {
      invalidateCache()
    }
    // Try to load from API first
    try {
      const products = await api.getProducts(forceRefresh)
      saveAll(products)
      return products
    } catch (e) {
      console.debug('Failed to load from API, using localStorage', e)
      return load()
    }
  },
  get(id) {
    return load().find((p) => p.id === id) || null
  },
  async add(product) {
    // Create in backend first
    try {
      const created = await api.createProduct(product)
      // Invalidate cache so next all() fetches fresh data from API
      invalidateCache()
      await logActivity({ type: 'add', id: created.id, name: created.name })
      return created
    } catch (e) {
      console.error('Failed to create product in backend', e)
      throw e // Re-throw so the UI knows it failed
    }
  },
  async update(updated) {
    const list = load()
    const idx = list.findIndex((p) => p.id === updated.id)
    if (idx === -1) throw new Error('Product not found')

    // Track what changed
    const original = list[idx]
    const changes = []
    const fields = ['name', 'sku', 'type', 'quantity', 'price', 'notes']

    fields.forEach(field => {
      // Convert to string for comparison to handle type differences
      const origVal = original[field] != null ? String(original[field]) : ''
      const newVal = updated[field] != null ? String(updated[field]) : ''
      if (origVal !== newVal) {
        changes.push(field)
      }
    })

    list[idx] = Object.assign({}, updated)
    saveAll(list)

    // Update in backend database (backend will handle activity logging)
    try {
      // Include current user info for backend activity logging
      const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null')
      const productData = {
        ...updated,
        _currentUsername: currentUser?.username || null
      }
      console.log('[inventory.js] Updating product:', {
        id: updated.id,
        username: currentUser?.username,
        oldQuantity: original.quantity,
        newQuantity: updated.quantity
      })
      const response = await api.updateProduct(updated.id, productData)
      console.log('[inventory.js] Update response:', response)
    } catch (e) {
      console.error('[inventory.js] Failed to update product in backend:', e)
      console.error('[inventory.js] Error details:', e.response?.data || e.message)
      console.error('[inventory.js] Product ID:', updated.id)
      console.error('[inventory.js] Product data:', updated)
    }

    return list[idx]
  },
  async remove(id) {
    // Remove from backend first
    try {
      await api.deleteProduct(id)
    } catch (e) {
      console.error('Failed to delete product from backend', e)
    }

    const list = load().filter((p) => p.id !== id)
    saveAll(list)
    await logActivity({ type: 'remove', id })
  },
  async clear() {
    saveAll([])
    await logActivity({ type: 'clear' })
  },
  async seed(sample = []) {
    saveAll(sample)
    await logActivity({ type: 'seed', count: sample.length })
  },
  // utility: return items below threshold (quantity <= threshold)
  lowStock(threshold = 5) {
    return load().filter((p) => (Number(p.quantity) || 0) <= threshold)
  },
  // export products as array (for CSV generation in UI)
  exportAll() {
    return load()
  },
  // import array of products (replace: true => overwrite store, false => merge by id)
  async importFromArray(items = [], replace = false) {
    try {
      // Use backend import API
      const response = await api.importProducts(items, replace)
      const imported = response.products || response
      saveAll(imported)
      await logActivity({ type: 'import', count: imported.length, replace: !!replace })
      return imported
    } catch (e) {
      console.error('Failed to import via backend, using localStorage fallback', e)
      // Fallback to localStorage only
      const cur = load()
      let out
      if (replace) {
        out = items.map((p) => Object.assign({}, p))
      } else {
        // merge: keep existing IDs, add new ones if missing
        const byId = Object.fromEntries(cur.map((p) => [p.id, p]))
        items.forEach((p) => {
          if (p.id && byId[p.id]) {
            byId[p.id] = Object.assign({}, p)
          } else {
            const np = Object.assign({}, p)
            np.id = np.id || generateId()
            byId[np.id] = np
          }
        })
        out = Object.values(byId)
      }
      saveAll(out)
      await logActivity({ type: 'import', count: out.length, replace: !!replace })
      return out
    }
  },
  // activity helpers
  async getActivity() {
    try {
      return await api.getActivity(100)
    } catch (e) {
      console.debug('Failed to load activity from backend', e)
      return []
    }
  },
  async logActivity(entry) {
    return logActivity(entry)
  },
  async clearActivity() {
    try {
      await api.clearActivity()
    } catch (e) {
      console.debug('Failed to clear activity', e)
    }
  },
}
