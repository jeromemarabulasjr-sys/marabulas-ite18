<template>
  <aside class="sidebar">
    <div class="bar-header">
      <strong>Menu</strong>
      <div class="header-actions">
        <button
          class="settings-btn"
          @click="showSettings = !showSettings"
          :aria-pressed="showSettings"
          aria-label="Open settings"
        >
          ⚙
        </button>
      </div>
    </div>

    <div class="controls">
      <div class="section">
        <div class="buttons">
          <button @click="$emit('go-to-dashboard')">📊 Dashboard</button>
          <button @click="$emit('go-to-products')">📦 Products</button>
          <button @click="$emit('update-product')">🔄 Update Product</button>
          <button @click="showExportFileTypeSelector">📋 Generate Report</button>
        </div>
      </div>

      <div class="section">
        <label class="field">
          <div class="label">Search</div>
          <input v-model="q" @input="onSearch" placeholder="Search name or SKU" />
        </label>

        <div class="buttons">
          <button @click="$emit('add')">Add</button>
          <button @click="deleteSelected">Delete selected</button>
          <button @click="$emit('delete-all')">Delete all</button>
        </div>
      </div>

      <div class="section">
        <div class="widget">
          <h5>Low stock</h5>
          <div v-if="lowStock.length === 0" class="small">No low-stock items</div>
          <div v-for="p in lowStock" :key="p.id" class="item">
            <strong>{{ p.name }}</strong>
            <div class="small">Qty: {{ p.quantity || 0 }} · SKU: {{ p.sku || '—' }}</div>
          </div>
          <div style="margin-top: 8px">
            <button @click="loadLowStock">Refresh</button>
          </div>
        </div>

        <div class="widget">
          <h5>Recent activity</h5>
          <div v-if="recentActivity.length === 0" class="small">No recent activity</div>
          <div v-for="(a, idx) in recentActivity" :key="idx" class="activity-item-wrapper">
            <div class="item activity-item">
              <div style="flex: 1">
                <div>
                  <strong>{{ formatActivityType(a.type) }}</strong> <span class="small">{{ getActivityDetails(a) }}</span>
                </div>
                <div class="small">{{ formatDate(a.at) }} · {{ a.madeBy?.username || 'System' }}</div>
              </div>
              <button class="activity-details-btn" @click="toggleActivityDetails(idx)" title="View details">
                ⋮
              </button>
            </div>
            <div v-if="expandedActivity === idx" class="activity-details">
              <div class="detail-row">
                <span class="detail-label">Made by:</span>
                <span class="detail-value">{{ a.madeBy?.username || 'System' }}</span>
              </div>
              <div v-if="a.madeBy?.email" class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ censorEmail(a.madeBy.email) }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Action:</span>
                <span class="detail-value">{{ formatActivityType(a.type) }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Details:</span>
                <span class="detail-value">{{ getActivityDetails(a) }}</span>
              </div>
              <div v-if="a.changes && a.changes.length > 0" class="detail-row">
                <span class="detail-label">Columns:</span>
                <span class="detail-value">{{ formatChanges(a.changes) }}</span>
              </div>
            </div>
          </div>
          <div style="margin-top: 8px; display: flex; gap: 8px">
            <button @click="clearActivity">Clear History</button>
          </div>
        </div>
      </div>
    </div>

    <!-- File Type Selector Modal -->
    <div v-if="showFileTypeModal" class="credentials-backdrop" @click="closeFileTypeModal"></div>
    <div v-if="showFileTypeModal" class="file-type-modal">
      <div class="file-type-panel">
        <button class="modal-close" @click="closeFileTypeModal">✕</button>
        <h4>Select Import File Type</h4>

        <div class="file-type-content">
          <p class="file-type-description">Choose the format of the file you want to import:</p>

          <div class="file-type-options">
            <label class="file-type-option" :class="{ selected: selectedImportFormat === 'json' }">
              <input type="radio" v-model="selectedImportFormat" value="json" />
              <div class="option-content">
                <div class="option-icon">📄</div>
                <div class="option-details">
                  <div class="option-title">JSON</div>
                  <div class="option-desc">JavaScript Object Notation (.json)</div>
                  <button @click.stop.prevent="downloadSampleFile('json')" class="sample-btn">
                    ⬇ Sample
                  </button>
                </div>
              </div>
            </label>

            <label class="file-type-option" :class="{ selected: selectedImportFormat === 'csv' }">
              <input type="radio" v-model="selectedImportFormat" value="csv" />
              <div class="option-content">
                <div class="option-icon">📊</div>
                <div class="option-details">
                  <div class="option-title">CSV</div>
                  <div class="option-desc">Comma-Separated Values (.csv)</div>
                  <button @click.stop.prevent="downloadSampleFile('csv')" class="sample-btn">
                    ⬇ Sample
                  </button>
                </div>
              </div>
            </label>

            <label class="file-type-option" :class="{ selected: selectedImportFormat === 'xml' }">
              <input type="radio" v-model="selectedImportFormat" value="xml" />
              <div class="option-content">
                <div class="option-icon">🔖</div>
                <div class="option-details">
                  <div class="option-title">XML</div>
                  <div class="option-desc">Extensible Markup Language (.xml)</div>
                  <button @click.stop.prevent="downloadSampleFile('xml')" class="sample-btn">
                    ⬇ Sample
                  </button>
                </div>
              </div>
            </label>

            <label class="file-type-option" :class="{ selected: selectedImportFormat === 'excel' }">
              <input type="radio" v-model="selectedImportFormat" value="excel" />
              <div class="option-content">
                <div class="option-icon">📗</div>
                <div class="option-details">
                  <div class="option-title">Excel</div>
                  <div class="option-desc">Microsoft Excel (.xlsx, .xls)</div>
                  <button @click.stop.prevent="downloadSampleFile('excel')" class="sample-btn">
                    ⬇ Sample
                  </button>
                </div>
              </div>
            </label>
          </div>

          <div class="modal-actions">
            <button @click="proceedToFileSelection" class="confirm-import-btn">
              Continue
            </button>
            <button @click="closeFileTypeModal" class="cancel-btn">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Export File Type Selector Modal -->
    <div v-if="showExportFileTypeModal" class="credentials-backdrop" @click="closeExportFileTypeModal"></div>
    <div v-if="showExportFileTypeModal" class="file-type-modal">
      <div class="file-type-panel">
        <button class="modal-close" @click="closeExportFileTypeModal">✕</button>
        <h4>Select Export Type</h4>

        <div class="file-type-content">
          <p class="file-type-description">Choose export type:</p>

          <!-- Export Type Tabs -->
          <div class="export-type-tabs">
            <button
              class="export-tab"
              :class="{ active: exportType === 'data' }"
              @click="exportType = 'data'"
            >
              📦 Raw Data
            </button>
            <button
              class="export-tab"
              :class="{ active: exportType === 'report' }"
              @click="exportType = 'report'"
            >
              📊 Summary Report
            </button>
          </div>

          <!-- Raw Data Export Options -->
          <div v-if="exportType === 'data'" class="file-type-options">
            <label class="file-type-option" :class="{ selected: selectedExportFormat === 'json' }">
              <input type="radio" v-model="selectedExportFormat" value="json" />
              <div class="option-content">
                <div class="option-icon">📄</div>
                <div class="option-details">
                  <div class="option-title">JSON</div>
                  <div class="option-desc">JavaScript Object Notation (.json)</div>
                </div>
              </div>
            </label>

            <label class="file-type-option" :class="{ selected: selectedExportFormat === 'csv' }">
              <input type="radio" v-model="selectedExportFormat" value="csv" />
              <div class="option-content">
                <div class="option-icon">📊</div>
                <div class="option-details">
                  <div class="option-title">CSV</div>
                  <div class="option-desc">Comma-Separated Values (.csv)</div>
                </div>
              </div>
            </label>

            <label class="file-type-option" :class="{ selected: selectedExportFormat === 'xml' }">
              <input type="radio" v-model="selectedExportFormat" value="xml" />
              <div class="option-content">
                <div class="option-icon">🔖</div>
                <div class="option-details">
                  <div class="option-title">XML</div>
                  <div class="option-desc">Extensible Markup Language (.xml)</div>
                </div>
              </div>
            </label>

            <label class="file-type-option" :class="{ selected: selectedExportFormat === 'excel' }">
              <input type="radio" v-model="selectedExportFormat" value="excel" />
              <div class="option-content">
                <div class="option-icon">📗</div>
                <div class="option-details">
                  <div class="option-title">Excel</div>
                  <div class="option-desc">Microsoft Excel (.xlsx)</div>
                </div>
              </div>
            </label>
          </div>

          <!-- Summary Report Options -->
          <div v-if="exportType === 'report'" class="report-options">
            <p class="report-description">
              Generate a detailed inventory summary report including sales, restocks,
              and inventory changes for a specific time period.
            </p>

            <!-- Date Range Selection -->
            <div class="date-range-section">
              <label class="field">
                <div class="label">Start Date</div>
                <input
                  type="date"
                  v-model="reportStartDate"
                  :max="reportEndDate || todayDate"
                  class="date-input"
                />
              </label>
              <label class="field">
                <div class="label">End Date</div>
                <input
                  type="date"
                  v-model="reportEndDate"
                  :min="reportStartDate"
                  :max="todayDate"
                  class="date-input"
                />
              </label>
            </div>

            <!-- Quick Select Options -->
            <div class="quick-select-section">
              <span class="quick-label">Quick select:</span>
              <div class="quick-buttons">
                <button @click="setReportRange('this-month')" class="quick-btn">This Month</button>
                <button @click="setReportRange('last-month')" class="quick-btn">Last Month</button>
                <button @click="setReportRange('last-7-days')" class="quick-btn">Last 7 Days</button>
                <button @click="setReportRange('last-30-days')" class="quick-btn">Last 30 Days</button>
                <button @click="setReportRange('this-year')" class="quick-btn">This Year</button>
              </div>
            </div>

            <!-- Report Format Selection -->
            <div class="report-format-section">
              <div class="label">Report Format</div>
              <div class="report-format-options">
                <label class="format-option" :class="{ selected: reportFormat === 'pdf' }">
                  <input type="radio" v-model="reportFormat" value="pdf" />
                  <span>📑 PDF</span>
                </label>
                <label class="format-option" :class="{ selected: reportFormat === 'excel' }">
                  <input type="radio" v-model="reportFormat" value="excel" />
                  <span>📗 Excel</span>
                </label>
                <label class="format-option" :class="{ selected: reportFormat === 'json' }">
                  <input type="radio" v-model="reportFormat" value="json" />
                  <span>📄 JSON</span>
                </label>
              </div>
            </div>
          </div>

          <div class="modal-actions">
            <button
              v-if="exportType === 'data'"
              @click="exportWithSelectedFormat"
              class="confirm-import-btn"
            >
              Export Data
            </button>
            <button
              v-if="exportType === 'report'"
              @click="generateReport"
              class="confirm-import-btn"
              :disabled="generatingReport"
            >
              {{ generatingReport ? 'Generating...' : 'Generate Report' }}
            </button>
            <button @click="closeExportFileTypeModal" class="cancel-btn">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showSettings" class="settings-backdrop" @click="showSettings = false"></div>
    <div v-if="showSettings" class="settings-overlay">
      <div class="settings-panel">
        <button class="settings-close" @click="showSettings = false">✕</button>
        <h4>Settings</h4>

        <!-- Profile Section -->
        <div class="setting-section profile-section">
          <h5>Profile</h5>
          <div class="profile-card">
            <div class="profile-avatar">
              {{ currentUser.username ? currentUser.username.charAt(0).toUpperCase() : 'U' }}
            </div>
            <div class="profile-details">
              <div class="profile-name">{{ currentUser.username || 'Guest' }}</div>
              <div class="profile-email">{{ currentUser.email || 'No email' }}</div>
              <div class="profile-meta">
                <span v-if="currentUser.loginAt">
                  Logged in: {{ formatLoginTime(currentUser.loginAt) }}
                </span>
              </div>
            </div>
          </div>
          <div class="profile-actions">
            <button @click="showEditProfile = true" class="profile-edit-btn">
              Edit Profile
            </button>
          </div>
        </div>

        <div class="setting">
          <label class="label">Theme</label>
          <select v-model="theme" @change="applyTheme(theme)">
            <option value="dark">Dark</option>
            <option value="light">Light</option>
          </select>
        </div>
        <div class="setting-section">
          <div class="credentials-header">
            <h5>Login Credentials</h5>
            <button @click="showCredentialsForm = true" class="toggle-form-btn">
              + Add New
            </button>
          </div>

          <div v-if="storedCredentials.length > 0" class="credentials-list">
            <div v-for="(cred, index) in storedCredentials" :key="index" class="credential-item">
              <div class="credential-info">
                <div class="credential-username">{{ cred.username }}</div>
                <div class="credential-email">{{ censorEmail(cred.email) }}</div>
                <div v-if="cred.verified" class="credential-badge">✓ Verified</div>
              </div>
              <button @click="removeCredential(index)" class="remove-credential-btn">
                Remove
              </button>
            </div>
          </div>
          <div v-else class="no-credentials">
            No credentials added yet. Click "Add New" to create one.
          </div>
        </div>

        <div class="setting actions-row">
          <button @click="showPrivacyPolicy = true" class="privacy-link-btn">📋 Privacy Policy</button>
        </div>
      </div>
    </div>

    <!-- Import Preview Modal -->
    <div v-if="showImportPreview" class="credentials-backdrop" @click="closeImportPreview"></div>
    <div v-if="showImportPreview" class="import-preview-modal">
      <div class="import-preview-panel">
        <button class="modal-close" @click="closeImportPreview">✕</button>
        <h4>Import Data Preview</h4>

        <div class="import-preview-content">
          <div class="file-info">
            <div class="info-row">
              <span class="info-label">File Name:</span>
              <span class="info-value">{{ importFileInfo.name }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">File Type:</span>
              <span class="info-value">{{ importFileInfo.type }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">File Size:</span>
              <span class="info-value">{{ formatFileSize(importFileInfo.size) }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Records Found:</span>
              <span class="info-value">{{ importPreviewData.length }}</span>
            </div>
          </div>

          <div class="preview-section">
            <h5>Data Preview (First 5 records)</h5>
            <div class="preview-table-wrapper">
              <table class="preview-table" v-if="importPreviewData.length > 0">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Price</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in importPreviewData.slice(0, 5)" :key="idx">
                    <td>{{ item.name || '-' }}</td>
                    <td>{{ item.sku || '-' }}</td>
                    <td>{{ item.type || '-' }}</td>
                    <td>{{ item.quantity || 0 }}</td>
                    <td>{{ item.price || '-' }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="no-preview">No valid data to preview</div>
            </div>
          </div>

          <div class="import-options">
            <label class="option-label">
              <input type="radio" v-model="importMode" value="replace" />
              Replace existing data
            </label>
            <label class="option-label">
              <input type="radio" v-model="importMode" value="merge" />
              Merge with existing data
            </label>
          </div>

          <div class="modal-actions">
            <button @click="confirmImport" class="confirm-import-btn">
              Import {{ importPreviewData.length }} Records
            </button>
            <button @click="closeImportPreview" class="cancel-btn">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Credentials Form Modal -->
    <div v-if="showCredentialsForm" class="credentials-backdrop" @click="closeCredentialsForm"></div>
    <div v-if="showCredentialsForm" class="credentials-modal">
      <div class="credentials-panel">
        <button class="modal-close" @click="closeCredentialsForm">✕</button>
        <h4>Add New Credentials</h4>

        <div class="credentials-form">
          <div class="form-field">
            <label>Username *</label>
            <input
              type="text"
              v-model="newUsername"
              placeholder="Enter username"
              required
            />
          </div>
          <div class="form-field">
            <label>Email *</label>
            <input
              type="email"
              v-model="newEmail"
              placeholder="Enter email address"
              required
            />
          </div>
          <div class="form-field">
            <label>Password *</label>
            <input
              type="password"
              v-model="newPassword"
              placeholder="Enter password"
              required
            />
          </div>
          <div class="form-field">
            <label>Verification Code</label>
            <div class="verification-group">
              <input
                type="text"
                v-model="verificationCode"
                placeholder="Enter code from email"
                :disabled="!emailSent"
              />
              <button
                @click="sendVerificationCode"
                class="send-code-btn"
                :disabled="!newEmail || emailSent || sendingEmail"
              >
                {{ sendingEmail ? 'Sending...' : (emailSent ? 'Code Sent ✓' : 'Send Code') }}
              </button>
            </div>
          </div>
          <div class="modal-actions">
            <button @click="addCredentials" class="add-credentials-btn">
              Save Credentials
            </button>
            <button @click="closeCredentialsForm" class="cancel-btn">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div v-if="showEditProfile" class="credentials-backdrop" @click="closeEditProfile"></div>
    <div v-if="showEditProfile" class="credentials-modal">
      <div class="credentials-panel">
        <button class="modal-close" @click="closeEditProfile">✕</button>
        <h4>Edit Profile</h4>

        <div class="credentials-form">
          <div class="form-field">
            <label>Username *</label>
            <input
              type="text"
              v-model="editUsername"
              placeholder="Enter new username"
              required
            />
          </div>
          <div class="form-field">
            <label>Current Password *</label>
            <input
              type="password"
              v-model="editCurrentPassword"
              placeholder="Enter current password"
              required
            />
          </div>
          <div class="form-field">
            <label>New Password (leave blank to keep current)</label>
            <input
              type="password"
              v-model="editNewPassword"
              placeholder="Enter new password (optional)"
            />
          </div>
          <div class="form-field">
            <label>Confirm New Password</label>
            <input
              type="password"
              v-model="editConfirmPassword"
              placeholder="Confirm new password"
              :disabled="!editNewPassword"
            />
          </div>
          <div class="modal-actions">
            <button @click="saveProfileChanges" class="add-credentials-btn">
              Save Changes
            </button>
            <button @click="closeEditProfile" class="cancel-btn">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div v-if="showPrivacyPolicy" class="credentials-backdrop" @click="showPrivacyPolicy = false"></div>
    <div v-if="showPrivacyPolicy" class="privacy-modal">
      <PrivacyPolicy @close="showPrivacyPolicy = false" @accept="acceptPrivacyPolicy" />
    </div>
  </aside>
</template>

<script>
import { ElMessageBox } from 'element-plus'
import store from '@/store/inventory.js'
import * as XLSX from 'xlsx'
import { sendVerificationEmail, isEmailServiceConfigured } from '@/services/emailService.js'
import PrivacyPolicy from './PrivacyPolicy.vue'
import api from '@/services/api.js'

export default {
  name: 'AppSidebar',
  components: { PrivacyPolicy },
  props: {
    selectedId: { type: String, default: null },
  },
  data() {
    return {
      q: '',
      showSettings: false,
      theme: 'dark',
      compact: false,
      lowStock: [],
      recentActivity: [],
      lowThreshold: 5,
      currentUser: { username: '', email: '', loginAt: '' },
      newUsername: '',
      newEmail: '',
      newPassword: '',
      verificationCode: '',
      emailSent: false,
      generatedCode: '',
      sendingEmail: false,
      showCredentialsForm: false,
      credentialsVersion: 0,
      expandedActivity: null,
      showFileTypeModal: false,
      showImportPreview: false,
      importFileInfo: { name: '', type: '', size: 0 },
      importPreviewData: [],
      selectedImportFormat: '',
      importMode: 'merge',
      pendingImportFile: null,
      showEditProfile: false,
      editUsername: '',
      editCurrentPassword: '',
      editNewPassword: '',
      editConfirmPassword: '',
      showExportFileTypeModal: false,
      selectedExportFormat: '',
      exportType: 'data',
      reportStartDate: '',
      reportEndDate: '',
      reportFormat: 'pdf',
      generatingReport: false,
      showPrivacyPolicy: false,
      activityTypeMap: {},
    }
  },
  computed: {
    storedCredentials() {
      // Use credentialsVersion to make this reactive
      void this.credentialsVersion
      try {
        return JSON.parse(localStorage.getItem('userCredentials') || '[]')
      } catch {
        return []
      }
    },
    todayDate() {
      return new Date().toISOString().split('T')[0]
    },
  },
  methods: {
    onSearch() {
      this.$emit('search', this.q)
    },
    acceptPrivacyPolicy() {
      // Save consent timestamp
      localStorage.setItem('privacyPolicyAccepted', new Date().toISOString())
      this.showPrivacyPolicy = false
      ElMessageBox.alert(
        'Thank you for reviewing our Privacy Policy.',
        'Privacy Policy Acknowledged',
        { type: 'success' }
      )
    },
    async deleteSelected() {
      if (!this.selectedId) {
        ElMessageBox.alert('No product selected to delete', 'Warning', { type: 'warning' })
        return
      }
      try {
        await ElMessageBox.confirm(
          'This will permanently delete this product. Continue?',
          'Delete selected product?',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
          },
        )
        this.$emit('delete-selected', this.selectedId)
      } catch {
        // User cancelled
      }
    },
    applyTheme(t) {
      try {
        if (t === 'light') {
          document.documentElement.setAttribute('data-theme', 'light')
        } else {
          document.documentElement.removeAttribute('data-theme')
        }
        localStorage.setItem('theme', t)
      } catch (e) {
        console.debug('applyTheme failed', e)
      }
    },
    resetSettings() {
      this.theme = 'dark'
      this.compact = false
      this.applyTheme(this.theme)
      this.applyCompact()
    },
    closeCredentialsForm() {
      this.showCredentialsForm = false
      this.newUsername = ''
      this.newEmail = ''
      this.newPassword = ''
      this.verificationCode = ''
      this.emailSent = false
      this.generatedCode = ''
    },
    censorEmail(email) {
      if (!email) return ''
      const [local, domain] = email.split('@')
      if (!domain) return email
      const visibleChars = Math.min(2, local.length)
      return local.substring(0, visibleChars) + '***@' + domain
    },
    getCurrentUser() {
      try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null')
        return currentUser
      } catch {
        return null
      }
    },
    toggleActivityDetails(index) {
      this.expandedActivity = this.expandedActivity === index ? null : index
    },
    removeCredential(index) {
      ElMessageBox.confirm(
        'Are you sure you want to remove this credential?',
        'Confirm Deletion',
        {
          confirmButtonText: 'Remove',
          cancelButtonText: 'Cancel',
          type: 'warning',
        }
      ).then(() => {
        const credentials = JSON.parse(localStorage.getItem('userCredentials') || '[]')
        const removedCred = credentials[index]
        credentials.splice(index, 1)
        localStorage.setItem('userCredentials', JSON.stringify(credentials))
        this.credentialsVersion++

        // Log to activity
        store.getActivity()
        const activity = JSON.parse(localStorage.getItem('inventory-activity-v1') || '[]')
        const currentUser = this.getCurrentUser()
        activity.unshift({
          at: Date.now(),
          type: 'credential-removed',
          username: removedCred.username,
          madeBy: currentUser ? { username: currentUser.username, email: currentUser.email } : null,
        })
        localStorage.setItem('inventory-activity-v1', JSON.stringify(activity.slice(0, 200)))
        this.loadActivity()

        ElMessageBox.alert('Credential removed successfully', 'Success', {
          confirmButtonText: 'OK',
        })
      }).catch(() => {
        // User cancelled
      })
    },
    sendVerificationCode() {
      // Validate email
      if (!this.newEmail) {
        ElMessageBox.alert('Please enter an email address', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(this.newEmail)) {
        ElMessageBox.alert('Please enter a valid email address', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // Generate a random 6-digit code
      this.generatedCode = Math.floor(100000 + Math.random() * 900000).toString()
      this.sendingEmail = true

      // Check if EmailJS is configured
      if (isEmailServiceConfigured()) {
        // Send real email using EmailJS
        sendVerificationEmail(this.newEmail, this.generatedCode, this.newUsername || 'User')
          .then(() => {
            this.emailSent = true
            this.sendingEmail = false
            ElMessageBox.alert(
              `A verification code has been sent to ${this.newEmail}.\n\nPlease check your email inbox and spam folder.`,
              'Verification Code Sent',
              { type: 'success' },
            )
          })
          .catch((error) => {
            this.sendingEmail = false
            ElMessageBox.alert(
              `Failed to send email: ${error.message}\n\nFor demo purposes, your code is: ${this.generatedCode}`,
              'Email Service Error',
              { type: 'warning' },
            )
            this.emailSent = true // Allow demo mode
          })
      } else {
        // Demo mode - EmailJS not configured
        this.sendingEmail = false
        this.emailSent = true
        ElMessageBox.alert(
          `EmailJS is not configured yet.\n\nFor demo purposes, your verification code is: ${this.generatedCode}\n\nTo enable real emails, configure EmailJS in src/services/emailService.js`,
          'Demo Mode - Verification Code',
          { type: 'info' },
        )
      }
    },
    async addCredentials() {
      // Validate inputs
      if (!this.newUsername || !this.newEmail || !this.newPassword) {
        ElMessageBox.alert('Please fill in all required fields (Username, Email, Password)', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(this.newEmail)) {
        ElMessageBox.alert('Please enter a valid email address', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // Verify the code if email was sent
      if (this.emailSent) {
        if (!this.verificationCode) {
          ElMessageBox.alert('Please enter the verification code', 'Validation Error', {
            type: 'warning',
          })
          return
        }
        if (this.verificationCode !== this.generatedCode) {
          ElMessageBox.alert('Invalid verification code. Please try again.', 'Verification Failed', {
            type: 'error',
          })
          return
        }
      }

      try {
        // Show confirmation dialog
        await ElMessageBox.confirm(
          `Are you sure you want to add credentials for user "${this.newUsername}"?`,
          'Confirm Add Credentials',
          {
            confirmButtonText: 'Add',
            cancelButtonText: 'Cancel',
            type: 'info',
          },
        )

        // Get existing credentials from localStorage
        const credentials = JSON.parse(localStorage.getItem('userCredentials') || '[]')

        // Check if username already exists
        if (credentials.find(cred => cred.username === this.newUsername)) {
          ElMessageBox.alert('Username already exists', 'Error', {
            type: 'error',
          })
          return
        }

        // Add new credentials
        credentials.push({
          username: this.newUsername,
          email: this.newEmail,
          password: this.newPassword,
          verified: this.emailSent && this.verificationCode === this.generatedCode,
          createdAt: new Date().toISOString(),
        })

        // Save to localStorage
        localStorage.setItem('userCredentials', JSON.stringify(credentials))
        this.credentialsVersion++

        // Log to activity
        const activity = JSON.parse(localStorage.getItem('inventory-activity-v1') || '[]')
        const currentUser = this.getCurrentUser()
        activity.unshift({
          at: Date.now(),
          type: 'credential-added',
          username: this.newUsername,
          verified: this.emailSent && this.verificationCode === this.generatedCode,
          madeBy: currentUser ? { username: currentUser.username, email: currentUser.email } : null,
        })
        localStorage.setItem('inventory-activity-v1', JSON.stringify(activity.slice(0, 200)))
        this.loadActivity()

        // Show success message
        const verificationStatus = this.emailSent && this.verificationCode === this.generatedCode
          ? ' (Email Verified)'
          : ''
        ElMessageBox.alert(
          `Credentials for "${this.newUsername}" have been added successfully!${verificationStatus}`,
          'Success',
          { type: 'success' },
        )

        // Clear form and hide it
        this.closeCredentialsForm()
      } catch {
        // User cancelled
      }
    },
    loadLowStock() {
      try {
        this.lowStock = store.lowStock(this.lowThreshold).slice(0, 8)
      } catch (e) {
        console.debug('loadLowStock failed', e)
        this.lowStock = []
      }
    },
    async loadActivity() {
      try {
        // Check if activity was cleared for this session
        if (sessionStorage.getItem('activityCleared') === 'true') {
          this.recentActivity = []
          return
        }
        this.recentActivity = await store.getActivity()
        // Limit to 12 most recent
        if (this.recentActivity.length > 12) {
          this.recentActivity = this.recentActivity.slice(0, 12)
        }
      } catch (e) {
        console.debug('loadActivity failed', e)
        this.recentActivity = []
      }
    },
    async clearActivity() {
      try {
        await ElMessageBox.confirm(
          'This will clear the activity history from view until you log in again. The records will remain in the database.',
          'Clear Activity History',
          {
            confirmButtonText: 'Clear',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        )
        // Log this action to the database before clearing view
        await store.logActivity({ type: 'clear', details: 'Activity history cleared from view' })
        // Mark as cleared for this session (until re-login)
        sessionStorage.setItem('activityCleared', 'true')
        this.recentActivity = []
        ElMessageBox.alert('Activity history cleared from view. It will reappear when you log in again.', 'Success', {
          type: 'success',
        })
      } catch {
        // User cancelled
      }
    },
    exportJSON() {
      this.showExportFileTypeSelector()
    },
    showExportFileTypeSelector() {
      this.selectedExportFormat = ''
      this.exportType = 'data'
      this.reportFormat = 'pdf'
      this.generatingReport = false
      // Default to current month
      const now = new Date()
      const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
      this.reportStartDate = firstDay.toISOString().split('T')[0]
      this.reportEndDate = now.toISOString().split('T')[0]
      this.showExportFileTypeModal = true
    },
    closeExportFileTypeModal() {
      this.showExportFileTypeModal = false
      this.selectedExportFormat = ''
      this.exportType = 'data'
      this.generatingReport = false
    },
    setReportRange(range) {
      const now = new Date()
      let start = new Date()
      let end = new Date()

      switch (range) {
        case 'this-month':
          start = new Date(now.getFullYear(), now.getMonth(), 1)
          end = now
          break
        case 'last-month':
          start = new Date(now.getFullYear(), now.getMonth() - 1, 1)
          end = new Date(now.getFullYear(), now.getMonth(), 0)
          break
        case 'last-7-days':
          start = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
          end = now
          break
        case 'last-30-days':
          start = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000)
          end = now
          break
        case 'this-year':
          start = new Date(now.getFullYear(), 0, 1)
          end = now
          break
        default:
          start = new Date(now.getFullYear(), now.getMonth(), 1)
          end = now
      }

      this.reportStartDate = start.toISOString().split('T')[0]
      this.reportEndDate = end.toISOString().split('T')[0]
    },
    async generateReport() {
      if (!this.reportStartDate || !this.reportEndDate) {
        ElMessageBox.alert('Please select a date range', 'Warning', { type: 'warning' })
        return
      }

      this.generatingReport = true

      try {
        const report = await api.generateInventoryReport(this.reportStartDate, this.reportEndDate)

        if (this.reportFormat === 'json') {
          this.downloadReportAsJSON(report)
        } else if (this.reportFormat === 'excel') {
          this.downloadReportAsExcel(report)
        } else if (this.reportFormat === 'pdf') {
          this.downloadReportAsPDF(report)
        }

        // Log the report generation activity
        console.log('Logging report activity...')
        await store.logActivity({
          type: 'report',
          format: this.reportFormat.toUpperCase(),
          dateRange: `${this.reportStartDate} to ${this.reportEndDate}`
        })
        console.log('Report activity logged, reloading activity list...')
        this.loadActivity()

        this.closeExportFileTypeModal()
        ElMessageBox.alert('Report generated successfully!', 'Success', { type: 'success' })
      } catch (e) {
        console.error('Report generation failed:', e)
        ElMessageBox.alert('Failed to generate report. Please try again.', 'Error', { type: 'error' })
      } finally {
        this.generatingReport = false
      }
    },
    downloadReportAsJSON(report) {
      const content = JSON.stringify(report, null, 2)
      const blob = new Blob([content], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `inventory-report-${this.reportStartDate}-to-${this.reportEndDate}.json`
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
    },
    downloadReportAsExcel(report) {
      const wb = XLSX.utils.book_new()

      // Summary sheet
      const summaryData = [
        ['Inventory Summary Report'],
        [''],
        ['Report Period', `${report.report_info.period.start_formatted} to ${report.report_info.period.end_formatted}`],
        ['Generated At', new Date(report.report_info.generated_at).toLocaleString()],
        ['Days Covered', report.report_info.period.days],
        [''],
        ['CURRENT INVENTORY STATUS'],
        ['Total Products', report.current_inventory.total_products],
        ['Total Quantity', report.current_inventory.total_quantity],
        ['Total Value', this.formatCurrency(report.current_inventory.total_value)],
        ['Low Stock Items', report.current_inventory.low_stock_count],
        [''],
        ['SALES SUMMARY'],
        ['Total Transactions', report.sales_summary.total_transactions],
        ['Items Sold', report.sales_summary.total_items_sold],
        ['Total Revenue', this.formatCurrency(report.sales_summary.total_revenue)],
        [''],
        ['RESTOCK SUMMARY'],
        ['Total Transactions', report.restock_summary.total_transactions],
        ['Items Restocked', report.restock_summary.total_items_restocked],
        ['Total Cost', this.formatCurrency(report.restock_summary.total_cost)],
        [''],
        ['NET INVENTORY FLOW'],
        ['Items In', report.net_inventory_flow.items_in],
        ['Items Out', report.net_inventory_flow.items_out],
        ['Net Flow', report.net_inventory_flow.net_flow],
        ['Revenue', this.formatCurrency(report.net_inventory_flow.revenue)],
        ['Restock Cost', this.formatCurrency(report.net_inventory_flow.restock_cost)],
        ['Gross Profit', this.formatCurrency(report.net_inventory_flow.gross_profit)],
      ]
      const summarySheet = XLSX.utils.aoa_to_sheet(summaryData)
      XLSX.utils.book_append_sheet(wb, summarySheet, 'Summary')

      // Products sheet
      if (report.product_details && report.product_details.length > 0) {
        const productData = report.product_details.map(p => ({
          Name: p.name,
          SKU: p.sku || '',
          Type: p.type || '',
          Quantity: p.quantity,
          Price: p.price,
          Value: p.value,
          'Low Stock': p.low_stock ? 'Yes' : 'No'
        }))
        const productSheet = XLSX.utils.json_to_sheet(productData)
        XLSX.utils.book_append_sheet(wb, productSheet, 'Products')
      }

      // Sales by Product sheet
      if (report.sales_summary.by_product && report.sales_summary.by_product.length > 0) {
        const salesData = report.sales_summary.by_product.map(s => ({
          'Product Name': s.product_name,
          'Product SKU': s.product_sku,
          Transactions: s.transactions,
          'Quantity Sold': s.quantity_sold,
          Revenue: this.formatCurrency(s.revenue)
        }))
        const salesSheet = XLSX.utils.json_to_sheet(salesData)
        XLSX.utils.book_append_sheet(wb, salesSheet, 'Sales by Product')
      }

      // Restocks by Product sheet
      if (report.restock_summary.by_product && report.restock_summary.by_product.length > 0) {
        const restockData = report.restock_summary.by_product.map(r => ({
          'Product Name': r.product_name,
          'Product SKU': r.product_sku,
          Transactions: r.transactions,
          'Quantity Added': r.quantity_added,
          Cost: this.formatCurrency(r.cost)
        }))
        const restockSheet = XLSX.utils.json_to_sheet(restockData)
        XLSX.utils.book_append_sheet(wb, restockSheet, 'Restocks by Product')
      }

      XLSX.writeFile(wb, `inventory-report-${this.reportStartDate}-to-${this.reportEndDate}.xlsx`)
    },
    downloadReportAsPDF(report) {
      // Generate HTML content for PDF
      const htmlContent = this.generateReportHTML(report)

      // Open in new window for printing to PDF
      const printWindow = window.open('', '_blank')
      printWindow.document.write(htmlContent)
      printWindow.document.close()
      printWindow.focus()

      // Auto-print after content loads
      printWindow.onload = function() {
        printWindow.print()
      }
    },
    generateReportHTML(report) {
      const formatCurrency = (val) => this.formatCurrency(val)
      const formatNumber = (val) => Number(val || 0).toLocaleString('en-PH')

      return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Inventory Report - ${report.report_info.period.start_formatted} to ${report.report_info.period.end_formatted}</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
    h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
    h2 { color: #34495e; margin-top: 30px; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; }
    .report-header { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    .report-header p { margin: 5px 0; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
    .stat-box { background: #ecf0f1; padding: 15px; border-radius: 8px; text-align: center; }
    .stat-value { font-size: 24px; font-weight: bold; color: #2980b9; }
    .stat-label { font-size: 12px; color: #7f8c8d; margin-top: 5px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #3498db; color: white; }
    tr:nth-child(even) { background: #f2f2f2; }
    .positive { color: #27ae60; }
    .negative { color: #e74c3c; }
    .summary-section { background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
    .flow-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .flow-item { text-align: center; }
    .flow-value { font-size: 28px; font-weight: bold; }
    .flow-label { color: #666; }
    @media print {
      body { margin: 20px; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
  <h1>📊 Inventory Summary Report</h1>

  <div class="report-header">
    <p><strong>Report Period:</strong> ${report.report_info.period.start_formatted} to ${report.report_info.period.end_formatted}</p>
    <p><strong>Days Covered:</strong> ${report.report_info.period.days} days</p>
    <p><strong>Generated:</strong> ${new Date(report.report_info.generated_at).toLocaleString()}</p>
  </div>

  <h2>📦 Current Inventory Status</h2>
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-value">${formatNumber(report.current_inventory.total_products)}</div>
      <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">${formatNumber(report.current_inventory.total_quantity)}</div>
      <div class="stat-label">Total Quantity</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">${formatCurrency(report.current_inventory.total_value)}</div>
      <div class="stat-label">Total Value</div>
    </div>
    <div class="stat-box">
      <div class="stat-value ${report.current_inventory.low_stock_count > 0 ? 'negative' : ''}">${formatNumber(report.current_inventory.low_stock_count)}</div>
      <div class="stat-label">Low Stock Items</div>
    </div>
  </div>

  <h2>💰 Net Inventory Flow</h2>
  <div class="summary-section">
    <div class="flow-grid">
      <div class="flow-item">
        <div class="flow-value positive">+${formatNumber(report.net_inventory_flow.items_in)}</div>
        <div class="flow-label">Items In (Restocked)</div>
      </div>
      <div class="flow-item">
        <div class="flow-value negative">-${formatNumber(report.net_inventory_flow.items_out)}</div>
        <div class="flow-label">Items Out (Sold)</div>
      </div>
      <div class="flow-item">
        <div class="flow-value ${report.net_inventory_flow.net_flow >= 0 ? 'positive' : 'negative'}">${report.net_inventory_flow.net_flow >= 0 ? '+' : ''}${formatNumber(report.net_inventory_flow.net_flow)}</div>
        <div class="flow-label">Net Flow</div>
      </div>
    </div>
    <div class="flow-grid" style="margin-top: 20px;">
      <div class="flow-item">
        <div class="flow-value positive">${formatCurrency(report.net_inventory_flow.revenue)}</div>
        <div class="flow-label">Revenue</div>
      </div>
      <div class="flow-item">
        <div class="flow-value negative">${formatCurrency(report.net_inventory_flow.restock_cost)}</div>
        <div class="flow-label">Restock Cost</div>
      </div>
      <div class="flow-item">
        <div class="flow-value ${report.net_inventory_flow.gross_profit >= 0 ? 'positive' : 'negative'}">${formatCurrency(report.net_inventory_flow.gross_profit)}</div>
        <div class="flow-label">Gross Profit</div>
      </div>
    </div>
  </div>

  <h2>📈 Sales Summary</h2>
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-value">${formatNumber(report.sales_summary.total_transactions)}</div>
      <div class="stat-label">Total Transactions</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">${formatNumber(report.sales_summary.total_items_sold)}</div>
      <div class="stat-label">Items Sold</div>
    </div>
    <div class="stat-box">
      <div class="stat-value positive">${formatCurrency(report.sales_summary.total_revenue)}</div>
      <div class="stat-label">Total Revenue</div>
    </div>
  </div>
  ${report.sales_summary.by_product && report.sales_summary.by_product.length > 0 ? `
  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Transactions</th>
        <th>Qty Sold</th>
        <th>Revenue</th>
      </tr>
    </thead>
    <tbody>
      ${report.sales_summary.by_product.slice(0, 10).map(s => `
      <tr>
        <td>${s.product_name}</td>
        <td>${s.product_sku}</td>
        <td>${s.transactions}</td>
        <td>${s.quantity_sold}</td>
        <td>${formatCurrency(s.revenue)}</td>
      </tr>
      `).join('')}
    </tbody>
  </table>
  ` : '<p>No sales during this period.</p>'}

  <h2>📦 Restock Summary</h2>
  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-value">${formatNumber(report.restock_summary.total_transactions)}</div>
      <div class="stat-label">Total Transactions</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">${formatNumber(report.restock_summary.total_items_restocked)}</div>
      <div class="stat-label">Items Restocked</div>
    </div>
    <div class="stat-box">
      <div class="stat-value">${formatCurrency(report.restock_summary.total_cost)}</div>
      <div class="stat-label">Total Cost</div>
    </div>
  </div>
  ${report.restock_summary.by_product && report.restock_summary.by_product.length > 0 ? `
  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th>SKU</th>
        <th>Transactions</th>
        <th>Qty Added</th>
        <th>Cost</th>
      </tr>
    </thead>
    <tbody>
      ${report.restock_summary.by_product.slice(0, 10).map(r => `
      <tr>
        <td>${r.product_name}</td>
        <td>${r.product_sku}</td>
        <td>${r.transactions}</td>
        <td>${r.quantity_added}</td>
        <td>${formatCurrency(r.cost)}</td>
      </tr>
      `).join('')}
    </tbody>
  </table>
  ` : '<p>No restocks during this period.</p>'}

  <h2>📋 Product Details</h2>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>SKU</th>
        <th>Type</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Value</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      ${report.product_details.slice(0, 20).map(p => `
      <tr>
        <td>${p.name}</td>
        <td>${p.sku || '-'}</td>
        <td>${p.type || '-'}</td>
        <td>${p.quantity}</td>
        <td>${formatCurrency(p.price)}</td>
        <td>${formatCurrency(p.value)}</td>
        <td>${p.low_stock ? '<span class="negative">Low Stock</span>' : '<span class="positive">OK</span>'}</td>
      </tr>
      `).join('')}
    </tbody>
  </table>
  ${report.product_details.length > 20 ? `<p><em>Showing first 20 of ${report.product_details.length} products</em></p>` : ''}

  <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
    <p>Generated by Inventory Management System</p>
  </div>
</body>
</html>
      `
    },
    exportWithSelectedFormat() {
      if (!this.selectedExportFormat) {
        ElMessageBox.alert('Please select an export format', 'Warning', { type: 'warning' })
        return
      }

      try {
        const data = store.exportAll()
        let content, filename, mimeType

        if (this.selectedExportFormat === 'json') {
          content = JSON.stringify(data, null, 2)
          filename = 'inventory-export.json'
          mimeType = 'application/json'
        } else if (this.selectedExportFormat === 'csv') {
          const headers = 'name,sku,type,quantity,price,notes'
          const rows = data.map(item =>
            `${item.name || ''},${item.sku || ''},${item.type || ''},${item.quantity || 0},${item.price || 0},${item.notes || ''}`
          ).join('\n')
          content = `${headers}\n${rows}`
          filename = 'inventory-export.csv'
          mimeType = 'text/csv'
        } else if (this.selectedExportFormat === 'xml') {
          const xmlItems = data.map(item => `  <product>
    <name>${item.name || ''}</name>
    <sku>${item.sku || ''}</sku>
    <type>${item.type || ''}</type>
    <quantity>${item.quantity || 0}</quantity>
    <price>${item.price || 0}</price>
    <notes>${item.notes || ''}</notes>
  </product>`).join('\n')
          content = `<?xml version="1.0" encoding="UTF-8"?>\n<products>\n${xmlItems}\n</products>`
          filename = 'inventory-export.xml'
          mimeType = 'application/xml'
        } else if (this.selectedExportFormat === 'excel') {
          const ws = XLSX.utils.json_to_sheet(data)
          const wb = XLSX.utils.book_new()
          XLSX.utils.book_append_sheet(wb, ws, 'Inventory')
          XLSX.writeFile(wb, 'inventory-export.xlsx')
          this.closeExportFileTypeModal()
          ElMessageBox.alert('Export successful!', 'Success', { type: 'success' })
          return
        }

        const blob = new Blob([content], { type: mimeType })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(url)

        this.closeExportFileTypeModal()
        ElMessageBox.alert('Export successful!', 'Success', { type: 'success' })
      } catch (e) {
        console.debug('export failed', e)
        ElMessageBox.alert('Export failed. Please try again.', 'Error', { type: 'error' })
      }
    },
    showFileTypeSelector() {
      this.selectedImportFormat = ''
      this.showFileTypeModal = true
    },
    closeFileTypeModal() {
      this.showFileTypeModal = false
      this.selectedImportFormat = ''
    },
    downloadSampleFile(type) {
      const sampleData = [
        { name: 'Product 1', sku: 'SKU001', type: 'Electronics', quantity: 10, price: 29.99 },
        { name: 'Product 2', sku: 'SKU002', type: 'Furniture', quantity: 5, price: 149.99 },
        { name: 'Product 3', sku: 'SKU003', type: 'Clothing', quantity: 25, price: 19.99 }
      ]

      let content, filename, mimeType

      if (type === 'json') {
        content = JSON.stringify(sampleData, null, 2)
        filename = 'sample-inventory.json'
        mimeType = 'application/json'
      } else if (type === 'csv') {
        const headers = 'name,sku,type,quantity,price'
        const rows = sampleData.map(item =>
          `${item.name},${item.sku},${item.type},${item.quantity},${item.price}`
        ).join('\n')
        content = `${headers}\n${rows}`
        filename = 'sample-inventory.csv'
        mimeType = 'text/csv'
      } else if (type === 'xml') {
        const xmlItems = sampleData.map(item => `  <product>
    <name>${item.name}</name>
    <sku>${item.sku}</sku>
    <type>${item.type}</type>
    <quantity>${item.quantity}</quantity>
    <price>${item.price}</price>
  </product>`).join('\n')
        content = `<?xml version="1.0" encoding="UTF-8"?>
<products>
${xmlItems}
</products>`
        filename = 'sample-inventory.xml'
        mimeType = 'application/xml'
      } else if (type === 'excel') {
        // Create Excel file using XLSX library
        const ws = XLSX.utils.json_to_sheet(sampleData)
        const wb = XLSX.utils.book_new()
        XLSX.utils.book_append_sheet(wb, ws, 'Inventory')
        XLSX.writeFile(wb, 'sample-inventory.xlsx')
        return // XLSX.writeFile handles the download
      }

      // Download the file
      const blob = new Blob([content], { type: mimeType })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
    },
    proceedToFileSelection() {
      if (!this.selectedImportFormat) {
        ElMessageBox.alert('Please select a file type before continuing', 'Warning', { type: 'warning' })
        return
      }
      this.showFileTypeModal = false
      this.$nextTick(() => {
        this.$refs.importFile.click()
      })
    },
    getAcceptedTypes() {
      const types = {
        json: 'application/json,.json',
        csv: 'text/csv,.csv',
        xml: 'application/xml,text/xml,.xml',
        excel: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,.xlsx,.xls'
      }
      return types[this.selectedImportFormat] || 'application/json,.json'
    },
    async onFileSelect(e) {
      const f = e.target.files && e.target.files[0]
      if (!f) {
        return
      }

      this.pendingImportFile = f
      this.importFileInfo = {
        name: f.name,
        type: f.type || 'Unknown',
        size: f.size
      }

      try {
        let data = []

        // Detect file type and parse accordingly
        // IMPORTANT: Check Excel BEFORE XML because Excel MIME type contains 'xml'
        if (f.name.endsWith('.xlsx') || f.name.endsWith('.xls') || f.type.includes('spreadsheet') || f.type.includes('excel')) {
          data = await this.parseExcel(f)
        } else if (f.name.endsWith('.json') || f.type === 'application/json') {
          const txt = await f.text()
          data = JSON.parse(txt)
          data = Array.isArray(data) ? data : [data]
        } else if (f.name.endsWith('.csv') || f.type === 'text/csv') {
          const txt = await f.text()
          data = this.parseCSV(txt)
        } else if (f.name.endsWith('.xml') || f.type.includes('xml')) {
          const txt = await f.text()
          data = this.parseXML(txt)
        } else {
          // Try JSON as default
          const txt = await f.text()
          try {
            data = JSON.parse(txt)
            data = Array.isArray(data) ? data : [data]
          } catch {
            ElMessageBox.alert('Unsupported file format. Please use JSON, CSV, XML, or Excel.', 'Error', { type: 'error' })
            return
          }
        }

        this.importPreviewData = data
        this.showImportPreview = true
      } catch (err) {
        console.error('File parsing failed', err)
        ElMessageBox.alert(`Failed to parse file: ${err.message}`, 'Error', { type: 'error' })
      } finally {
        // reset input
        try {
          this.$refs.importFile.value = null
        } catch (e) {
          console.debug('reset import input failed', e)
        }
      }
    },
    parseCSV(text) {
      const lines = text.split('\n').filter(line => line.trim())
      if (lines.length === 0) return []

      const headers = lines[0].split(',').map(h => h.trim().toLowerCase())
      const data = []

      for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',')
        const obj = {}
        headers.forEach((header, idx) => {
          let value = values[idx]?.trim() || ''
          // Convert numeric fields
          if (header === 'quantity' || header === 'price') {
            value = parseFloat(value) || 0
          }
          obj[header] = value
        })
        if (obj.name || obj.sku) { // Only add if has name or sku
          data.push(obj)
        }
      }
      return data
    },
    parseXML(text) {
      try {
        const parser = new DOMParser()
        const xmlDoc = parser.parseFromString(text, 'text/xml')
        const items = xmlDoc.getElementsByTagName('product')
        const data = []

        for (let i = 0; i < items.length; i++) {
          const item = items[i]
          const obj = {
            name: item.getElementsByTagName('name')[0]?.textContent || '',
            sku: item.getElementsByTagName('sku')[0]?.textContent || '',
            type: item.getElementsByTagName('type')[0]?.textContent || '',
            quantity: parseFloat(item.getElementsByTagName('quantity')[0]?.textContent) || 0,
            price: parseFloat(item.getElementsByTagName('price')[0]?.textContent) || 0,
          }
          if (obj.name || obj.sku) {
            data.push(obj)
          }
        }
        return data
      } catch (err) {
        console.debug('XML parsing failed', err)
        return []
      }
    },
    async parseExcel(file) {
      try {
        const arrayBuffer = await file.arrayBuffer()
        const workbook = XLSX.read(arrayBuffer, { type: 'array' })
        const firstSheetName = workbook.SheetNames[0]
        const worksheet = workbook.Sheets[firstSheetName]
        const jsonData = XLSX.utils.sheet_to_json(worksheet)

        console.log('Excel - Raw data from file:', jsonData)
        console.log('Excel - Number of rows:', jsonData.length)
        if (jsonData.length > 0) {
          console.log('Excel - First row columns:', Object.keys(jsonData[0]))
        }

        // Transform the data to match our schema
        const data = jsonData.map(row => {
          return {
            name: row.name || row.Name || row.NAME || row.product || row.Product || '',
            sku: row.sku || row.SKU || row.Sku || row.code || row.Code || '',
            type: row.type || row.Type || row.TYPE || row.category || row.Category || '',
            quantity: parseFloat(row.quantity || row.Quantity || row.QUANTITY || row.qty || row.Qty || row.QTY || 0),
            price: parseFloat(row.price || row.Price || row.PRICE || row.cost || row.Cost || 0),
          }
        }).filter(item => {
          const hasName = item.name && String(item.name).trim().length > 0
          const hasSku = item.sku && String(item.sku).trim().length > 0
          return hasName || hasSku
        })

        return data
      } catch {
        ElMessageBox.alert('Failed to parse Excel file. Please check the file format.', 'Error', { type: 'error' })
        return []
      }
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
    closeImportPreview() {
      this.showImportPreview = false
      this.importPreviewData = []
      this.importFileInfo = { name: '', type: '', size: 0 }
      this.pendingImportFile = null
    },
    async confirmImport() {
      try {
        const replace = this.importMode === 'replace'

        // Check for duplicate SKUs within the import data
        const skuMap = new Map()
        const duplicateSKUs = []

        this.importPreviewData.forEach((product, index) => {
          if (product.sku && product.sku.trim()) {
            const skuLower = product.sku.toLowerCase()
            if (skuMap.has(skuLower)) {
              duplicateSKUs.push(product.sku)
            } else {
              skuMap.set(skuLower, index)
            }
          }
        })

        // If not replacing, also check against existing products
        if (!replace) {
          const existingProducts = await store.all()
          this.importPreviewData.forEach(product => {
            if (product.sku && product.sku.trim()) {
              const skuLower = product.sku.toLowerCase()
              const existing = existingProducts.find(p =>
                p.sku && p.sku.toLowerCase() === skuLower
              )
              if (existing && !duplicateSKUs.includes(product.sku)) {
                duplicateSKUs.push(product.sku)
              }
            }
          })
        }

        if (duplicateSKUs.length > 0) {
          const uniqueDuplicates = [...new Set(duplicateSKUs)]

          try {
            await ElMessageBox.confirm(
              `Found ${uniqueDuplicates.length} duplicate SKU(s): ${uniqueDuplicates.join(', ')}.\n\nDo you want to skip the conflicting products and import the rest?`,
              'Duplicate SKU Detected',
              {
                confirmButtonText: 'Skip Conflicts',
                cancelButtonText: 'Cancel Import',
                type: 'warning',
                distinguishCancelAndClose: true
              }
            )

            // User chose to skip conflicts - filter out products with duplicate SKUs
            const skusToSkip = new Set(uniqueDuplicates.map(s => s.toLowerCase()))
            const filteredData = this.importPreviewData.filter(product => {
              if (!product.sku || !product.sku.trim()) return true
              return !skusToSkip.has(product.sku.toLowerCase())
            })

            if (filteredData.length === 0) {
              ElMessageBox.alert(
                'No products left to import after skipping conflicts.',
                'Import Cancelled',
                { type: 'info' }
              )
              return
            }

            await store.importFromArray(filteredData, replace)
            this.loadLowStock()
            this.loadActivity()
            ElMessageBox.alert(
              `Successfully imported ${filteredData.length} out of ${this.importPreviewData.length} records.\nSkipped ${uniqueDuplicates.length} product(s) with duplicate SKUs.`,
              'Import Complete',
              { type: 'success' }
            )
            this.closeImportPreview()
            // Notify parent to refresh product list and dashboard
            this.$emit('import-complete')
          } catch {
            // User cancelled the import
            return
          }
        } else {
          // No duplicates, proceed with normal import
          await store.importFromArray(this.importPreviewData, replace)
          this.loadLowStock()
          this.loadActivity()
          ElMessageBox.alert(
            `Successfully imported ${this.importPreviewData.length} records!`,
            'Success',
            { type: 'success' }
          )
          this.closeImportPreview()
          // Notify parent to refresh product list and dashboard
          this.$emit('import-complete')
        }
      } catch (err) {
        console.debug('import failed', err)
        ElMessageBox.alert('Import failed. Please try again.', 'Error', { type: 'error' })
      }
    },
    formatDate(ts) {
      try {
        const d = new Date(ts)
        return d.toLocaleString()
      } catch (e) {
        console.debug('formatDate failed', e)
        return ''
      }
    },
    formatCurrency(value) {
      const num = Number(value) || 0
      return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    formatActivityType(type) {
      // Use dynamically loaded activity type map from database
      return this.activityTypeMap[type] || type
    },
    async loadActivityTypes() {
      try {
        this.activityTypeMap = await api.getActivityTypes()
      } catch (e) {
        console.debug('loadActivityTypes failed', e)
        // If API fails, activityTypeMap stays empty and formatActivityType will return the raw type key
      }
    },
    getActivityDetails(activity) {
      if (activity.type === 'login') {
        return activity.username || activity.madeBy?.username || ''
      }
      if (activity.type === 'logout') {
        return activity.username || activity.madeBy?.username || ''
      }
      if (activity.type === 'report') {
        return `${activity.format} (${activity.dateRange})`
      }
      if (activity.type === 'credential-added') {
        const verified = activity.verified ? ' ✓' : ''
        return `${activity.username}${verified}`
      }
      if (activity.type === 'credential-removed') {
        return activity.username
      }
      if (activity.type === 'sale') {
        return `${activity.name} (${activity.quantity} units)`
      }
      if (activity.type === 'restock') {
        return `${activity.name} (${activity.quantity} units)`
      }
      if (activity.type === 'import') {
        return `${activity.count} items`
      }
      if (activity.type === 'clear') {
        return 'All products'
      }
      if (activity.type === 'add' || activity.type === 'update' || activity.type === 'remove') {
        return activity.name || activity.id || ''
      }
      return activity.name || activity.id || ''
    },
    formatChanges(changes) {
      if (!changes || changes.length === 0) return 'None'
      // Capitalize first letter of each field
      const formatted = changes.map(field => {
        return field.charAt(0).toUpperCase() + field.slice(1)
      })
      return formatted.join(', ')
    },
    formatLoginTime(timestamp) {
      if (!timestamp) return 'Unknown'
      try {
        const date = new Date(timestamp)
        const now = new Date()
        const diffMs = now - date
        const diffMins = Math.floor(diffMs / 60000)
        const diffHours = Math.floor(diffMs / 3600000)
        const diffDays = Math.floor(diffMs / 86400000)

        if (diffMins < 1) return 'Just now'
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`

        return date.toLocaleDateString()
      } catch {
        return 'Unknown'
      }
    },
    loadCurrentUser() {
      try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null')
        if (currentUser) {
          this.currentUser = currentUser
        }
      } catch {
        // Silently ignore errors
      }
    },
    closeEditProfile() {
      this.showEditProfile = false
      this.editUsername = ''
      this.editCurrentPassword = ''
      this.editNewPassword = ''
      this.editConfirmPassword = ''
    },
    async saveProfileChanges() {
      // Validate inputs
      if (!this.editUsername || !this.editCurrentPassword) {
        ElMessageBox.alert('Please fill in username and current password', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // If changing password, validate new password
      if (this.editNewPassword) {
        if (this.editNewPassword.length < 6) {
          ElMessageBox.alert('New password must be at least 6 characters', 'Validation Error', {
            type: 'warning',
          })
          return
        }
        if (this.editNewPassword !== this.editConfirmPassword) {
          ElMessageBox.alert('New passwords do not match', 'Validation Error', {
            type: 'warning',
          })
          return
        }
      }

      try {
        await ElMessageBox.confirm(
          'Are you sure you want to update your profile?',
          'Confirm Changes',
          {
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel',
            type: 'info',
          },
        )

        // Call backend API to update profile
        const response = await fetch('http://localhost:8000/api/profile/update', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          credentials: 'include', // Enable credentials for CORS
          body: JSON.stringify({
            username: this.editUsername,
            current_password: this.editCurrentPassword,
            new_password: this.editNewPassword || null,
          }),
        })

        const data = await response.json()

        if (!response.ok) {
          ElMessageBox.alert(data.message || 'Failed to update profile', 'Error', {
            type: 'error',
          })
          return
        }

        // Also update localStorage for consistency
        const credentials = JSON.parse(localStorage.getItem('userCredentials') || '[]')
        const currentUser = this.getCurrentUser()

        if (currentUser) {
          const userIndex = credentials.findIndex(cred => cred.username === currentUser.username)
          if (userIndex !== -1) {
            credentials[userIndex].username = this.editUsername
            if (this.editNewPassword) {
              credentials[userIndex].password = this.editNewPassword
            }
            localStorage.setItem('userCredentials', JSON.stringify(credentials))
            this.credentialsVersion++

            // Update current user info
            const updatedUser = {
              username: this.editUsername,
              email: credentials[userIndex].email,
              loginAt: currentUser.loginAt,
            }
            localStorage.setItem('currentUser', JSON.stringify(updatedUser))
            this.currentUser = updatedUser

            // Log activity
            const activity = JSON.parse(localStorage.getItem('inventory-activity-v1') || '[]')
            activity.unshift({
              at: Date.now(),
              type: 'update',
              name: 'Profile updated',
              madeBy: { username: this.editUsername, email: credentials[userIndex].email },
            })
            localStorage.setItem('inventory-activity-v1', JSON.stringify(activity.slice(0, 200)))
            this.loadActivity()
          }
        }

        ElMessageBox.alert(
          'Profile updated successfully!',
          'Success',
          { type: 'success' },
        )

        this.closeEditProfile()
      } catch (error) {
        if (error !== 'cancel') {
          console.error('Error updating profile:', error)
          ElMessageBox.alert('An error occurred while updating profile', 'Error', {
            type: 'error',
          })
        }
      }
    },
  },
  watch: {},
  mounted() {
    try {
      const s = localStorage.getItem('theme')
      if (s === 'light') this.theme = 'light'
      else this.theme = 'dark'
      this.applyTheme(this.theme)

      const c = localStorage.getItem('compact')
      this.compact = c === '1'
      this.applyCompact()
    } catch (e) {
      console.debug('init settings failed', e)
    }
    this.loadCurrentUser()
    this.loadLowStock()
    this.loadActivity()
    this.loadActivityTypes()
  },
}
</script>

<style scoped>
.sidebar {
  width: 200%;
  max-width: 200px;
  min-width: 160px;
  flex-shrink: 0;
  background: var(--color-background);
  border-right: 1px solid var(--color-border);
  padding: 0;
  color: var(--color-text);
  height: 100%;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

/* Custom scrollbar styling to match theme */
.sidebar::-webkit-scrollbar {
  width: 20px;
}

.sidebar::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 6px;
  margin: 8px 0;
}

.sidebar::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 50px;
  transition: background 0.2s;
  border: 6px solid var(--color-background);
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: var(--color-accent);
  border: 6px solid var(--color-background);
}

.controls::-webkit-scrollbar {
  width: 10px;
}

.controls::-webkit-scrollbar-track {
  background: transparent;
}

.controls::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: 3px;
}

.controls::-webkit-scrollbar-thumb:hover {
  background: var(--color-accent);
}

.bar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 8px;
}
.header-actions {
  display: flex;
  gap: 6px;
  align-items: center;
}
.settings-btn {
  background: var(--color-button-bg);
  border: none;
  color: var(--color-button-text);
  padding: 6px 8px;
  border-radius: var(--border-radius);
  cursor: pointer;
}
.settings-btn[aria-pressed='true'] {
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.12);
}
.controls {
  display: flex;
  flex-direction: column;
  gap: 16px;
  flex: 1;
  overflow-y: auto;
}
.controls .section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.controls .field {
  margin-bottom: 0;
}
.controls input {
  width: 100%;
  padding: 6px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  border-radius: var(--border-radius);
  transition: border-color 0.2s;
}
.controls input:focus {
  outline: none;
  border-color: var(--color-accent);
}
.buttons {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.buttons button {
  display: block;
  width: 100%;
  margin: 0;
  background: var(--color-button-bg);
  border: none;
  color: var(--color-button-text);
  padding: 8px 12px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.2s;
  font-size: 13px;
  text-align: left;
}
.buttons button:hover {
  background: var(--color-button-hover);
}

.settings-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 2000;
}

.settings-overlay {
  position: fixed;
  top: 80px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2010;
  width: 90%;
  max-width: 400px;
}

.settings-panel {
  position: relative;
  padding: 20px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
  animation: fadeInScale 0.3s ease-out;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.settings-close {
  position: absolute;
  right: 12px;
  top: 12px;
  background: transparent;
  border: none;
  color: var(--color-text);
  font-size: 20px;
  cursor: pointer;
  padding: 4px 8px;
  line-height: 1;
}

.settings-close:hover {
  color: var(--color-accent);
}

.settings-panel h4 {
  margin: 0 0 8px 0;
  color: var(--color-heading);
}
.setting {
  margin-bottom: 8px;
}
.setting select {
  width: 100%;
  padding: 6px;
  border-radius: var(--border-radius);
  border: 1px solid var(--color-border);
  background: var(--color-background);
  color: var(--color-text);
}
.actions-row button {
  width: auto;
  display: inline-block;
}

.privacy-link-btn {
  background: var(--color-background-mute) !important;
  color: var(--color-accent) !important;
  border: 1px solid var(--color-accent) !important;
  flex: 1;
}

.privacy-link-btn:hover {
  background: rgba(64, 156, 255, 0.1) !important;
}

.setting-section {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.profile-section {
  margin-top: 0;
  padding-top: 0;
  border-top: none;
  margin-bottom: 20px;
}

.profile-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.profile-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--color-accent);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: 600;
  flex-shrink: 0;
}

.profile-details {
  flex: 1;
  min-width: 0;
}

.profile-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-heading);
  margin-bottom: 4px;
}

.profile-email {
  font-size: 13px;
  color: var(--color-text-muted);
  margin-bottom: 6px;
  word-break: break-word;
}

.profile-meta {
  font-size: 12px;
  color: var(--color-text-muted);
  font-style: italic;
}

.profile-actions {
  margin-top: 12px;
  display: flex;
  gap: 8px;
}

.profile-edit-btn {
  flex: 1;
  padding: 8px 16px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s;
}

.profile-edit-btn:hover {
  background: var(--color-button-hover);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
}

.setting-section h5 {
  margin: 0;
  color: var(--color-heading);
  font-size: 14px;
}

.credentials-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.toggle-form-btn {
  padding: 6px 12px;
  background: var(--color-button-bg);
  color: var(--color-button-text);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.toggle-form-btn:hover {
  background: var(--color-button-hover);
  border-color: var(--color-accent);
}

.credentials-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 12px;
  padding: 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.credentials-form .form-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.verification-group {
  display: flex;
  gap: 8px;
}

.verification-group input {
  flex: 1;
}

.send-code-btn {
  padding: 8px 12px;
  background: var(--color-button-bg);
  color: var(--color-button-text);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: all 0.2s;
  white-space: nowrap;
}

.send-code-btn:hover:not(:disabled) {
  background: var(--color-button-hover);
  border-color: var(--color-accent);
}

.send-code-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.credentials-form label {
  font-size: 13px;
  color: var(--color-text);
  font-weight: 500;
}

.credentials-form input {
  width: 100%;
  padding: 8px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 13px;
  box-sizing: border-box;
}

.credentials-form input:focus {
  border-color: var(--color-accent);
  outline: none;
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.1);
}

.add-credentials-btn {
  padding: 8px 16px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s;
}

.add-credentials-btn:hover {
  background: var(--color-button-hover);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
}

.credentials-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(3px);
  z-index: 3000;
}

.credentials-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 3010;
  width: 90%;
  max-width: 450px;
}

.credentials-panel {
  position: relative;
  padding: 24px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
  animation: fadeInScale 0.3s ease-out;
}

.modal-close {
  position: absolute;
  right: 12px;
  top: 12px;
  background: transparent;
  border: none;
  color: var(--color-text);
  font-size: 20px;
  cursor: pointer;
  padding: 4px 8px;
  line-height: 1;
}

.modal-close:hover {
  color: var(--color-accent);
}

.credentials-panel h4 {
  margin: 0 0 20px 0;
  color: var(--color-heading);
  font-size: 18px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.modal-actions button {
  flex: 1;
}

.cancel-btn {
  padding: 8px 16px;
  background: var(--color-background-soft);
  color: var(--color-text);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s;
}

.cancel-btn:hover {
  background: var(--color-background-mute);
  border-color: var(--color-accent);
}

.credentials-list {
  margin-top: 15px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.credential-item {
  padding: 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.2s;
}

.credential-item:hover {
  border-color: var(--color-accent);
  background: var(--color-background-mute);
}

.credential-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.credential-username {
  font-weight: 600;
  font-size: 13px;
  color: var(--color-heading);
}

.credential-email {
  font-size: 12px;
  color: var(--color-text-muted);
}

.credential-badge {
  display: inline-block;
  padding: 2px 8px;
  margin-left: 8px;
  background: var(--color-accent);
  color: var(--color-background);
  border-radius: 10px;
  font-size: 10px;
  font-weight: 600;
}

.remove-credential-btn {
  padding: 6px 12px;
  background: transparent;
  border: 1px solid #e74c3c;
  color: #e74c3c;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 12px;
  font-weight: 500;
  transition: all 0.2s;
}

.remove-credential-btn:hover {
  background: #e74c3c;
  color: #fff;
}

.no-credentials {
  padding: 20px;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 13px;
  font-style: italic;
}

.sidebar .widget {
  padding: 14px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  margin-top: 8px;
}
.widget h5 {
  margin: 0 0 12px 0;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-heading);
  display: flex;
  align-items: center;
  gap: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.widget h5::before {
  content: '';
  display: inline-block;
  width: 3px;
  height: 14px;
  background: var(--color-accent);
  border-radius: 2px;
}
.widget .item {
  font-size: 13px;
  padding: 10px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  margin-bottom: 8px;
  transition: all 0.2s;
}
.widget .item:hover {
  border-color: var(--color-accent);
  transform: translateX(2px);
}
.widget .item:last-child {
  margin-bottom: 0;
}
.widget .item strong {
  color: var(--color-heading);
  font-weight: 600;
}
.widget .small {
  font-size: 11px;
  color: var(--color-text-muted);
  margin-top: 4px;
}

.activity-item-wrapper {
  margin-bottom: 8px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  overflow: hidden;
  transition: all 0.2s;
}

.activity-item-wrapper:hover {
  border-color: var(--color-accent);
}

.activity-item-wrapper:last-child {
  margin-bottom: 0;
}

.activity-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: none !important;
}

.activity-details-btn {
  background: transparent !important;
  border: 1px solid var(--color-border) !important;
  padding: 4px 8px !important;
  font-size: 14px !important;
  font-weight: bold;
  color: var(--color-text-muted) !important;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s;
  min-width: auto !important;
}

.activity-details-btn:hover {
  background: var(--color-accent) !important;
  border-color: var(--color-accent) !important;
  color: white !important;
}

.activity-details {
  padding: 12px 14px;
  background: var(--color-background-mute);
  border-top: 1px solid var(--color-border);
  font-size: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-row {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.detail-label {
  font-weight: 600;
  color: var(--color-text-muted);
  min-width: 70px;
  flex-shrink: 0;
  font-size: 11px;
  text-transform: uppercase;
}

.detail-value {
  color: var(--color-text);
  word-wrap: break-word;
  word-break: break-word;
  overflow-wrap: break-word;
  flex: 1;
  white-space: normal;
}

.widget button {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  color: var(--color-text);
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 12px;
  font-weight: 500;
}
.widget button:hover {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: white;
}
.import-input {
  display: none;
}

.file-type-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 3010;
  width: 90%;
  max-width: 500px;
}

.file-type-panel {
  position: relative;
  padding: 24px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
  animation: fadeInScale 0.3s ease-out;
}

.file-type-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.file-type-description {
  margin: 0;
  color: var(--color-text);
  font-size: 13px;
}

.file-type-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.file-type-option {
  display: block;
  padding: 16px;
  background: var(--color-background-soft);
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all 0.2s;
}

.file-type-option:hover {
  border-color: var(--color-accent);
  background: var(--color-background-mute);
}

.file-type-option.selected {
  border-color: var(--color-accent);
  background: var(--color-background-mute);
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.1);
}

.file-type-option input[type="radio"] {
  display: none;
}

.option-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.option-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.option-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.option-title {
  font-weight: 600;
  font-size: 15px;
  color: var(--color-heading);
}

.option-desc {
  font-size: 12px;
  color: var(--color-text-muted);
}

.sample-btn {
  margin-top: 8px;
  padding: 4px 12px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 11px;
  font-weight: 500;
  transition: all 0.2s;
  align-self: flex-start;
}

.sample-btn:hover {
  background: var(--color-button-hover);
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(96, 165, 250, 0.3);
}

/* Export Type Tabs */
.export-type-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.export-tab {
  flex: 1;
  padding: 12px 16px;
  background: var(--color-background-soft);
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.export-tab:hover {
  border-color: var(--color-accent);
  background: var(--color-background-mute);
}

.export-tab.active {
  border-color: var(--color-accent);
  background: var(--color-accent);
  color: white;
}

/* Report Options */
.report-options {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.report-description {
  margin: 0;
  padding: 12px;
  background: var(--color-background-soft);
  border-radius: var(--border-radius);
  font-size: 13px;
  color: var(--color-text-muted);
  line-height: 1.5;
}

/* Date Range Section */
.date-range-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.date-range-section .field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.date-range-section .label {
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text);
}

.date-input {
  padding: 10px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 14px;
  width: 100%;
  box-sizing: border-box;
}

.date-input:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2);
}

/* Quick Select Section */
.quick-select-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.quick-label {
  font-size: 12px;
  color: var(--color-text-muted);
}

.quick-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.quick-btn {
  padding: 6px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  color: var(--color-text);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-btn:hover {
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: white;
}

/* Report Format Section */
.report-format-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.report-format-section .label {
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text);
}

.report-format-options {
  display: flex;
  gap: 12px;
}

.format-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: var(--color-background-soft);
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
  justify-content: center;
}

.format-option:hover {
  border-color: var(--color-accent);
}

.format-option.selected {
  border-color: var(--color-accent);
  background: var(--color-background-mute);
}

.format-option input[type="radio"] {
  display: none;
}

.format-option span {
  font-size: 14px;
  color: var(--color-text);
}

.import-preview-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 3010;
  width: 90%;
  max-width: 700px;
}

.import-preview-panel {
  position: relative;
  padding: 24px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
  max-height: 85vh;
  overflow-y: auto;
  animation: fadeInScale 0.3s ease-out;
}

.import-preview-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.file-info {
  padding: 16px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px solid var(--color-border);
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: var(--color-heading);
  font-size: 13px;
}

.info-value {
  color: var(--color-text);
  font-size: 13px;
}

.format-selector {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.format-select {
  width: 100%;
  padding: 8px 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text);
  font-size: 13px;
}

.preview-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.preview-section h5 {
  margin: 0;
  color: var(--color-heading);
  font-size: 14px;
}

.preview-table-wrapper {
  overflow-x: auto;
  max-height: 300px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.preview-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.preview-table th {
  background: var(--color-background-soft);
  padding: 10px 8px;
  text-align: left;
  font-weight: 600;
  color: var(--color-heading);
  border-bottom: 2px solid var(--color-border);
  position: sticky;
  top: 0;
  z-index: 1;
}

.preview-table td {
  padding: 8px;
  border-bottom: 1px solid var(--color-border);
  color: var(--color-text);
}

.preview-table tbody tr:hover {
  background: var(--color-background-soft);
}

.no-preview {
  padding: 40px;
  text-align: center;
  color: var(--color-text-muted);
  font-style: italic;
}

.import-options {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 16px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}

.option-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-text);
  cursor: pointer;
}

.option-label input[type="radio"] {
  cursor: pointer;
}

.confirm-import-btn {
  padding: 8px 16px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.2s;
}

.confirm-import-btn:hover {
  background: var(--color-button-hover);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
}

/* Privacy Policy Modal */
.privacy-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  max-width: 90%;
  max-height: 90vh;
  z-index: 10001;
  animation: fadeInScale 0.3s ease-out;
}

</style>

<style>
/* Global override to ensure Element Plus dialogs appear above modals */
.el-message-box__wrapper {
  z-index: 99999 !important;
}
.el-overlay {
  z-index: 99998 !important;
}
</style>
