<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Enums\BatchStatus;
use App\Enums\BranchStatus;
use App\Enums\ItemType;
use App\Enums\OrderChannel;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PrinterType;
use App\Enums\ServeMode;
use App\Enums\SessionStatus;
use App\Enums\StationCode;
use App\Enums\TableShape;
use App\Enums\TableStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\Category;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\KitchenTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\Role;
use App\Models\SessionTable;
use App\Models\Station;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Kitchen\KitchenTicketDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Dữ liệu dùng chung cho test MOD04: chi nhánh, người dùng theo vai trò, quầy/máy in và order đã gửi bếp.
 */
trait BuildsKitchenOrders
{
    private function createBranch(string $code): Branch
    {
        return Branch::query()->create([
            'code' => $code,
            'name' => 'Chi nhánh '.$code,
            'timezone' => 'Asia/Ho_Chi_Minh',
            'status' => BranchStatus::ACTIVE,
        ]);
    }

    private function createUserWithRole(string $roleCode, ?Branch $branch = null, ?string $fullName = null): User
    {
        $user = User::query()->create([
            'username' => Str::lower(Str::random(10)),
            'full_name' => $fullName ?? 'Người dùng '.$roleCode,
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::ACTIVE,
        ]);

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('code', $roleCode)->value('id'),
            'scope_type' => $branch === null ? 'ALL' : 'BRANCH',
            'scope_id' => $branch?->id,
            'valid_from' => now()->subMinute(),
            'granted_by' => null,
        ]);

        return $user;
    }

    /**
     * Tạo quầy chế biến. $printerIsActive = null: quầy chưa gắn máy in.
     */
    private function createStation(Branch $branch, StationCode $stationCode, ?bool $printerIsActive = null): Station
    {
        $printer = $printerIsActive === null ? null : Printer::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Máy in '.$stationCode->value,
            'type' => PrinterType::KITCHEN,
            'connection' => '192.168.1.'.random_int(10, 250),
            'is_active' => $printerIsActive,
        ]);

        return Station::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'station_code' => $stationCode],
            ['name' => 'Quầy '.$stationCode->value, 'printer_id' => $printer?->id, 'is_active' => true],
        );
    }

    /**
     * Tạo order tại bàn và gửi bếp qua KitchenTicketDispatcher (giống MOD03 bấm "Gửi bếp").
     *
     * @param  list<array{0: string, 1: StationCode, 2?: string|null}>  $items  [tên món, quầy, ghi chú]
     */
    private function createSentOrder(Branch $branch, string $tableCode, array $items, ?User $waiter = null): Order
    {
        $waiter ??= $this->createUserWithRole(ConstantHelper::ROLE_WAITER, $branch);
        $table = DiningTable::query()->create([
            'branch_id' => $branch->id,
            'code' => $tableCode,
            'shape' => TableShape::SQUARE,
            'status' => TableStatus::OCCUPIED,
            'is_active' => true,
        ]);
        $session = DiningSession::query()->create([
            'branch_id' => $branch->id,
            'opened_by' => $waiter->id,
            'opened_at' => now(),
            'status' => SessionStatus::OPEN,
        ]);
        SessionTable::query()->create([
            'session_id' => $session->id,
            'table_id' => $table->id,
            'joined_at' => now(),
        ]);
        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'session_id' => $session->id,
            'order_no' => 'T-'.Str::upper(Str::random(10)),
            'channel' => OrderChannel::DINE_IN,
            'status' => OrderStatus::DRAFT,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'created_by' => $waiter->id,
        ]);
        $batch = OrderBatch::query()->create([
            'order_id' => $order->id,
            'batch_no' => 1,
            'serve_mode' => ServeMode::TOGETHER,
            'status' => BatchStatus::OPEN,
        ]);

        foreach ($items as $item) {
            [$name, $stationCode] = $item;

            if (! Station::query()->where('branch_id', $branch->id)->where('station_code', $stationCode)->exists()) {
                $this->createStation($branch, $stationCode);
            }

            $category = Category::query()->create(['name' => 'Danh mục '.Str::random(6), 'is_active' => true]);
            $menuItem = MenuItem::query()->create([
                'category_id' => $category->id,
                'sku' => Str::upper(Str::random(8)),
                'name' => $name,
                'item_type' => match ($stationCode) {
                    StationCode::DRINK => ItemType::DRINK,
                    StationCode::SIDE => ItemType::SIDE,
                    default => ItemType::DISH,
                },
                'station_code' => $stationCode,
                'base_price' => 50000,
                'tax_rate' => 0,
                'is_active' => true,
            ]);
            OrderItem::query()->create([
                'order_id' => $order->id,
                'batch_id' => $batch->id,
                'menu_item_id' => $menuItem->id,
                'item_name_snapshot' => $name,
                'unit_price_snapshot' => 50000,
                'quantity' => 1,
                'line_total' => 50000,
                'note' => $item[2] ?? null,
                'status' => OrderItemStatus::PENDING,
            ]);
        }

        app(KitchenTicketDispatcher::class)->sendBatch($batch, $waiter);

        return $order;
    }

    /**
     * Order một món đã gửi bếp, trả về phiếu bếp của món đó.
     */
    private function createTicket(Branch $branch, string $tableCode, string $itemName, StationCode $stationCode, ?string $note = null): KitchenTicket
    {
        $order = $this->createSentOrder($branch, $tableCode, [[$itemName, $stationCode, $note]]);

        return KitchenTicket::query()->where('order_id', $order->id)->firstOrFail();
    }
}
