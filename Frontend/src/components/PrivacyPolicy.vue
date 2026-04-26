<template>
  <div class="privacy-policy-container">
    <div class="privacy-header">
      <h1>Privacy Policy</h1>
      <p class="last-updated">Last Updated: December 3, 2025</p>
      <button @click="$emit('close')" class="close-btn" aria-label="Close privacy policy">✕</button>
    </div>

    <div class="privacy-content">
      <section>
        <h2>1. Information We Collect</h2>
        <h3>Personal Information</h3>
        <ul>
          <li><strong>User Credentials:</strong> Username, email, encrypted password</li>
          <li><strong>Inventory Data:</strong> Product names, SKUs, quantities, prices</li>
          <li><strong>Activity Logs:</strong> User actions, timestamps, audit trails</li>
          <li><strong>Session Data:</strong> Login times, authentication tokens</li>
        </ul>
      </section>

      <section>
        <h2>2. How We Use Your Information</h2>
        <ul>
          <li><strong>Authentication:</strong> Verify identity and secure access</li>
          <li><strong>Inventory Management:</strong> Track and manage products</li>
          <li><strong>Analytics:</strong> Generate reports and insights</li>
          <li><strong>Communication:</strong> Send verification codes and notifications</li>
          <li><strong>Security:</strong> Prevent fraud and unauthorized access</li>
        </ul>
      </section>

      <section>
        <h2>3. Data Protection</h2>
        <div class="security-measures">
          <div class="measure">
            <span class="icon">🔒</span>
            <div>
              <strong>Password Encryption</strong>
              <p>All passwords encrypted with bcrypt</p>
            </div>
          </div>
          <div class="measure">
            <span class="icon">🛡️</span>
            <div>
              <strong>Secure Storage</strong>
              <p>MySQL database with access controls</p>
            </div>
          </div>
          <div class="measure">
            <span class="icon">🔐</span>
            <div>
              <strong>API Security</strong>
              <p>Token-based authentication, CORS protection</p>
            </div>
          </div>
        </div>
      </section>

      <section>
        <h2>4. Third-Party Services</h2>
        <p>We use <strong>EmailJS</strong> for sending verification codes:</p>
        <ul>
          <li>Data shared: Email address, username, codes</li>
          <li>Purpose: Email delivery only</li>
          <li>Privacy: <a href="https://www.emailjs.com/legal/privacy-policy/" target="_blank">EmailJS Privacy Policy</a></li>
        </ul>
        <p class="important">We do NOT sell your data or share it for marketing purposes.</p>
      </section>

      <section>
        <h2>5. Your Rights</h2>
        <div class="rights-grid">
          <div class="right-item">
            <h4>✅ View Your Data</h4>
            <p>Access all your information and activity logs</p>
          </div>
          <div class="right-item">
            <h4>📥 Export Your Data</h4>
            <p>Download in JSON, CSV, XML, or Excel</p>
          </div>
          <div class="right-item">
            <h4>✏️ Update Information</h4>
            <p>Change username, email, or password</p>
          </div>
          <div class="right-item">
            <h4>🗑️ Delete Account</h4>
            <p>Request permanent data deletion</p>
          </div>
        </div>
      </section>

      <section>
        <h2>6. Data Retention</h2>
        <table class="retention-table">
          <thead>
            <tr>
              <th>Data Type</th>
              <th>Retention Period</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>User Accounts</td>
              <td>While active + 30 days</td>
            </tr>
            <tr>
              <td>Inventory Data</td>
              <td>Business need + 1 year</td>
            </tr>
            <tr>
              <td>Activity Logs</td>
              <td>1 year</td>
            </tr>
            <tr>
              <td>Session Data</td>
              <td>24 hours</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section>
        <h2>7. Legal Compliance</h2>
        <p>This system complies with:</p>
        <ul>
          <li>✅ <strong>GDPR</strong> - European Union</li>
          <li>✅ <strong>CCPA</strong> - California, USA</li>
          <li>✅ <strong>PIPEDA</strong> - Canada</li>
          <li>✅ Industry security best practices</li>
        </ul>
      </section>

      <section>
        <h2>8. Contact Us</h2>
        <p>Questions about this privacy policy?</p>
        <p><strong>Email:</strong> privacy@inventory-system.com</p>
        <p><strong>Response Time:</strong> Within 5 business days</p>
      </section>

      <div class="consent-notice">
        <p><strong>By using this system, you acknowledge that you have read and understood this Privacy Policy.</strong></p>
      </div>
    </div>

    <div class="privacy-footer">
      <button @click="$emit('accept')" class="accept-btn">I Understand</button>
      <button @click="$emit('close')" class="cancel-btn">Close</button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PrivacyPolicy',
}
</script>

