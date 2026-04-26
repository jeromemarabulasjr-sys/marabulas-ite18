<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'username',
        'quantity_sold',
        'unit_price',
        'total_amount',
        'previous_stock',
        'new_stock',
    ];

    protected $casts = [
        'quantity_sold' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product associated with this sale.
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    /**
     * Get the user who made this sale.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
