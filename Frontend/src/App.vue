<template>
  <div id="app">
    <template v-if="!isLoggedIn">
      <LoginForm @login="handleLogin" />
    </template>
    <template v-else>
      <header class="topbar">
        <div class="topbar-content">
          <h1>Inventory Management System</h1>
          <div class="nav-links">
            <button class="nav-link" @click="handleLogout">Logout</button>
          </div>
        </div>
      </header>

      <div class="app-layout">
        <Sidebar
          :selectedId="currentProduct ? currentProduct.id : null"
          @search="onSearch"
          @add="openAdd"
          @delete-selected="onDeleteSelected"
          @delete-all="onDeleteAll"
          @go-to-dashboard="goToDashboard"
          @go-to-products="goToList"
          @update-product="openUpdate"
          @import-complete="onImportComplete"
          ref="sidebar"
        />

        <main class="main-content">
          <Dashboard
            v-if="view === 'dashboard' || view === 'form' || view === 'details' || view === 'update'"
            v-show="view === 'dashboard'"
            @view-products="goToList"
            ref="dashboard"
          />
          <ProductList
            v-if="view === 'list' || view === 'form' || view === 'details' || view === 'update'"
            v-show="view === 'list' || view === 'form' || view === 'details' || view === 'update'"
            @add="openAdd"
            @edit="openEdit"
            @view="openView"
            ref="list"
          />
        </main>
      </div>

      <div
        v-if="view === 'form' || view === 'details' || view === 'update'"
        class="panel-backdrop"
        @click="onCancel"
      ></div>

      <div v-if="view === 'form' || view === 'details' || view === 'update'" class="panel-overlay" @click.stop>
        <div class="panel-content">
          <button class="panel-close" @click="onCancel" aria-label="Close panel">✕</button>

          <div v-if="view === 'form'">
            <ProductForm :product="editingProduct" @save="onSave" @cancel="onCancel" />
          </div>

          <div v-else-if="view === 'details'">
            <ProductDetails :product="currentProduct" @close="onCancel" @edit="openEdit" />
          </div>

          <div v-else-if="view === 'update'">
            <UpdateProduct @save="onUpdateSave" @cancel="onCancel" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { ElMessageBox } from 'element-plus'
import ProductList from './components/ProductList.vue'
import ProductForm from './components/ProductForm.vue'
import ProductDetails from './components/ProductDetails.vue'
import UpdateProduct from './components/UpdateProduct.vue'
import Sidebar from './components/Sidebar.vue'
import Dashboard from './components/Dashboard.vue'
import LoginForm from './components/LoginForm.vue'
import store from './store/inventory'

