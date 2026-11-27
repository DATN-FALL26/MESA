<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Enums\SupplierDocumentType;
use App\Models\SupplierDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SupplierDocument
 */
class SupplierDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'document_type' => $this->document_type instanceof SupplierDocumentType
                ? $this->document_type->value
                : (string) $this->document_type,
            'document_type_label' => $this->document_type_label,
            'document_number' => $this->document_number,
            'document_name' => $this->document_name,
            'issuing_authority' => $this->issuing_authority,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'file_url' => $this->file_url,
            'notes' => $this->notes,
            'is_expired' => $this->is_expired,
            'is_expiring_soon' => $this->is_expiring_soon,
            'days_until_expiration' => $this->days_until_expiration,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
