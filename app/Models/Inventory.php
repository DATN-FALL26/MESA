<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'ingredient_id',
        'quantity',
        'reserved_quantity',
        'min_stock_level',
        'max_stock_level',
        'last_checked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'min_stock_level' => 'decimal:3',
            'max_stock_level' => 'decimal:3',
            'last_checked_at' => 'datetime',
        ];
    }

    /**
     * Get the warehouse this inventory belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the ingredient this inventory record tracks.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }

    /**
     * Available physical quantity (total minus reserved).
     */
    protected function availableQuantity(): Attribute
    {
        return Attribute::make(
            get: fn (): string => bcsub((string) $this->quantity, (string) $this->reserved_quantity, 3)
        );
    }
}
