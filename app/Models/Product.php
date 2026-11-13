<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fit_title',
        'category',
        'sub_category',
        'price',
        'old_price',
        'rating',
        'reviews_count',
        'image',
        'gallery',
        'sizes',
        'description',
        'tags',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'reviews_count' => 'integer',
            'gallery' => 'array',
            'sizes' => 'array',
            'tags' => 'array',
            'stock' => 'integer',
        ];
    }

    /**
     * Get the cart entries containing this product.
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the order items associated with this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
