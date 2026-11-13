<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupplierDocumentType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $contact
 * @property string|null $phone
 * @property string|null $address
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read Collection<int, SupplierDocument> $documents
 */
class Supplier extends Model
{
    protected $table = ConstantHelper::TABLE_SUPPLIERS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class, 'supplier_id');
    }

    /**
     * Kiểm tra nhà cung cấp có chứng nhận ATVSTP còn hiệu lực hay không.
     */
    public function hasValidFoodSafetyCert(): bool
    {
        return $this->documents()
            ->where('document_type', SupplierDocumentType::FOOD_SAFETY_CERT)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->exists();
    }
}
