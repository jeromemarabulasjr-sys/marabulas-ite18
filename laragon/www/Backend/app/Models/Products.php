<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'type',
        'price',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Get all sales for this product.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'product_id');
    }

    /**
     * Get all restocks for this product.
     */
    public function restocks()
    {
        return $this->hasMany(Restock::class, 'product_id');
    }
}
