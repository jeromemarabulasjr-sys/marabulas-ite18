<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restock extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'username',
        'quantity_added',
        'unit_cost',
        'total_cost',
        'previous_stock',
        'new_stock',
    ];

    protected $casts = [
        'quantity_added' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product associated with this restock.
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    /**
     * Get the user who performed this restock.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
