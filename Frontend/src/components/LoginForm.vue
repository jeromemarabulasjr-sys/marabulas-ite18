<template>
  <div class="login-container">
    <div class="brand-showcase animate-fade-in-scale" v-if="!showLoginForm">
      <div class="brand-content">
        <div class="brand-icon animate-float">
          <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="8" y="8" width="48" height="48" rx="8" stroke="currentColor" stroke-width="3"/>
            <path d="M20 28 L28 36 L44 20" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h1 class="brand-title">Inventory Management System</h1>
        <p class="brand-tagline">Streamline your operations. Optimize your stock. Maximize efficiency.</p>
        <div class="brand-features">
          <div class="feature">
            <span class="feature-icon">📊</span>
            <span>Real-time Analytics</span>
          </div>
          <div class="feature">
            <span class="feature-icon">🔒</span>
            <span>Secure & Reliable</span>
          </div>
          <div class="feature">
            <span class="feature-icon">⚡</span>
            <span>Lightning Fast</span>
          </div>
        </div>
        <button class="cta-button" @click="scrollToLogin">
          Sign In Now →
        </button>
        <button class="contact-button" @click="showContactModal = true">
          📧 Contact Us
        </button>
      </div>
    </div>

    <div class="login-form animate-fade-in-scale" v-if="showLoginForm">
      <button class="back-to-brand" @click="showLoginForm = false">
        ← Back
      </button>
      <div class="login-form-content">
        <h2>{{ showForgotPassword ? 'Reset Password' : 'Welcome Back' }}</h2>
        <p class="subtitle">{{ showForgotPassword ? 'Enter your email to reset password' : 'Sign in to continue' }}</p>

      <form v-if="!showForgotPassword" @submit.prevent="handleSubmit">
        <div class="form-field">
          <label>Username</label>
          <input type="text" v-model="username" placeholder="Enter your username" required />
        </div>

        <div class="form-field">
          <label>Password</label>
          <input type="password" v-model="password" placeholder="Enter your password" required />
        </div>

        <button type="submit">Sign In</button>

        <div class="forgot-password-link">
          <a @click="showForgotPassword = true">Forgot Password?</a>
        </div>
      </form>

      <form v-else @submit.prevent="handlePasswordReset">
        <div class="form-field">
          <label>Email Address</label>
          <input type="email" v-model="resetEmail" placeholder="Enter your email" required />
        </div>

        <button type="submit">Send Reset Link</button>

        <div class="back-to-login">
          <a @click="showForgotPassword = false">← Back to Login</a>
        </div>
      </form>
      </div>
    </div>

    <!-- Contact Us Modal -->
    <div v-if="showContactModal" class="contact-backdrop" @click="showContactModal = false"></div>
    <div v-if="showContactModal" class="contact-modal">
      <div class="contact-panel">
        <button class="modal-close" @click="showContactModal = false" aria-label="Close contact form">✕</button>
        <h2>Contact Us</h2>
        <p class="contact-subtitle">Get in touch with our support team</p>

        <form @submit.prevent="submitContactForm">
          <div class="form-field">
            <label>Name *</label>
            <input
              type="text"
              v-model="contactForm.name"
              placeholder="Your full name"
              required
            />
          </div>

          <div class="form-field">
            <label>Email *</label>
            <input
              type="email"
              v-model="contactForm.email"
              placeholder="your.email@example.com"
              required
            />
          </div>

          <div class="form-field">
            <label>Subject *</label>
            <select v-model="contactForm.subject" required>
              <option value="">Select a topic</option>
              <option value="support">Technical Support</option>
              <option value="sales">Sales Inquiry</option>
              <option value="demo">Request Demo</option>
              <option value="feedback">Feedback</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="form-field">
            <label>Message *</label>
            <textarea
              v-model="contactForm.message"
              placeholder="How can we help you?"
              rows="5"
              required
            ></textarea>
          </div>

          <button type="submit" :disabled="sendingContact">
            {{ sendingContact ? 'Sending...' : 'Send Message' }}
          </button>
        </form>

        <div class="contact-info">
          <h3>Other ways to reach us:</h3>
          <div class="contact-methods">
            <div class="contact-method">
              <span class="method-icon">📧</span>
              <div>
                <strong>Email</strong>
                <p>yondaime525@gmail.com</p>
              </div>
            </div>
            <div class="contact-method">
              <span class="method-icon">📞</span>
              <div>
                <strong>Phone</strong>
                <p>+639271621369</p>
              </div>
            </div>
            <div class="contact-method">
              <span class="method-icon">🕐</span>
              <div>
                <strong>Business Hours</strong>
                <p>Mon-Fri: 9:00 AM - 6:00 PM GMT+8</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ElMessageBox } from 'element-plus'