<style scoped>
.privacy-policy-container {
  background: var(--color-background-soft);
  border-radius: 12px;
  max-width: 800px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.privacy-header {
  padding: 24px 32px;
  border-bottom: 1px solid var(--color-border);
  position: relative;
}

.privacy-header h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  color: var(--color-heading);
}

.last-updated {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-muted);
}

.close-btn {
  position: absolute;
  right: 24px;
  top: 24px;
  background: transparent;
  border: none;
  font-size: 24px;
  color: var(--color-text);
  cursor: pointer;
  padding: 4px;
  line-height: 1;
}

.close-btn:hover {
  color: var(--color-accent);
}

.privacy-content {
  padding: 32px;
  overflow-y: auto;
  flex: 1;
}

section {
  margin-bottom: 32px;
}

section:last-child {
  margin-bottom: 0;
}

h2 {
  color: var(--color-heading);
  font-size: 20px;
  margin: 0 0 16px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--color-accent);
}

h3 {
  color: var(--color-text);
  font-size: 16px;
  margin: 16px 0 12px 0;
}

h4 {
  margin: 0 0 4px 0;
  font-size: 15px;
  color: var(--color-text);
}

p {
  color: var(--color-text);
  line-height: 1.6;
  margin: 8px 0;
}

ul {
  padding-left: 24px;
  margin: 12px 0;
}

li {
  color: var(--color-text);
  line-height: 1.8;
  margin: 6px 0;
}

.important {
  background: rgba(255, 193, 7, 0.1);
  border-left: 3px solid #ffc107;
  padding: 12px;
  border-radius: 4px;
  font-weight: 500;
}

.security-measures {
  display: grid;
  gap: 16px;
  margin-top: 16px;
}

.measure {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--color-background-mute);
  border-radius: 8px;
  border: 1px solid var(--color-border);
}

.measure .icon {
  font-size: 24px;
  flex-shrink: 0;
}

.measure strong {
  display: block;
  color: var(--color-heading);
  margin-bottom: 4px;
}

.measure p {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-muted);
}

.rights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.right-item {
  padding: 16px;
  background: var(--color-background-mute);
  border-radius: 8px;
  border: 1px solid var(--color-border);
}

.right-item p {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted);
}

.retention-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
}

.retention-table th,
.retention-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.retention-table th {
  background: var(--color-background-mute);
  color: var(--color-heading);
  font-weight: 600;
}

.retention-table td {
  color: var(--color-text);
}

.consent-notice {
  margin-top: 32px;
  padding: 20px;
  background: rgba(64, 156, 255, 0.1);
  border: 2px solid var(--color-accent);
  border-radius: 8px;
  text-align: center;
}

.consent-notice p {
  margin: 0;
  color: var(--color-heading);
}

.privacy-footer {
  padding: 20px 32px;
  border-top: 1px solid var(--color-border);
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.accept-btn,
.cancel-btn {
  padding: 10px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.accept-btn {
  background: var(--color-accent);
  color: white;
}

.accept-btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.cancel-btn {
  background: var(--color-background-mute);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.cancel-btn:hover {
  background: var(--color-background);
}

a {
  color: var(--color-accent);
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .privacy-header {
    padding: 20px;
  }

  .privacy-content {
    padding: 20px;
  }

  .privacy-header h1 {
    font-size: 24px;
  }

  .rights-grid {
    grid-template-columns: 1fr;
  }

  .privacy-footer {
    padding: 16px 20px;
    flex-direction: column-reverse;
  }

  .accept-btn,
  .cancel-btn {
    width: 100%;
  }
}
</style>
