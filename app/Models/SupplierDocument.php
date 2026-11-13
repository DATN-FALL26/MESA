<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupplierDocumentType;
use App\Helpers\ConstantHelper;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $supplier_id
 * @property SupplierDocumentType $document_type
 * @property string|null $document_number
 * @property string $document_name
 * @property string|null $issuing_authority
 * @property CarbonInterface|null $issue_date
 * @property CarbonInterface|null $expiry_date
 * @property string $file_path
 * @property string $file_name
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property string|null $notes
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Supplier $supplier
 * @property-read bool $is_expired
 * @property-read bool $is_expiring_soon
 * @property-read int|null $days_until_expiration
 * @property-read string $file_url
 * @property-read string $document_type_label
 */
class SupplierDocument extends Model
{
    protected $table = ConstantHelper::TABLE_SUPPLIER_DOCUMENTS;

    protected $guarded = [];

    protected $appends = [
        'is_expired',
        'is_expiring_soon',
        'days_until_expiration',
        'document_type_label',
        'file_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => SupplierDocumentType::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'file_size' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }

        return $this->expiry_date->endOfDay()->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if ($this->expiry_date === null || $this->is_expired) {
            return false;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false) <= 30;
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->expiry_date, false);
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return $this->document_type instanceof SupplierDocumentType
            ? $this->document_type->label()
            : (string) $this->document_type;
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