export default {
  name: 'LoginForm',
  data() {
    return {
      username: '',
      password: '',
      showForgotPassword: false,
      resetEmail: '',
      showLoginForm: false,
      showContactModal: false,
      sendingContact: false,
      contactForm: {
        name: '',
        email: '',
        subject: '',
        message: '',
      },
    }
  },
  methods: {
    scrollToLogin() {
      this.showLoginForm = true
      this.$nextTick(() => {
        const usernameInput = this.$el.querySelector('input[type="text"]')
        if (usernameInput) usernameInput.focus()
      })
    },
    handleSubmit() {
      // Initialize default credential if no credentials exist
      const credentials = JSON.parse(localStorage.getItem('userCredentials') || '[]')

      // Add default admin credential if storage is empty
      if (credentials.length === 0) {
        credentials.push({
          username: 'admin',
          email: 'admin@inventory.com',
          password: 'admin123'
        })
        localStorage.setItem('userCredentials', JSON.stringify(credentials))
      }

      // Verify credentials
      const user = credentials.find(cred =>
        cred.username === this.username && cred.password === this.password
      )

      if (!user) {
        ElMessageBox.alert('Invalid username or password', 'Login Failed', {
          type: 'error',
        })
        return
      }

      // Store current user info for activity tracking
      localStorage.setItem('currentUser', JSON.stringify({
        username: user.username,
        email: user.email,
        loginAt: new Date().toISOString(),
      }))

      this.$emit('login', this.username)
    },
    async handlePasswordReset() {
      if (!this.resetEmail) {
        ElMessageBox.alert('Please enter your email address', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(this.resetEmail)) {
        ElMessageBox.alert('Please enter a valid email address', 'Validation Error', {
          type: 'warning',
        })
        return
      }

      // Check if email exists in stored credentials
      const credentials = JSON.parse(localStorage.getItem('userCredentials') || '[]')
      const userExists = credentials.find(cred => cred.email === this.resetEmail)

      if (!userExists) {
        ElMessageBox.alert('No account found with this email address', 'Email Not Found', {
          type: 'error',
        })
        return
      }

      // Simulate sending reset link
      await ElMessageBox.alert(
        `A password reset link has been sent to ${this.resetEmail}.\n\nPlease check your email to continue.`,
        'Reset Link Sent',
        { type: 'success' },
      )

      // Reset form and go back to login
      this.resetEmail = ''
      this.showForgotPassword = false
    },
    async submitContactForm() {
      this.sendingContact = true

      // Simulate sending contact form (in production, send to backend or email service)
      await new Promise(resolve => setTimeout(resolve, 1500))

      this.sendingContact = false
      this.showContactModal = false

      await ElMessageBox.alert(
        'Thank you for contacting us! We will respond to your inquiry within 24 hours.',
        'Message Sent Successfully',
        { type: 'success' }
      )

      // Reset form
      this.contactForm = {
        name: '',
        email: '',
        subject: '',
        message: '',
      }
    },
  },
}
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  min-height: 100vh;
  gap: 60px;
  padding: 60px 40px 40px 40px;
  background: var(--color-background);
}

.brand-showcase {
  flex: 1;
  max-width: 600px;
}

.brand-content {
  text-align: center;
}

.brand-icon {
  color: var(--color-accent);
  margin-bottom: 24px;
}

.brand-title {
  font-size: 42px;
  font-weight: 700;
  color: var(--color-heading);
  margin: 0 0 16px 0;
  line-height: 1.2;
}

.brand-tagline {
  font-size: 18px;
  color: var(--color-text-muted);
  margin-bottom: 40px;
  line-height: 1.6;
}

