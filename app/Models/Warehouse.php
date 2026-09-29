<?php

namespace App\Models;

use App\Enums\Inventory\WarehouseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'code',
        'name',
        'type',
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
            'type' => WarehouseType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the inventories managed in this warehouse.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }

    /*
     * Relationship to Store model:
     * Pending definition of Store model by module owner (MOD01 / Core).
     *
     * public function store(): BelongsTo
     * {
     *     return $this->belongsTo(Store::class, 'store_id');
     * }
     */
}
