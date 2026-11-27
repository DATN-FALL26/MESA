<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Inventory\StockAlertService;
use Illuminate\Console\Command;

class ScanInventoryAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:scan-alerts {--branch= : Mã chi nhánh cần quét (để trống để quét tất cả)} {--days=3 : Số ngày trước hạn sử dụng để cảnh báo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét kiểm tra tồn kho dưới mức tối thiểu và lô nguyên liệu sắp/đã hết hạn';

    /**
     * Execute the console command.
     */
    public function handle(StockAlertService $alertService): int
    {
        $branchOption = $this->option('branch');
        $branchId = $branchOption !== null ? (int) $branchOption : null;
        $days = (int) $this->option('days');

        $this->info("Bắt đầu quét cảnh báo tồn kho và hạn sử dụng nguyên liệu...");

        // 1. Quét cảnh báo tồn kho
        $lowStockResult = $alertService->scanLowStockAlerts($branchId);
        $this->info(">> Cảnh báo tồn kho: {$lowStockResult['created']} mới tạo, {$lowStockResult['updated']} cập nhật.");

        // 2. Quét cảnh báo hạn sử dụng
        $expiryResult = $alertService->scanExpiryAlerts($days, $branchId);
        $this->info(">> Cảnh báo hạn sử dụng (ngưỡng {$days} ngày): {$expiryResult['created']} mới tạo, {$expiryResult['updated']} cập nhật.");

        $this->info("Hoàn tất quét cảnh báo kho.");

        return Command::SUCCESS;
    }
}
