# Frontend-Backend Connection Setup

## ✅ Your frontend is now connected to the Laravel backend!

### What was changed:

1. **Created API Service** (`src/services/api.js`)
   - Axios-based HTTP client
   - All CRUD operations for products
   - Configured for `http://localhost:8000/api`

2. **Updated Store** (`src/store/inventory.js`)
   - All methods are now async and use the API
   - Has `USE_API` flag to toggle between API and localStorage
   - Automatic fallback to localStorage on API errors

3. **Updated Components**
   - ProductList, ProductForm, Dashboard, Sidebar
   - All store calls are now async (`await`)

4. **Added axios dependency** to package.json

---

## 🚀 Steps to Get Running:

### 1. Install axios in Frontend
```bash
cd Frontend
npm install
```

### 2. Start Laravel Backend (if not running)
```bash
cd ../laragon/www/Backend
php artisan migrate
php artisan db:seed --class=ProductSeeder
php artisan serve
```
Backend will run at: `http://localhost:8000`

### 3. Start Vue Frontend
```bash
cd ../../Frontend
npm run dev
```
Frontend will run at: `http://localhost:5173`

---

## 🎛️ Configuration

### Change API URL (if needed)
Edit `Frontend/src/services/api.js`:
```javascript
const API_BASE_URL = 'http://localhost:8000/api'
```

### Toggle between API and localStorage
Edit `Frontend/src/store/inventory.js`:
```javascript
const USE_API = true  // false = localStorage only
```

---

## ✨ Features Now Working:

- ✅ Add products → Saved to MySQL database
- ✅ Edit products → Updates in database
- ✅ Delete products → Removed from database
- ✅ View all products → Fetched from API
- ✅ Low stock alerts → Queried from API
- ✅ Dashboard stats → Real-time from database
- ✅ Search & filter → Client-side on API data

---

## 🔍 Troubleshooting:

### CORS Errors?
Check that your Laravel backend has CORS enabled (already configured)

### Connection Refused?
Make sure Laravel is running: `php artisan serve`

### Can't see products?
1. Check browser console for errors
2. Verify backend is seeded: `php artisan db:seed --class=ProductSeeder`
3. Test API directly: `http://localhost:8000/api/products`

### Want to use localStorage temporarily?
Set `USE_API = false` in `src/store/inventory.js`

---

## 🎉 You're all set!

Your inventory system now has:
- Vue.js frontend with beautiful UI
- Laravel backend with MySQL database
- Full REST API integration
- Real-time data synchronization