.brand-features {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.feature {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;
  color: var(--color-text);
  padding: 12px;
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  transition: all 0.2s;
}

.feature:hover {
  transform: translateX(8px);
  border-color: var(--color-accent);
  background: var(--color-background-mute);
}

.feature-icon {
  font-size: 24px;
}

.cta-button {
  margin-top: 32px;
  padding: 14px 32px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(64, 156, 255, 0.3);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.cta-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(64, 156, 255, 0.4);
}

.cta-button:active {
  transform: translateY(0);
}

.contact-button {
  margin-top: 16px;
  padding: 12px 28px;
  background: transparent;
  color: var(--color-accent);
  border: 2px solid var(--color-accent);
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.contact-button:hover {
  background: rgba(64, 156, 255, 0.1);
  transform: translateY(-2px);
}

.contact-button:active {
  transform: translateY(0);
}

.login-form {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 0;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  position: relative;
  overflow: hidden;
}

.back-to-brand {
  width: auto;
  border: none;
  color: var(--color-text-muted);
  font-size: 14px;
  cursor: pointer;
  padding: 12px 16px;
  text-align: left;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: transparent;
  border-radius: 6px;
}

.back-to-brand:hover {
  color: var(--color-accent);
  background: var(--color-background-mute);
  transform: translateX(-2px);
}

.login-form-content {
  padding: 32px;
}



@media (max-width: 900px) {
  .login-container {
    flex-direction: column;
    gap: 40px;
    padding: 20px;
  }

  .brand-showcase {
    text-align: center;
  }

  .brand-content {
    text-align: center;
  }

  .brand-title {
    font-size: 32px;
  }

  .brand-tagline {
    font-size: 16px;
  }

  .feature:hover {
    transform: none;
  }
}

h2 {
  margin: 0 0 4px 0;
  color: var(--color-text);
  font-size: 24px;
}

.subtitle {
  color: var(--color-text-muted);
  margin-bottom: 24px;
  font-size: 14px;
}

.form-field {
  margin-bottom: 16px;
}

label {
  display: block;
  margin-bottom: 6px;
  color: var(--color-text);
  font-size: 14px;
  font-weight: 500;
}

input {
  width: 100%;
  padding: 10px 14px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  color: var(--color-text);
  font-size: 14px;
  transition: border-color 0.2s;
}

input:focus {
  border-color: var(--color-accent);
  outline: none;
}

button {
  width: 100%;
  padding: 12px;
  background: var(--color-accent);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
  transition: opacity 0.2s, transform 0.1s;
}

button:hover {
  opacity: 0.9;
}

button:active {
  transform: scale(0.98);
}

.forgot-password-link {
  text-align: center;
  margin-top: 16px;
}

.forgot-password-link a,
.back-to-login a {
  color: var(--color-accent);
  font-size: 13px;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.2s;
}

.forgot-password-link a:hover,
.back-to-login a:hover {
  opacity: 0.8;
  text-decoration: underline;
}

.back-to-login {
  text-align: center;
  margin-top: 16px;
}

.back-to-login a {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Contact Modal */
.contact-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  z-index: 9998;
  animation: fadeIn 0.3s ease-out;
}

.contact-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  z-index: 9999;
  animation: fadeInScale 0.3s ease-out;
}

.contact-panel {
  background: var(--color-background-soft);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.5);
  overflow-y: auto;
  max-height: 90vh;
  position: relative;
}

.contact-panel h2 {
  margin: 0 0 8px 0;
  font-size: 24px;
  color: var(--color-heading);
}

.contact-subtitle {
  margin: 0 0 24px 0;
  color: var(--color-text-muted);
  font-size: 14px;
}

.contact-panel .form-field {
  margin-bottom: 20px;
}

.contact-panel textarea {
  width: 100%;
  padding: 10px 14px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  color: var(--color-text);
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  transition: border-color 0.2s;
}

.contact-panel textarea:focus {
  border-color: var(--color-accent);
  outline: none;
}

.contact-panel select {
  width: 100%;
  padding: 10px 14px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  color: var(--color-text);
  font-size: 14px;
  cursor: pointer;
  transition: border-color 0.2s;
}

.contact-panel select:focus {
  border-color: var(--color-accent);
  outline: none;
}

.modal-close {
  position: absolute;
  right: 16px;
  top: 16px;
  background: var(--color-background-mute);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: var(--color-text);
  cursor: pointer;
  line-height: 1;
  transition: all 0.2s;
}

.modal-close:hover {
  background: var(--color-accent);
  color: white;
  transform: rotate(90deg);
}

.contact-info {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid var(--color-border);
}

.contact-info h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: var(--color-heading);
}

.contact-methods {
  display: grid;
  gap: 12px;
}

.contact-method {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  background: var(--color-background-mute);
  border-radius: 8px;
  border: 1px solid var(--color-border);
}

.method-icon {
  font-size: 20px;
  flex-shrink: 0;
}

.contact-method strong {
  display: block;
  color: var(--color-heading);
  font-size: 14px;
  margin-bottom: 2px;
}

.contact-method p {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

@media (max-width: 768px) {
  .contact-panel {
    padding: 24px;
  }

  .contact-modal {
    width: 95%;
  }
}
</style>
