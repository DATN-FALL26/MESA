<?php

declare(strict_types=1);

namespace App\Services\Inventory;

class CostCalculationService
{
    /**
     * Tính toán giá vốn bình quân gia quyền (Weighted Average Cost) khi nhập kho mới.
     *
     * Công thức:
     * New Cost = ((Tồn hiện tại * Giá vốn hiện tại) + (Số lượng nhập * Đơn giá nhập)) / (Tồn hiện tại + Số lượng nhập)
     *
     * @param float $currentQty Số lượng tồn kho hiện thời trước khi nhập
     * @param float $currentAvgCost Đơn giá vốn bình quân hiện thời (VND)
     * @param float $inboundQty Số lượng nhập kho mới (> 0)
     * @param float $inboundUnitCost Đơn giá nhập kho mới (VND)
     * @return float Đơn giá vốn bình quân mới (làm tròn 2 chữ số thập phân)
     */
    public function calculateWeightedAverageCost(
        float $currentQty,
        float $currentAvgCost,
        float $inboundQty,
        float $inboundUnitCost
    ): float {
        // Nếu số lượng nhập <= 0 hoặc đơn giá nhập không hợp lệ, giữ nguyên giá cũ
        if ($inboundQty <= 0) {
            return round(max(0.0, $currentAvgCost), 2);
        }

        // Trường hợp tồn kho hiện tại âm hoặc bằng 0: Giá vốn mới chính là đơn giá nhập
        if ($currentQty <= 0) {
            return round(max(0.0, $inboundUnitCost), 2);
        }

        $currentTotalValue = $currentQty * $currentAvgCost;
        $inboundTotalValue = $inboundQty * $inboundUnitCost;
        $newTotalQty = $currentQty + $inboundQty;

        if ($newTotalQty <= 0) {
            return round(max(0.0, $inboundUnitCost), 2);
        }

        $newAvgCost = ($currentTotalValue + $inboundTotalValue) / $newTotalQty;

        return round(max(0.0, $newAvgCost), 2);
    }
}
