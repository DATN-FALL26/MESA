<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\ConstantHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DbDictionaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:dictionary {--output=docs/data-dictionary.md : File output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate complete Markdown data dictionary directly from MySQL information_schema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $databaseName = DB::connection()->getDatabaseName();
        $this->info("Extracting data dictionary for database: [{$databaseName}]...");

        // Danh sách 51 bảng nghiệp vụ theo nhóm
        $modules = [
            'A. TỔ CHỨC VÀ PHÂN QUYỀN' => [
                ConstantHelper::TABLE_BRANCHES,
                ConstantHelper::TABLE_DEPARTMENTS,
                ConstantHelper::TABLE_USERS,
                ConstantHelper::TABLE_ROLES,
                ConstantHelper::TABLE_PERMISSIONS,
                ConstantHelper::TABLE_ROLE_PERMISSIONS,
                ConstantHelper::TABLE_USER_ROLES,
                ConstantHelper::TABLE_AUDIT_LOGS,
            ],
            'B. BÀN VÀ LƯỢT DÙNG BỮA' => [
                ConstantHelper::TABLE_AREAS,
                ConstantHelper::TABLE_DINING_TABLES,
                ConstantHelper::TABLE_DINING_SESSIONS,
                ConstantHelper::TABLE_SESSION_TABLES,
            ],
            'C. THỰC ĐƠN & TÙY CHỌN' => [
                ConstantHelper::TABLE_CATEGORIES,
                ConstantHelper::TABLE_MENU_ITEMS,
                ConstantHelper::TABLE_ITEM_VARIANTS,
                ConstantHelper::TABLE_MODIFIER_GROUPS,
                ConstantHelper::TABLE_MODIFIERS,
                ConstantHelper::TABLE_MENU_ITEM_MODIFIER_GROUPS,
                ConstantHelper::TABLE_BRANCH_MENU_ITEMS,
            ],
            'D. GỌI MÓN (ORDER)' => [
                ConstantHelper::TABLE_ORDERS,
                ConstantHelper::TABLE_ORDER_BATCHES,
                ConstantHelper::TABLE_ORDER_ITEMS,
                ConstantHelper::TABLE_ORDER_ITEM_MODIFIERS,
                ConstantHelper::TABLE_ORDER_STATUS_HISTORY,
            ],
            'E. BẾP, KDS VÀ IN ẤN' => [
                ConstantHelper::TABLE_PRINTERS,
                ConstantHelper::TABLE_STATIONS,
                ConstantHelper::TABLE_KITCHEN_TICKETS,
                ConstantHelper::TABLE_KITCHEN_TICKET_ITEMS,
                ConstantHelper::TABLE_PRINT_JOBS,
            ],
            'F. THANH TOÁN VÀ HÓA ĐƠN' => [
                ConstantHelper::TABLE_PAYMENTS,
                ConstantHelper::TABLE_INVOICES,
            ],
            'G. KHO VÀ ĐỊNH LƯỢNG' => [
                ConstantHelper::TABLE_INGREDIENTS,
                ConstantHelper::TABLE_WAREHOUSES,
                ConstantHelper::TABLE_STOCK_LEVELS,
                ConstantHelper::TABLE_STOCK_MOVEMENTS,
                ConstantHelper::TABLE_SUPPLIERS,
                ConstantHelper::TABLE_RECIPES,
                ConstantHelper::TABLE_RECIPE_ITEMS,
                ConstantHelper::TABLE_PURCHASE_ORDERS,
                ConstantHelper::TABLE_PURCHASE_ORDER_ITEMS,
                ConstantHelper::TABLE_STOCK_ADJUSTMENTS,
                ConstantHelper::TABLE_STOCK_ADJUSTMENT_ITEMS,
            ],
            'H. AI VÀ PHÊ DUYỆT' => [
                ConstantHelper::TABLE_AI_RUNS,
                ConstantHelper::TABLE_AI_FORECASTS,
                ConstantHelper::TABLE_AI_ALERTS,
                ConstantHelper::TABLE_APPROVAL_REQUESTS,
            ],
            'I. HẠ TẦNG VÀ BÁO CÁO' => [
                ConstantHelper::TABLE_DOCUMENT_SEQUENCES,
                ConstantHelper::TABLE_NOTIFICATIONS,
                ConstantHelper::TABLE_NOTIFICATION_READS,
                ConstantHelper::TABLE_DAILY_SALES_SUMMARY,
                ConstantHelper::TABLE_DAILY_ITEM_SALES,
            ],
        ];

        $markdown = "# TỪ ĐIỂN DỮ LIỆU (DATA DICTIONARY)\n";
        $markdown .= "**Hệ thống Quản lý Vận hành Chuỗi Quán Phở (MESA POS & Operation System)**\n\n";
        $markdown .= "> Tài liệu được sinh tự động từ cấu trúc cơ sở dữ liệu thực tế (`information_schema`).\n";
        $markdown .= "> Tổng số bảng nghiệp vụ: **51 bảng**.\n\n";
        $markdown .= "---\n\n";

        // Mục lục nhanh
        $markdown .= "## MỤC LỤC BẢNG DỮ LIỆU\n\n";
        $tableCounter = 1;
        foreach ($modules as $moduleName => $tables) {
            $markdown .= "### {$moduleName}\n";
            foreach ($tables as $tableName) {
                $tableComment = DB::table('information_schema.TABLES')
                    ->where('TABLE_SCHEMA', $databaseName)
                    ->where('TABLE_NAME', $tableName)
                    ->value('TABLE_COMMENT');

                $markdown .= "{$tableCounter}. [**`{$tableName}`**](#".str_replace('_', '-', $tableName).") – {$tableComment}\n";
                $tableCounter++;
            }
            $markdown .= "\n";
        }

        $markdown .= "---\n\n";

        // Chi tiết từng bảng
        $tableCounter = 1;
        foreach ($modules as $moduleName => $tables) {
            $markdown .= "## {$moduleName}\n\n";

            foreach ($tables as $tableName) {
                $tableComment = DB::table('information_schema.TABLES')
                    ->where('TABLE_SCHEMA', $databaseName)
                    ->where('TABLE_NAME', $tableName)
                    ->value('TABLE_COMMENT') ?: 'Bảng dữ liệu nghiệp vụ';

                $markdown .= "### {$tableCounter}. Bảng `{$tableName}`\n";
                $markdown .= "- **Mô tả:** {$tableComment}\n";
                $markdown .= '- **Tên hằng:** `ConstantHelper::TABLE_'.strtoupper($tableName)."`\n\n";

                $columns = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', $databaseName)
                    ->where('TABLE_NAME', $tableName)
                    ->orderBy('ORDINAL_POSITION')
                    ->get();

                $markdown .= "| STT | Tên cột | Kiểu dữ liệu | Nullable | Mặc định | Khóa / Ràng buộc | Mô tả tiếng Việt |\n";
                $markdown .= "| :--- | :--- | :--- | :---: | :--- | :--- | :--- |\n";

                $colIndex = 1;
                foreach ($columns as $col) {
                    $keyLabel = match ($col->COLUMN_KEY) {
                        'PRI' => 'PK (Khóa chính)',
                        'UNI' => 'UNIQUE',
                        'MUL' => 'INDEX / FK',
                        default => '',
                    };

                    if (! empty($col->EXTRA)) {
                        $keyLabel = $keyLabel ? "{$keyLabel}, {$col->EXTRA}" : $col->EXTRA;
                    }

                    $defaultValue = $col->COLUMN_DEFAULT !== null ? "`{$col->COLUMN_DEFAULT}`" : 'NULL';
                    if ($col->IS_NULLABLE === 'NO' && $col->COLUMN_DEFAULT === null) {
                        $defaultValue = '*Không có*';
                    }

                    $colType = $col->COLUMN_TYPE;
                    $colComment = $col->COLUMN_COMMENT ?: '-';

                    $markdown .= sprintf(
                        "| %d | `%s` | `%s` | %s | %s | %s | %s |\n",
                        $colIndex,
                        $col->COLUMN_NAME,
                        $colType,
                        $col->IS_NULLABLE === 'YES' ? 'Có' : 'Không',
                        $defaultValue,
                        $keyLabel ?: '-',
                        str_replace('|', '/', $colComment)
                    );
                    $colIndex++;
                }

                $markdown .= "\n---\n\n";
                $tableCounter++;
            }
        }

        $outputPath = base_path($this->option('output'));
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $markdown);

        $this->info("Data dictionary generated successfully at: [{$outputPath}]");

        return self::SUCCESS;
    }
}
