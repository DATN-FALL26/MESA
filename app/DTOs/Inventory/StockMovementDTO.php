<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

use App\Enums\MovementType;

final readonly class StockMovementDTO
{
    public function __construct(
        public int $warehouseId,
        public int $ingredientId,
        public MovementType $movementType,
        public float $quantity,
        public ?float $unitCost = null,
        public ?string $refType = null,
        public ?int $refId = null,
        public ?string $idempotencyKey = null,
        public ?string $note = null,
        public ?int $createdBy = null,
    ) {}

    /**
     * Khởi tạo DTO từ mảng dữ liệu đầu vào.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $movementType = $data['movement_type'] instanceof MovementType
            ? $data['movement_type']
            : MovementType::from((string) $data['movement_type']);

        return new self(
            warehouseId: (int) $data['warehouse_id'],
            ingredientId: (int) $data['ingredient_id'],
            movementType: $movementType,
            quantity: (float) $data['quantity'],
            unitCost: isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            refType: $data['ref_type'] ?? null,
            refId: isset($data['ref_id']) ? (int) $data['ref_id'] : null,
            idempotencyKey: $data['idempotency_key'] ?? null,
            note: $data['note'] ?? null,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : (auth()->id() ? (int) auth()->id() : null),
        );
    }

    /**
     * Chuyển đổi DTO sang mảng phục vụ lưu trữ Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'warehouse_id' => $this->warehouseId,
            'ingredient_id' => $this->ingredientId,
            'movement_type' => $this->movementType->value,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unitCost,
            'ref_type' => $this->refType,
            'ref_id' => $this->refId,
            'idempotency_key' => $this->idempotencyKey,
            'note' => $this->note,
            'created_by' => $this->createdBy,
            'created_at' => now(),
        ];
    }
}
