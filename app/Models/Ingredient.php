<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'unit_id',
        'code',
        'name',
        'shelf_life_days',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shelf_life_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the category that the ingredient belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(IngredientCategory::class, 'category_id');
    }

    /**
     * Get the standard unit of the ingredient.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the inventory records for this ingredient across warehouses.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'ingredient_id');
    }
}
