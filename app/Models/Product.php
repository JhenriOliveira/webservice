<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'barbershop_id',
        'name',
        'description',
        'price',
        'stock_quantity',
        'min_stock',
        'category',
        'brand',
        'sku',
        'ingredients',
        'usage_instructions',
        'is_active',
        'image_url'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean'
    ];

    // Relacionamentos
    public function barbershop()
    {
        return $this->belongsTo(Barbershop::class, 'barbershop_id');
    }

    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointments_products', 'product_id', 'appointment_id')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->where('stock_quantity', '<=', $this->min_stock)
                    ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    public function scopeForBarbershop($query, $barbershopId)
    {
        return $query->where('barbershop_id', $barbershopId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
    }

    public function decreaseStock($quantity)
    {
        $this->stock_quantity = max(0, $this->stock_quantity - $quantity);
        return $this->save();
    }

    public function increaseStock($quantity)
    {
        $this->stock_quantity += $quantity;
        return $this->save();
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->min_stock && $this->stock_quantity > 0;
    }

    public function isOutOfStock()
    {
        return $this->stock_quantity <= 0;
    }

    public function getStockStatusAttribute()
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }
        
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    public function getFormattedPriceAttribute()
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}