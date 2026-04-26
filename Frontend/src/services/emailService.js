import emailjs from '@emailjs/browser'

// EmailJS Configuration
// To use this service:
// 1. Sign up at https://www.emailjs.com/
// 2. Create an email service (Gmail, Outlook, etc.)
// 3. Create an email template with variables: {{to_email}}, {{verification_code}}, {{username}}
// 4. Replace the IDs below with your actual EmailJS credentials

const EMAILJS_CONFIG = {
  serviceId: 'service_xxxxxxx',  // Replace with your EmailJS Service ID
  templateId: 'template_xxxxxxx', // Replace with your EmailJS Template ID
  publicKey: 'YOUR_PUBLIC_KEY',   // Replace with your EmailJS Public Key
}

/**
 * Send verification code via email using EmailJS
 * @param {string} toEmail - Recipient email address
 * @param {string} verificationCode - 6-digit verification code
 * @param {string} username - Username for personalization
 * @returns {Promise<boolean>} - Returns true if email sent successfully
 */
export async function sendVerificationEmail(toEmail, verificationCode, username = 'User') {
  try {
    // Initialize EmailJS with public key
    emailjs.init(EMAILJS_CONFIG.publicKey)

    const templateParams = {
      to_email: toEmail,
      to_name: username,
      verification_code: verificationCode,
      app_name: 'Inventory Management System',
      from_name: 'Inventory System',
    }

    const response = await emailjs.send(
      EMAILJS_CONFIG.serviceId,
      EMAILJS_CONFIG.templateId,
      templateParams
    )

    console.log('Email sent successfully:', response)
    return true
  } catch (error) {
    console.error('Failed to send email:', error)
    throw new Error('Failed to send verification email. Please try again.')
  }
}

/**
 * Check if EmailJS is properly configured
 * @returns {boolean}
 */
export function isEmailServiceConfigured() {
  return (
    EMAILJS_CONFIG.serviceId !== 'service_xxxxxxx' &&
    EMAILJS_CONFIG.templateId !== 'template_xxxxxxx' &&
    EMAILJS_CONFIG.publicKey !== 'YOUR_PUBLIC_KEY'
  )
}