export default {
  name: 'App',
  components: { ProductList, ProductForm, ProductDetails, UpdateProduct, Sidebar, Dashboard, LoginForm },
  data() {
    return {
      isLoggedIn: false,
      username: '',
      view: 'list',
      currentProduct: null,
      editingProduct: null,
    }
  },
  methods: {
    openAdd() {
      this.editingProduct = null
      this.view = 'form'
    },
    openEdit(id) {
      this.editingProduct = store.get(id)
      this.view = 'form'
    },
    openUpdate() {
      this.view = 'update'
    },
    openView(id) {
      this.currentProduct = store.get(id)
      this.view = 'details'
    },
    onSave() {
      this.view = 'list'
      this.editingProduct = null
      if (this.$refs.list && this.$refs.list.load) this.$refs.list.load()
      if (this.$refs.sidebar && this.$refs.sidebar.loadActivity) this.$refs.sidebar.loadActivity()
    },
    onUpdateSave() {
      this.view = 'dashboard'
      this.$nextTick(() => {
        if (this.$refs.list && this.$refs.list.load) this.$refs.list.load()
        if (this.$refs.sidebar && this.$refs.sidebar.loadActivity) this.$refs.sidebar.loadActivity()
        if (this.$refs.dashboard && this.$refs.dashboard.load) this.$refs.dashboard.load()
      })
    },
    onCancel() {
      this.view = 'list'
      this.currentProduct = null
      this.editingProduct = null
    },
    onSearch(q) {
      if (this.$refs.list) this.$refs.list.query = q
    },
    async onDeleteAll() {
      try {
        await ElMessageBox.confirm(
          'This will permanently delete all products. Continue?',
          'Delete all products?',
          {
            confirmButtonText: 'Delete All',
            cancelButtonText: 'Cancel',
            type: 'warning',
          },
        )
        store.clear()
        if (this.$refs.list && this.$refs.list.load) this.$refs.list.load()
        if (this.$refs.sidebar && this.$refs.sidebar.loadActivity) this.$refs.sidebar.loadActivity()
        this.onCancel()
      } catch {
        // User cancelled
      }
    },
    onDeleteSelected(id) {
      if (!id) return
      store.remove(id)
      if (this.$refs.list && this.$refs.list.load) this.$refs.list.load()
      if (this.$refs.sidebar && this.$refs.sidebar.loadActivity) this.$refs.sidebar.loadActivity()
      if (this.currentProduct && this.currentProduct.id === id) {
        this.onCancel()
      }
    },
    onImportComplete() {
      if (this.$refs.list && this.$refs.list.load) this.$refs.list.load()
      if (this.$refs.dashboard && this.$refs.dashboard.load) this.$refs.dashboard.load()
    },
    goToList() {
      this.view = 'list'
      this.$nextTick(() => {
        if (this.$refs.list && this.$refs.list.load) this.$refs.list.load()
      })
    },
    goToDashboard() {
      this.view = 'dashboard'
    },
    async handleLogin(username) {
      this.isLoggedIn = true
      this.username = username
      this.view = 'dashboard'
      // Persist login state
      localStorage.setItem('isLoggedIn', 'true')
      localStorage.setItem('username', username)
      // Clear the activity cleared flag so activity shows again
      sessionStorage.removeItem('activityCleared')
      // Log the login activity
      await store.logActivity({ type: 'login', username })
      // Reload sidebar activity to show the login entry
      if (this.$refs.sidebar && this.$refs.sidebar.loadActivity) {
        this.$refs.sidebar.loadActivity()
      }
    },
    async handleLogout() {
      try {
        await ElMessageBox.confirm(
          'Are you sure you want to log out?',
          'Confirm Logout',
          {
            confirmButtonText: 'Logout',
            cancelButtonText: 'Cancel',
            type: 'warning',
          },
        )
        // Log the logout activity before clearing user info
        await store.logActivity({ type: 'logout', username: this.username })
        this.isLoggedIn = false
        this.username = ''
        this.view = 'dashboard'
        // Clear login state from localStorage
        localStorage.removeItem('isLoggedIn')
        localStorage.removeItem('username')
      } catch {
        // User cancelled
      }
    },
  },
  mounted() {
    // Always start at login page - no session persistence
    // Clear any stale login state
    localStorage.removeItem('isLoggedIn')
    localStorage.removeItem('username')
    this.isLoggedIn = false
    this.username = ''
  },
}
</script>

<style>
body,
html {
  height: 100%;
  margin: 0;
  font-family: Inter, system-ui, Arial;
  overflow: hidden;
}
#app {
  height: 100vh;
  margin: 0;
  font-family: Inter, system-ui, Arial;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.topbar {
  background: var(--color-background-soft);
  color: var(--color-text);
  padding: 8px 12px;
  border-bottom: 1px solid var(--color-border);
  flex-shrink: 0;
}
.topbar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 100%;
  width: 100%;
}
.topbar h1 {
  margin: 0;
  font-size: 18px;
  color: var(--color-heading);
}
.nav-links {
  display: flex;
  gap: 12px;
}
.nav-link {
  background: var(--color-button-bg);
  border: none;
  color: var(--color-button-text);
  cursor: pointer;
  font-size: 13px;
  padding: 6px 12px;
  border-radius: var(--border-radius);
  transition: background 0.2s;
}
.nav-link:hover {
  background: var(--color-button-hover);
}

.app-layout {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.main-content {
  flex: 1;
  overflow: hidden;
  background: var(--color-background-mute);
  display: flex;
  min-height: 0;
}

.panel-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 1200;
}
.panel-overlay {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: var(--right-width, 480px);
  max-width: calc(100% - 48px);
  max-height: calc(100vh - 48px);
  z-index: 1210;
}
.panel-content {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  width: 100%;
  max-height: calc(100vh - 48px);
  padding: 16px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.8);
  overflow: auto;
}
.panel-close {
  position: absolute;
  right: 24px;
  top: 18px;
  background: transparent;
  border: none;
  color: var(--color-text);
  font-size: 18px;
  cursor: pointer;
}

@media (max-width: 900px) {
  .panel-overlay {
    left: 12px;
    right: 12px;
    width: auto;
    top: 12px;
    bottom: 12px;
  }
  .panel-content {
    padding: 10px;
  }
}
</style>
