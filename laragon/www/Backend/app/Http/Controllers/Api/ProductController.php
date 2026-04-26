<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Products as ProductModel;
use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Models\Sale;
use App\Models\Restock;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Get activity type ID by type key.
     */
    private function getActivityTypeId(string $typeKey): ?int
    {
        $activityType = ActivityType::where('type_key', $typeKey)->first();
        return $activityType ? $activityType->id : null;
    }

    /**
     * Get user ID from username.
     * Searches by name, email, or email prefix (e.g., 'admin' matches 'admin@inventory.com')
     */
    private function getUserIdFromUsername(?string $username): ?int
    {
        if (!$username) {
            return Auth::id();
        }

        // Try exact name match first
        $user = User::where('name', $username)->first();

        // If not found, try email match
        if (!$user) {
            $user = User::where('email', $username)->first();
        }

        // If still not found, try matching email prefix (username@domain.com)
        if (!$user) {
            $user = User::where('email', 'like', $username . '@%')->first();
        }

        return $user ? $user->id : null;
    }

    /**
     * Display a listing of all products.
     * Optimized: Added select for only needed columns and limit option.
     */
    public function index(Request $request)
    {
        $query = ProductModel::query();

        // Optional pagination for large datasets
        $limit = $request->input('limit');
        if ($limit) {
            $limit = min((int)$limit, 1000); // Cap at 1000
            $query->limit($limit);
        }

        // Optional search filter
        $search = $request->input('search');
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $products = $query->get();
        return response()->json($products);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        // Get username from request
        $usernameFromRequest = $request->input('_currentUsername');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'sku'         => 'required|string|max:100|unique:products,sku',
            'type'        => 'nullable|string|max:100',
            'price'       => 'nullable|numeric|min:0',
            'quantity'    => 'nullable|integer|min:0',
            'notes'       => 'nullable|string',
        ]);

        $product = ProductModel::create($validated);

        // Get username (prefer request, fallback to Auth)
        $username = $usernameFromRequest ?? (Auth::check() ? Auth::user()->name : null);

        // Log activity
        ActivityLog::create([
            'type' => 'add',
            'activity_type_id' => $this->getActivityTypeId('add'),
            'user_id' => $this->getUserIdFromUsername($username),
            'username' => $username,
            'details' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ]);

        return response()->json($product, 201);
    }

    /**
     * Display the specified product.
     */
    public function show(string $id)
    {
        $product = ProductModel::findOrFail($id);
        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = ProductModel::findOrFail($id);

        // Get username from request before validation strips it
        $usernameFromRequest = $request->input('_currentUsername');

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'sku'         => 'nullable|string|max:100|unique:products,sku,' . $id,
            'type'        => 'nullable|string|max:100',
            'price'       => 'nullable|numeric|min:0',
            'quantity'    => 'nullable|integer|min:0',
            'notes'       => 'nullable|string',
        ]);

        // Track what changed
        $original = $product->getOriginal();
        $changes = [];
        $fields = ['name', 'sku', 'type', 'quantity', 'price', 'notes'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                $origVal = $original[$field] ?? '';
                $newVal = $validated[$field] ?? '';
                if ((string)$origVal !== (string)$newVal) {
                    $changes[] = $field;
                }
            }
        }

        // Check if quantity changed and determine if it's a sale or restock
        $activityType = 'update';
        $activityDetails = [
            'id' => $product->id,
            'name' => $product->name,
            'changes' => $changes,
        ];

        if (in_array('quantity', $changes)) {
            $oldQuantity = (int)$original['quantity'];
            $newQuantity = (int)$validated['quantity'];
            $difference = $newQuantity - $oldQuantity;

            // Get username from authenticated user or from request
            $username = null;
            if (Auth::check()) {
                $username = Auth::user()->name;
            } elseif ($usernameFromRequest) {
                $username = $usernameFromRequest;
            }

            if ($difference < 0) {
                // Stock decreased - record as sale
                $quantitySold = abs($difference);
                Sale::create([
                    'product_id' => $product->id,
                    'username' => $username,
                    'quantity_sold' => $quantitySold,
                    'unit_price' => $product->price ?? 0,
                    'total_amount' => $quantitySold * ($product->price ?? 0),
                    'previous_stock' => $oldQuantity,
                    'new_stock' => $newQuantity,
                ]);

                // Update activity type and details for sale
                $activityType = 'sale';
                $activityDetails['quantity'] = $quantitySold;
                $activityDetails['previousStock'] = $oldQuantity;
                $activityDetails['newStock'] = $newQuantity;
            } elseif ($difference > 0) {
                // Stock increased - record as restock
                Restock::create([
                    'product_id' => $product->id,
                    'username' => $username,
                    'quantity_added' => $difference,
                    'unit_cost' => $product->price ?? 0,
                    'total_cost' => $difference * ($product->price ?? 0),
                    'previous_stock' => $oldQuantity,
                    'new_stock' => $newQuantity,
                ]);

                // Update activity type and details for restock
                $activityType = 'restock';
                $activityDetails['quantity'] = $difference;
                $activityDetails['previousStock'] = $oldQuantity;
                $activityDetails['newStock'] = $newQuantity;
            }
        }

        $product->update($validated);

        // Log activity with appropriate type
        $activityUsername = null;
        if (Auth::check()) {
            $activityUsername = Auth::user()->name;
        } elseif ($usernameFromRequest) {
            $activityUsername = $usernameFromRequest;
        }

        ActivityLog::create([
            'type' => $activityType,
            'activity_type_id' => $this->getActivityTypeId($activityType),
            'user_id' => $this->getUserIdFromUsername($activityUsername),
            'username' => $activityUsername,
            'details' => $activityDetails,
        ]);

        return response()->json($product);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $product = ProductModel::findOrFail($id);
        $productName = $product->name;

        // Get username from request
        $usernameFromRequest = $request->input('_currentUsername');
        $username = $usernameFromRequest ?? (Auth::check() ? Auth::user()->name : null);

        $product->delete();

        // Log activity
        ActivityLog::create([
            'type' => 'remove',
            'activity_type_id' => $this->getActivityTypeId('remove'),
            'user_id' => $this->getUserIdFromUsername($username),
            'username' => $username,
            'details' => [
                'id' => $id,
                'name' => $productName,
            ],
        ]);

        return response()->json(null, 204);
    }

    /**
     * Get low stock products.
     * Optimized: Uses index on quantity column, limits results.
     */
    public function lowStock(Request $request)
    {
        $threshold = $request->input('threshold', 5);
        $limit = min((int)$request->input('limit', 50), 100);

        $products = ProductModel::where('quantity', '<=', $threshold)
            ->orderBy('quantity', 'asc')
            ->limit($limit)
            ->get();

        return response()->json($products);
    }

    /**
     * Delete all products.
     */
    public function destroyAll(Request $request)
    {
        $count = ProductModel::count();

        // Get username from request
        $usernameFromRequest = $request->input('_currentUsername');
        $username = $usernameFromRequest ?? (Auth::check() ? Auth::user()->name : null);

        ProductModel::truncate();

        // Log activity
        ActivityLog::create([
            'type' => 'clear',
            'activity_type_id' => $this->getActivityTypeId('clear'),
            'user_id' => $this->getUserIdFromUsername($username),
            'username' => $username,
            'details' => [
                'count' => $count,
            ],
        ]);

        return response()->json(['message' => 'All products deleted successfully', 'count' => $count]);
    }

    /**
     * Export all products.
     */
    public function export()
    {
        $products = ProductModel::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'type' => $product->type,
                'quantity' => $product->quantity,
                'price' => $product->price,
                'notes' => $product->notes,
            ];
        });

        return response()->json($products);
    }

    /**
     * Import multiple products (bulk insert or replace).
     */
    public function import(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.name' => 'required|string|max:255',
            'products.*.sku' => 'nullable|string|max:100',
            'products.*.type' => 'nullable|string|max:100',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.quantity' => 'nullable|integer|min:0',
            'products.*.notes' => 'nullable|string',
            'replace' => 'nullable|boolean',
        ]);

        $replace = $validated['replace'] ?? false;
        $products = $validated['products'];

        // Check for duplicate SKUs within import data
        $skus = array_filter(array_column($products, 'sku'));
        $duplicateSKUs = [];
        $skuCounts = array_count_values(array_map('strtolower', $skus));

        foreach ($skuCounts as $sku => $count) {
            if ($count > 1) {
                $duplicateSKUs[] = $sku;
            }
        }

        // If not replacing, check against existing products
        if (!$replace) {
            foreach ($products as $product) {
                if (!empty($product['sku'])) {
                    $exists = ProductModel::whereRaw('LOWER(sku) = ?', [strtolower($product['sku'])])->exists();
                    if ($exists && !in_array(strtolower($product['sku']), $duplicateSKUs)) {
                        $duplicateSKUs[] = strtolower($product['sku']);
                    }
                }
            }
        }

        if (!empty($duplicateSKUs)) {
            return response()->json([
                'error' => 'Duplicate SKUs found',
                'duplicates' => array_unique($duplicateSKUs),
            ], 422);
        }

        if ($replace) {
            ProductModel::truncate();
        }

        $imported = [];
        foreach ($products as $productData) {
            if (isset($productData['id']) && !$replace) {
                // Update existing product
                $product = ProductModel::find($productData['id']);
                if ($product) {
                    $product->update($productData);
                    $imported[] = $product;
                } else {
                    $imported[] = ProductModel::create($productData);
                }
            } else {
                // Create new product
                unset($productData['id']); // Remove id if present
                $imported[] = ProductModel::create($productData);
            }
        }

        // Get username from request
        $usernameFromRequest = $request->input('_currentUsername');
        $username = $usernameFromRequest ?? (Auth::check() ? Auth::user()->name : null);

        // Log activity
        ActivityLog::create([
            'type' => 'import',
            'activity_type_id' => $this->getActivityTypeId('import'),
            'user_id' => $this->getUserIdFromUsername($username),
            'username' => $username,
            'details' => [
                'count' => count($imported),
                'replace' => $replace,
            ],
        ]);

        return response()->json([
            'message' => 'Products imported successfully',
            'count' => count($imported),
            'products' => $imported,
        ], 201);
    }
}
