# Backend Setup Instructions

## Complete! Your inventory backend is ready.

### What was updated:

✅ **Migration** - Added inventory fields to products table
✅ **Model** - Updated Products model with new fillable fields
✅ **Controller** - Enhanced ProductController with inventory operations
✅ **Routes** - Registered all API endpoints
✅ **CORS** - Configured for frontend communication
✅ **Seeder** - Added sample inventory data

---

## Next Steps - Run these commands:

### 1. Run the migration
```bash
php artisan migrate
```

### 2. Seed the database with sample data
```bash
php artisan db:seed --class=ProductSeeder
```

Or run all seeders:
```bash
php artisan db:seed
```

### 3. Start the Laravel server
```bash
php artisan serve
```
The API will be available at: `http://localhost:8000/api`

---

## API Endpoints

**Base URL:** `http://localhost:8000/api`

### Products
- `GET /products` - Get all products
- `POST /products` - Create a new product
- `GET /products/{id}` - Get single product
- `PUT /products/{id}` - Update product
- `DELETE /products/{id}` - Delete product
- `GET /products/low-stock?threshold=5` - Get low stock items

### Authentication
- `POST /login` - User login
- `POST /signup` - User registration

---

## Sample Product JSON

```json
{
  "name": "Wireless Mouse",
  "sku": "MOU-001",
  "price": 19.99,
  "quantity": 45,
  "location": "A1",
  "notes": "Ergonomic design"
}
```

---

## Database Configuration

Current settings (from .env):
- **Database:** school
- **Host:** localhost
- **Port:** 3306
- **Username:** root
- **Password:** qwerty

Make sure your MySQL server is running before migrating.

---

## Testing the API

You can test with:
- Postman
- Thunder Client (VS Code extension)
- cURL commands
- Your Vue.js frontend

Example cURL:
```bash
curl http://localhost:8000/api/products
```
